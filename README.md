PHP Router
==========

This is a simple PHP Router with a syntax comparable to Laravel, that allows you to define routes in an intuitive way. It supports auto-loading of controller classes from the controllers directory. Anonymous functions from PHP 5.3 can be used as callbacks for matching routes, make sure to check if your hosting supports PHP 5.3 or higher.

*Since this router is based on the Laravel router syntax, some code is reused and this documentation is the same as on the Laravel website (http://laravel.com/docs/routing).*

**Note**: Routes will run in the order they are defined. Higher routes will always take precedence over lower ones.

Installation
------------

Install using composer or include Router.php. For cleaner urls use the following .htaccess file:

	<IfModule mod_rewrite.c>
		Options +FollowSymLinks
		RewriteEngine On

		RewriteCond %{REQUEST_FILENAME} !-f
		RewriteCond %{REQUEST_FILENAME} !-d
		RewriteRule ^(.*)$ index.php/$1 [L]
	</IfModule>

Example
-------

Registering a route that responds to "GET /":

	Route::get('/', function()
	{
		return "Hello World!";
	});
	
Registering a route that is valid for any HTTP verb (GET, POST, PUT, and DELETE):

	Route::any('/', function()
	{
		return "Hello World!";
	});

Registering routes for other request methods:

	Route::post('user', function()
	{
		//
	});

	Route::put('user/(:num)', function($id)
	{
		//
	});

	Route::delete('user/(:num)', function($id)
	{
		//
	});

Registering a single URI for multiple HTTP verbs:

	Route::register(array('GET', 'POST'), $uri, function()
	{
		//
	});

Registering a route that is only valid for HTTPS requests:

	Route::secure('GET', '/', function()
	{
		//
	});

Wildcards
---------

Forcing a URI segment to be any digit:

	Route::get('user/(:num)', function($id)
	{
		//
	});
	
Allowing a URI segment to be any alpha-numeric string:

	Route::get('post/(:any)', function($title)
	{
		//
	});
	
Catching the remaining URI without limitations:

	Route::get('files/(:all)', function($path)
	{
		//
	});
	
Allowing a URI segment to be optional:

	Route::get('page/(:any?)', function($page = 'index')
	{
		//
	});

Controller Routing
------------------

Controllers provide another way to manage your application logic. It is important to be aware that all routes must be explicitly defined, including routes to controllers. This means that controller methods that have not been exposed through route registration cannot be accessed. It's possible to automatically expose all methods within a controller using controller route registration. 

Registering the "home" controller with the Router:

	Route::controller('home');
	
Registering several controllers with the router:

	Route::controller(array('dashboard.panel', 'admin'));
	
This convention is similar to that employed by CodeIgniter and other popular frameworks, where the first segment is the controller name, the second is the method, and the remaining segments are passed to the method as arguments. If no method segment is present, the "index" method will be used.

This routing convention may not be desirable for every situation, so you may also explicitly route URIs to controller actions using a simple, intuitive syntax.

Registering a route that points to a controller action:

	Route::get('welcome', 'home@index');

Extras
------

Get your site base URL:

	Router::base();

Get the current URI:

	Router::uri();
