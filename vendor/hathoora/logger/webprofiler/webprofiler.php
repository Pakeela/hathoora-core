<?php
namespace hathoora\logger\webprofiler;

use hathoora\logger\logger,
    hathoora\logger\profiler;

class webprofiler
{
    public function __construct()
    {
    }

    public function display(\hathoora\container &$container)
    {
        $webProfiler = $container->getConfig('logger.webprofiler.enabled');

        if (!$webProfiler)
            return;
        
        // request
        $request = $container->getRequest();
       
        // skip webprofiler for specified POST params
        if ($request->postParam() && ($arrSkipParams = $container->getConfig('logger.webprofiler.skip_on_post_params')))
        {
            foreach ($arrSkipParams as $param)
            {
                if ($request->postParam($param))
                {
                    return;
                }
            }
        }
        
        // skip webprofiler for specified GET params
        if ($request->getParam() && ($arrSkipParams = $container->getConfig('logger.webprofiler.skip_on_get_params')))
        {
            foreach ($arrSkipParams as $param)
            {
                if ($request->getParam($param))
                {
                    return;
                }
            }
        }
        
        $response = $container->getResponse();
        $contentType = $response->getHeader('Content-Type');
        
        // do we need to display profiler?
        $contentTypeRegex = $container->getConfig('logger.webprofiler.content_type');
        if (!$contentTypeRegex)
            $contentTypeRegex = 'text/html';
            
        if (preg_match('#' . $contentTypeRegex  . '#i', $contentType))
        {
            // include template
            $template = dirname ( __FILE__ ) . '/template.php';
            if (file_exists($template))
            {
                // kernel
                $totalMemory = memory_get_peak_usage();
                $scriptEndTime = microtime();
                $version = $container->getKernel()->getVersion();
                
                // route
                $httpStatus = $response->getStatus();
                $controller = $container->getController();
                
                // config
                $arrConfigs = $container->getAllConfig();
                
                // logging
                $loggingStatus = $container->getConfig('logger.logging.enabled');
                
                $arrProfile =& profiler::$arrProfile;
                $arrLog =& logger::$arrLog;
                include_once($template);
            }
        }
    }
}