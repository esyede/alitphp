# alitphp
Simple, lightweight php microframework
[https://github.com/esyede/alitphp/wiki](official wiki)

### What you get?
* Simple routing engine with middleware support
* Simple native template
* INI-style configuration
* Dot-notation array access


### Requirements
 * PHP 5.3+ (untested on php7)
 * Webserver (you can use built-in webserver on php5.4+)
 * `mode_rewrite` if you use apache
 * _PCRE_ 8.02+ (usually already bundled with php)
 * Writable access to `TMP` directory.



### Webserver Configuration:
Apache
```apache
Options +FollowSymlinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

NginX
```nginx
location / {
    try_files $uri index.php;
}
```


### Routing Engine
Alit routing engine can be used either procedural or object orirnted way

#### Procedural routing
```php
$fw=require('fw/alit.php');
$fw->route('GET /',function() {
    echo 'Hello world!';
});

$fw->run();
```

Regex pattern is also supported:
```php
$fw->route('GET /hello(/\w+)?',function($word) {
    echo 'Hello '.(isset($word)?$word:'dude');
});
```

Multiple methods is supported:
```php
$fw->route('GET|POST|PUT /',function() use($fw) {
    echo 'Using '.$fw->get('METHOD').' on '.$fw->get('URI');
});
```
Supported methods:
`CONNECT` `DELETE` `GET` `HEAD` `OPTIONS` `PATCH` `POST` `PUT`

Do you need middleware?
```php
$fw->route('GET /admin',function() {
    echo 'Actual route';
});

$fw->before('GET /admin',function() {
    echo 'this is before-middleware...<br/>';
});

$fw->after('GET /admin',function() {
    echo '<br/>...this is after-middleware';
});
```


#### Dealing with OOP
Firstly, you must create the controller class:
```php
// file: user.php
class User {

    function home() {
        echo 'User home';
    }

    function profile($name) {
        echo 'Profile of: '.(isset($name)?$name:'unknown');
    }
}
```

Then, register it to your route:
```php
$fw->route('GET /user','User@home');
$fw->route('GET /user/profile(/[a-zA-Z]+)?','User@profile');
```

#### Routing to namespaced class?
```php
// File: application/controllers/test.php
namespace App\Controllers;
use \Alit;

class Test {
    protected $fw;

    function __construct() {
        $this->fw=Alit::instance();
    }

    function index() {
        echo $this->fw->get('METHOD').' method used here';
    }
    // ...
}
```

And finally, you can register it to your route:
```php
$fw->route('GET /test','App\Controllers\Test@index');
```

You can also specify routes in a config file:
```ini
[route]
GET /                 = Welcome@home
GET /profile(/\w+)?   = Welcome@profile
GET|POST|PUT /test    = App\Controllers\Test@index
```

And your _index.php_ will be more simpler:
```php
$fw=require('fw/alit.php');
$fw->config('app.cfg')->run();
```

Do you still need the middleware?
```php
class Test {

    function index() {
        echo 'Actual route';
    }

    //! You can define middleware as a method name inside your controller classes
    function before() {
        echo 'this is before-middleware...<br/>';
    }

    function after() {
        echo '<br/>...this is before-middleware';
    }
}
```


### Config Flags
Alit provide some configuration flags such as:
 * `global` to define global hive assignment
 * `route` for automatic route definition
 * `config`  to includ other config files inside your current config

You can also define your own flags.
```ini
[global]
VIEW = views/

[route]
GET /     = Welcome@home
GET /test = App\Controllers\Test@index

[config]
db.cfg   = TRUE
user.cfg = TRUE

; Example of defining custom flag
[books]
price = 1000
discount = 0.2
store.name = Happy Bookstore
store.address.street = Walikukun, Ngawi
store.dummy.text     = This is an example \
                        how to truncate long text \
                        on your config file.
```


### Playing with hive
Hive (like a _bee hive_) is a variable that holds an array of whole system configuration.
Alit provide simple methods to play with it. Let's take a look at some of them:

Set a value to hive:
```php
$fw->set('profile',array(
    'uname'=>'paijo77',
    'surname'=> 'Paijo',
    'interest'=>array('reading','football'),
    'family'=>array('wife'=>'Painem')
));
$fw->set('profile.family.son','Jarwo');
```

Multiple set:
```php
$fw->set(array(
    'entry' =>array(
        'title'=>'Indonesia Raya',
        'posted'=>'14/10/2017',
        'by'=>'paijo77',
        'category'=>'Art',
    ),
    'categories'=>array('General','Art'),
    'settings.base.url'=>'http://myblog.com'
));
```
_Tip: You can also assign the hive value from config file_

Get a value from hive:
```php
$fw->get('profile')['uname'];    // paijo77
$fw->get('profile.surname');     // Paijo
$fw->get('profile.interest.1');  // football
$fw->get('profile.family.son');  // Jarwo
$fw->hive()['entry']['title'];   // Indonesia Raya
```

Add a value or array of value:
```php
$fw->add('profile.nationality','Indonesia');
$fw->add(array(
    'profile.city'=>'Ngawi',
    'profile.favorite.food'=>'Nasi Goreng'
));
```

Check if key exists in hive:
```php
$fw->has('profile.family.wife'); // TRUE
```

Erase a hive path or array of hive paths:
```php
$fw->erase('entry.by');
// $fw->get('entry.by'); // NULL
$fw->erase(array('profile.city','profile.favorite.food'));
```


#### Framework Variables
Since all framework variables are stored in `Alit::hive` property,
You can get, set, add or dump it to see the contents:
```php
// See all hive vars
var_dump($fw->hive());
// Or, see the ntire object
var_dump($fw);
```


### Loading Thirdparty Libraries
Since it's a thirdparty libraries, you need to set the 
containing-path of your library to the `AUTOLOAD` directive in order to 
help alit find the library. Let's say you put your 3rd-party libraries
in the `[root]/vendor` directory, so it will become:
```php
$fw->set('AUTOLOAD','vendor/');
```

And the folder struture of the libraries must match 
with namespace inside your class. For example if your namespace 
is `Foo\Bar` so your class file must be placed in:
`vendor/Foo/Bar/YourClass.php`

It's also possible to set multiple 3rd-party directory 
using `|`, `,` or `;` as separator:
```php 
$fw->set('AUTOLOAD','vendor/|foo_vendor/;bar_vendor/,baz_vendor/');
```


### Debugging
Alit provide a `SYSDEBUG` directive that you can adjust to see more detailed error info:
```php
$fw->set('DEBUG',3);
```

Possible value for debug is:
 * 0 : Suppresses prints of the stack trace _(default)_
 * 1 : Prints files & lines
 * 2 : Prints classes & functions as well
 * 3 : Prints detailed infos of the objects as well


### System Log
You can enable system log by setting the `LOG` hive to `TRUE`
and alit will log your system errors to  `alit.log` file inside your `TMP` directory.
```php
$fw->set('LOG',TRUE);
```

You can also make your custom log:
```php
$fw->log('[info] paijo is logged in!','info.log');
```


### Documentation
Full documentation available on [https://github.com/esyede/alitphp/wiki](official wiki)


### Contribute
Please fork or pull request if you find this useful.
