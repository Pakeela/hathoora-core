<?php
namespace hathoora\database;

use hathoora\configure\config,
    hathoora\logger\profiler,
    hathoora\logger\logger;

/**
 * A db wrapper
 */
class db
{
    // constant for query reges
    const DB_QUERY_REGEXP = '/(\?)/';

    /**
     * Pool Name
     */
    protected $poolName;

    /**
     * Dsn Name
     */
    protected $dsnName;

    /**
     * for debugging
     */
    protected $dbName;

    /**
     * the query
     */
    protected $query;
    
    /**
     * the query args
     */
    protected $queryArgs;
    
    /**
     * query counter
     */
    protected $queryCounter = 0;
    
    /**
     * affected rows
     */
    protected $rowCount = 0;
    
    /**
     * last insert id
     */
    protected $lastInsertId = 0;
    
    /**
     * an array containg error stuff
     */
    protected $error;
    
    /**
     * User can specify which server to use on per usage basis
     */
    protected $userSpecifiedDsn;
    
    /**
     * db factory
     */
    protected $factory;
    
    /**
     * query results from factory
     */
    protected $queryResult;
    
    /**
     * Constructor which connects to db (factory)
     *
     * @param string
     *      poolName : which are defined in dbAdapter::$arrPools
     *      dsnName : which are defined in dbAdapter::$arrDsns @todo
     */
    public function __construct($poolName, $dsnName = null)
    {
        $this->poolName = $poolName; 
        $this->dsnName = $dsnName;
        
        $this->profiling = config::get('logger.profiling');
        
        return $this;
    }
    
    ##############################################################
    ##
    ##   DSN stuff
    ## 
    /**
     * From the pool, get a dsn to connect to based on read/write logic & weights
     *
     * @param string $type read or write 
     */
    private function setDsnFactory($type)
    {
        static $arrPoolCurrentDsnForType;
        $conn = null;
        $dbDsnName = null;
        $currentPoolDsnTypeIdentifier = $this->poolName .':' . $type;
        
        if (isset($arrPoolCurrentDsnForType[$currentPoolDsnTypeIdentifier]))
        {
            $arr = $arrPoolCurrentDsnForType[$currentPoolDsnTypeIdentifier];
        }
        else if (isset(dbAdapter::$arrPools[$this->poolName]) && ($arrPool = dbAdapter::$arrPools[$this->poolName]))
        {
            $type = strtolower($type);
            if (isset($arrPool['servers']))
            {
                $arrTypeServers = $arrPool['servers'][$type];
                $arr = $this->getAvailableDsn($arrTypeServers);
                
                $arrPoolCurrentDsnForType[$currentPoolDsnTypeIdentifier] = $arr;
            }
        }
        
        if (is_array($arr))
        {
            $uniqueDsnName = isset($arr['uniqueDsnName']) ? $arr['uniqueDsnName'] : null;
            $dbDsnName = isset($arr['name']) ? $arr['name'] : null;
            
            if (isset(dbAdapter::$arrDsns[$uniqueDsnName]))
            {
                $arrDsn =& dbAdapter::$arrDsns[$uniqueDsnName];
                $conn = isset($arrDsn['conn']) ? $arrDsn['conn'] : null;
            }
        }
        
        $this->dbName = $this->poolName . ($dbDsnName ? '/'. $dbDsnName : null);
        
        $this->factory = $conn;
    }

    /**
     * From given set of servers returns the next available server
     *
     * @param array $arrTypeServers
     */
    private function getAvailableDsn(&$arrTypeServers)
    {
        $arr = null;
        foreach($arrTypeServers as $i => $arrTypeServer)
        {
            $dsn = $arrTypeServer['dsn'];
            $options = isset($arrTypeServer['options']) ? $arrTypeServer['options'] : null;
            $name = isset($arrTypeServer['name']) ? $arrTypeServer['name'] : null;
            
            $uniqueDsnName = $dsn;
            if ($options)
            {
                $md5Options = @md5(serialize($options));
                $uniqueDsnName .= ':' . $md5Options;
            }                
            
            $arrDsn =& dbAdapter::$arrDsns[$uniqueDsnName];
            $dsnStatus = $arrDsn['status'];
            if ($dsnStatus == 'connected')
            {
                $arr = array(
                                'uniqueDsnName' => $uniqueDsnName,
                                'name' => $name
                            );            
                break;
            }
            else if ($dsnStatus == 'not connected')
            {
                if (preg_match('/^(\w+):\/\/(\w+):(|\w+)@(.+?):(\d+)\/(.+?)$/', $dsn, $arrMatch))
                {
                    $driver = $arrMatch['1'];
                    $host = $arrMatch['4'];
                    $port = $arrMatch['5'];
                    $user = $arrMatch['2'];
                    $password = $arrMatch['3'];
                    $schema = $arrMatch['6'];
                    $socket = null;
                    
                    // @todo: handle more db drivers
                    if ($driver == 'mysqli')
                    {
                        try
                        {
                            //echo "Trying to connect to $name -> $uniqueDsnName ";
                            $arrDsn['conn'] = new dbMysqli($host, $user, $password, $schema, $port, $socket, $options);
                            $arrDsn['status'] = 'connected';
                            $arr = array(
                                            'uniqueDsnName' => $uniqueDsnName,
                                            'name' => $name
                                        );
                                        
                            logger::log(logger::LEVEL_INFO, 'mySQL connected to '. $this->poolName . ($name ? '/'. $name : null));
                        }
                        catch (\Exception $e)
                        {
                            //echo ": FAILED";
                            $error = $e->getMessage();
                            $arrDsn['status'] = 'cannot connect';
                            
                            logger::log(logger::LEVEL_ERROR, 'mySQL connection error for '. $this->poolName . ($name ? '/'. $name : null) .': '. $error);
                        }
                        
                        //echo "<br/>";
                    }
                }
            }
        }
        
        return $arr;
    }
    
