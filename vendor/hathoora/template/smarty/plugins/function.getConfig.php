<?php
use hathoora\container;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {getConfig} function plugin
 * usage:
 *  {getConfig name="var.varkey.blah"}
 *
 * Type:     function<br>
 * Name:     getConfig<br>
 * Purpose:  get hathoora config
 *
 * @param array                    $params   parameters
 * @param Smarty_Internal_Template $template template object
 * @return string|null
 */
function smarty_function_getConfig($params, $template)
{
    $key = (isset($params['name'])) ? $params['name'] : null;
    
    return container::getConfig($key);
}