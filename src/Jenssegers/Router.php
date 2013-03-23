<?php namespace Jenssegers;

/**
* @name    PHP Router
* @author  Jens Segers
* @link    http://www.jenssegers.be
* @license MIT License Copyright (c) 2012 Jens Segers
*
* This router is based on the Laravel routing system. Some code is reused.
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/

class Router
{

    /**
     * The URI for the current request.
     *
     * @var string
     */
    public static $uri;

    /**
     * The base URL for the current request.
     *
     * @var string
     */
    public static $base;

    /**
     * Was the user routed yet?
     */
    private static $routed = FALSE;

    /**
     * Get the URI for the current request.
     *
     * @return string
     */
    public static function uri()
    {
        if (!is_null(static::$uri))
            return static::$uri;
        
        if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME']))
        {
            // Detect using REQUEST_URI, this works in most situations.
            static::$uri = $_SERVER['REQUEST_URI'];
            
            // Remove equal parts with SCRIPT_NAME.
            if (strpos(static::$uri, $_SERVER['SCRIPT_NAME']) === 0)
            {
                static::$uri = substr(static::$uri, strlen($_SERVER['SCRIPT_NAME']));
            }
            elseif (strpos(static::$uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
            {
                static::$uri = substr(static::$uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
            
            // Remove the query string.
            if (($pos = strpos(static::$uri, '?')) !== false)
            {
                static::$uri = substr(static::$uri, 0, $pos);
            }
        }
        else if (isset($_SERVER['PATH_INFO']))
        {
            // Detect URI using PATH_INFO
            static::$uri = $_SERVER['PATH_INFO'];
        }
        
        // Remove leading and trailing slashes
        static::$uri = trim(static::$uri, '/');
        
        if (static::$uri == '')
        {
            static::$uri = '/';
        }
        
        return static::$uri;
    }

    /**
     * Get the base URL for the current request.
     *
     * @return string
     */
    public static function base($uri = '')
    {
        if (!is_null(static::$base))
            return static::$base . $uri;
        
        if (isset($_SERVER['HTTP_HOST']))
        {
            static::$base = Router::secure() ? 'https' : 'http';
            static::$base .= '://' . $_SERVER['HTTP_HOST'];
            static::$base .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        }
        else
        {
            static::$base = 'http://localhost/';
        }
        
        return static::$base . $uri;
    }

    /**
     * Check if the the request is requested by HTTPS 
     *
     * @return bool
     */
    public static function secure()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }

    /**
     * Get the request method for the current request.
     *
     * @return string
     */
    public static function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Match the route and execute the action
     * 
     * @param  string  $method
     * @param  string  $route
     * @param  mixed   $action
     * @return void
     */
    public static function route($method, $route, $action)
    {
        // If a previous route was matched, we can skip all routes with a lower
        // priority.
        if (static::$routed)
        {
            return;
        }
        
        // We can ignore this route if the request method does not match
        if ($method != '*' && strtoupper($method) != static::method())
        {
            return;
        }
        
        $route = trim($route, '/');
        
        if ($route == '')
        {
            $route = '/';
        }
        
        // Of course literal route matches are the quickest to find, so we will
        // check for those first. If the destination key exists in the routes
        // array we can just return that route now.
        if ($route == static::uri())
        {
            static::call($action);
            return;
        }
        
        // We only need to check routes with regular expression since all others
        // would have been able to be matched by the search for literal matches
        // we just did before we started searching.
        if (strpos($route, '(') !== FALSE)
        {
            $patterns = array(
                '(:num)' => '([0-9]+)', 
                '(:any)' => '([a-zA-Z0-9\.\-_%=]+)', 
                '(:all)' => '(.*)', 
                '/(:num?)' => '(?:/([0-9]+))?', 
                '/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%=]+))?', 
                '/(:all?)' => '(?:/(.*))?'
            );
            
            $route = str_replace(array_keys($patterns), array_values($patterns), $route);
            
            // If we get a match we'll return the route and slice off the first
            // parameter match, as preg_match sets the first array item to the
            // full-text match of the pattern.
            if (preg_match('#^' . $route . '$#', static::uri(), $parameters))
            {
                static::call($action, array_slice($parameters, 1));
                return;
            }
        }
    }

    /**
     * Execute an action matched by the router
     *
     * @param  mixed   $action
     * @param  mixed   $parameters
     * @return void
     */
    private static function call($action, $parameters = array())
    {
        if (is_callable($action))
        {
            // The action is an anonymous function, let's execute it.
            echo call_user_func_array($action, $parameters);
        }
        else if (is_string($action) && strpos($action, '@'))
        {
            list($controller, $method) = explode('@', $action);
            $class = basename($controller);
            
            // Controller delegates may use back-references to the action parameters,
            // which allows the developer to setup more flexible routes to various
            // controllers with much less code than would be usual.
            if (strpos($method, '(:') !== FALSE)
            {
                foreach ($parameters as $key => $value)
                {
                    $method = str_replace('(:' . ($key + 1) . ')', $value, $method, $count);
                    if ($count > 0)
                    {
                        unset($parameters[$key]);
                    }
                }
            }
            
            // Default controller method if left empty.
            if (!$method)
            {
                $method = 'index';
            }
            
            // Load the controller class file if needed.
            if (!class_exists($class))
            {
                if (file_exists("controllers/$controller.php"))
                {
                    include ("controllers/$controller.php");
                }
            }
            
            // The controller class was still not found. Let the next routes handle the
            // request.
            if (!class_exists($class))
            {
                return;
            }
            
            $instance = new $class();
            echo call_user_func_array(array($instance, $method), $parameters);
        }
        
        // The current route was matched. Ignore new routes.
        static::$routed = TRUE;
    }

    /**
     * Match the route with a controller and execute a method
     *
     * @param  string|array  $controllers
     * @param  string        $defaults
     * @return void
     */
    public static function controller($controllers, $defaults = 'index')
    {
        foreach ((array) $controllers as $controller)
        {
            // If the current URI does not match this controller we can simply skip
            // this route.
            if (strpos(strtolower(static::uri()), strtolower($controller)) === 0)
            {
                // First we need to replace the dots with slashes in the controller name
                // so that it is in directory format. The dots allow the developer to use
                // a cleaner syntax when specifying the controller. We will also grab the
                // root URI for the controller's bundle.
                $controller = str_replace('.', '/', $controller);
                
                // Automatically passes a number of arguments to the controller method
                $wildcards = str_repeat('/(:any?)', 6);
                
                // Once we have the path and root URI we can build a simple route for
                // the controller that should handle a conventional controller route
                // setup of controller/method/segment/segment, etc.
                $pattern = trim($controller . $wildcards, '/');
                
                // Rregister the controller route with a wildcard method so it is 
                // available on every request method.
                static::route('*', $pattern, "$controller@(:1)");
            }
        }
    }

}
