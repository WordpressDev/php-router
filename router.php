<?php
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

class Route
{

    /**
     * Register a GET route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function get($route, $action)
    {
        static::register('GET', $route, $action);
    }

    /**
     * Register a POST route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function post($route, $action)
    {
        static::register('POST', $route, $action);
    }

    /**
     * Register a PUT route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function put($route, $action)
    {
        static::register('PUT', $route, $action);
    }

    /**
     * Register a DELETE route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function delete($route, $action)
    {
        static::register('DELETE', $route, $action);
    }

    /**
     * Register a route that handles any request method.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function any($route, $action)
    {
        static::register('*', $route, $action);
    }

    /**
     * Register a HTTPS route with the router.
     *
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function secure($method, $route, $action)
    {
        // stop when not secure
        if (!Router::secure())
            return;
        
        static::register($method, $route, $action);
    }

    public static function register($method, $route, $action)
    {
        // register multiple methods
        if (is_array($method)) {
            foreach ($method as $http) {
                Router::route($http, $route, $action);
            }
            return;
        }
        
        Router::route($method, $route, $action);
    }
}

class Router
{

    /**
     * The URI for the current request.
     *
     * @var string
     */
    public static $uri;

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
        if (!is_null(static::$uri)) return static::$uri;
        
        if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME'])) {
            // detect using REQUEST_URI, this works in most situations
            static::$uri = $_SERVER['REQUEST_URI'];
            
            // remove equal parts with SCRIPT_NAME
            if (strpos(static::$uri, $_SERVER['SCRIPT_NAME']) === 0) {
                static::$uri = substr(static::$uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos(static::$uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                static::$uri = substr(static::$uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
            
            // remove query string
            if (($pos = strpos(static::$uri, '?')) !== false) {
                static::$uri = substr(static::$uri, 0, $pos);
            }
        } 
        else if (isset($_SERVER['PATH_INFO'])) {
            // detect URI using PATH_INFO
            static::$uri = $_SERVER['PATH_INFO'];
        } else if (isset($_SERVER['ORIG_PATH_INFO'])) {
            // detect URI using ORIG_PATH_INFO
            static::$uri = $_SERVER['ORIG_PATH_INFO'];
        }
        
        // remove leading and trailing slashes
        static::$uri = trim(static::$uri, '/');
        
        if (static::$uri == '') {
            static::$uri = '/';
        }
        
        return static::$uri;
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
     * @return void
     */
    public static function route($method, $route, $action)
    {
        // only route once
        if (static::$routed) {
            return;
        }
        
        // validate the request method
        if ($method != '*' && strtoupper($method) != static::method()) {
            return;
        }
        
        // remove leading and trailing slashes
        $route = trim($route, '/');
        
        if ($route == '') {
            $route = '/';
        }
        
        // route contains wildcards
        if (strpos($route, '(') !== FALSE) {
            $patterns = array(
                '(:num)' => '([0-9]+)',
                '(:any)' => '([a-zA-Z0-9\.\-_%=]+)',
                '(:all)' => '(.*)',
                '/(:num?)' => '(?:/([0-9]+))?',
                '/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%=]+))?',
                '/(:all?)' => '(?:/(.*))?'
            );
            
            foreach ($patterns as $pattern => $replace) {
                $route = str_replace($pattern, $replace, $route);
            }
        }
        
        // build regular expression pattern
        $pattern = '#^' . $route . '$#';
        
        // match pattern
        if (preg_match($pattern, static::uri(), $parameters)) {
            $parameters = array_slice($parameters, 1);
            
            if (is_callable($action)) {
                // execute anonymous function
                call_user_func_array($action, $parameters);
            } else if (is_string($action) && strpos($action, '@')) {
                // execute class@method
                list($class, $method) = explode('@', $action);
                
                // search for a class file
                if (!class_exists($class)) {
                    // locations to look for a class file
                    $locations = array(
                        "$class.php",
                        "controllers/$class.php"
                    );
                    
                    // check all locations for their existence
                    foreach ($locations as $location) {
                        if (file_exists($location)) {
                            include($location);
                            break;
                        }
                    }
                }
                
                // class was not found
                if (!class_exists($class))
                    return;
                
                $instance = new $class();
                call_user_func_array(array($instance, $method), $parameters);
            }
            
            // user was routed
            static::$routed = TRUE;
        }
    }
}