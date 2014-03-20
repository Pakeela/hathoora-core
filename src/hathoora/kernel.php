<?php
namespace hathoora
{
    /**
     * Kernel for Hathoora PHP Framework
     *
     * This is the main class of Hathoora PHP Framework. This class is responsible
     * initializing application, routing URLs, executing controllers and returning response.
     *
     * @author Usman Malik <attozk@khat.pk>
     * @version 0.45 Stable for production use
     */
    use hathoora\configure\config,
        hathoora\configure\serviceManager,
        hathoora\registry,
        hathoora\router\request as routeRequest,
        hathoora\http\response,
        hathoora\controller\base as controller,
        hathoora\logger\logger,
        hathoora\logger\errorHandler;

    /**
     * Singleton hathoora\kernel
     */
    class kernel
    {
        /**
         * Version of Hathoora PHP Framework
         *
         * @var float
         */
        private $version = 0.5;

        /**
         * Stores routing request object
         *
         * @var object hathoora\router\request
         */
        public $routeRequest;

        /**
         * Stores routing dispatcher object
         *
         * @var object hathoora\router\dispatcher
         */
        public $routeDispatcher;

        /**
         * Stores base controller object
         *
         * @var object hathoora\controller\base
         */
        public $controller;

        /**
         * Stores response object
         *
         * @var object hathoora\http\response
         */
        public $response;

        /**
         * Stores container
         *
         * @var object hathoora\container
         */
        public $container;

        /**
         *  Stores observer
         *
         * @var object observer
         */
        public $observer;

        /**
         * Defines environment and bootstraps application
         *
         * Constants defined by this function:
         *  <ul>
         *      <li> HATHOORA_PROFILE_START_TIME: Stores microtime() which is used for debugging
         *           in keepign track of execution order and execution time.
         *           It should be defined in frontend controller for accurate profiling.
         *      </li>
         *      <li> HATHOORA_ENV: The environment in wich application is running e.g. prod, dev etc.. </li>
         *      <li> HATHOORA_PATH: File path for Hathoora library folder </li>
         *      <li> HATHOORA_UUID: A unique ID of this request. This is also used in identifying
         *           the relationshipe between requests to debug log
         *      </li>
         *  </ul>
         *
         * @param string $env
         * @return \hathoora\kernel
         */
        public function __construct($env = 'dev')
        {
            if (!defined('HATHOORA_PROFILE_START_TIME'))
                define('HATHOORA_PROFILE_START_TIME', microtime());

            define('HATHOORA_ENV', $env);
            define('HATHOORA_PATH', __DIR__ . '/');
            define('HATHOORA_UUID', uniqid('', true));

            $this->bootstrap();
        }

        /**
         * Bootstraps application, load configurations and fires `kernel.ready` event
         *
         * This function bootstraps the application. An application can be bootstrapped into a command line
         * mode or web (default) mode.
         *
         * For web mode, this function figures out which app (from ROOT/app folder) is being called by
         * examing the request.
         *
         * Then function then loads YML configuration file(s) for the appropriate application. It also
         * adds default hathoora services (e.g. translation, gulabooAssets) if permitted by configuration.
         *
         * This function stores kernel in registry
         *  <code>
         *      registry::setByRef('hathooraKernel', $this);
         *  </code>
         *
         * Constants defined by this function:
         *  <ul>
         *      <li> HATHOORA_APP: The active app which is called upon </li>
         *      <li> HATHOORA_APP_PATH: Path of HATHOORA_APP </li>
         *  </ul>
         *
         * @returns kernel object
         */
        public function bootstrap()
        {
            // change error handlers
            #$errorHandler = new errorHandler();
            #set_error_handler(array($errorHandler, 'customErrorHandler'));
            #set_exception_handler(array($errorHandler, 'customExceptionHandler'));

            $app = null;
            $sapi = php_sapi_name();

            // need to figure out app to load appropriate config files
            if ($sapi == 'cli')
            {
                if (!empty($_SERVER['argv']) && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == '-r' &&
                    isset($_SERVER['argv'][2]))
                {
                    $url = $_SERVER['argv'][2];
                    $arrUrl = parse_url($url);

                    // set $_SERVER to make things work..
                    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = isset($arrUrl['host']) ? $arrUrl['host'] : null;
                    $_SERVER['QUERY_STRING'] =  isset($arrUrl['query']) ? $arrUrl['query'] : null;
                    $_SERVER['REQUEST_URI'] = isset($arrUrl['path']) ? $arrUrl['path'] : null;
                    $_SERVER['REQUEST_URI'] .= $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : null;
                    if ($_SERVER['QUERY_STRING'])
                        parse_str($_SERVER['QUERY_STRING'], $_GET);
                }
                else
                    die('Invalid command line, format is index.php -r http://www.example.com/controller/method/param1?var1=1' . "\n");
            }

            $this->routeRequest = new routeRequest();
            $app = $this->routeRequest->getApp();
            $appPath = $this->routeRequest->appDirectoryPath;

            define('HATHOORA_APP', $app);
            define('HATHOORA_APP_PATH', $appPath);

            registry::setByRef('hathooraKernel', $this);

            new config(array(
                HATHOORA_APP_PATH .'/config/config_'. HATHOORA_ENV .'.yml'));

            // load configuration dependent hathoora services
            serviceManager::loadDefaultServicesFromConfig();

            $this->container = new container();

            // add all listeners
            $this->observer = new observer();
            $this->observer->addEventListenersFromConfig();

            // start session - @todo handle session start differently
            @session_start();

            // fire kernel.ready event
            $this->addKernelEvent('ready');

            return $this;
        }

