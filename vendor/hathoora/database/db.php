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
     * for debugging
     */
    protected $dsnName;
    
    /**
     * the query
     */
    public $query;
    
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
     * db factory
     */
    protected $factory;
    
    /**
     * query results from factory
     */
    protected $queryResult;
    
    /**
     * Constructor which connects to db (factory)
     */
    public function __construct($arrConfig)
    {
        $this->dsnName = $arrConfig['dsn_name'];
        $this->profiling = config::get('logger.profiling');
        
        // @todo: handle more db drivers
        if ($arrConfig['driver'] == 'mysql')
            $this->factory = new dbMysqli($arrConfig['host'], $arrConfig['user'], $arrConfig['password'], $arrConfig['schema'], $arrConfig['port']);

        $this->finalize();
        return $this;
     }

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
    public function finalize($returnStatus = false, $arrDebug = false)
    {   
        $this->error = $this->factory->getError();
        $hasError = false;
        if (isset($this->error['number']))
            $hasError = true;
        
        if ($this->query && config::get('logger.profiling'))
        {
            $arrDebug['end_query'] = microtime();
            $arrDebug['query'] = $this->query;
            if ($hasError)
                $arrDebug['error'] = $this->error['message'];
            profiler::profile('db', $this->queryCounter, $arrDebug);
        }

        $this->rowCount = $this->factory->getAffectedRows();
        $this->lastInsertId = $this->factory->getlastInsertId();
        
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
    public function errorfactory()
    {
        // log this error
        logger::log(logger::LEVEL_ERROR, 'SQL Error ('. $this->error['number'] .') '. $this->error['message'] .'<br/><br/>'. $this->query);
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
    
    /**
     * Escape string
     */
    public function escape($string)
    {
        return $this->factory->quote($string);
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
        
        if (is_array($args) && count($args))
        {
            $this->queryArgsReplace($args, $this);
            $query = preg_replace_callback(self::DB_QUERY_REGEXP, '\hathoora\database\db::queryArgsReplace', $query);
        }
        $this->query = $query;
        
        // for debugging
        $arrDebug = array();
        if (config::get('logger.profiling'))
        {
            $arrDebug['dsn_name'] = $this->dsnName;
            $arrDebug['start'] = microtime();
        }
        
        // log it
        logger::log(logger::LEVEL_INFO, $query);
        
        if (!$isMultiQuery)
            $this->queryResult = $this->factory->query($query);
        else
            $this->queryResult = $this->factory->multiQuery($query);
            
        $hasNoErrors = $this->finalize(true, $arrDebug);
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
     * Begins transaction by setting autocommit to off
     */
    public function beginTransaction()
    {
        $this->factory->beginTransaction();
    }
    
    /**
     * Commits the query 
     */
    public function commit()
    {
        return $this->factory->commit();
    }
    
    /**
     * Function to rollback
     */
    public function rollback()
    {
        return $this->factory->rollback();
    }

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
            
        profiler::modify('db', $this->queryCounter, array('end_execution' => microtime()));
            
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
        profiler::modify('db', $this->queryCounter, array('end_execution' => microtime()));

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
        profiler::modify('db', $this->queryCounter, array('end_execution' => microtime()));

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
        profiler::modify('db', $this->queryCounter, array('end_execution' => microtime()));
            
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