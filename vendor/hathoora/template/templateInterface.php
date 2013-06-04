<?php
namespace hathoora\template;

/**
 * Template interface
 */
interface templateInterface
{
    /**
     * constructor
     *
     * @param array of $config
     */
    public function __construct($config);

    /**
     * Assign variable to be used in template
     *
     * @param string $name of the variable
     * @param mixed $value of the variable
     */
    public function assign($name, $value);

    /**
    * Assign variable, by reference, to be used in template
    *
    * @param string $name of the variable
    * @param mixed $value of the variable
    */
    public function assignByRef($name, &$value);
    
    /**
     * Append variable to be used in template
     *
     * @param string $name of the variable
     * @param mixed $value of the variable
     */
    public function append($name, $value);
    
     /**
     * Determines if an entry is cached
     *
     * @param string $template
     * @param string $id Unique ID of this data
     * @param string $group Group to store data under
     */
    public function isCached($template, $id, $group = null);

    /**
     * Return variable value
     *
     * @param string $name of the variable
     * @return value of variable
     */
    public function getVar($name);
    
    /**
     * Return all variables
     */
    public function getVars();

    /**
     * Include a template
     */
    public function load($file, $vars = array());

    /**
     * Returns flash message and clears flash session
     */
    public function getFlashMessage();
    
    /**
     * a wrapper - fetches a rendered template
     * 
     * @param string $template the resource handle of the template file or template object
     * @param mixed $cache_id cache id to be used with this template
     * @param array $arrExtra for additional requirements
     * @return string rendered template output
     */
    public function fetch($template, $cache_id = null, $arrExtra = array());

    /**
     * a wrapper - displays a Smarty template
     * 
     * @param string $ |object $template the resource handle of the template file  or template object
     * @param mixed $cache_id cache id to be used with this template
     * @param array $arrExtra for additional requirements
     * @result outputs the rendered template
     */
    public function display($template, $cache_id = null, $arrExtra = array());
    
    /**
     * Render a controller
     *
     * @param array $arrController containing controller & method names
     * @param array $args to be passed to the method
     */
    public function render($arrController, $args);
}