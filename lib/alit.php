<?php
/**
*   Core Class of Alit PHP.
*   @package     Alit PHP
*   @subpackage  Alit
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT).
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Define framework constant to prohibit direct file access
if (!defined('ALIT')) define('ALIT',true);


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Alit - The core class
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
final class Alit extends \Factory implements \ArrayAccess {

	const
		// Package and version info
		PACKAGE='Alit PHP',
		VERSION='1.0.0-stable',
		// Valid request methods
		METHODS='CONNECT|DELETE|GET|HEAD|OPTIONS|PATCH|POST|PUT';
	const
		// HTTP status codes
		HTTP_100='Continue',
		HTTP_101='Switching Protocols',
		HTTP_200='OK',
		HTTP_201='Created',
		HTTP_202='Accepted',
		HTTP_203='Non-Authorative Information',
		HTTP_204='No Content',
		HTTP_205='Reset Content',
		HTTP_206='Partial Content',
		HTTP_300='Multiple Choices',
		HTTP_301='Moved Permanently',
		HTTP_302='Found',
		HTTP_303='See Other',
		HTTP_304='Not Modified',
		HTTP_305='Use Proxy',
		HTTP_307='Temporary Redirect',
		HTTP_400='Bad Request',
		HTTP_401='Unauthorized',
		HTTP_402='Payment Required',
		HTTP_403='Forbidden',
		HTTP_404='Not Found',
		HTTP_405='Method Not Allowed',
		HTTP_406='Not Acceptable',
		HTTP_407='Proxy Authentication Required',
		HTTP_408='Request Timeout',
		HTTP_409='Conflict',
		HTTP_410='Gone',
		HTTP_411='Length Required',
		HTTP_412='Precondition Failed',
		HTTP_413='Request Entity Too Large',
		HTTP_414='Request-URI Too Long',
		HTTP_415='Unsupported Media Type',
		HTTP_416='Requested Range Not Satisfiable',
		HTTP_417='Expectation Failed',
		HTTP_500='Internal Server Error',
		HTTP_501='Not Implemented',
		HTTP_502='Bad Gateway',
		HTTP_503='Service Unavailable',
		HTTP_504='Gateway Timeout',
		HTTP_505='HTTP Version Not Supported';
	public
		// Store all framework variables
		$hive;

	/**
	*	Set a before-route middleware and a handling function to be -
	*	executed when accessed using one of the specified methods.
	*	@param  $request  string
	*	@param  $handler  object|callable
	*/
	function before($request,$handler) {
        $request=explode(' ',preg_replace('/\s+/',' ',$request));
		$methods=$request[0];
		$pattern=$request[1];
        foreach ($this->split($methods) as $method)
            $this->hive['ALIT']['before'][$method][]=['pattern'=>$pattern,'handler'=>$handler];
    }

	/**
	*	Set a after-route middleware and a handling function to be -
	*	executed when accessed using one of the specified methods.
	*	@param  $request  string
	*	@param  $handler  object|callable
	*/
	function after($request,$handler) {
        $request=explode(' ',preg_replace('/\s+/',' ',$request));
		$methods=$request[0];
		$pattern=$request[1];
        foreach ($this->split($methods) as $method)
            $this->hive['ALIT']['after'][$method][]=['pattern'=>$pattern,'handler'=>$handler];
    }

	/**
	*	Store a route and a handling function to be executed -
	*	when accessed using one of the specified methods.
	*	@param  $methods  string
	*	@param  $handler  object|callable
	*/
	function route($request,$handler) {
        $request=explode(' ',preg_replace('/\s+/',' ',$request));
		$methods=$request[0];
		$pattern=$request[1];
	    foreach ($this->split($methods) as $method) {
			if (!in_array($method,$this->split(self::METHODS)))
				$this->abort(500,"Invalid method supplied: {$method}");
	        $this->hive['ALIT']['route'][$method][]=['pattern'=>$pattern,'handler'=>$handler];
		}
	}

	/**
	*	Get all request headers.
	*	@return array
	*/
    function headers() {
        if (function_exists('getallheaders'))
            return getallheaders();
        $res=[];
        foreach ($_SERVER as $key=>$val)
            if ((substr($k,0,5)=='HTTP_')
			||($key=='CONTENT_TYPE')
			||($key=='CONTENT_LENGTH'))
                $res[str_replace([' ','Http'],['-','HTTP'],
					ucwords(strtolower(str_replace('_',' ',substr($key,5)))))]=$val;
        return $res;
    }

	/**
	*	Get the request method used, taking overrides into account.
	*	@return  string
	*/
    function method() {
		// If it's a HEAD request, override it to being GET and -
		// Reference: http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
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
	*	and execute the handling function if a route was found.
	*	@return  bool
	*/
    function run() {
        $this->hive['ALIT']['method']=$this->method();
		// Execute before-route middleware if any
        if (isset($this->hive['ALIT']['before'][$this->hive['ALIT']['method']]))
            $this->handle($this->hive['ALIT']['before'][$this->hive['ALIT']['method']]);
        $handled=0;
		// Execute user-defined routes
        if (isset($this->hive['ALIT']['route'][$this->hive['ALIT']['method']]))
            $handled=$this->handle($this->hive['ALIT']['route'][$this->hive['ALIT']['method']],true);
			// No route specified
        if ($handled===0) {
			// Call notfound handler if any
            if ($this->hive['ALIT']['notfound']
			&&is_callable($this->hive['ALIT']['notfound']))
                call_user_func($this->hive['ALIT']['notfound']);
            else $this->abort(404,"The page you've requested can not be found on this server");
        }
		// Execute after-route middleware if any
        else if (isset($this->hive['ALIT']['after'][$this->hive['ALIT']['method']]))
            $this->handle($this->hive['ALIT']['after'][$this->hive['ALIT']['method']]);
        if ($_SERVER['REQUEST_METHOD']=='HEAD')
        	ob_end_clean();
        if ($handled===0)
        	return false;
        return true;
    }

	/**
	*	Set the page not found (404) handling function
	*	@param  $handler  object|callable
	*/
    function notfound($handler=null) {
		$this->hive['ALIT']['notfound']=$handler;

    }

	/**
	*	Handler for common php errors
	*	@param  $code    int
	*	@param  $reason  string|null
	*	@param  $file    string|null
	*	@param  $line    string|null
	*	@param  $trace   mixed|null
	*	@param  $level   int
	*/
	function abort($code,$reason=null,$file=null,$line=null,$trace=null,$level=0) {
		if ($code) {
			$hdrmsg=@constant('self::HTTP_'.$code);
			error_log("{$code} {$hdrmsg}");
			$trace=$this->backtrace($trace);
			foreach (explode("\n",$trace) as $log)
			if ($log)
				error_log($log);
			$this->hive['ERROR']=[
				'status'=>$hdrmsg,
				'code'=>$code,
				'text'=>"{$code} {$hdrmsg}",
				'trace'=>$trace,
				'level'=>$level
			];
			ob_start();
			if (!headers_sent())
				header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$hdrmsg);
				// if DEBUG value larger than 0
				if ((int)$this->hive['DEBUG']>0) {
					echo "<!DOCTYPE html>\n<html>".
						"\n\t<head>\n\t\t<title>{$code} {$hdrmsg}</title>\n\t</head>".
						"\n\t<body>\n".
						"\t\t<h1>{$code} {$hdrmsg}</h1>\n".
						"\t\t<p>{$reason}</p>\n";
					(!is_null($file)?$path="\t\t<pre>{$file}:<font color=red>{$line}</font></pre></br>\n":$path="");
					echo $path."\t\t<b>Back Trace:</b><br>\n".
						// Show backtrace
						"\t\t<pre>{$trace}</pre>\n".
						"\t</body>\n</html>";
				}
				else {
					echo "<!DOCTYPE html>\n<html>".
						"\n\t<head>\n\t\t<title>{$code} {$hdrmsg}</title>\n\t</head>".
						"\n\t<body>\n".
						"\t\t<h1>{$code} {$hdrmsg}</h1>\n".
						"\t\t<p>{$reason}</p>\n";
					(!is_null($file)?$path="\t\t<pre>{$file}:<font color=red>{$line}</font></pre></br>\n":$path="");
					echo $path."\t</body>\n</html>";
				}
            ob_end_flush();
            die();
		}
	}

	/**
	*	Return filtered stack trace as a formatted string (or array)
	*	@param   $trace        array|null
	*	@param   $format       bool
	*	@return  string|array
	*/
	function backtrace(array $trace=null,$format=true) {
		if (!$trace) {
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$stack=$trace[0];
			if (isset($stack['file'])
			&&$stack['file']==__FILE__)
				array_shift($trace);
		}
		$debug=$this->hive['DEBUG'];
		$trace=array_filter($trace,
			function($stack) use ($debug) {
				return isset($stack['file'])
					&&($debug>1||($stack['file']!=__FILE__||$debug)
					&&(empty($stack['function'])
					||!preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/',
						$stack['function'])));
			}
		);
		if (!$format)
			return $trace;
		$out='';
		$nl="\n";
		// Analyze stack trace
		foreach ($trace as $stack) {
			$line='';
			if (isset($stack['class']))
				$line.=$stack['class'].$stack['type'];
			if (isset($stack['function']))
				$line.=$stack['function'].'('.($debug>2&&isset($stack['args'])?$stack['args']:'').')';
			$src=$this->slash(str_replace($_SERVER['DOCUMENT_ROOT'].
				'/','',$stack['file'])).':<font color=red>'.$stack['line'].'</font>';
			$out.='['.$src.'] '.$line.$nl;
		}
		return $out;
	}

	/**
	*	Handler for common and fatal errors
	*	@param  $cwd  string
	*/
	function shutdown($cwd) {
		chdir($cwd);
		if (!($error=error_get_last())
		&&session_status()==PHP_SESSION_ACTIVE)
			session_commit();
		if ($error&&in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR]))
			$this->abort(500,$error['message'],$error['file'],$error['line']);
	}


	/**
	*	Redirect to specified URI
	*	@param  $url   string
	*	@param  $wait  int
	*/
	function redirect($url=null) {
		$base=$this->hive['PROTO'].'://'.rtrim($this->hive['BASE'],'/');
		$url=filter_var($url,FILTER_SANITIZE_URL);
		if (!is_null($url)) {
			// Scheme must be included for valid url
			if (filter_var($url,FILTER_VALIDATE_URL))
				$url=$url;
			else $url=$base.$url;
		}
		else $url=$base;
        try {
            ob_start();
            header('Location: '.$url,true,302);
            ob_end_flush();
            exit;
        }
        catch (\Exception $ex) {
			$this->abort(500,"Can't redirect to specified url: {$url}");
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
                if (is_callable($route['handler']))
                    call_user_func_array($route['handler'],$params);
                elseif (stripos($route['handler'],'@')!==false) {
                    list($controller,$method)=explode('@',$route['handler']);
                    if (class_exists($controller)) {
                        if (call_user_func_array([new $controller,$method],$params)===false)
                        	if (forward_static_call_array([$controller,$method],$params)===false)
								$this->abort(500,"Can't forward route handler: {$route['handler']}");
					}
					else $this->abort(500,"Can't find route handler: {$controller}::{$method}");
                }
                $handled++;
                if ($quit)
					break;
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
		$file=$this->hive['BASE'].str_replace('./','',$this->hive['UI'].$name);
		$file=str_replace('/',DIRECTORY_SEPARATOR,$file);
		if (!file_exists($file))
			$this->abort(500,"Can't find view file: {$name}");
        ob_start();
        if (is_array($data))
        	extract($data);
        require $file;
        echo trim(ob_get_clean());
    }

	/**
	*	Parse INI file and store it's array to hive
	*	@param  $source  string|array
	*/
	function config($source) {
		if (is_string($source))
			$source=$this->split($source);
		foreach ($source as $file) {
			preg_match_all(
			'/(?<=^|\n)(?:\[(?<child>.+?)\]|(?<left>[^\h\r\n;].*?)\h*=\h*(?<right>(?:\\\\\h*\r?\n|.+?)*))(?=\r?\n|$)/',
			$this->read($file),$matches,PREG_SET_ORDER);
			if ($matches) {
				$child='global';
				$fn=[];
				foreach ($matches as $match) {
					if ($match['child']) {
						$child=$match['child'];
						if (preg_match('/^(?!(?:global|config|route|before|after)\b)((?:\.?\w)+)/i',$child,$gchild)
						&&!$this->exists($gchild[0],$this->hive))
							$this->set($gchild[0],null);
						preg_match('/^(config|route|before|after)\b|^((?:\.?\w)+)\s*\>\s*(.*)/i',$child,$fn);
						continue;
					}
					if (!empty($fn))
						call_user_func_array([$this,$fn[1]],
							array_merge([$match['left']],str_getcsv($match['right']))
						);
					else {
						$right=preg_replace('/\\\\\h*(\r?\n)/','\1',$match['right']);
						if (preg_match('/^(.+)\|\h*(\d+)$/',$right,$tmp)) {
							array_shift($tmp);
							list($right)=$tmp;
						}
						// Mark quoted strings with 0x00 whitespace
						str_getcsv(preg_replace('/(?<!\\\\)(")(.*?)\1/',"\\1\x00\\2\\1",trim($right)));
						preg_match('/^(?<child>[^:]+)?/',$child,$node);
						$custom=(strtolower($node['child']!='global'));
						call_user_func_array([$this,'set'],
							array_merge([($custom?($node['child'].'.'):'').$match['left']],[$right])
						);
					}
				}
			}
		}
		return $this;
	}

	/**
	*	Read from file (with option to apply Unix LF as standard line ending)
	*	@param   $file   string
	*	@param   $lf     bool
	*	@return  string
	*/
	function read($file,$lf=false) {
	    $file=file_get_contents($file);
	    return $lf?preg_replace('/\r\n|\r/',"\n",$file):$file;
	}

	/**
	*	Write to file (or append if $append is true)
	*	@param   $file      string
	*	@param   $data      mixed
	*	@param   $append    bool
	*	@return  int|false
	**/
	function write($file,$data,$append=false) {
		return file_put_contents($file,$data,LOCK_EX|($append?FILE_APPEND:0));
	}

	/**
	*	Get client's ip
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
		if ($ip===false)
			$ip='0.0.0.0';
		// Force local ip to ipv4
		if ($ip=='::1')
			$ip='127.0.0.1';
		return $ip;
	}

	/**
	*	Validate ip address
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
			foreach ($range as $r)
				if ((ip2long($ip)>=ip2long($r[0]))
				&&(ip2long($ip)<=ip2long($r[1])))
					return false;
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
		$fn=null;
		foreach ($this->split($this->hive['LIB'].'|./') as $auto) {
			if (is_file($file=$auto.$class.'.php')
			||is_file($file=$auto.strtolower($class).'.php')
			||is_file($file=strtolower($auto.$class).'.php'))
				return require($file);
		}
	}

	/**
	*	Replace backslash with slash
	*	@param   $str    string
	*	@return  string
	*/
	function slash($str) {
		return $str?strtr($str,'\\','/'):$str;
	}

	/**
	*	Split comma-, semicolon-, or pipe-separated string
	*	@param   $str      string
	*	@param   $noempty  bool
	*	@return  array
	*/
	function split($str,$noempty=true) {
		return array_map('trim',preg_split('/[,;|]/',$str,0,$noempty?PREG_SPLIT_NO_EMPTY:0));
	}


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Hive - functions to playing around with framework variables
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	/**
	*	Set a value to hive or rewrite it if already exist
	*	@param  $key  mixed
	*	@param  $val  mixed
	*/
	function set($key,$val=null) {
        if (is_string($key)) {
            if (is_array($val)&&!empty($val))
                foreach ($val as $k=>$v)
                	$this->set("$key.$k",$v);
            else {
                $keys=explode('.',$key);
                $arr=&$this->hive;
                foreach ($keys as $key) {
                    if (!isset($arr[$key])
                    ||!is_array($arr[$key]))
						$arr[$key]=[];
                    $arr=&$arr[$key];
                }
                $arr=$val;
            }
        }
        elseif (is_array($key))
            foreach ($key as $k=>$v)
            	$this->set($k,$v);
    }

	/**
	*	Multi-set data to hive using associative array
	*	@param $arr     array
	*	@param $prefix  string
	*	@return null
	*/
	function mset(array $arr,$prefix='') {
		foreach ($arr as $k=>$v)
			$this->set($prefix.$k,$v);
	}

	/**
	*	Get a value from hive or default value if path doesn't exist
	*	@param  $key      string
	*	@param  $default  mixed
	*/
    function get($key,$default=null) {
        $keys=explode('.',(string)$key);
        $arr=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$arr))
            	return $default;
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
                foreach ($val as $k=>$v)
                	$this->add("$key.$k",$v,true);
            else {
                $keys=explode('.',$key);
                $arr=&$this->hive;
                if ($pop===true)
                    array_pop($keys);
                foreach ($keys as $key) {
                    if (!isset($arr[$key])
                    ||!is_array($arr[$key]))
                    	$arr[$key]=[];
                    $arr=&$arr[$key];
                }
                $arr[]=$val;
            }
        }
        elseif (is_array($key))
            foreach ($key as $k=>$v)
            	$this->add($k,$v);
    }

	/**
	*	Check if hive path exists
	*	@param  $key  string
	*/
    function has($key) {
        $keys=explode('.',(string)$key);
        $arr=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$arr))
            	return false;
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
    function exists($key,$arr) {
        if ($arr instanceof \ArrayAccess)
        	return isset($arr[$key]);
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
                if (!$this->exists($key,$arr))
                	return;
                $arr=&$arr[$key];
            }
            unset($arr[$last]);
        }
        elseif (is_array($key))
            foreach ($key as $k)
            	$this->erase($k);
    }

	/**
	*	Sort the values of a hive path or all the stored values
	*	@param   $key   string|null
	*	@return  array
	*/
    function sort($key=null) {
        if (is_string($key)) {
            $val=$this->get($key);
            return $this->arrsort((array)$val);
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
            $vals=$this->get($key);
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
        if ($this->accessible($arr))
        	$this->hive=&$arr;
    }

	/**
	*	Grab all stored values in hive
	*	@return  array
	*/
    function hive() {
        return $this->hive;
    }


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! ArrayAccess Interface's Methods
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function offsetSet($offset,$val) {
        $this->set($offset,$val);
    }


    function offsetExists($offset) {
        return $this->has($offset);
    }


    function offsetGet($offset) {
        return $this->get($offset);
    }


    function offsetUnset($offset) {
        $this->erase($offset);
    }


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! PHP's Magic Methods
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function __set($key,$val=null) {
        $this->set($key,$val);
    }

    function __get($key) {
        return $this->get($key);
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
		// Set default charset
		ini_set('default_charset',$charset='UTF-8');
		if (extension_loaded('mbstring'))
			mb_internal_encoding($charset);
		// Turn-off standard error reporting (remove duplicate report)
		ini_set('display_errors',0);
		$fw=$this;
		// Exception handler
		set_exception_handler(function($obj) {
			$fw->hive['EXCEPTION']=$obj;
			$this->abort(500,
				$obj->getMessage().' in '.
				$obj->getFile().':'.
				'<font color=red>'.$obj->getLine().'</font>',
				$obj->getTrace(),
				$obj->getCode()
			);
		});
		// Error handler
		set_error_handler(function($level,$reason,$file,$line) {
			if ($level & error_reporting())
				$this->abort(500,$reason,$file,$line);
		});
		if (null===$fw->hive['URI'])
			$base=implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/';
        $uri=substr($_SERVER['REQUEST_URI'],strlen($base));
        if (strstr($uri,'?')) $uri=substr($uri,0,strpos($uri,'?'));
		// Determine server protocol
		$proto='http';
		if (isset($_SERVER['HTTPS'])
		&&$_SERVER['HTTPS']=='on'
		||$_SERVER['SERVER_PORT']==443)
			$proto='https';
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&&$_SERVER['HTTP_X_FORWARDED_PROTO']=='https'
		||!empty($_SERVER['HTTP_X_FORWARDED_SSL'])
		&&$_SERVER['HTTP_X_FORWARDED_SSL']=='on')
			$proto='https';
		// Assign default value to route variables
		$fw->hive['ALIT']=[
			'route'=>[],
			'before'=>[],
			'after'=>[],
			'method'=>'',
			'notfound'=>null,
		];
		// Assign default value to system variables
		$fw->hive+=[
			'BASE'=>$_SERVER['SERVER_NAME'].$base,
			'DEBUG'=>0,
			'ERROR'=>null,
			'EXCEPTION'=>null,
			'HOST'=>$_SERVER['SERVER_NAME'],
			'IP'=>$fw->ip(),
			'LIB'=>$fw->slash(__DIR__).'/',
			'PACKAGE'=>self::PACKAGE,
			'PROTO'=>$proto,
			'ROOT'=>$_SERVER['DOCUMENT_ROOT'].$base,
			'SESSION'=>null,
			'TEMP'=>'tmp/',
			'TIME'=>&$_SERVER['REQUEST_TIME_FLOAT'],
			'TZ'=>@date_default_timezone_get(),
			'UI'=>'./',
			'URI'=>'/'.trim($uri,'/'),
			'VERSION'=>self::VERSION,
		];
		// Set default timezone
		date_default_timezone_set($fw->hive['TZ']);
		// Register autoloader function
		spl_autoload_register([$fw,'autoloader']);
		// Register fatal error handler
		register_shutdown_function([$fw,'shutdown'],getcwd());
	}
}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Preview - lightweight template compiler engine
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Preview extends \Factory {
    protected
		$fw,
    	$ui,
		$block,
		$stack;

	// Class constructor
    function __construct() {
        $fw=\Alit::instance();
		$this->fw=$fw;
		$this->block=[];
		$this->stack=[];
        $this->ui=str_replace('/',DIRECTORY_SEPARATOR,$fw->hive['ROOT'].
        	str_replace('./','',$fw->hive['UI']));
    }

	/**
	*	Add file to include
	*	@param   $name   string
	*	@return  string
	*/
    protected function tpl($name) {
        return preg_replace('/\s+/','',$this->ui.$name);
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
//! Factory - A factory class for single-instance objects
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
abstract class Factory {

	/**
	*	Return class instance
	*	@return  static
	*/
	static function instance() {
		if (!\Warehouse::exists($class=get_called_class())) {
			$ref=new \ReflectionClass($class);
			$args=func_get_args();
			\Warehouse::set($class,$args?$ref->newInstanceArgs($args):new $class);
		}
		return \Warehouse::get($class);
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
	*/
	static function exists($key) {
		return isset(self::$table[$key]);
	}

	/**
	*	Add object to table
	*	@param   $key    string
	*	@param   $obj    object
	*	@return  object
	*/
	static function set($key,$obj) {
		return self::$table[$key]=$obj;
	}

	/**
	*	Retrieve object from table
	*	@param   $key    string
	*	@return  object
	*/
	static function get($key) {
		return self::$table[$key];
	}

	/**
	*	Delete object from table
	*	@param   $key  string
	*	@return  null
	*/
	static function clear($key) {
		self::$table[$key]=null;
		unset(self::$table[$key]);
	}

	// Prohibit cloning
	private function __clone() {}
	// Prohibit instantiation
	private function __construct() {}
}
// Return framework instance on file inclusion
return \Alit::instance();