        /**
         * After bootstraping, this function does the rest of the work for loading a page for
         * a given request
         */
        public function bootstrapWebPage()
        {
            // dispatch route
            $this->routeDispatcher = $this->routeRequest->dispatch();

            // fire kernel.route event
            $this->addKernelEvent('route');

            // hand if off to controller
            $this->controller = new controller($this->routeDispatcher);

            // path is reachable
            if ($this->controller->isExecutable())
            {
                // fire kernel.controller event
                $this->addKernelEvent('controller');
                $this->response = $this->controller->execute();
            }
            // controller::action not executable/reacable
            else
            {
                logger::log(logger::LEVEL_ERROR, $this->controller->getControllerNamespace().'->'.$this->controller->getControllerActionName().' not found.');

                // fire kernel.route_unreachable event
                $arrUnReachable = $this->addKernelEvent('route_unreachable', true);
                if (is_array($arrUnReachable))
                {
                    foreach($arrUnReachable as $_observerName => $observerResponse)
                    {
                        if (is_object($observerResponse) && ($observerResponse instanceof response))
                        {
                            $this->response = $observerResponse;
                            break;
                        }
                    }
                }

            }

            // issue a 404 response when no route found and 400 for CRUD 
            if (!is_object($this->response))
            {
                if ($this->controller->isCRUD)
                    $this->response = new response('400 Bad Request', false, 400);
                else
                    $this->response = new response('<h1>404 - Not Found</h1>', false, 404);
            }

            // kernel response ready and about to be sent out
            $this->addKernelEvent('response');

            if (!headers_sent())
                $this->response->send();
            else if (php_sapi_name() == 'cli')
                $this->response->send();

            // kernel terminate
            $this->addKernelEvent('terminate');
        }

        /**
         * Add kernel events to observer
         *
         * @param string $name without 'kernel.' prefix
         * @param bool $returnResult to return response back
         * @return mixed|object
         */
        protected function addKernelEvent($name, $returnResult = false)
        {
            return $this->observer->addEvent('kernel.' . $name, $this->container, $returnResult);
        }

        /**
         * Returns hathoora version
         */
        public function getVersion()
        {
            return $this->version;
        }
    }
}

// global scope
namespace {
    /**
     * Print_r shortcut
     * @param $var
     * @param bool $die
     */
    function printr($var, $die = false)
    {
        echo '<pre>'. print_r($var, true) .'</pre>';

        if ($die)
            die($die);
    }

    /**
     * var_dump shortcut
     * @param $var
     * @param bool $die
     */
    function vardump($var, $die = false)
    {
        echo '<pre>';var_dump($var);echo '</pre>';

        if ($die)
            die($die);
    }
}