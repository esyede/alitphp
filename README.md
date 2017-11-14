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


### Webserver Configuration:
Apache
```apache
RewriteEngine On
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l

RewriteRule ^(.*)$ index.php [QSA,L]
```
NginX
```nginx
location / {
    try_files $uri index.php;
}
```
IIS
```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Main Rule" stopProcessing="true">
                    <match url=".*" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
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
    echo "You're using {$app->get('METHOD')} method !";
});
```
Supported methods: `CONNECT` `DELETE` `GET` `HEAD` `OPTIONS` `PATCH` `POST` `PUT`

Regex pattern is also supported:

```php
$app->route('GET /test(/\w+)?',function($param1) use($app) {
    echo 'Hello from '.(!isset($param1)
            ? '/test'
            : '/test/'.$param1).' !';
});
```

Do you need middleware? Yes, it's yours!

```php
$app->route('GET /admin',function() use($app) {
    echo 'Actual route';
});


$app->before('GET /admin',function() use($app) {
    echo 'before...<br/>';
});

$app->after('GET /admin',function() use($app) {
    echo '<br/>...after';
});
```


#### Dealing with OOP

Firstly, you must create the handler class:

```php
// file: user.php

class User {

    function home() {
        echo 'User home';
    }

    function profile($name) {
        echo 'Profile of: '.(isset($name)
            ? $name
            : 'unknown');
    }
}
```

Then, register it to your route:

```php
$app->route('GET /user','User@home');
$app->route('GET /user/profile(/\w+)?','User@profile');
```
#### Wait, what about routing to namespaced class? Yes, you can!
```php
// file: app/controllers/test.php
namespace App\Controllers;

class Test {
    protected $app;

    function __construct() {
        // get the framework instance
        $this->app=\Alit::instance();
        // we add `\` (backslash) on core-class instantiation
        // because on alit, 'lib/' dir is the base namespace
    }

    function index() {
        echo "Hello from Test class! you're using {$this->app->get('METHOD')} method";
    }
    // ...
}
```
Then you **must** append class directory to `MODULES` directive in order to help autoloader find your classes
```php
$app->set('MODULES','app/controllers/'); // note: you must add trailing slash to the end of it
```

And finally, you can register it to your route:

```php
$app->route('GET /test','App\Controllers\Test@index');
```


Or even further, you can specify routes in a config file, like this:

```ini
; file: config.ini
; [route] is a flag for automatic routing definition

[route]
GET /                 = Welcome@home
GET /profile(/\w+)    = Welcome@profile
GET|POST|PUT /test    = App\Controllers\Test@index
```

And your _index.php_ will be even simpler:

```php
$app=require('lib/alit.php');
$app->config('config.ini');
$app->run();
```

You need the middleware? This is it!

```php
class Test {

    function index() {
        echo "Hello world !<br/>";
    }


    // Yes, you can define before and after-middleware as a method inside controller class
    function before() {
        echo "Before route here!<br/>";
    }

    function after() {
        echo "<br/>After route here!";
    }
}
```


### Config Flags
Alit provide some configuration flags such as:
```ini
; for global hive assignment
[global]
UI = ui/

; for automatic route definition
[route]
GET /     = Welcome@home
GET /test = App\Controllers\Test@index

; for including other config file
[config]
database.ini = true
user.ini = true

; example of custom flags
[books]
price = 1000
discount = 0.2
store.name = Happy Bookstore
store.address.street = Walikukun, Ngawi
store.address.postal = 63256
```
And much more!


