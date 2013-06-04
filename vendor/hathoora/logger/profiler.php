<?php
namespace hathoora\logger;

use hathoora\configure\config;

class profiler
{
    /**
     * static variable for storing information
     */
    public static $arrProfile;
    
    /**
     * constructor
     */
    public function construct()
    {}
    
    /**
     * init function
     */
    public function init()
    {
        self::$arrProfile = array(
                                    'benchmark' => array(
                                        'Hathoora::ExecutionTime' => array(
                                            'start' => microtime()
                                        )
                                    ),
                                    'log' => array(),
                                    'db' => array(),
                                    'cache' => array());
    }

    /**
     * Simple benchmark function
     * @param string $name of benchmark
     * @param string $action (start or stop)
     */
    public function benchmark($name)
    {
        if (!config::get('logger.profiling'))
            return false;
            
        $arr =& self::$arrProfile['benchmark'];

        if (!isset($arr[$name]))
            $arr[$name]['start'] = microtime();
        else
            $arr[$name]['end'] = microtime();  
    }

    /**
     * we want to debug various things categrozied by type debugging
     * 
     * @param string $type debugging type
     * @param $name a unique identifier
     * @param $arr stuff to debug (contains like start, end time etc..)
     */
    public static function profile($type, $name = false, $arr)
    {
        if (!config::get('logger.profiling'))
            return false;
        
        if ($name)
            self::$arrProfile[$type][$name] = $arr;
        else
            self::$arrProfile[$type][] = $arr;
    }
    
    /**
     * Adjust debugging values
     *
     * @param string $type debugging type
     * @param $name a unique identifier
     * @param $arr key value pair that we want to modiff
     */
    public static function modify($type, $name, $arr)
    {
        if (!config::get('logger.profiling'))
            return false;
        
        if (!$name && !is_array($arr))
            return false;
            
        
        if ($name && isset(self::$arrProfile[$type][$name]) && is_array(self::$arrProfile[$type][$name]))
        {
            foreach($arr as $k => $v)
            {
                self::$arrProfile[$type][$name][$k] = $v;
            }
        }
    }    
    
    /**
     * returns microtime difference
     *
     * @param int $a start microtime(false)
     * @param int $b end microtime(false)
     */
    public static function microtimeDiff($a, $b) 
    {
        list($a_dec, $a_sec) = explode(" ", $a);
        list($b_dec, $b_sec) = explode(" ", $b);
        return $b_sec - $a_sec + $b_dec - $a_dec;
    }    
}