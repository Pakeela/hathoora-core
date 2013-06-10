<?php
namespace site\controller;

use hathoora\controller\controller;

/**
 * Default controller
 */
class defaultController extends controller
{
    public function __construct()
    { }
    
    /**
     * Homepage action
     */
    public function index()
    {
        #$db2 = \hathoora\database\dbAdapter::getConnection('db2');
        #printr($db2->fetchArray('SELECT NOW();'));
        
        $default = \hathoora\database\dbAdapter::getConnection('default');
        try
        {
            $r = $default->server('slave:name')->fetchArray('SELECT NOW();');
            printr($r);
        }
        catch (\Exception $e)
        {
        }
        
        
        $arrTplParams = array(
            'bodyClass' => 'homepage',
        );
        $template = $this->template('index.tpl.php', $arrTplParams);
        $response = $this->response($template);
        
        return $response;
    }    
}