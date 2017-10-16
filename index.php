<?php
// framework instantiation
$app=require('lib/alit.php');
// load config file
$app->config('config.ini');
// register a GET route to base url
$app->route('GET /',function() use($app) {
	// render the view
    Knife::instance()->render($app->get('myvar.view'),$app->get('myvar'));
});

// run the app
$app->run();
