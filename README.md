# Alit PHP
Lightweight, blazing fast micro framework


### Features
* Clean routing engine
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


### Apache Configuration:
```apache
# Enable rewrite engine
RewriteEngine On

# Some server needs to specify base directory for rewriting
# RewriteBase /

# Continue if a match is not an existing file, directory or symlink
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l

# Redirect all request to index.php,
# append any query string from original url,
# and then stop processing this
RewriteRule ^(.*)$ index.php [QSA,L]

# Give 404 error when accessing /tmp dir or
# .cache, .ini, or .log files
RewriteRule ^(tmp)\/|\.(cache|ini|log)$ - [R=404]
# Disable directory listing of php files
IndexIgnore *.php
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

    function profile($name) {
        echo "Welcome home dude !";
    }
}
```

Then, register it to your route:

```php
$app->route('GET /','Welcome@home');
$app->route('GET /profile(/\w+)','Welcome@profile');
```

Or even further, you can specify routes in a config file, like this:

```txt
; file: config.ini
; [route] is a flag for automatic routing definition

[route]
GET /                 = Welcome@home
GET /profile(/\w+)    = Welcome@user
GET|POST|PUT /hello   = Welcome@hello
```

And your _index.php_ will be even simpler:

```php
$app=require('lib/alit.php');
$app->config('config.ini');
$app->run();
```
Wait, 3 lines? Woohoo !!


### Playing With Hive
Hive is a variable that holds an array of whole system variables.
Alit provide some method to play around with it. Let's take a look some of them:


Set a value
```php
$app->set('profile',[
    'uname'=>'johndoe',
    'surname'=> 'John Doe',
    'interest'=>['reading','football'],
    'family'=>[
        'wife'=>'Jane Doe'
    ]
]);
$app->set('profile.family.son','John Roe');
```

Multiple set:
```php
$app->mset([
    'entry' =>[
        'title'=>'Lorem ipsum',
        'posted'=>'14/10/2017',
        'by'=>'johndoe',
        'category'=>'Art',
    ],
    'categories'=>['General','Art'],
]);
```
_Tip: You can also setting hive value from config file_


Get a value:
```php
$app->get('profile')['uname']; // johndoe
$app->get('profile.surname'); // John Doe
$app->get('profile.interest.1'); // football
$app->get('profile.family.son'); // John Roe
$app->hive['entry']['title']; // Lorem ipsum
```

Add a value or array of value:
```php
$app->add('profile.nationality','Indonesia');
$app->add([
    'profile.city'=>'Ngawi',
    'profile.food'=>'Nasi goreng'
]);
```

Check if hive path exists:
```php
$app->has('profile.family.wife'); // true
```

Erase a hive path or array of hive paths:
```php
$app->erase('entry.by');
// $app->get('entry.by'); // null
$app->erase(['profile.age','profile.uname']);
```
..and much more.


#### Framework Variables
All framework variables are stored on `$hive` property, So, maybe useful to see hive values:
```php
print_r($app->hive);
// or
print_r($app->hive());
// or
print_r($app);
```


### Validation Library
You can use validation library after instantiate the class:

```php
$eval=Validation::instance();
```

Then you will be able to use all available methods, for example:

```php
$data=['email'=>'johndoe@gmail.com'];
$eval->isvalid($data,['email'=>'required|valid_email']); // Return: true
$eval->isvalid($data,['email'=>'required|min_len,100']);
// Return:
// Array (
//    [0] => The Email field needs to be at least 100 characters
// )
```

Default error message is in english. To set error message you can use the `setlang()` method before calling the `isvalid()`.




### DB\SQL Library
You can use DB\SQL library by passing config array on class instantiation:

```php
$config=[
    'driver'=>'mysql',
    'host'=>'localhost',
    'username'=>'johndoe',
    'password'=>'s3cr3t',
    'database'=>'my_database',
    'port'=>3306,                  // optional
    'prefix'=>'',                  // optional
    'charset'=>'utf8',             // optional
    'collation'=>'utf8_general_ci' // optional
];
$db=new DB\SQL($config);
```

Now you can use all DB\SQL methods, for example:

```php
$db->table('profile')
   ->select('id,name,address')
   ->where('age','>',15)
   ->many();
```



### Session Library
To use the session library, you need to pass database connection object to session constructor.
Let's assume we have database connection object saved on `$db` variable, like we did above, so
the session instantiation will looks like:

```php
$sess=new Session($db,'mysession','mycookie');
```
It will automatically create db table named `mysession` on your database and will set the cookie


Then you can start using Session library like:

```php
// store session data to database
$sess->set('role','administrator');
// grab session data from database
$sess->get('role'); // Result: administrator
$sess->erase('role'); // erase/unset the session
$sess->destroy(); // destroy session and remove from db
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


#### Unit-testing Tool
You can use Unit-testing library by instantiate the class, like this:
```php
$test=new Test($level);
```

The `$level` takes the following values: `RL_FALSE`, `RL_TRUE`, `RL_BOTH`, which means that the testing stack only returns results for expections that resolved to TRUE, FALSE or BOTH (default).


Then you can do unit testing like:

```php
// function hello() {
//     return "hello!";
// }

$hello=hello();
$test->expect(!empty($hello),'Something was returned');

// This test should succeed
$test->expect(is_string($hello),'Return value is a string');

// This test is bound to fail
$test->expect(is_array($hello),'Return value is array');

// Display the results
foreach ($test->results() as $res) {
    echo $res['text'].'<br>';
    echo $res['status']
        ?"Test passed"
        :"Fail ({$res['source']})";
    echo '<br>';
}
```


### Documentation
Full documentation is still work in progress..


### To-do List
* Bug fixing
* Add regex alias to route pattern
* Add route-caching
* Add CLI tool, ~~unit testing~~ (done!), ~~logger~~ (done!), etc.




### Contribute
Please fork or pull request if you find this useful.
