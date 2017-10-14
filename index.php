<?php
$app=require('lib/alit.php');
$app->config('config.ini');

$app->route('GET /',function() use($app) {
    Knife::instance()->render($app->get('myvar.view'),$app->get('myvar'));
});

$app->run();