    /**
     * use a particular dsn
     *
     */
    public function server($string)
    {
        $this->userSpecifiedDsn = $string;
        
        return $this;
    }
    
    
    ##############################################################
    ##
    ##   Factory operations
    ##    
    /**
     * Initialize function which sets debug & factory
     *
     * @param string $queryType write|read to select appropriate factory
     * @param string $comment for debugging
     */
    private function initialize($queryType, $comment = null)
    {
        $this->setDsnFactory($queryType);
        
        // for debugging
        if (config::get('logger.profiling'))
        {
            $this->arrDebug = array();
            $this->arrDebug['dsn_name'] = $this->dbName;
            $this->arrDebug['start'] = microtime();
            $this->arrDebug['comment'] = $comment;
        }
    }
    
    /**
     * This function does bunch of stuff:
     *      assign $this->rowCount
     *      assign $this->lastInsertId
     *      check if factory occured any errors
     *          return true false or throw exception
     *
     * @param bool $returnStatus, when true then we don't throw exception upon errors
     * @return 
     *     when returnStatus = true, then returns true when no errors, false when errors 
     *      when returnStatus = false, then throws exception on errors
     */
    private function finalize($returnStatus = false)
    {   
        if (is_object($this->factory))
            $this->error = $this->factory->getError();
        else
        {
            $this->error = array(
                                    'number' => -1,
                                    'message' => 'Unable to connect.');
        }
        
        $hasError = false;
        if (isset($this->error['number']))
            $hasError = true;
        
        if (config::get('logger.profiling') && ($this->query || !empty($this->arrDebug['comment'])))
        {
            $this->arrDebug['end_query'] = microtime();
            $this->arrDebug['query'] = $this->query;
            
            if (!$this->arrDebug['query'] && !empty($this->arrDebug['comment']))
                $this->arrDebug['query'] = $this->arrDebug['comment'];
            
            if ($hasError)
                $this->arrDebug['error'] = $this->error['message'];
                
            profiler::profile('db', $this->poolName . $this->queryCounter, $this->arrDebug);
        }

        $this->rowCount = $this->lastInsertId = null;
        if (is_object($this->factory))
        {
            $this->rowCount = $this->factory->getAffectedRows();
            $this->lastInsertId = $this->factory->getlastInsertId();
        }
        
        if ($hasError)
        {
            $this->errorfactory();
            if (!$returnStatus)
                throw new \Exception($this->error['message'], $this->error['number']); 
            else
                return false;
        }
        
        return true;
    }
    
    /**
     * Error factory
     */
    private function errorfactory()
    {
        // log this error
        logger::log(logger::LEVEL_ERROR, 'SQL Error ('. $this->error['number'] .') '. $this->error['message'] .'<br/><br/>'. $this->query);
    }

    /**
     * Returns db factory
     */
    public function getFactory()
    {
        return $this->factory;
    }
    
    /**
     * Get SQL error
     *
     * @return array of error
     */
    public function getError()
    {
        return $this->error;
    }


    
    ##############################################################
    ##
    ##   Query stuff
    ##
    /**
     * Query args replacements (drupal inspired)
     * 
     * This has to be static..
     */
    public static function queryArgsReplace($match, &$factory = false) 
    {
        static $args = NULL;
        static $objEscapeFactory; // since its a static function we need to get db factory from
        if (is_object($factory))
        {
            $args = $match;
            $objEscapeFactory = $factory;
            return;
        }
        
        switch ($match[1]) 
        {
            case '?':
            {
                if (is_array($args))
                    return $objEscapeFactory->escape(array_shift($args));
                return '?';
            }
        }
    }
    
