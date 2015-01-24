<?php
namespace hathoora\router;

use hathoora\container,
    hathoora\logger\logger;

/**
 * Route dispatching class
 */
class dispatcher
{

    /**
     * Constructor function for dispatch request
     */
    public function __construct(\hathoora\router\request $request)
    {
        $this->request =& $request;
        $this->routeURI =& $request->routeURI;
        $this->uri =& $request->uri;
        $this->app =& $request->app;
        $this->appNamespace =& $request->appNamespace;
        $this->appDirectoryPath =& $request->appDirectoryPath;
        $this->appHasDispatcher =& $request->appHasDispatcher;
        $this->baseURS =& $request->baseURS;
        $this->htRO = array();
        $this->isCRUD = false;

        // @todo check using HTTP_HTRO as well
        if (!empty($_SERVER['REDIRECT_HTRO']))
        {
            $this->htRO = array(
                'controller' => isset($_SERVER['REDIRECT_HTRO_CONTROLLER']) ? $_SERVER['REDIRECT_HTRO_CONTROLLER'] : null,
                'action' => isset($_SERVER['REDIRECT_HTRO_ACTION']) ? $_SERVER['REDIRECT_HTRO_ACTION'] : null,
                'params' => isset($_SERVER['REDIRECT_HTRO_PARAMS']) ? $_SERVER['REDIRECT_HTRO_PARAMS'] : null
            );
        }

    }

    /**
     * Dispatch request and execute controller
     *
     * @return \hathoora\router\dispatcher class
     */
    public function dispatch()
    {
        // take multi apps in consideration and figure out root URI only when app != site
        $adjustedURI = $this->routeURI;
        $arrPossibleRoute = $controllerFolder = false;

        // Hathoora routing override in .htacces
        if (count($this->htRO))
        {
            $controller = $this->htRO['controller'];
            $action = $this->getActionName($this->htRO['action']);
            $arrParams = $this->htRO['params'];
        }
        else
        {
            // custom dispatcher
            if (is_array($this->appHasDispatcher))
            {
                // @todo check file before loading..
                #sqlLoadClassTest(true);
                $class = $this->appNamespace . '\\' . $this->appHasDispatcher['class'];
                $method =  $this->appHasDispatcher['method'];
                $callable = is_callable(array($class, $method));
                #sqlLoadClassTest(false);

                if ($callable)
                {
                    logger::log(logger::LEVEL_DEBUG, 'Calling App dispatcher ('. print_r($this->appHasDispatcher, true) .')...');

                    $obj = new $class();

                    $arrPossibleRoute = call_user_func_array(array(
                            $obj,
                            $method
                        ),
                        array(container::getContainer()));

                    if (!is_array($arrPossibleRoute))
                        logger::log(logger::LEVEL_INFO, 'App dispatcher did not return valid response ('. print_r($arrPossibleRoute, true) .')');
                }
                else
                    logger::log(logger::LEVEL_ERROR, 'App dispatcher ('. print_r($this->appHasDispatcher, true) .') is not callable.');
            }

            if (is_array($arrPossibleRoute))
            {
                $controllerFolder = !empty($arrPossibleRoute['folder']) ? $arrPossibleRoute['folder'] : null;
                $controller = !empty($arrPossibleRoute['controller']) ? $arrPossibleRoute['controller'] : null;
                $action = !empty($arrPossibleRoute['action']) ? $this->getActionName($arrPossibleRoute['action']) : null;
                $arrParams = !empty($arrPossibleRoute['params']) ? $arrPossibleRoute['params'] : array();
            }
            else
            {
                #0) build controller, action & params from the path URL
                $arrParams = explode('/', $adjustedURI);
                array_shift($arrParams); // remove the first useless key

                $controller = array_shift($arrParams);
                if (!$controller)   // default controller name
                    $controller = 'default';
                $controller .= 'Controller';

                // is crud?
                $this->isCRUD = is_subclass_of($this->appNamespace .'\controller\\' . $controller, '\hathoora\controller\CRUD');

                if (!$this->isCRUD)
                {
                    $action = $this->getActionName(array_shift($arrParams));
                    if (!$action)       // default action name
                    $action = 'index';
                }
                // for CRUD manipulate action..
                else
                {
                    $this->isCRUD = $_SERVER['REQUEST_METHOD'];
                    if (count($arrParams) == 1 && empty($arrParams[0]))
                        $arrParams = array();

                    if ($this->isCRUD == 'GET')
                    {
                        $action = 'collection';
                        if (count($arrParams))
                            $action = 'read';
                    }
                    else if ($this->isCRUD == 'POST')
                        $action = 'create';
                    else if ($this->isCRUD == 'PUT')
                        $action = 'update';
                    else if ($this->isCRUD == 'DELETE')
                        $action = 'delete';
                    else
                        $action = strtolower($this->isCRUD);
                }
            }
        }

        // assign locally
        $this->controllerFolder = $controllerFolder; // used mostly for customAppDispatcher
        $this->controller = $controller;
        $this->action = $action;
        $this->params = $arrParams;

        return $this;
    }

    /**
     * We want to use camelCase action names in controller classes, but be
     * able to access them via '-' seperated SEO names
     *
     * ex: controller::actionDash() can be accessed via the web as
     *     /controller/actionDash as well as /controller/action-dash
     */
    private function getActionName($str)
    {
        $actionName = null;
        if (strpos($str, '-') !== false)
        {
            $arrStr = explode('-', $str);
            if (is_array($arrStr))
            {
                foreach($arrStr as $v)
                {
                    if ($actionName) $v = ucwords($v);
                    $actionName .= $v;
                }
            }
        }
        else
            $actionName = $str;

        return $actionName;
    }
}