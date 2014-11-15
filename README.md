PHP Plugin API
==============

-----

About
-----

This project provides a Plugin API for managing actions and filters, hooking functions and consequently calling them. 

It is based on the amazing plugin API system which is built into the award-winning WordPress platform.

The PHP Plugin API allows for creating actions and filters and hooking functions, class and object methods, as well as any other PHP callback. The functions or methods will then be run when the action or filter is called.

Since this library replicates the WordPress Plugin API, additional information about the plugin hooks (filters, actions) and their usage can be found here: [WordPress Plugin API](http://codex.wordpress.org/Plugin_API)

-----

Usage & Examples
-----

#### 1. Using filters

	// Define a variable.
	$title = 'Hello';

	// Add a filter function to the "custom_filter" filter.
	// This will take the current variable as a parameter and
	// will append ", World" to it, returning the changed value.
    Plugin_API::add_filter('custom_filter', function( $var ) {
    	return $var . ', World';
    });

    // Call the functions, hooked to the "custom_filter" filter
    // on the $title variable.
    $title = Plugin_API::apply_filters('custom_filter', $title);

    // The $title variable will now contain "Hello, World".
    echo $title;