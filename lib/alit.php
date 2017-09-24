<?php
/**
*   Base Class of Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Alit - base structure
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
final class Alit extends \Factory implements \ArrayAccess {

	const
		PACKAGE='Alit PHP',
		VERSION='1.0.0-devel';
	// store all framework arrays
	public $hive;

	/**
	*	Store a before middleware route and a handling function to be -
	*	executed when accessed using one of the specified methods
	*	@param  $methods  string
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
	function before($methods,$pattern,$fn) {
        $pattern=$this->hive['FW']['base_route'].'/'.trim($pattern,'/');
        $pattern=$this->hive['FW']['base_route']?rtrim($pattern,'/'):$pattern;
        foreach (explode('|',$methods) as $method)
            $this->hive['FW']['before'][$method][]=['pattern'=>$pattern,'func'=>$fn];
    }

	/**
	*	Store a route and a handling function to be executed -
	*	when accessed using one of the specified methods
	*	@param  $methods  string
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function route($methods,$pattern,$fn) {
        $pattern=$this->hive['FW']['base_route'].'/'.trim($pattern,'/');
        $pattern=$this->hive['FW']['base_route']?rtrim($pattern,'/'):$pattern;
        foreach (explode('|',$methods) as $method)
            $this->hive['FW']['after'][$method][]=['pattern'=>$pattern,'func'=>$fn];
    }


	/**
	*	Shorthand for a route accessed using any method
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
	function any($pattern,$fn) {
        $this->route('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using GET
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function get($pattern,$fn) {
        $this->route('GET',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using POST
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function post($pattern,$fn) {
        $this->route('POST',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using PATCH
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function patch($pattern,$fn) {
        $this->route('PATCH',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using DELETE
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function delete($pattern,$fn) {
        $this->route('DELETE',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using PUT
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function put($pattern,$fn) {
        $this->route('PUT',$pattern,$fn);
    }

	/**
	*	Shorthand for a route accessed using OPTIONS
	*	@param  $pattern  string
	*	@param  $fn       object|callable
	*/
    function options($pattern,$fn) {
        $this->route('OPTIONS',$pattern,$fn);
    }

	/**
	*	Group a collection of callbacks onto a base route
	*	@param  $base  string
	*	@param  $fn    callable
	*/
    function group($base,$fn) {
        $curr=$this->hive['FW']['base_route'];
        $this->hive['FW']['base_route'].=$base;
        call_user_func($fn);
        $this->hive['FW']['base_route']=$curr;
    }

	/**
	*	Get all request headers
	*	@return array
	*/
    function headers() {
        if (function_exists('getallheaders'))
            return getallheaders();
        $headers=[];
        foreach ($_SERVER as $name=>$value)
            if ((substr($name,0,5)=='HTTP_')
			||($name=='CONTENT_TYPE')
			||($name=='CONTENT_LENGTH'))
                $headers[str_replace([' ','Http'],['-','HTTP'],
					ucwords(strtolower(str_replace('_',' ',substr($name,5)))))]=$value;
        return $headers;
    }

	/**
	*	Get the request method used, taking overrides into account
	*	@return  string
	*/
    function method() {
		// If it's a HEAD request override it to being GET and -
		// prevent any output, as per HTTP Specification
        // ref: http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        $method=$_SERVER['REQUEST_METHOD'];
        if ($_SERVER['REQUEST_METHOD']=='HEAD') {
            ob_start();
            $method='GET';
        } // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD']=='POST') {
            $headers=$this->headers();
            if (isset($headers['X-HTTP-Method-Override'])
			&&in_array($headers['X-HTTP-Method-Override'],['PUT','DELETE','PATCH']))
                $method=$headers['X-HTTP-Method-Override'];
        }
        return $method;
    }

	/**
	*	Execute the framework: Loop all defined route before middleware's and routes, -
	*	and execute the handling function if a route() was found.
	*	$callback is a function to be executed after a matching route was handled (= after route middleware)
	*	@param   $callback  object|callable
	*	@return  bool
	*/
    function run($callback=null) {
        $this->hive['FW']['method']=$this->method();
        if (isset($this->hive['FW']['before'][$this->hive['FW']['method']]))
            $this->handle($this->hive['FW']['before'][$this->hive['FW']['method']]);
        $handled=0;
        if (isset($this->hive['FW']['after'][$this->hive['FW']['method']]))
            $handled=$this->handle($this->hive['FW']['after'][$this->hive['FW']['method']],true);
        if ($handled===0) {
            if ($this->hive['FW']['notfound']
			&&is_callable($this->hive['FW']['notfound']))
                call_user_func($this->hive['FW']['notfound']);
            else {
				header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
				echo json_encode(['error'=>404,'message'=>'Page not found']);
				exit();
			}
        }
        else if ($callback) $callback();
        if ($_SERVER['REQUEST_METHOD']=='HEAD') ob_end_clean();
        if ($handled===0) return false;
        return true;
    }

	/**
	*	Set the page not found (404) handling function
	*	@param  $fn  object|callable
	*/
    function notfound($fn=null) {
		$this->hive['FW']['notfound']=$fn;

    }

	/**
	*	Handler for common php errors
	*	@param  $code    int
	*	@param  $reason  string
	*	@param  $file    string
	*	@param  $line    string
	*/
	function abort($code,$reason,$file,$line) {
		if ($code) {
			echo '<h1>Alit Error:</h1>
				<p>'.$reason.'<br><code>'.$file.':<font color="red">'.$line.'</font></code></p>';
			die();
		}
	}

	/**
	*	Handler for common php fatal errors
	*	@param  $cwd  string
	*/
	function shutdown($cwd) {
		chdir($cwd);
		if (!($error=error_get_last())
		&&session_status()==PHP_SESSION_ACTIVE)
			session_commit();
		if ($error&&in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR]))
			$this->abort($error['type'],$error['message'],$error['file'],$error['line']);
	}


	/**
	*	Redirect to specified URI
	*	@param  $url   string
	*	@param  $wait  int
	*/
	function redirect($url=null) {
        if($url==null) {
			if (null===$fw->hive['URI'])
				$base=implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/';
			$base=$this->hive['HOST'].$base;
			$url=$base;
		}
        elseif (is_string(filter_var($url,FILTER_VALIDATE_URL))) $url=$url;
        else $url=$base.$url;
        try {
            ob_start();
            header('Location: '.$url,true,302);
            ob_end_flush();
            exit;
        }
        catch (\Exception $ex) {
            throw new \Exception("Can't redirect to specified url: {$url}");
        }
    }

	/**
	*	Handle a set of routes: if a match is found, execute the relating handling function
	*	@param   $routes  array
	*	@param   $quit    boolean
	*	@return  int
	*/
    private function handle($routes,$quit=false) {
        $handled=0;
        foreach ($routes as $route) {
            if (preg_match_all('#^'.$route['pattern'].'$#',$this->hive['URI'],$matches,PREG_OFFSET_CAPTURE)) {
                $matches=array_slice($matches,1);
                $params=array_map(function ($match,$index) use ($matches) {
                    if (isset($matches[$index+1])
					&&isset($matches[$index+1][0])
					&&is_array($matches[$index+1][0]))
                        return trim(substr($match[0][0],0,$matches[$index+1][0][1]-$match[0][1]),'/');
                    else return (isset($match[0][0])?trim($match[0][0],'/'):null);
                },$matches,array_keys($matches));
                if (is_callable($route['func']))
                    call_user_func_array($route['func'],$params);
                elseif (stripos($route['func'],'@')!==false) {
                    list($controller,$method)=explode('@',$route['func']);
                    if (class_exists($controller))
                        if (call_user_func_array([new $controller,$method],$params)===false)
                            if (forward_static_call_array([$controller,$method],$params)===false);
                }
                $handled++;
                if ($quit) break;
            }
        }
        return $handled;
    }

	/**
    *   Render native view
    *   @param  $name  string
    *   @param  $data  null|array
    */
	function render($name,$data=null) {
		$file=$this->grab('BASE').str_replace('./','',$this->grab('UI').$name);
		$file=str_replace('/',DIRECTORY_SEPARATOR,$file);
		if (!file_exists($file))
			throw new \Exception("Can't find view file '{$name}'");
        ob_start();
        if (is_array($data)) extract($data);
        require $file;
        echo trim(ob_get_clean());
    }



	/**
	*	Parse .ini file and keep it's array to hive (or overwrite if key exists )
	*	@param  $file  string
	*/
	function config($file) {
		$file=parse_ini_file(str_replace('/',DIRECTORY_SEPARATOR,$file),true);
        foreach ($file as $key=>$val) {
            $config=&$this->hive;
            $dots=explode('.',$key);
            foreach ($dots as $dot) {
                if (!isset($config[$dot]))
					$config[$dot]=[];
                $config=&$config[$dot];
            }
            $config=$val;
        }
		return $this;
    }

	/**
	*	Read from file (with option to apply Unix LF as standard line ending)
	*	@param   $file   string
	*	@param   $lf     bool
	*	@return  string
	**/
	function read($file,$lf=false) {
		$data=@file_get_contents($file);
		return $lf?preg_replace('/\r\n|\r/',"\n",$data):$data;
	}

	/**
	*	Write to file (or append if $append is true)
	*	@param   $file     string
	*	@param   $data     mixed
	*	@param   $append   bool
	*	@return  int|false
	**/
	function write($file,$data,$append=false) {
		return file_put_contents($file,$data,LOCK_EX|($append?FILE_APPEND:0));
	}

	/**
	*	Get client's IP
	*	@return  string
	*/
	function ip() {
		$ip=null;
		if (isset($_SERVER['HTTP_CLIENT_IP'])
		&&$this->ipvalid($_SERVER['HTTP_CLIENT_IP']))
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
		&&!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipx=explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach ($ipx as $ipv) {
				if ($this->ipvalid($ipv)) {
					$ip=$ipv;
					break;
				}
			}
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED'])
		&&$this->ipvalid($_SERVER['HTTP_X_FORWARDED']))
			$ip=$_SERVER['HTTP_X_FORWARDED'];
		elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])
		&&$this->ipvalid($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
			$ip=$_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])
		&&$this->ipvalid($_SERVER['HTTP_FORWARDED_FOR']))
			$ip=$_SERVER['HTTP_FORWARDED_FOR'];
		elseif (isset($_SERVER['HTTP_FORWARDED'])
		&&$this->ipvalid($_SERVER['HTTP_FORWARDED']))
			$ip=$_SERVER['HTTP_FORWARDED'];
		elseif (isset($_SERVER['HTTP_VIA'])
		&&$this->ipvalid($_SERVER['HTTP_VIAD']))
			$ip=$_SERVER['HTTP_VIA'];
		elseif (isset($_SERVER['REMOTE_ADDR'])
		&&!empty($_SERVER['REMOTE_ADDR']))
			$ip=$_SERVER['REMOTE_ADDR'];
		if ($ip===false) $ip='0.0.0.0';
		if ($ip=='::1') $ip='127.0.0.1';
		return $ip;
	}

	/**
	*	Validate IP
	*	@param   $ip   string
	*	@return  bool
	*/
	function ipvalid($ip) {
		$ip=trim($ip);
		if (!empty($ip)&&ip2long($ip)!=-1) {
			$range=[
				['0.0.0.0','2.255.255.255'],
				['10.0.0.0','10.255.255.255'],
				['127.0.0.0','127.255.255.255'],
				['169.254.0.0','169.254.255.255'],
				['172.16.0.0','172.31.255.255'],
				['192.0.2.0','192.0.2.255'],
				['192.168.0.0','192.168.255.255'],
				['255.255.255.0','255.255.255.255']
			];
			foreach ($range as $r) {
				$min=ip2long($r[0]);
				$max=ip2long($r[1]);
				if ((ip2long($ip)>=$min)
				&&(ip2long($ip)<=$max))
					return false;
			}
			return true;
		}
		return false;
	}

	/**
	*	Class autoloader
	*	@param   $class  string
	*	@return  bool
	*/
	protected function autoloader($class) {
		$class=$this->slash(ltrim($class,'\\'));
		$func=NULL;
		if (is_array($path=$this->hive['AUTOLOAD'])
		&&isset($path[1])
		&&is_callable($path[1]))
			list($path,$func)=$path;
		foreach ($this->split($this->hive['LIB'].';'.$path) as $auto)
			if ($func&&is_file($file=$func($auto.$class).'.php')
			||is_file($file=$auto.$class.'.php')
			||is_file($file=$auto.strtolower($class).'.php')
			||is_file($file=strtolower($auto.$class).'.php'))
				return require($file);
	}

	/**
	*	Class autoloader
	*	@param   $class  string
	*	@return  mixed
	**/
	function slash($str) {
		return $str?strtr($str,'\\','/'):$str;
	}

	/**
	*	Split comma-, semi-colon, or pipe-separated string
	*	@param   $str      string
	*	@param   $noempty  bool
	*	@return  array
	**/
	function split($str,$noempty=true) {
		return array_map('trim',preg_split('/[,;|]/',$str,0,$noempty?PREG_SPLIT_NO_EMPTY:0));
	}


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Hive
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	/**
	*	Store a value to hive or rewrite it if already exist
	*	@param  $key  mixed
	*	@param  $val  mixed
	*/
	function store($key,$val=null) {
        if (is_string($key)) {
            if (is_array($val)&&!empty($val))
                foreach ($val as $k=>$v) $this->store("$key.$k",$v);
            else {
                $keys=explode('.',$key);
                $arr=&$this->hive;
                foreach ($keys as $key) {
                    if (!isset($arr[$key])||!is_array($arr[$key]))
						$arr[$key]=[];
                    $arr=&$arr[$key];
                }
                $arr=$val;
            }
        }
        elseif (is_array($key))
            foreach ($key as $k=>$v) $this->store($k,$v);
    }

	/**
	*	Grab a value from hive or default value if path doesn't exist
	*	@param  $key      string
	*	@param  $default  mixed
	*/
    function grab($key,$default=null) {
        $keys=explode('.',(string)$key);
        $arr=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($arr,$key)) return $default;
            $arr=&$arr[$key];
        }
        return $arr;
    }

	/**
	*	Add a value or array of value to hive
	*	$pop is a helper to pop out the last key if the value is an array
	*	@param  $key  mixed
	*	@param  $val  mixed
	*	@param  $pop  bool
	*/
    function add($key,$val=null,$pop=false) {
        if (is_string($key)) {
            if (is_array($val))
                foreach ($val as $k=>$v) $this->add("$key.$k",$v,true);
            else {
                $keys=explode('.',$key);
                $arr=&$this->hive;
                if ($pop===true)
                    array_pop($keys);
                foreach ($keys as $key) {
                    if (!isset($arr[$key])||!is_array($arr[$key])) $arr[$key]=[];
                    $arr=&$arr[$key];
                }
                $arr[]=$val;
            }
        }
        elseif (is_array($key))
            foreach ($key as $k=>$v) $this->add($k,$v);
    }

	/**
	*	Check if hive path exists
	*	@param  $key  string
	*/
    function has($key) {
        $keys=explode('.',(string)$key);
        $arr=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($arr,$key)) return false;
            $arr=&$arr[$key];
        }
        return true;
    }

	/**
	*	Determine if the given key exists in the provided array
	*	@param   $arr  object|array
	*	@param   $key  string
	*	@return  bool
	*/
    function exists($arr,$key) {
        if ($arr instanceof \ArrayAccess) return isset($arr[$key]);
        return array_key_exists($key,$arr);
    }

	/**
	*	Erase a hive path or array of hive paths
	*	@param  $key  mixed
	*/
    function erase($key) {
        if (is_string($key)) {
            $keys=explode('.',$key);
            $arr=&$this->hive;
            $last=array_pop($keys);
            foreach ($keys as $key) {
                if (!$this->exists($arr,$key)) return;
                $arr=&$arr[$key];
            }
            unset($arr[$last]);
        }
        elseif (is_array($key))
            foreach ($key as $k) $this->erase($k);
    }

	/**
	*	Sort the values of a hive path or all the stored values
	*	@param   $key   string|null
	*	@return  array
	*/
    function sort($key=null) {
        if (is_string($key)) {
            $vals=$this->grab($key);
            return $this->arrsort((array)$vals);
        }
        elseif (is_null($key))
            return $this->arrsort($this->hive);
    }

	/**
	*	Recursively sort the values of a hive path or all the stored values
	*	@param   $key   string|null
	*	@param   $arr   array
	*	@return  array
    */
    function recsort($key=null,$arr=null) {
        if (is_array($arr)) {
            foreach ($arr as &$val)
                if (is_array($val))
					$val=$this->recsort(null,$val);
            return $this->arrsort($arr);
        }
        elseif (is_string($key)) {
            $vals=$this->grab($key);
            return $this->recsort(null,(array)$vals);
        }
        elseif (is_null($key))
            return $this->recsort(null,$this->hive);
    }

	/**
	*	Sort the given array
	*	@param 	 $arr   array
	*	@return  array
	*/
    function arrsort($arr) {
        $this->isassoc($arr)?ksort($arr):sort($arr);
        return $arr;
    }

	/**
	*	Determine whether the given value is array accessible
	*	@param 	 $val  mixed
	*	@return  bool
	*/
    function accessible($val) {
        return is_array($val)||$val instanceof \ArrayAccess;
    }

	/**
	*	Determine if an array is associative
	*	@param 	 $arr  array|null
	*	@return  bool
	*/
    function isassoc($arr=null) {
        $keys=is_array($arr)?array_keys($arr):array_keys($this->hive);
        return array_keys($keys)!==$keys;
    }

	/**
	*	Store an array as a reference
	*	@param  $arr  array
	*/
    function ref(&$arr) {
        if ($this->accessible($arr)) $this->hive=&$arr;
    }

	/**
	*	Get all stored values in hive
	*	@return  array
	*/
    function hive() {
        return $this->hive;
    }


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! ArrayAccess Interface's Methods
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function offsetSet($offset,$val) {
        $this->store($offset,$val);
    }


    function offsetExists($offset) {
        return $this->has($offset);
    }


    function offsetGet($offset) {
        return $this->grab($offset);
    }


    function offsetUnset($offset) {
        $this->erase($offset);
    }


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! PHP's Magic Methods
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function __set($key,$val=null) {
        $this->store($key,$val);
    }

    function __get($key) {
        return $this->grab($key);
    }

    function __isset($key) {
        return $this->has($key);
    }

    function __unset($key) {
        $this->erase($key);
    }

	// Prohibit cloning
	private function __clone() {}

	// Class constructor
	function __construct() {
		$fw=$this;
		// TODO: Fix error handler to trap parsing error
		set_error_handler([$fw,'abort']);
		if (null===$fw->hive['URI'])
			$base=implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/';
        $uri=substr($_SERVER['REQUEST_URI'],strlen($base));
        if (strstr($uri,'?')) $uri=substr($uri,0,strpos($uri,'?'));
        $fw->hive['PACKAGE']=self::PACKAGE;
		$fw->hive['VERSION']=self::VERSION;
		$fw->hive+=[
			'FW'=>['after'=>[],'before'=>[],'notfound'=>null,'base_route'=>'','method'=>''],
			// 'ROUTES'=>[], // not yet implemented :)
			'ROOT'=>$_SERVER['DOCUMENT_ROOT'],
			'BASE'=>$_SERVER['DOCUMENT_ROOT'].$base,
			'HOST'=>$_SERVER['SERVER_NAME'],
			'URI'=>'/'.trim($uri,'/'),
			'IP'=>$fw->ip(),
			'TZ'=>@date_default_timezone_get(),
			'TIME'=>&$_SERVER['REQUEST_TIME_FLOAT'],
			'LIB'=>$fw->slash(__DIR__).'/',
			'AUTOLOAD'=>'./',
			'UI'=>'./',
			'TEMP'=>'tmp/',
			'SESSION'=>null,
			// TODO: implements debug and error management
			// 'DEBUG'=>3,        // not yet implemented
			// 'EXCEPTION'=>null, // not yet implemented
			// 'ERROR'=>null,     // not yet implemented
		];
		date_default_timezone_set($fw->hive['TZ']);
		spl_autoload_register([$fw,'autoloader']);
		// TODO: Fix error handler to trap parsing error
		register_shutdown_function([$fw,'shutdown'],getcwd());
	}
}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Preview - lightweight template engine compiler
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Preview extends \Factory {
    protected
		$block,
    	$stack,
    	$ui;

	// Class constructor
    function __construct() {
        $fw=Alit::instance();
        $ui=$fw->grab('BASE').str_replace('./','',$fw->grab('UI'));
        $ui=str_replace('/',DIRECTORY_SEPARATOR,$ui);
        $this->ui=$ui;
        $this->block=[];
        $this->stack=[];
    }

	/**
	*	Add file to include
	*	@param   $name   string
	*	@return  string
	*/
    protected function tpl($name) {
        return $this->ui.$name;
    }

	/**
	*	Print result of templating
	*	@param  $name  string
	*	@param  $data  array
	*/
    function render($name,$data=[]) {
        echo $this->retrieve($name,$data);
    }

	/**
	*	Retrieve result of templating
	*	@param   $name   string
	*	@param   $data   array
	*	@return  string
	*/
    function retrieve($name,$data=[]) {
        $this->tpl[]=$name;
        if (!empty($data))
            extract($data);
        while ($file=array_shift($this->tpl)) {
            $this->beginblock('content');
            require ($this->tpl($file));
            $this->endblock(true);
        }
        return $this->block('content');
    }

	/**
	*	Check existance of a template file
	*	@param   $name  string
	*	@return  bool
	*/
    function exists($name) {
        return file_exists($this->tpl($name));
    }

	/**
	*	Cleanup template cache
	*	@return  bool
	*/
    function cleanup() {
        foreach (scandir($this->cache) as $file) {
            if (!in_array($file,['.','..','index.html','index.php','.htaccess','.cache','.log']))
                if (unlink($this->cache.DIRECTORY_SEPARATOR.$file)) return true;
        }
        return false;
    }

	/**
	*	Define parent
	*	@param  $name  string
	*/
    protected function extend($name) {
        $this->tpl[]=$name;
    }

	/**
	*	Return content of block if exists
	*	@param   $name     string
	*	@param   $default  string
	*	@return  string
	*/
    protected function block($name,$default='') {
        return array_key_exists($name,$this->block)?$this->block[$name]:$default;
    }

	/**
	*	Block begins
	*	@param  $name  string
	*/
    protected function beginblock($name) {
        array_push($this->stack,$name);
        ob_start();
    }

	/**
	*	Block ends
	*	@param   $overwrite  boolean
	*	@return  string
	*/
    protected function endblock($overwrite=false) {
        $name=array_pop($this->stack);
        if ($overwrite||!array_key_exists($name,$this->block))
            $this->block[$name]=ob_get_clean();
        else $this->block[$name].=ob_get_clean();
        return $name;
    }
}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Factory - factory class for single-instance objects
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
abstract class Factory {

