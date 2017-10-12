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
    // Knife::instance()->render($app->get('data.view'),$app->get('data'));
    $eval=Validation::instance();
    $data=['email'=>'johndoe@gmail.com'];
    // $eval->isvalid([$data],['required|valid_email']); // Return: true
    $res=$eval->isvalid($data,['required|min_len,100']);
    print_r($res);
    // Return:
    // Array (
    //    [0] => The 0 field needs to be at least 100 characters
    // )
});

$app->run();
