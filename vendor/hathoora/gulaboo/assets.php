<?php
namespace hathoora\gulaboo;

use hathoora\container;

/**
 * Assets manager
 */
class assets extends container
{
    /**
     * holds container
     */
    private $container;

    public function __construct(/*$container*/)
    {
        //$this->container =& $container;
    }
    
    /**
     * This function retrivers an app asset
     */
    public function getAppAsset($path, $versioning = true)
    {
        static $arrAppPathHash;
        
        $appPathHash = null;
        if (!isset($arrAppPathHash[HATHOORA_APP_PATH]))
        {
            preg_match('/app\/(.+?)\/'. HATHOORA_APP .'/i', HATHOORA_APP_PATH, $arrMatch);
            $arrAppPathHash[HATHOORA_APP_PATH] = array_pop($arrMatch) . '::'. HATHOORA_APP . '/';
        }
        
        $appPathHash = $arrAppPathHash[HATHOORA_APP_PATH];
        
        $url = $this->getConfig('assets.urls.http');
        $url .= '/_assets/_app/' . $appPathHash;
        $url .=  $path . '?' . $this->getConfig('assets.version');
        
        return $url;    
    }
    
    public function getAsset($path, $versioning = true)
    {
        $url = $this->getConfig('assets.urls.http');
        //$url = null; //'http://media01.mine.pk';
        $url .=  $path . '?' . $this->getConfig('assets.version');
        //$url .= time();
        
        return $url;
    }
}
