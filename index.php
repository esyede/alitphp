<?php
// Framework instantiation
$app=require('lib/alit.php');
// Load config file
$app->config('config.ini');
// Register a GET route to base url
$app->route('GET /',function() use($app) {
    // Render the view
    Knife::instance()->render($app->get('myvar.view'),$app->get('myvar'));
});

// Run the app
$app->run();