    /**
     * query (or execute) function
     *
     * @param string $query
     * @param array $args (optional)
     * @return dbResult object successful, else returns false
     */
    public function query($query, $args = false, $isMultiQuery = false)
    {
        $this->queryCounter++;
        $this->queryArgs = $args;
        
        if (preg_match('/^s{0,}SELECT/im', $query))
            $queryType = 'read';
        else
            $queryType = 'write';
        
        $this->initialize($queryType);
        
        if (is_array($args) && count($args))
        {
            $this->queryArgsReplace($args, $this);
            $query = preg_replace_callback(self::DB_QUERY_REGEXP, '\hathoora\database\db::queryArgsReplace', $query);
        }
        $this->query = $query;
        
        // log it
        logger::log(logger::LEVEL_INFO, $query);
        
        if (is_object($this->factory))
        {
            if (!$isMultiQuery)
                $this->queryResult = $this->factory->query($query);
            else
                $this->queryResult = $this->factory->multiQuery($query);
        }
            
        $hasNoErrors = $this->finalize(false);
        if ($hasNoErrors)
            return new dbResult($this->factory, $this->queryResult);
        
        return false;
    }
    
    /**
     * Runs a multi query
     */
    public function multiQuery($query, $args = false)
    {
        $arrResult = null;
        $this->query($query, $args, true);
        
        // @todo do this at adapter level
        if ($this->queryResult) 
        {
            do 
            {
                if ($result = $this->factory->store_result()) 
                { 
                    while($row = $result->fetch_row()) 
                    {
                        $arrResult[] =$row;
                    }
                    $result->close();
                }
            } while($this->factory->next_result());
        }
        
        return $arrResult;
    }
    
    /**
     * Escape string
     */
    public function escape($string)
    {
        $escapedString = null;
        $this->initialize('read');
        
        if (is_object($this->factory))
            $escapedString = $this->factory->quote($string);
            
        $this->finalize(false);
        
        return $escapedString;
    }
    
    /**
     * Begins transaction by setting autocommit to off
     */
    public function beginTransaction()
    {
        $this->initialize('write', 'BEGIN TRANSACTION');
        
        if (is_object($this->factory))
            $this->factory->beginTransaction();
            
        $this->finalize(true);            
    }
    
    /**
     * Commits the query 
     */
    public function commit()
    {
        $dbResponse = null;
        $this->initialize('write', 'COMMIT');
        
        if (is_object($this->factory))
            $dbResponse = $this->factory->commit();
            
        $this->finalize();
        
        return $dbResponse;
    }
    
    /**
     * Function to rollback
     */
    public function rollback()
    {
        $dbResponse = null;
        $this->initialize('write', 'ROLLBACK');
        
        if (is_object($this->factory))
            $dbResponse = $this->factory->rollback();
            
        $this->finalize();
        
        return $dbResponse;            
    }

    
    
    ##############################################################
    ##
    ##   Common query operation shortcuts
    ##    
    /**
     * query (or execute) function
     *
     * @param string $query
     * @param array $args (optional)
     * @return last insert id when successful, else returns false
     */
    public function insert($query, $args = false)
    {
        $result = $this->query($query, $args);
        if ($result && $this->rowCount)
            $return = $this->lastInsertId;
        else
            $return = false;
            
        profiler::modify('db', $this->poolName . $this->queryCounter, array('end_execution' => microtime()));
            
        return $return;
    }
    
    /**
     * fetch coulmn fetch for a single row
     *
     * @param string $query
     * @param array $args (optional)
     * @return array of row
     */
    public function fetchValue($query, $args = false)
    {
        $result = $this->query($query, $args);
        if ($result && $result->rowCount())
            $arrResult = @array_pop($result->fetchArray());
        else
            $arrResult = false;
            
        // for debugging, we need to keep track SQL exection and total time 
        profiler::modify('db', $this->poolName . $this->queryCounter, array('end_execution' => microtime()));

        return $arrResult;
    }    
    
    /**
     * fetch single row
     *
     * @param string $query
     * @param array $args (optional)
     * @return array of row
     */
    public function fetchArray($query, $args = false)
    {
        $result = $this->query($query, $args);
        if ($result && $result->rowCount())
            $arrResult = $result->fetchArray();
        else
            $arrResult = false;
            
        // for debugging, we need to keep track SQL exection and total time 
        profiler::modify('db', $this->poolName . $this->queryCounter, array('end_execution' => microtime()));

        return $arrResult;
    }
    
    /**
     * fetch all rows
     * 
     * @param string $query
     * @param array $args (optional)
     * @param array $arrExtra for extra logic
     *      - pk: field name to return array keyed with this value from results set
     * @return array of row     
     */
    public function fetchArrayAll($query, $args = false, $arrExtra = array())
    {
        $result = $this->query($query, $args);
        if ($result && $result->rowCount())
        {
            while ($row = $result->fetchArrayAll())
            {
                if (isset($arrExtra['pk']) && $row[$arrExtra['pk']])
                    $arrResult[$row[$arrExtra['pk']]] = $row;
                else
                    $arrResult[] = $row;
            }
        }
        else
            $arrResult = false;
        
        // for debugging, we need to keep track SQL exection and total time 
        profiler::modify('db', $this->poolName . $this->queryCounter, array('end_execution' => microtime()));
            
        return $arrResult;
    }
    
    /**
     * upon destruct close factory connection
     */
    public function __destruct()
    {
        if (is_object($this->factory))
            $this->factory->disconnect();
    }
}