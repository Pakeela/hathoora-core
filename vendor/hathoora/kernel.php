<?php
namespace hathoora;

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
    hathoora\router\route,
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

    /* 
     * Stores container
     *
     * @var object container
     */
    public $container;
    
    /**
     * Defines environment and bootstraps application
     *
     * Constants defined by this function:
     *  <ul>
     *      <li> HATHOORA_PROFILE_START_TIME: Stores microtime() which is used for debugging
     *           in keepign track of execution order and execution time
     *      </li>
     *      <li> HATHOORA_ENV: The environment in wich application is running e.g. prod, dev etc.. </li>
     *      <li> HATHOORA_PATH: File path for Hathoora root </li>
     *      <li> HATHOORA_UUID: A unique ID of this request. This is also used in identifying
     *           the relationshipe between requests to debug log
     *      </li>
     *  </ul>
     *
     * @param string $env
     * @return object kernel
     */
    public function __construct($env = 'dev')
    {
        if (!defined('HATHOORA_PROFILE_START_TIME'))
            define('HATHOORA_PROFILE_START_TIME', microtime());
            
        // define constants about kernel
        define('HATHOORA_ENV', $env);
        define('HATHOORA_PATH', __DIR__ . '/');
        define('HATHOORA_UUID', uniqid('', true)); // unqiue request id for request
        
        // now bootstrap
        $this->bootstrap();
    }
    
    /**
     * Bootstraps application, load configurations and broadcasts `kernel.ready` event
     *
     * This function bootstraps the application. An application can be bootstrapped into a command line
     * mode or web (default) mode.
     *
     * For web mode, this function figures out which app (from ROOT/app folder) is being called by
     * examing the request.
     *
     * Then function then loads YML configuration file(s) for the appropriate application. It also
     * adds default hathoora services (e.g. translation) if permitted by configuration.
     *
     * This function stores kernel in registry
     *  <code>
     *      registry::setByRef('hathooraKernel', $this);
     *  </code>
     *  
     * Constants defined by this function:
     *  <ul>
     *      <li> HATHOORA_APP_URS </li>
     *      <li> HATHOORA_APP: The active app  which is called upon </li>
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
        
        $sapi_name = php_sapi_name();
        
        // need to figure out app to load appropriate config files
        if ($sapi_name != 'cli')
        {
            $this->routeRequest = new routeRequest();
            $app = $this->routeRequest->getApp();
            $appPath = $this->routeRequest->appDirectoryPath;
            define('HATHOORA_APP_URS', $this->routeRequest->baseURS); // base URL
        }
        else
        {
            $app = null;
            define('HATHOORA_APP_URS', null);
        }
        define('HATHOORA_APP', $app);
        define('HATHOORA_APP_PATH', $appPath);
        
        // make kernel available to registry
        registry::setByRef('hathooraKernel', $this);
        
        // now load configurations in this particular order because later on would replace previous ones
        $config = new config(array(
                                    HATHOORA_APP_PATH .'/config/config_'. HATHOORA_ENV .'.yml'));
               
        logger::log(logger::LEVEL_DEBUG, 'Configuration loaded: <br/> <pre>' . print_r($config::$arrConfigFiles, true) .'</pre>');
        
        // load configuration dependent hathoora services
        serviceManager::loadDefaultServicesFromConfig();
        
        $this->container = new container();

        // add all listeners
        $this->observer = new observer();
        $this->observer->addEventListenersFromConfig();
        
        // start session
        @session_start();

        // kernel ready
        $this->addKernelEvent('ready');

        return $this;
    }
    
    /**
     * After bootstraping, this function does the rest of the work for loading a page
     */
    public function bootstrapWebPage()
    {
        // now dispatch route
        $this->routeDispatcher = $this->routeRequest->dispatch();
        
        // kernel route ready
        $this->addKernelEvent('route');
        
        // hand if off to controller
        $this->controller = new controller($this->routeDispatcher);
        
        if ($this->controller->isExecutable())
        {
            // kernel controller is loaded
            $this->addKernelEvent('controller');
            $this->response = $this->controller->execute();
        }
        // controller::action not executable/reacable, add 'kernel.route_unreachable' event for this
        else
        {
            logger::log(logger::LEVEL_ERROR, $this->controller->getControllerNamespace().'->'.$this->controller->getControllerActionName().' not found.');

            // kernel route is not reachable
            $this->response = $this->addKernelEvent('route_unreachable', true);
        }

        // issue a 404 response when no route found
        if (!is_object($this->response))
            $this->response = new response('<h1>404 - Not Found</h1>', false, 404);

        // kernel response ready and about to be sent out
        $this->addKernelEvent('response');
        
        if (!headers_sent())
            $this->response->send();
        
        // kernel terminate
        $this->addKernelEvent('terminate');
    }
    
    /**
     * After bootstraping, this function does the rest of the work for command line
     */
    public function bootstrapCLI()
    {
        // kernel terminate
        $this->addKernelEvent('terminate');
    }
    
    /**
     * Add kernel events to observer
     *
     * @param string $name without 'kernel.' prefix
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