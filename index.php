<?php
$app=require('lib/alit.php');

$app->set('UI','ui/');
$app->set('data',[
    'app'     => 'Alit PHP',
    'version' => '1.0.0-stable',
    'tagline' => 'Lightweight, blazing fast micro framework',
    'view'    => 'home',
    'link'    => 'https://github.com/esyede/alitphp'
]);

$app->route('GET /',function() use($app) {
    Knife::instance()->render($app->get('data.view'),$app->get('data'));
});

$app->run();
