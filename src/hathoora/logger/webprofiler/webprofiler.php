<?php
namespace hathoora\logger\webprofiler
{
    use hathoora\logger\logger,
        hathoora\logger\profiler,
        hathoora\template\template,
        hathoora\container,
        hathoora\helper\stringHelper;

    class webprofiler
    {
        public function __construct()
        {
        }

        /**
         * Intercept redirects
         */
        public function redirectIntercept()
        {
            if (!headers_sent() && php_sapi_name() != 'cli')
            {
                $httpStatus = container::getResponse()->getStatus();
                if (container::getConfig('hathoora.logger.webprofiler.show_redirects') &&
                    ($redirectURL = container::getResponse()->getRedirectURL()) &&
                    !container::getResponse()->getContentLength()
                )
                {
                    // send a 200 OK header for redirect interception
                    header('HTTP/1.1 200 OK');
                    header('Content-type: text/html');

                    $flashMessage = container::getResponse()->getFlash(false);

                    ob_start();
                    $template = dirname ( __FILE__ ) . '/redirect.tpl.php';
                    include_once($template);

                    // flush headers to kernel won't try to do reponse->send()
                    ob_flush();
                    flush();

                    // set to true so we can display web profiler as well..
                    container::setConfig('hathoora.logger.webprofiler.show_redirects_render', true);
                }
            }
        }

        public function display(\hathoora\container &$container)
        {
            $webProfiler = container::getConfig('hathoora.logger.webprofiler.enabled');

            if (!$webProfiler)
            {
                if (!container::getConfig('hathoora.logger.webprofiler.show_redirects_render'))
                    return;
            }

            // request
            $request = container::getRequest();

            // skip on ajax?
            $skipOnAjax = true;
            if (container::hasConfig('hathoora.logger.webprofiler.skip_on_ajax'))
                $skipOnAjax = container::getConfig('hathoora.logger.webprofiler.skip_on_ajax');

            if ($skipOnAjax && $request->isAjax())
                return;

            // skip webprofiler for specified POST params
            if ($request->postParam() && ($arrSkipParams = container::getConfig('hathoora.logger.webprofiler.skip_on_post_params')) && is_array($arrSkipParams))
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
            if ($request->getParam() && ($arrSkipParams = container::getConfig('hathoora.logger.webprofiler.skip_on_get_params')) && is_array($arrSkipParams))
            {
                foreach ($arrSkipParams as $param)
                {
                    if ($request->getParam($param))
                    {
                        return;
                    }
                }
            }
            $response = container::getResponse();
            $contentType = $response->getHeader('Content-Type');

            // do we need to display profiler?
            $contentTypeRegexMatched = false;
            $arrContentTypeRegexes = container::getConfig('hathoora.logger.webprofiler.content_types');
            if (!$arrContentTypeRegexes || !is_array($arrContentTypeRegexes))
                $arrContentTypeRegexes = array('text/html');

            foreach($arrContentTypeRegexes as $contentTypeRegex)
            {
                if (preg_match('#' . $contentTypeRegex  . '#i', $contentType))
                {
                    $contentTypeRegexMatched = true;
                    break;
                }
            }

            if ($contentTypeRegexMatched)
            {
               $this->renderTemplate();
            }
        }

        private function renderTemplate()
        {
            // request
            $request = container::getRequest();
            $response = container::getResponse();

            // include template
            $template = dirname ( __FILE__ ) . '/template.tpl.php';
            if (container::getConfig('hathoora.logger.webprofiler.template'))
                $template = container::getConfig('hathoora.logger.webprofiler.template');

            if (file_exists($template))
            {
                // kernel
                $totalMemory = memory_get_peak_usage();
                $totalMemoryFormatted = stringHelper::formatBytes($totalMemory);
                $scriptEndTime = microtime();
                $executionTime = profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $scriptEndTime);
                $version = container::getKernel()->getVersion();

                // system info?
                $system = null;
                if (container::getConfig('hathoora.logger.webprofiler.system'))
                {
                    $system['load'] = sys_getloadavg();

                    // memory stuff
                    $_mem = @explode("\n", file_get_contents('/proc/meminfo'));
                    if ($_mem)
                    {
                        $meminfo = array();
                        foreach ($_mem as $_line)
                        {
                            @list($key, $val) = @explode(':', $_line);
                            $val = @preg_replace('/[^0-9]/', '', $val);
                            if ($val)
                                $system['memory'][$key] = stringHelper::formatBytes($val);
                        }
                    }

                    // total http connections
                    $system['sockets'] = array('http' => array());
                    $system['sockets']['http']['total'] = @exec('netstat -an | grep ":'. $_SERVER['SERVER_PORT'] .'\s" | grep -vc "LISTEN" ');
                    $system['sockets']['http']['established'] = @exec('netstat -an | grep -e  ":'. $_SERVER['SERVER_PORT'] .'\s" | grep -c "ESTABLISHED"');
                }

                // route
                $httpStatus = $response->getStatus();
                $controller = container::getController();

                // config
                $arrConfigs = container::getAllConfig();

                // logging
                $loggingStatus = container::getConfig('hathoora.logger.logging.enabled');

                $arrProfile =& profiler::$arrProfile;
                $arrLog =& logger::$arrLog;

                include_once($template);
            }
        }
    }
}