### Playing with Hive
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
    'settings.base.url'=>'http://myblog.com'
]);
```
_Tip: You can also setting hive value from config file like we did above_


Get a value:
```php
$app->get('profile')['uname'];    // johndoe
$app->get('profile.surname');     // John Doe
$app->get('profile.interest.1');  // football
$app->get('profile.family.son');  // John Roe
$app->hive()['entry']['title'];   // Lorem ipsum
```

Add a value or array of value:
```php
$app->add('profile.nationality','Indonesia');
$app->add([
    'profile.city'=>'Ngawi',
    'profile.favorite.food'=>'Nasi goreng'
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
$app->erase(['profile.city','profile.favorite.food']);
```
..and much more!


#### Framework Variables
All framework variables are stored in `$hive` property, So, to see available vars:
```php
var_dump($app->hive());
// or
var_dump($app);
```


### String Manipulation Library
You can use string manipulation library after instantiate the class:

```php
$str=String::instance();
```

Then you will be able to use all available methods, for example:
```php
$text='world';
$str->from($text)
    ->prepend('Hello ')
    ->wrap('#','??')
    ->append(' this is ')
    ->append('alit!')
    ->get(); // Return: '#Hello world?? this is alit!'
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

Default error message is in english. To set the error message you can use the `setlang()` method before calling the `isvalid()`.
```php
// ...some validation logic...
$data=['email'=>'johndoe@gmail.com'];
$lang=[
    'required'=>'Kolom {field} wajib diisi!',
    'min_len'=>'Kolom {field} setidaknya harus berisi {param} karakter'
];
$eval->setlang($lang);
$eval->isvalid($data,['email'=>'required|min_len,100']);
```
_Tip: you can see all available error languages by using `$eval->languages()` function._



### DB\SQL Library
You can use DB\SQL library by passing config array on class instantiation:

```php
$config=[
    'username'=>'johndoe',
    'password'=>'s3cr3t',
    'database'=>'my_database',
    'host'=>'localhost',            // optional, default to 'localhost'
    'driver'=>'mysql',              // optional, default to 'mysql'
    'port'=>3306,                   // optional, default to 'null'
    'prefix'=>'',                   // optional, default to 'null'
    'charset'=>'utf8',              // optional, default to 'utf8'
    'collation'=>'utf8_general_ci', // optional, default to 'utf8_general_ci'
    'cachedir'=>null,               // optional, default to temp dir.
];
$db=new DB\SQL($config);
```

Now you can use all DB\SQL methods, for example:

```php
// Get multiple results
$db->table('profile')
   ->select('id,name,address')
   ->where('age','>',15)
   ->many();

// Get single result
$db->table('profile')
  ->select('id,name,address')
  ->where('age','>',15)
  ->one();
```



### Session Library
To use the session library, you need to pass database connection object to session constructor.
Let's assume we have database connection object saved in `$db` variable, like we did above, so
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
$sess->get('role');    // Result: administrator
$sess->erase('role');  // erase/unset the session
$sess->destroy();      // destroy session and remove from db
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
`UI` is setted to `ui/` by default, so Knife will look for `ui/mytemplate.knife.php` inside your alit installation directory.

You can change this with `$app->set('UI','path/to/dir/');`
before calling the `render()` function.


And the last step is creating the `ui/mytemplate.knife.php` file:

```php
// file: ui/mytemplate.knife.php
@include('the-header')
    <body>
        Hello {{ $name }}, how are you today?
    </body>
@include('the-footer')
```


#### Benchmark Library
And again you have to instantiate benchmark class to before using this library:
```php
$bench=Bench::instance();
```
And then use the benchmark library as follow:
```php
$bench->start('my-app');

// some code to benchmark here

$bench->elapsed('my-app'); // Return: 0.012
$bench->memory(); // Return: 768 KB

$bench->stop('my-app'); // stop the benchmark
```


#### Loading 3rd-party Library
Since alit treats external class as modules (including your controller classes),
you can load external modules by appending the containing-path of your library to the `MODULES` directive, for example:
```php
$app->set('MODULES','app/controllers/|thirdparty/')
```
Note that you must add `|` (pipe, or you can also use `,` or `;`) for each of supplied folder.


#### Unit-testing Tool
You can use Unit-testing library by instantiate the class, like this:
```php
$test=new Test($level);
```

The `$level` takes the following values: `RL_FALSE`, `RL_TRUE`, `RL_BOTH`, which means that the testing stack only returns results for expections that resolved to TRUE, FALSE or BOTH (default).


Then you can do unit testing like:

```php
function hello() {
    return "hello!";
}

$hello=hello();
$test->expect(!empty($hello),'Something was returned');

// This test should succeed
$test->expect(is_string($hello),'Return value is a string');

// This test is bound to fail
$test->expect(is_array($hello),'Return value is array');

// Display the results
foreach ($test->results() as $res) {
    echo $res['message'].'<br>';
    echo $res['status']
        ?"Test passed"
        :"Fail ({$res['source']})";
    echo '<br>';
}
```


### Debugging
Alit provide a `DEBUG` directive that you can adjust to see more detailed error info:
```php
$app->set('DEBUG',3);
```
Possible value for debug is:

 * 0 : suppresses prints of the stack trace (default)
 * 1 : prints files & lines
 * 2 : prints classes & functions as well
 * 3 : prints detailed infos of the objects as well


### System Log
You can enable system log by setting the `SYSLOG` hive to `true`
and alit will log your system errors to  `syslog.log` file inside your `TEMP` directory
```php
$app->set('SYSLOG',true);
```
Need a custom logger? do this:
```php
$app->log('[info] 3 users currently logged in','test.log');
```


### Documentation
Full documentation is still work in progress..


### Contribute
Please fork or pull request if you find this useful.
