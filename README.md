PHP Router
==========

This is a simple PHP Router with a syntax comparable to Laravel, that allows you to define routes in an intuitive way. It supports auto-loading of controller classes from the base directory or a 'controllers' directory.

*Since this router is based on the Laravel router syntax, some code is reused.*

**Note**: Routes will run in the order they are defined. Higher routes will always take precedence over lower ones.

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

Controller classes can be auto-loaded as long as they are located in the base directory or a 'controllers' directory.

Registering a route that points to a controller action:

	Route::get('welcome', 'home@index');