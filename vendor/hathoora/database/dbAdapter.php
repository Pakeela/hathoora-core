<?php
namespace hathoora\database;

use hathoora\configure\config,
    hathoora\logger\logger;

class dbAdapter
{
    /**
     * array for storing dsn
     */
    static $arrDsn = array();
    
    /**
     * Constructor
     */
    public function __costruct()
    { }
    
    /**
     * this function returns db connecttion class in a singleton faction,
     * would retutn false when unable to connect
     * 
     * @param string $dsn_name defined in the config
     * @param bool $reBuild when true 
     * @param bool $throwException upon failure
     * @return hathoora\database\db class
     */
    public static function getConnection($dsn_name = 'default', $reBuild = false, $throwException = false)
    {
        $arrDsn =& self::$arrDsn;
        $error = false;
        if (!isset($arrDsn[$dsn_name]) || $reBuild)
        {
            // if connection is already open, close it
            if (isset($arrDsn[$dsn_name]) && is_object($arrDsn[$dsn_name]))
                unset($arrDsn[$dsn_name]);
            
            $configKey = 'database.'. $dsn_name;
            $dsn_string = config::get($configKey);
            if ($dsn_string)
            {
                if (preg_match('/^(\w+):\/\/(\w+):(|\w+)@(.+?):(\d+)\/(.+?)$/', $dsn_string, $arrMatch))
                {
                    $arrConfig = array(
                        'dsn_name' => $dsn_name,
                        'driver' => $arrMatch['1'],
                        'host' => $arrMatch['4'],
                        'port' => $arrMatch['5'],
                        'user' => $arrMatch['2'],
                        'password' => $arrMatch['3'],
                        'schema' => $arrMatch['6']
                    );
                    try {
                        $arrDsn[$dsn_name] = new db($arrConfig);
                        $arrDsn[$dsn_name]->query('SET NAMES "utf8"');
                        
                        
                    } catch (\Exception $e)
                    {
                        $error = $e->getMessage();
                    }
                }
                else
                    $error = 'Unable to parse "'. $configKey .'" configuration key. Please make sure it is of format driver://user:password@host:port/schema.';
            }
            else
                $error = 'Unable to parse "'. $configKey .'" configuration key. Please make sure it is of format driver://user:password@host:port/schema.';
        }
        
        
        // return dsn & set dsn to static variable
        if (!isset($arrDsn[$dsn_name]) || !is_object($arrDsn[$dsn_name]))
        {
            logger::log(logger::LEVEL_ERROR, $error);
            
            if ($throwException)
                throw new \Exception($error); 

            return false;
        }
        
        return $arrDsn[$dsn_name];
    }
}