	/**
	*	Return class instance
	*	@return  static
	*/
	static function instance() {
		if (!\Warehouse::exists($class=get_called_class())) {
			$ref=new \Reflectionclass($class);
			$args=func_get_args();
			\Warehouse::store($class,$args?$ref->newInstanceArgs($args):new $class);
		}
		return \Warehouse::grab($class);
	}

}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Warehouse - container for singular object instances
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
final class Warehouse {
	// object table
	private static $table;

	/**
	*	Return TRUE if object exists in table
	*	@param   $key  string
	*	@return  bool
	**/
	static function exists($key) {
		return isset(self::$table[$key]);
	}

	/**
	*	Add object to table
	*	@param   $key    string
	*	@param   $obj    object
	*	@return  object
	**/
	static function store($key,$obj) {
		return self::$table[$key]=$obj;
	}

	/**
	*	Retrieve object from table
	*	@param   $key    string
	*	@return  object
	**/
	static function grab($key) {
		return self::$table[$key];
	}

	/**
	*	Delete object from table
	*	@param   $key  string
	*	@return  null
	**/
	static function clear($key) {
		self::$table[$key]=null;
		unset(self::$table[$key]);
	}

	// Prohibit cloning
	private function __clone() {}
	// Prohibit instantiation
	private function __construct() {}
}
// Return alit instance on file include
return Alit::instance();
