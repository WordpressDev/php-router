PHP Router
==========

The Laravel router, for use outside of the Laravel framework.

Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "seytar/php-router": "*"
        }
    }

Usage
-----

To start using the router you will need to bootstrap it like this:

	require 'vendor/autoload.php';

	use Seytar\Routing\Router;

	Router::bootstrap(function($ex) {
            header('Content-Type: text/html; charset=utf-8');
            echo '404 - Page Not Found';
        });

Once this has been done, you can define any route like you would in Laravel:

	Route::get('/', function()
	{
		echo 'Hello world.';
	});

The bootstrap process will check if there is a `routes.php` file in your application, and will automatically load it for you. It will also register a shutdown function that dispatches the current request. If you want to dispatch the current request manually, you can call `Router::dispatch()`.

The `Request`, `Response`, `Input` and `URL` facades are also available.

#### Basic GET Route

	Route::get('/', function()
	{
		return 'Hello World';
	});

#### Basic POST Route

	Route::post('foo/bar', function()
	{
		return 'Hello World';
	});

#### Registering A Route For Multiple Verbs

	Route::match(array('GET', 'POST'), '/', function()
	{
		return 'Hello World';
	});

#### Registering A Route Responding To Any HTTP Verb

	Route::any('foo', function()
	{
		return 'Hello World';
	});

#### Forcing A Route To Be Served Over HTTPS

	Route::get('foo', array('https', function()
	{
		return 'Must be over HTTPS';
	}));

Often, you will need to generate URLs to your routes, you may do so using the `URL::to` method:

	$url = URL::to('foo');

<a name="route-parameters"></a>
## Route Parameters

	Route::get('user/{id}', function($id)
	{
		return 'User '.$id;
	});

#### Optional Route Parameters

	Route::get('user/{name?}', function($name = null)
	{
		return $name;
	});

#### Optional Route Parameters With Defaults

	Route::get('user/{name?}', function($name = 'John')
	{
		return $name;
	});

#### Regular Expression Route Constraints

	Route::get('user/{name}', function($name)
	{
		//
	})
	->where('name', '[A-Za-z]+');

	Route::get('user/{id}', function($id)
	{
		//
	})
	->where('id', '[0-9]+');

#### Passing An Array Of Wheres

Of course, you may pass an array of constraints when necessary:

	Route::get('user/{id}/{name}', function($id, $name)
	{
		//
	})
	->where(array('id' => '[0-9]+', 'name' => '[a-z]+'))

#### Defining Global Patterns

If you would like a route parameter to always be constrained by a given regular expression, you may use the `pattern` method:

	Route::pattern('id', '[0-9]+');

	Route::get('user/{id}', function($id)
	{
		// Only called if {id} is numeric.
	});

#### Accessing A Route Parameter Value

If you need to access a route parameter value outside of a route, you may use the `Route::input` method:

	Route::filter('foo', function()
	{
		if (Route::input('id') == 1)
		{
			//
		}
	});

<a name="route-filters"></a>
## Route Filters

Route filters provide a convenient way of limiting access to a given route, which is useful for creating areas of your site which require authentication. There are several filters included in the Laravel framework, including an `auth` filter, an `auth.basic` filter, a `guest` filter, and a `csrf` filter. These are located in the `app/filters.php` file.

#### Defining A Route Filter

	Route::filter('old', function()
	{
		if (Input::get('age') < 200)
		{
			return Redirect::to('home');
		}
	});

If the filter returns a response, that response is considered the response to the request and the route will not execute. Any `after` filters on the route are also cancelled.

#### Attaching A Filter To A Route

	Route::get('user', array('before' => 'old', function()
	{
		return 'You are over 200 years old!';
	}));

#### Attaching A Filter To A Controller Action

	Route::get('user', array('before' => 'old', 'uses' => 'UserController@showProfile'));

#### Attaching Multiple Filters To A Route

	Route::get('user', array('before' => 'auth|old', function()
	{
		return 'You are authenticated and over 200 years old!';
	}));

#### Attaching Multiple Filters Via Array

	Route::get('user', array('before' => array('auth', 'old'), function()
	{
		return 'You are authenticated and over 200 years old!';
	}));

#### Specifying Filter Parameters

	Route::filter('age', function($route, $request, $value)
	{
		//
	});

	Route::get('user', array('before' => 'age:200', function()
	{
		return 'Hello World';
	}));

After filters receive a `$response` as the third argument passed to the filter:

	Route::filter('log', function($route, $request, $response)
	{
		//
	});

#### Pattern Based Filters

You may also specify that a filter applies to an entire set of routes based on their URI.

	Route::filter('admin', function()
	{
		//
	});

	Route::when('admin/*', 'admin');

In the example above, the `admin` filter would be applied to all routes beginning with `admin/`. The asterisk is used as a wildcard, and will match any combination of characters.

You may also constrain pattern filters by HTTP verbs:

	Route::when('admin/*', 'admin', array('post'));

#### Filter Classes

For advanced filtering, you may wish to use a class instead of a Closure. Since filter classes are resolved out of the application [IoC Container](/docs/ioc), you will be able to utilize dependency injection in these filters for greater testability.

#### Registering A Class Based Filter

	Route::filter('foo', 'FooFilter');

By default, the `filter` method on the `FooFilter` class will be called:

	class FooFilter {

		public function filter()
		{
			// Filter logic...
		}

	}

If you do not wish to use the `filter` method, just specify another method:

	Route::filter('foo', 'FooFilter@foo');

<a name="named-routes"></a>
## Named Routes

Named routes make referring to routes when generating redirects or URLs more convenient. You may specify a name for a route like so:

	Route::get('user/profile', array('as' => 'profile', function()
	{
		//
	}));

You may also specify route names for controller actions:

	Route::get('user/profile', array('as' => 'profile', 'uses' => 'UserController@showProfile'));

Now, you may use the route's name when generating URLs or redirects:

	$url = URL::route('profile');

	$redirect = Redirect::route('profile');

You may access the name of a route that is running via the `currentRouteName` method:

	$name = Route::currentRouteName();

<a name="route-groups"></a>
## Route Groups

Sometimes you may need to apply filters to a group of routes. Instead of specifying the filter on each route, you may use a route group:

	Route::group(array('before' => 'auth'), function()
	{
		Route::get('/', function()
		{
			// Has Auth Filter
		});

		Route::get('user/profile', function()
		{
			// Has Auth Filter
		});
	});

You may also use the `namespace` parameter within your `group` array to specify all controllers within that group as being in a given namespace:

	Route::group(array('namespace' => 'Admin'), function()
	{
		//
	});

<a name="sub-domain-routing"></a>
## Sub-Domain Routing

Laravel routes are also able to handle wildcard sub-domains, and pass you wildcard parameters from the domain:

#### Registering Sub-Domain Routes

	Route::group(array('domain' => '{account}.myapp.com'), function()
	{

		Route::get('user/{id}', function($account, $id)
		{
			//
		});

	});

<a name="route-prefixing"></a>
## Route Prefixing

A group of routes may be prefixed by using the `prefix` option in the attributes array of a group:

	Route::group(array('prefix' => 'admin'), function()
	{

		Route::get('user', function()
		{
			//
		});

	});
