<?php

namespace Seytar\Routing;

use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

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
		'Router'	 => self::class,
		'App'		 => App::class,
		'Input'		 => Input::class,
		'Redirect'	 => Redirect::class,
		'Request'	 => Request::class,
		'Response'	 => Response::class,
		'Route'		 => Route::class,
		'URL'		 => URL::class
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

	public static function bootstrap()
	{
		// Only bootstrap once.
		if (static::$bootstrapped)
		{
			return;
		}

		// Load helper functions.
		require_once __DIR__ . '/../../../illuminate/support/helpers.php';

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
		HttpRequest::enableHttpMethodParameterOverride();

		// Create the request.
		$app['request'] = HttpRequest::createFromGlobals();

		// Register services.
		with(new EventServiceProvider($app))->register();
		with(new RoutingServiceProvider($app))->register();

		// Register aliases.
		foreach (static::$aliases as $alias => $class) {
			class_alias($class, $alias);
		}

		// Dispatch on shutdown.
		register_shutdown_function('Seytar\Routing\Router::dispatch');

		// Mark bootstrapped.
		static::$bootstrapped = true;
	}

	/**
	 * Dispatch the current request to the application.
	 *
	 * @return HttpResponse
	 */
	public static function dispatch()
	{
		// Only dispatch once.
		if (static::$dispatched)
		{
			return;
		}

		// Get the request.
		$request = static::$container['request'];

		// Pass the request to the router.
		$response = static::$container['router']->dispatch($request);

		// Send the response.
		$response->send();

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
