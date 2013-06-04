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
        #$arrTplParams = array(
        #    'bodyClass' => 'homepage',
        #);
        #$template = $this->template('default/index.tpl.php', $arrTplParams);
        #$response = $this->response($template);
        
        #return $response;
    }    
}