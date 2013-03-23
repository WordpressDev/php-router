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

    /**
     * Register a controller with the router.
     *
     * @param  string|array  $controllers
     * @param  string|array  $defaults
     * @return void
     */
    public static function controller($controllers, $defaults = 'index')
    {
        Router::controller($controllers, $defaults);
    }

    /**
     * Register a route with the router.
     * 
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public static function register($method, $route, $action)
    {
        // If the developer is registering multiple request methods to handle
        // the URI, we'll spin through each method and register the route
        // for each of them along with each URI and action.
        if (is_array($method))
        {
            foreach ($method as $http)
            {
                Router::route($http, $route, $action);
            }
            return;
        }
        
        foreach ((array) $route as $uri) {
            Router::route($method, $uri, $action);
        }
    }

}