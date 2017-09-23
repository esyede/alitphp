<?php
$app=require ('lib/alit.php');
$app->store('UI','ui/');

$app->get('/',function() use($app) {
    $app->store('data',[
        'fw'=>'Alit PHP',
        'tagline'=>'A PHP micro framework that doesn\'t sucks!',
        'link'=>'http://alitphp.github.io',
        'ui'=>'home'
    ]);
    Knife::instance()->render($app->grab('data.ui'),$app->grab('data'));
});

$app->run();
