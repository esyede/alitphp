<?php
// Instantiate the framework
$app=require('lib/alit.php');
// Load config file
$app->config('config.ini')
	// Define route to main page
	->route('GET /',
		// Define route handler
		function() use($app) {
			// Render the view
    		Knife::instance()->render(
    			// Locate view file
    			$app->get('myApp.view'),
    			// Pass config data to view
    			$app->get('myApp')
    		);
		}
	)
	// Ready. Broom! broom!!
	->run();
