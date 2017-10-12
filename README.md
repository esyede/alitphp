# Alit PHP
Lightweight, blazing fast micro framework


### Features
* Clean routing engine with route-mounting support
* Tiny laravel blade-like templating engine
* INI style configuration
* Dot-notation array ready
* Tiny DB-driven session library
* Tiny PDO library
* Tiny input validation library.


### Requirements
 * PHP 5.6+ (Untested on PHP7)
 * Apache webserver (optional, you can use php built-in webserver)
 * `mode_rewrite` if using apache webserver
 * _PCRE_ 8.02+ (usually already bundled with php)
 * Writable access to `tmp/` dir, for temporary files.


### Apache Configuration
```php
RewriteEngine On
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l

# Alit rewrite rule
RewriteRule ^(.*)$ index.php [QSA,L]

# Prohibit direct access to system files
RewriteRule ^(tmp)\/|\.(cache|ini|log)$ - [R=404]
```

### Routing Engine
Alit routing engine can be used either procedural or Object Orirnted way

#### Procedural routing
```php
$app=require('lib/alit.php');

$app->route('GET /',function() use($app) {
    echo "Hello world !";
});

$app->run();
```

Multiple methods is supported:

```php
$app->route('GET|POST|PUT /',function() use($app) {
    echo "You're using {$app->method()} method !";
});
```
Supported methods: `CONNECT` `DELETE` `GET` `HEAD` `OPTIONS` `PATCH` `POST` `PUT`

Regex pattern is also supported:

```php
$app->route('GET /test(/\w+)?',function($param1) use($app) {
    !isset($param1)
        ? $text="Hello from /test !"
        : $text="Hello from /test/{$param1} !";
    echo $text;
});
```

#### Dealing with OOP

Firstly, you must create the handler class:

```php
// file: welcome.php

class Welcome {

    function home() {
        echo "Welcome home dude !";
    }
}
```

Then, register it to your route:

```php
$app->route('GET /','Welcome@home');
```

Or even further, you can specify routes in a config file, like this:

```ini
; file: config.ini
; NOTE: [route] is a flag for automatic routing definition
[route]
GET /                 = Welcome@home
GET|POST|PUT  /hello  = Welcome@hello
GET /user(/\w+)       = Welcome@test
```

And your _index.php_ will be even simpler:

```php
$app=require('lib/alit.php');
$app->config('config.ini');
$app->run();
```
Wait, 3 lines? Woohoo !!


### Validation Library
You can use validation library after instantiate the class:

```php
$eval=Validation::instance();
```

Then you now you will be able to use all available methods, for example:

```php
$data=['email'=>'johndoe@gmail.com'];
$eval->isvalid([$data],['required|valid_email']); // Return: true
$eval->isvalid($data,['email'=>'required|min_len,100']);
// Return:
// Array (
//    [0] => The Email field needs to be at least 100 characters
// )
```

Default error message is in english. To set error message you can use the `seterrors()` method before calling the `Alit::isvalid()` method.




### DB\SQL Library
You can use DB\SQL library by passing config array on class instantiation:

```php
$config=[
    'driver'=>'mysql',
    'host'=>'localhost',
    'username'=>'johndoe',
    'port'=>3306,
    'database'=>'my_database',
    'prefix'=>'',
    'charset'=>'utf8',
    'collation'=>'utf8_general_ci'
];
$db=new DB\SQL($config);
```

Now you can use all DB\SQL methods, for example:

```php
$db->table('users')
   ->select('id,name,address')
   ->where('age','>',15)
   ->many();
```



### Session Library
To use the session library, you need to pass database connection object to session constructor.
Let's assume we have database connection object saved on `$db` variable, like we already did on above example, so
the session instantiation will looks like:

```php
$sess=new Session($db);
```

Then you can use all of Session library methods like:

```php
$sess->set('role','administrator');
$sess->get('role'); // Result: administrator
```



### Knife (Template Library)
As usual, do a instantiation first:

```php
$tpl=Knife::instance();
```

Then you are ready to render the templates:

```php
$data=['name'=>'John Doe'];
$tpl->render('mytemplate',$data);
```

Based on above code, knife will look for `mytemplate.knife.php` file in `UI` directory.
`UI` is setted to `ui/` by default, so Knife will look for `ui/mytemplate.knife.php` on your alit installation directory.

You can change this with `$app->set('UI','path/to/dir/');`
before calling the `render()` function.


And the last step is creating the `ui/mytemplate.knife.php` file:

```php
// file: ui/mytemplate.knife.php
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>My Template</title>
    </head>
    <body>
        Hello {{ $name }}, how are you today?
    </body>
</html>
```




### Documentation
Full documentation is work in progress..




### To-do List
* Bug fixing
* Add regex alias to route pattern
* Add route-caching
* Add CLI tool, unit testing, logger, etc.




### Contribute
Please fork or pull request if you find this useful.
