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
    public function getAppAsset($path, $app = null)
    {
        static $arrAppPathHash;
        
        $appPath = $appPathHash = null;
	
        if (empty($app))
        {
            $app = HATHOORA_APP;
            $appPath = HATHOORA_APP_PATH;
        }

        if (!isset($arrAppPathHash[$app]))
        {
            preg_match('/app\/(.+?)\/'. $app .'/i', $appPath, $arrMatch);
            $arrAppPathHash[$appPath] = array_pop($arrMatch) . ':'. $app . '/';
        }
        
        $appPathHash = $arrAppPathHash[$appPath];
        
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
