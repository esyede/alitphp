# Alit PHP
Lightweight closure-based PHP micro framework


### Requirements
 * PHP 5.5+
 * Apache webserver (optional, you can use php built-in webserver)
 * mode_rewrite if using apache webserver
 * PCRE 8.02+ (usually already bundled with php)
 * Writable access for `tmp/` dir, for temporary files


### Features
 * Clean routing engine with route-mounting support
 * Tiny blade-like templating engine
 * Tiny DB-driven session library
 * Tiny ORM library
 * Tiny input validation library


### Documentation
Work in progress..


### Code sample
```php
$app=require ('lib/alit.php');
$app->store('UI','ui/');

$app->get('/',function() use($app) {
    $app->store('data',[
        'fw'=>'Alit PHP',
        'tagline'=>'Lightweight closure-based PHP micro framework',
        'link'=>'http://alitphp.github.io',
        'ui'=>'home'
    ]);
    Knife::instance()->render($app->grab('data.ui'),$app->grab('data'));
});

$app->run();
```
