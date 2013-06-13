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
            $r = $default->fetchArray('SELECT NOW();');
            #$r = $default->server('master:dbMaster1')->fetchArray('SELECT NOW();');
            #$r = $default->server('slave:dbSlave1')->fetchArray('INSERT IGNORE NOW();');
            #printr($r);
            $r = $default->server('slave:dbSlave1')->fetchArray('SELECT NOW();');
            #printr($r);            
            $r = $default->comment('hello world')->fetchArray('SELECT NOW();');
            $r = $default->server('master:dbMaster1')->fetchArray('SELECT NOW();');
            $r = $default->server('last')->fetchArray('SELECT NOW();');
            $r = $default->fetchArray('SELECT NOW();');
            #printr($r);            
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