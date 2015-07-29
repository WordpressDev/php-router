<?php namespace Seytar\Routing;

use Illuminate\Container\Container;
use Illuminate\Support\ClassLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Foundation\AliasLoader;

class Router {

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected static $container;

    /**
     * Mark if the router has been bootstrapped.
     *
     * @var boolean
     */
    protected static $bootstrapped = false;

    /**
     * Mark if the request has been dispatched.
     *
     * @var boolean
     */
    protected static $dispatched = false;

    /**
     * Class aliases.
     *
     * @var array
     */
    protected static $aliases = array(
        'Router' => 'Seytar\Routing\Router',
        'App' => 'Illuminate\Support\Facades\App',
        'Input' => 'Illuminate\Support\Facades\Input',
        'Redirect' => 'Illuminate\Support\Facades\Redirect',
        'Request' => 'Illuminate\Support\Facades\Request',
        'Response' => 'Illuminate\Support\Facades\Response',
        'Route' => 'Illuminate\Support\Facades\Route',
        'URL' => 'Illuminate\Support\Facades\URL'
    );

    /**
     * Create a new router instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->bootstrap();
    }

    public static function bootstrap($errorCallbacks)
    {
        // Only bootstrap once.
        if (static::$bootstrapped)
            return;

        // Load helper functions.
        require_once __DIR__ . '/../../../illuminate/support/Illuminate/Support/helpers.php';

        // Directories.
        $basePath = str_finish(realpath(__DIR__ . '/..'), '/');
        $controllersDirectory = $basePath . 'Controllers';
        $modelsDirectory = $basePath . 'Models';

        // Register the autoloader and add directories.
        ClassLoader::register();
        ClassLoader::addDirectories(array($controllersDirectory, $modelsDirectory));

        // Instantiate the container.
        $app = new Container;
        static::$container = $app;

        // Tell facade about the application instance.
        Facade::setFacadeApplication($app);

        // Register application instance with container
        $app['app'] = $app;

        // Set environment.
        $app['env'] = 'production';

        // Enable HTTP Method Override.
        Request::enableHttpMethodParameterOverride();

        // Create the request.
        $app['request'] = Request::createFromGlobals();

        // Register services.
        with(new EventServiceProvider($app))->register();
        with(new RoutingServiceProvider($app))->register();

        // Register aliases.
        foreach (static::$aliases as $alias => $class)
        {
            class_alias($class, $alias);
        }

        // Load the routes file if it exists.
        if (file_exists($basePath . 'routes.php'))
        {
            require_once $basePath . 'routes.php';
        }

        // Dispatch on shutdown.
        register_shutdown_function('Seytar\Routing\Router::dispatch', $errorCallbacks);

        // Mark bootstrapped.
        static::$bootstrapped = true;
    }

    /**
     * Dispatch the current request to the application.
     *
     * @return \Illuminate\Http\Response
     */
    public static function dispatch($callbacks)
    {
        // Only dispatch once.
        if (static::$dispatched) return;

        // Get the request.
        $request = static::$container['request'];

        try {
            // Pass the request to the router.
            $response = static::$container['router']->dispatch($request);

            // Send the response.
            $response->send();
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $ex) {
			$callback = is_array($callbacks) ? $callbacks['not_found'] : $callbacks;
            call_user_func($callback, $ex);
        } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $ex) {
			$callback = is_array($callbacks) ? $callbacks['not_allowed'] : $callbacks;
            call_user_func($callback, $ex);
        }

        // Mark as dispatched.
        static::$dispatched = true;
    }

    /**
     * Dynamically pass calls to the router instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array(static::$container['router'], $method), $parameters);
    }

}
