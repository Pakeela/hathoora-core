<?php
namespace hathoora\database;

use hathoora\configure\config,
    hathoora\logger\logger;

class dbAdapter
{
    /**
     * array containing db pools defined in configurtation
     */
    public static $arrPools = array();
    
    /**
     * array containing all dns in all db pools, so we can reuse them
     */
    public static $arrDsns = array();
    
    /**
     * array containing db connection class
     */
    private static $arrPoolDsnConnection = array();    
    
    /**
     * Constructor
     */
    public function __costruct()
    { }
    
    /**
     * this function returns db connection class in lazy fashion
     * 
     * @param string $poolName defined in the config
     * @param string $dsnName for a given pool when used advanced db configuration @TODO
     *      When a specific dsnName is passed, then there is no failover logic
     *
     * @return hathoora\database\db class
     */
    public static function getConnection($poolName = 'default', $dsnName = null)
    {
        $arrPools =& self::$arrPools;
        $arrDsns =& self::$arrDsns;
        $arrPoolDsnConnection =& self::$arrPoolDsnConnection;
        $error = false;
        $configKey = 'database.'. $poolName;
        $poolDsnConnectionObject = $poolName . ':' . $dsnName;
        
        if (!isset($arrPoolDsnConnection[$poolDsnConnectionObject]))
        {
            // check if arrPool is already is processed
            if (isset($arrPools[$poolName]))
            {
                $arrPool =& $arrPools[$poolName];
            }
            else
            {
                $poolConfigValue = config::get($configKey);
                $arrPool = array();
                $arrPool['name'] = $poolName;
            
                // advances db config
                if (is_array($poolConfigValue))
                {
                    $failover = !empty($poolConfigValue['failover']) ? $poolConfigValue['failover'] : 'default';
                    $arrPool['failover'] = $failover;
                    
                    // we need to order db servers in the order of read & write..
                    if (isset($poolConfigValue['servers']) && is_array($poolConfigValue['servers']))
                    {
                        foreach ($poolConfigValue['servers'] as $nickName =>& $arrServer)
                        {
                            $role = !empty($arrServer['role']) ? strtolower($arrServer['role']) : 'master';
                            $dsn = !empty($arrServer['dsn']) ? strtolower($arrServer['dsn']) : null;
                            $options = !empty($arrServer['options']) ? $arrServer['options'] : null;
                            
                            // read only master are used only for reads after slaves are exhausted
                            $readOnly = ($role == 'master' && isset($arrServer['read_only'])) ? $arrServer['read_only'] : false;
                            
                            // allow read master can be used for reads but as last resort (once all slaves and read only master fail)
                            if (!$readOnly && $role == 'master' && !isset($arrServer['allow_read']))
                                $arrServer['allow_read'] = true; //default value
                                
                            $allowRead = (!$readOnly && $role == 'master' && !empty($arrServer['allow_read'])) ? $arrServer['allow_read'] : null;
                            $weight = !empty($arrServer['weight']) ? strtolower($arrServer['weight']) : 1;
                            
                            if ($dsn)
                            {
                                $uniqueDsnName = $dsn;
                                if ($options)
                                {
                                    $md5Options = @md5(serialize($options));
                                    $uniqueDsnName .= ':' . $md5Options;
                                }
                                
                                if (empty($arrDsns[$uniqueDsnName]))
                                {
                                    $arrDsns[$uniqueDsnName] = array();
                                    $arrDsns[$uniqueDsnName]['name'] = $uniqueDsnName;
                                    $arrDsns[$uniqueDsnName]['dsn'] = $dsn;
                                    $arrDsns[$uniqueDsnName]['options'] = $options;
                                    $arrDsns[$uniqueDsnName]['status'] = 'not connected';
                                    
                                    if (!preg_match('/^(\w+):\/\/(\w+):(|\w+)@(.+?):(\d+)\/(.+?)$/', $dsn))
                                    {
                                        $arrDsns[$uniqueDsnName]['status'] = 'invalid format';
                                        $error = 'Unable to parse "'. $configKey .'" configuration key. Please make sure "dsn" format driver://user:password@host:port/schema.';
                                        
                                        logger::log(logger::LEVEL_ERROR, $error);
                                    }                                    
                                }
                                $arrDsns[$uniqueDsnName]['pools'][$poolName .':'. $nickName] = 1;                            
                                
                                $roleType = 'read';
                                if ($role == 'master')
                                {
                                    // read only master are used only for reads after slaves are exhausted
                                    if ($readOnly)
                                    {
                                        $roleType = 'read';
                                        $weight = '0.' . $weight;
                                    }
                                    else
                                        $roleType = 'write';
                                }
                                    
                                $arrPool['servers'][$roleType][$weight . ':' . $nickName] = $arrServer;
                                $arrPool['servers'][$roleType][$weight . ':' . $nickName]['name'] = $nickName;
                                
                                // allow read master can be used for reads but as last resort (once all slaves and read only master fail)
                                if ($role == 'master' && $allowRead)
                                {
                                    $roleType = 'read';
                                    $weight = '0.0' . $weight;
                                    $arrPool['servers'][$roleType][$weight . ':' . $nickName] = $arrServer;
                                    $arrPool['servers'][$roleType][$weight . ':' . $nickName]['name'] = $nickName;                            
                                }
                            }
                        }
                    
                        foreach ($arrPool['servers'] as $roleType =>& $arrPoolServer)
                        {
                            krsort($arrPoolServer);
                        }
                    }
                }
                // simple db config
                else
                {
                    $arrPool['failover'] = 'default';
                    $dsn = $poolConfigValue;
                    
                    $uniqueDsnName = $dsn;
                    if (empty($arrDsns[$uniqueDsnName]))
                    {
                        $arrDsns[$uniqueDsnName] = array();
                        $arrDsns[$uniqueDsnName]['name'] = $uniqueDsnName;
                        $arrDsns[$uniqueDsnName]['dsn'] = $dsn;
                        $arrDsns[$uniqueDsnName]['status'] = 'not connected';
                        
                        if (!preg_match('/^(\w+):\/\/(\w+):(|\w+)@(.+?):(\d+)\/(.+?)$/', $dsn))
                        {
                            $arrDsns[$uniqueDsnName]['status'] = 'invalid format';
                            $error = 'Unable to parse "'. $configKey .'" configuration key. Please make sure "dsn" format driver://user:password@host:port/schema.';
                            
                            logger::log(logger::LEVEL_ERROR, $error);
                        }
                    }
                    $arrDsns[$uniqueDsnName]['pools'][$poolName .':'] = 1;

                    // read and write servers are the same in this case
                    $arrPool['servers']['write']['10:'] = array('dsn' => $dsn);
                    $arrPool['servers']['read']['10:'] = array('dsn' => $dsn);
                }
                
                $arrPools[$poolName] = $arrPool;
            }
            
            if (is_array($arrPool))
            {
                $arrConfig = array(
                    'pool_name' => $poolName,
                    'dsn_name' => $dsnName);
                
                $arrPoolDsnConnection[$poolDsnConnectionObject] = new db($poolName, $dsnName);
            }
        }
        
        if (!isset($arrPoolDsnConnection[$poolDsnConnectionObject]))
            $arrPoolDsnConnection[$poolDsnConnectionObject] = null;
        
        return $arrPoolDsnConnection[$poolDsnConnectionObject];
    }
}