<?php
/**
*   Core Class of Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT).
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Define framework constant to prohibit direct file access
if (!defined('DS')) define('DS',DIRECTORY_SEPARATOR);


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Alit - The core class
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
final class Alit extends \Factory implements \ArrayAccess {

	const
		// Package and version info
		PACKAGE='Alit PHP',
		VERSION='1.0.0-stable';

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

	const
		// Error messages
		E_Method="Invalid method supplied: '%s'",
		E_Redirect="Can't redirect to specified url: '%s'",
		E_Route="Can't find route handler: '%s'",
		E_Middleware="Can't execute %s-route middleware in '%s'",
		E_Forward="Can't forward route handler: '%s'",
		E_View="Can't find view file: '%s'",
		E_Notfound="The page you have requested can not be found on this server.";

	const
		// Valid request methods
		METHODS='CONNECT|DELETE|GET|HEAD|OPTIONS|PATCH|POST|PUT';

	protected
		// Stores all framework variables
		$hive;

	/**
	*	Set a before-route middleware and a handling function to be -
	*	executed when accessed using one of the specified methods.
	*	@param  $req  string
	*	@param  $fn   object|callable
	*/
	function before($req,$fn) {
        $req=preg_split('/ /',preg_replace('/\s\s+/',' ',$req),-1,PREG_SPLIT_NO_EMPTY);
        foreach ($this->split($req[0]) as $verb)
            $this->push('ROUTES.Before.'.$verb,['uri'=>$req[1],'fn'=>is_string($fn)?trim($fn):$fn]);
    }

	/**
	*	Set a after-route middleware and a handling function to be -
	*	executed when accessed using one of the specified methods.
	*	@param  $req  string
	*	@param  $fn   object|callable
	*/
	function after($req,$fn) {
        $req=preg_split('/ /',preg_replace('/\s\s+/',' ',$req),-1,PREG_SPLIT_NO_EMPTY);
        foreach ($this->split($req[0]) as $verb)
            $this->push('ROUTES.After.'.$verb,['uri'=>$req[1],'fn'=>is_string($fn)?trim($fn):$fn]);
    }

	/**
	*	Set the page not found (404) handling function
	*	@param  $fn  object|callable
	*/
    function notfound($fn=null) {
		$this->set('ROUTES.Notfound',is_string($fn)?trim($fn):$fn);

    }

	/**
	*	Store a route and a handling function to be executed -
	*	when accessed using one of the specified methods.
	*	@param  $req  string
	*	@param  $fn   object|callable
	*/
	function route($req,$fn) {
        $req=preg_split('/ /',preg_replace('/\s\s+/',' ',$req),-1,PREG_SPLIT_NO_EMPTY);
	    foreach ($this->split($req[0]) as $verb) {
			if (!in_array($verb,$this->split(self::METHODS)))
				user_error(sprintf(self::E_Method,$verb),E_USER_ERROR);
			$this->push('ROUTES.Main.'.$verb,['uri'=>$req[1],'fn'=>is_string($fn)?trim($fn):$fn]);
		}
	}

	/**
	*	Execute the framework: Loop all defined route before middleware's and routes, -
	*	and execute the handling function if a route was found.
	*	@return  bool
	*/
    function run() {
    	// Get routes info
    	$before=$this->get('ROUTES.Before');
    	$main=$this->get('ROUTES.Main');
    	$after=$this->get('ROUTES.After');
    	$notfound=$this->get('ROUTES.Notfound');
    	$verb=$this->get('VERB');
		// Execute before-route middleware if any
        if (isset($before[$verb]))
            $this->execute($before[$verb]);
        $executed=0;
		// Execute main routes
        if (isset($main[$verb]))
            $executed=$this->execute($main[$verb],true);
		// No route specified or fail to execute routes
        if ($executed===0) {
			// Call notfound handler if any
			if (!is_null($notfound)) {
				// Call directly if it's a callable (anonymous/lambda) function
				if (is_callable($notfound))
	                call_user_func($notfound);
	            // If it's a class method
				elseif (is_string($notfound)) {
					if (stripos($notfound,'@')!==false) {
						list($controller,$fn)=explode('@',$notfound);
						// Check class existence, then call it!
						if (class_exists($controller))
							call_user_func([new $controller,$fn]);
						// Error, class or class-method cannot be found
						else user_error(sprintf(self::E_Route,$controller.'@'.$fn),E_USER_ERROR);
					}
					else user_error(sprintf(self::E_Route,$notfound),E_USER_ERROR);
				}
			}
			// Call default notfound message if notfound handler is not set
			else $this->abort(404,self::E_Notfound);
        }
		// Execute after-route middleware if any
        else if (isset($after[$verb]))
            $this->execute($after[$verb]);
        if ($verb=='HEAD')
        	ob_end_clean();
		return ($executed>0);
    }

	/**
	*	Execute a set of routes: if a route is found, invoke the relating handling function
	*	@param   $routes  array
	*	@param   $quit    boolean
	*	@return  int
	*/
    private function execute($routes,$quit=false) {
    	$verb=$this->get('VERB');
    	$uri=$this->get('URI');
        $executed=0;
        foreach ($routes as $route) {
            if (preg_match_all('~^'.$route['uri'].'$~',$uri,$matches,PREG_OFFSET_CAPTURE)) {
                $matches=array_slice($matches,1);
                $args=array_map(function ($match,$index) use ($matches) {
                    if (isset($matches[$index+1])
					&&isset($matches[$index+1][0])
					&&is_array($matches[$index+1][0]))
                        return trim(substr($match[0][0],0,$matches[$index+1][0][1]-$match[0][1]),'/');
                    else return (isset($match[0][0])?trim($match[0][0],'/'):null);
                },$matches,array_keys($matches));
				// Execute directly if route handler is a callable function
                if (is_callable($route['fn']))
                    call_user_func_array($route['fn'],$args);
				// But, if it's a string
                elseif (stripos($route['fn'],'@')!==false) {
                	// Find the appropriate class and method
                    list($controller,$fn)=explode('@',$route['fn']);
                    // Check existence of the class
                    if (class_exists($controller)) {
						$class=new $controller;
						// Check existence of before-route middleware first
						if (method_exists($class,'before')) {
							// Assign before-route middleware info to hive
							$this->push('ROUTES.Before.'.$verb,['uri'=>$uri,'fn'=>$controller.'@before']);
							// Execute before-route middleware inside controller class
							if (call_user_func([$class,'before'])===false)
								user_error(sprintf(self::E_Middleware,'before',$controller.'@'.$fn),E_USER_ERROR);
						}
						// Assign after-route middleware info to hive if exists
						if (method_exists($class,'after'))
							$this->push('ROUTES.After.'.$verb,['uri'=>$uri,'fn'=>$controller.'@after']);
						// Execute main route handler
                        if (call_user_func_array([$class,$fn],$args)===false)
                        	if (forward_static_call_array([$controller,$fn],$args)===false)
								user_error(sprintf(self::E_Forward,$route['fn']),E_USER_ERROR);
						// Check existence of after-route middleware
						if (method_exists($class,'after'))
							// Execute after-route middleware inside controller class
							if (call_user_func([$class,'after'])===false)
								user_error(sprintf(self::E_Middleware,'after',$controller.'@'.$fn),E_USER_ERROR);
					}
					// Class doesn't exists
					else user_error(sprintf(self::E_Route,$controller.'@'.$fn),E_USER_ERROR);
                }
				// Invalid route handler detected!
                else user_error(sprintf(self::E_Route,$route['fn']),E_USER_ERROR);
                $executed++;
                if ((bool)$quit===true)
					break;
            }
        }
        return $executed;
    }

	/**
	*	Trigger error message
	*	@param  $code    int
	*	@param  $reason  string|null
	*	@param  $file    string|null
	*	@param  $line    string|null
	*	@param  $trace   mixed|null
	*	@param  $level   int
	*/
	function abort($code,$reason=null,$file=null,$line=null,$trace=null,$level=0) {
		if ($code) {
			$status=@constant('self::HTTP_'.$code);
			error_log($code.' '.$status);
			$trace=$this->backtrace($trace);
			foreach (explode("\n",$trace) as $log)
				if ($log)
					error_log($log);
			$this->set('ERROR',[
				'status'=>$status,
				'code'=>$code,
				'file'=>$file,
				'line'=>$line,
				'reason'=>$reason,
				'trace'=>$trace,
				'level'=>$level
			]);
			// Write error to file if SYSLOG is activated
			$debug='[ERROR]'.PHP_EOL;
			$debug.='---------------------------------------------'.PHP_EOL;
			if ($this->get('SYSLOG')) {
				$err=$this->get('ERROR');
				unset($err['level']);
				unset($err['trace']);
				foreach ($err as $k=>$v)
					$debug.=ucfirst(((strlen($k)<=8)?$k.str_repeat(' ',(8-strlen($k))):$k)).': '.$v.PHP_EOL;
				$this->log($debug,$this->get('TEMP').'syslog.log');
			}
			ob_start();
			if (!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$status);
				header('X-Powered-By: '.$this->get('PACKAGE'));
			}
			if ($this->get('AJAX'))
					echo json_encode(['status'=>500,'data'=>$this->get('ERROR')]);
			else {
				echo "<!DOCTYPE html>\n<html>".
					"\n\t<head>\n\t\t<title>".$code." ".$status."</title>\n\t</head>".
					"\n\t<body style=\"color:#666\">\n".
					"\t\t<h1>".$code." ".$status."</h1>\n".
					"\t\t<p>".ucfirst($reason)."</p>\n";
				echo (!empty($file)&&!empty($line))
					?"\t\t<pre>".$file.":<font color=\"red\">".$line."</font></pre><br>\n":"";
				// Show debug backtrace if DEBUG is activated
				if ((int)$this->get('DEBUG')>0)
					echo "\t\t<b>Back Trace:</b><br>\n\t\t<pre>".$trace."</pre>\n";
				echo "\t</body>\n</html>";
			}
            ob_end_flush();
            die(1);
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
		$debug=$this->get('DEBUG');
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
		// Analyze stack trace
		foreach ($trace as $stack) {
			$line='';
			// Delete the function arguments, e don't need it :)
			if (isset($stack['args']))
				unset($stack['args']);
			if (isset($stack['class']))
				$line.=$stack['class'].$stack['type'];
			if (isset($stack['function']))
				$line.=$stack['function'].'()';
			$src=$this->slash(str_replace($_SERVER['DOCUMENT_ROOT'].'/','',$stack['file'])).
				':<font color="red">'.$stack['line'].'</font>';
			$out.='['.$src.'] '.$line.PHP_EOL;
		}
		return $out;
	}

	/**
	*	Custom shutdown function for error handling purpose
	*	@param  $cwd  string
	*/
	function shutdown($cwd) {
		chdir($cwd);
		if (!($error=error_get_last())
		&&session_status()==PHP_SESSION_ACTIVE)
			session_commit();
		$errors=[E_ERROR,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING];
		if ($error&&in_array($error['type'],$errors))
			$this->abort(500,$error['message'],$error['file'],$error['line']);
	}


	/**
	*	Redirect to specified URI
	*	@param  $url   string
	*	@param  $wait  int
	*/
	function redirect($url=null,$permanent=true) {
		$base=$this->get('PROTO').'://'.rtrim($this->get('BASE'),'/');
		$url=filter_var($url,FILTER_SANITIZE_URL);
		if (!is_null($url)) {
			if (!preg_match('~^(http(s)?://)?[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$~i',$url)
			||!filter_var($url,FILTER_VALIDATE_URL))
				$url=$base.$url;
		}
		else $url=$base;
        try {
            ob_start();
            header('Location: '.$url,true,$permanent?302:307);
            ob_end_flush();
            exit();
        }
        catch (\Exception $ex) {
			user_error(sprintf(self::E_Redirect,$url),E_USER_ERROR);
        }
    }

	/**
    *   Render view using native PHP template
    *   @param  $name  string
    *   @param  $data  null|array
    */
	function render($name,$data=null) {
		$file=str_replace('/',DS,$this->get('BASE').str_replace('./','',$this->get('UI').$name));
		if (!file_exists($file))
			user_error(sprintf(self::E_View,$name),E_USER_ERROR);
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
				'/(?<=^|\n)(?:'.
				'\[(?<child>.+?)\]|'.
				'(?<left>[^\h\r\n;].*?)\h*=\h*'.
				'(?<right>(?:\\\\\h*\r?\n|.+?)*)'.
				')(?=\r?\n|$)/',
				$this->read($file),$matches,PREG_SET_ORDER
			);
			if ($matches) {
				$child='global';
				$fn=[];
				foreach ($matches as $match) {
					if ($match['child']) {
						$child=$match['child'];
						if (preg_match('/^(?!(?:global|config|route)\b)((?:\.?\w)+)/i',$child,$gchild)
						&&!$this->exists($gchild[0],$this->hive()))
							$this->set($gchild[0],null);
						preg_match('/^(config|route)\b|^((?:\.?\w)+)\s*\>\s*(.*)/i',$child,$fn);
						continue;
					}
					if (!empty($fn))
						call_user_func_array(
							// Call method based on config flag (route/config)
							[$this,$fn[1]],
							// Array to be passed in
							array_merge([$match['left']],str_getcsv($match['right']))
						);
					else {
						$right=preg_replace('/\\\\\h*(\r?\n)/','\1',$match['right']);
						if (preg_match('/^(.+)\|\h*(\d+)$/',$right,$tmp)) {
							array_shift($tmp);
							list($right)=$tmp;
						}
						// Remove invisible characters
						$right=preg_replace('/[[:cntrl:]]/u','',$right);
						// Mark quoted strings with 0x00 whitespace
						str_getcsv(preg_replace('/(?<!\\\\)(")(.*?)\1/',"\\1\x00\\2\\1",trim($right)));
						preg_match('/^(?<child>[^:]+)?/',$child,$node);
						$custom=(strtolower($node['child']!='global'));
						$left=($custom?($node['child'].'.'):'').preg_replace('/\s\s+/','',$match['left']);
						// Set config array to hive
						call_user_func_array([$this,'set'],array_merge([$left],[$right]));
					}
				}
			}
		}
		// It's chainable :)
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
	*	Write log message to file
	*	@param   $file      string
	*	@param   $data      mixed
	*	@param   $block     bool
	*	@return  bool
	*/
	function log($data,$file,$block=false) {
		$ts=time();
		$date=new \DateTime('now',new \DateTimeZone($this->get('TZ')));
		$date->setTimestamp($ts);
		return $this->write($file,"[".$date->format('Y/m/d H:i:s')."]".
			((bool)$block?PHP_EOL:" ").$data.PHP_EOL,file_exists($file));
	}

	/**
	*	Return base url (with protocol)
	*	@param   $suffix  string|null
	*	@return  string
	*/
	function base($suffix=null) {
		$base=rtrim($this->get('PROTO').'://'.$this->get('BASE'),'/');
		return empty($suffix)?$base:$base.(string)$suffix;
	}

	/**
	*	Class autoloader
	*	@param   $class  string
	*	@return  bool
	*/
	protected function autoloader($class) {
		$class=$this->slash(ltrim($class,'\\'));
		$fn=null;
		if (is_array($loc=$this->hive['MODULES'])
		&&isset($loc[1])
		&&is_callable($loc[1]))
			list($loc,$fn)=$loc;
		foreach ($this->split($this->hive['LIB'].'|./|'.$loc) as $auto)
			if ($fn&&is_file($file=$fn($auto.$class).'.php')
			||is_file($file=$auto.$class.'.php')
			||is_file($file=$auto.strtolower($class).'.php')
			||is_file($file=strtolower($auto.$class).'.php'))
				return require($file);
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
//! Hive - functions to play around with framework variables
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
                $hive=&$this->hive;
                foreach ($keys as $key) {
                    if (!isset($hive[$key])
                    ||!is_array($hive[$key]))
						$hive[$key]=[];
                    $hive=&$hive[$key];
                }
                $hive=$val;
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
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$hive))
            	return $default;
            $hive=&$hive[$key];
        }
        return $hive;
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
                $hive=&$this->hive;
                if ($pop===true)
                    array_pop($keys);
                foreach ($keys as $key) {
                    if (!isset($hive[$key])
                    ||!is_array($hive[$key]))
                    	$hive[$key]=[];
                    $hive=&$hive[$key];
                }
                $hive[]=$val;
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
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$hive))
            	return false;
            $hive=&$hive[$key];
        }
        return true;
    }

	/**
	*	Determine if the given key exists in the provided array
	*	@param   $hive  object|array
	*	@param   $key   string
	*	@return  bool
	*/
    protected function exists($key,$hive) {
        if ($hive instanceof \ArrayAccess)
        	return isset($hive[$key]);
        return array_key_exists($key,(array)$hive);
    }

	/**
	*	Clear a values of given key or keys
	*	@param  $key  string|array
	*/
	function clear($key) {
		if (is_string($key))
			$this->set($key,[]);
		elseif (is_array($key))
			foreach ($key as $k)
				$this->clear($k,[]);
	}

	/**
	*	Erase a hive path or array of hive paths
	*	@param  $key  mixed
	*/
    function erase($key) {
        if (is_string($key)) {
            $keys=explode('.',$key);
            $hive=&$this->hive;
            $last=array_pop($keys);
            foreach ($keys as $key) {
                if (!$this->exists($key,$hive))
                	return;
                $hive=&$hive[$key];
            }
            unset($hive[$last]);
        }
        elseif (is_array($key))
            foreach ($key as $k)
            	$this->erase($k);
    }

	/**
	*	Check if given key or keys are empty
	*	@param   $key  string|array
	*	@return  bool
	*/
	function dry($key) {
		if (is_string($key))
			return empty($this->get($key));
		elseif (is_array($key))
			foreach ($key as $k)
				if (!empty($this->get($k)))
					return false;
		return true;
	}

	/**
	*	Merge a given array with the given key
	*	@param  $key  mixed
	*/
	function merge($key,$val=null) {
		if (is_array($key))
			$this->hive=array_merge($this->hive,$key);
		elseif (is_string($key)) {
			$item=(array)$this->get($key);
			$val=array_merge($item,($val instanceof \ArrayAccess||is_array($val))?$val:(array)$val);
			$this->set($key,$val);
		}
	}

	/**
	*	Return the value of given key and delete the key
	*	@param   $key      string
	*	@param   $default  mixed|null
	*	@return  mixed
	*/
	function pull($key,$default=null) {
		if (!is_string($key))
			return false;
		$val=$this->get($key,$default);
		$this->erase($key);
		return $val;
	}

	/**
	*	Push a given array to the end of the array in a given key
	*	@param   $key   string
	*	@param   $val   mixed|null
	*/
	function push($key,$val=null) {
		if (is_null($val)) {
			$this->set($key,null);
			return;
		}
		$item=$this->get($key);
		if (is_array($val)||is_null($item)) {
			$item[]=$val;
			$this->set($key,$item);
		}
	}

	/**
	*	Sort the values of a hive path or all the stored values
	*	@param   $key   string|null
	*	@return  array
	*/
    function sort($key=null) {
        if (is_string($key))
            return $this->arrsort((array)$this->get($key));
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
        elseif (is_string($key))
            return $this->recsort(null,(array)$this->get($key));
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
	*	Store a key as reference
	*	@param  $key  string
	*	@param  $val  mixed|null
	*/
    function ref($key,&$val=null) {
        $this->hive[$key]=&$val;
    }

	/**
	*	Grab all stored values in hive
	*	@return  array
	*/
    function hive() {
        return $this->hive;
    }

    /**
	*	Return string representation of PHP value
	*	@param   $key    mixed
	*	@return  string
	**/
	function serialize($key) {
		return ($this->get('SERIALIZER')=='igbinary')
			?igbinary_serialize($key):serialize($key);
	}

	/**
	*	Return PHP value derived from string
	*	@param   $key    mixed
	*	@return  string
	**/
	function unserialize($key) {
		return ($this->get('SERIALIZER')=='igbinary')
			?igbinary_unserialize($key):unserialize($key);
	}

	/**
	*	Recursively convert array to object
	*	@param   $arr    array
	*	@return  object
	*/
	function arr2obj($arr) {
	    return json_decode(json_encode((array)$arr));
	}

	/**
	*	Recursively convert object to array
	*	@param   $obj    object
	*	@return  array
	*/
	function obj2arr($obj) {
	    return json_decode(json_encode((object)$obj),true);
	}

	/**
	*	Retrieve POST data
	*	@param   $key         string|null
	*	@param   $escape      bool
	*	@return  string|null
	*/
	function post($key=null,$escape=true) {
		$eval=\Validation::instance();
		if (is_null($key)) {
			$post=[];
			if ($escape===true)
				foreach ($_POST as $k=>$v)
					$post[$k]=$eval->xss_clean([$v]);
			return ($escape===true)?$post:$_POST;
		}
		elseif (isset($_POST[$key]))
			return ($escape===true)?$eval->xss_clean([$_POST[$key]]):$_POST[$key];
		return null;
	}

    /**
    *   Retrieve COOKIE data
    *   @param   $key          string
    *   @param   $escape       bool
    *   @return  string|false
    */
    function cookie($key=null,$escape=true) {
		$eval=\Validation::instance();
		if (is_null($key)) {
			$cookie=[];
			if ($escape===true)
				foreach ($_COOKIE as $k=>$v)
					$cookie[$k]=$eval->xss_clean([$v]);
			return ($escape===true)?$cookie:$_COOKIE;
		}
		elseif (isset($_COOKIE[$key]))
			return ($escape===true)?$eval->xss_clean([$_COOKIE[$key]]):$_COOKIE[$key];
		return false;
    }

    /**
    *   Set a cookie
    *   @param   $key    string
    *   @param   $val    mixed
    *   @param   $ttl    int|null
    *   @return  string
    */
    function setcookie($key,$val,$ttl=null) {
        $ttl=is_null($ttl)?(time()+(60*60*24)):$ttl;
        setcookie($key,$val,$ttl,$this->get('TEMP'));
    }

	/**
	*	Retrieve parts of URI
	*	@param   $key         int
	*	@param   $default     string|null
	*	@return  string|null
	*/
	function segment($key,$default=null) {
		$uri=array_map('trim',preg_split('~/~',$app->get('URI'),-1,PREG_SPLIT_NO_EMPTY));
		return array_key_exists($key,$uri)?$uri[$key]:$default;
	}


//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! ArrayAccess Interface Methods
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
//! PHP Magic Methods
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
		// Turn-off default error reporting
		ini_set('display_errors',0);
		// Override default PHP exception handler
		set_exception_handler(function($obj) {
			$this->set('EXCEPTION',$obj);
			$this->abort(500,
				$obj->getMessage(),
				$obj->getFile(),
				$obj->getLine(),
				$obj->getTrace(),
				$obj->getCode()
			);
		});
		// Override default PHP error handler
		set_error_handler(function($level,$reason,$file,$line) {
			if ($level & error_reporting())
				$this->abort(500,$reason,$file,$line,null,$level);
		});
		$fw=$this;
		$base=null;
		if (is_null($fw->hive['URI']))
			$base=implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/';
        $uri=substr($_SERVER['REQUEST_URI'],strlen($base));
        if (strstr($uri,'?'))
			$uri=substr($uri,0,strpos($uri,'?'));
		$uri='/'.trim($uri,'/');
        // Get all headers
        $headers=[];
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $key=>$val) {
				$tmp=strtoupper(strtr($key,'-','_'));
				$key=strtr(ucwords(strtolower(strtr($key,'-',' '))),' ','-');
				$headers[$key]=$val;
				if (isset($_SERVER['HTTP_'.$tmp]))
					$headers[$key]=&$_SERVER['HTTP_'.$tmp];
			}
		}
		else {
			if (isset($_SERVER['CONTENT_LENGTH']))
				$headers['Content-Length']=&$_SERVER['CONTENT_LENGTH'];
			if (isset($_SERVER['CONTENT_TYPE']))
				$headers['Content-Type']=&$_SERVER['CONTENT_TYPE'];
			foreach (array_keys($_SERVER) as $key)
				if (substr($key,0,5)=='HTTP_')
					$headers[strtr(ucwords(strtolower(strtr(substr($key,5),'_',' '))),' ','-')]=&$_SERVER[$key];
		}
		// Get user-agent
		$ua=isset($headers['X-Operamini-Phone-UA'])
			?$headers['X-Operamini-Phone-UA']
			:(isset($headers['X-Skyfire-Phone'])
				?$headers['X-Skyfire-Phone']
				:(isset($headers['User-Agent'])
					?$headers['User-Agent']:''));
        // Get client ip
		$ip=isset($headers['Client-IP'])
	        ?$headers['Client-IP']
	        :(isset($headers['X-Forwarded-For'])
	            ?explode(',',$headers['X-Forwarded-For'])[0]
	            :(isset($_SERVER['REMOTE_ADDR'])
	                ?$_SERVER['REMOTE_ADDR']:''));
        // Get server protocol
		$proto=isset($_SERVER['HTTPS'])
	        &&$_SERVER['HTTPS']=='on'
	        ||isset($headers['X-Forwarded-Proto'])
	        &&$headers['X-Forwarded-Proto']=='https'?'https':'http';
		// Get request method
		// if it's a HEAD request, override it to being GET and prevent any output
		// Reference: http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        $verb=$_SERVER['REQUEST_METHOD'];
        if ($_SERVER['REQUEST_METHOD']=='HEAD') {
            ob_start();
            $verb='GET';
        } // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD']=='POST') {
            if (isset($headers['X-HTTP-Method-Override'])
			&&in_array($headers['X-HTTP-Method-Override'],['PUT','DELETE','PATCH']))
                $verb=$headers['X-HTTP-Method-Override'];
        }
		// Determine server port
		$port=80;
		if (isset($headers['X-Forwarded-Port']))
			$port=$headers['X-Forwarded-Port'];
		elseif (isset($_SERVER['SERVER_PORT']))
			$port=$_SERVER['SERVER_PORT'];
        // Determine ajax request
        $isajax=(isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest')?true:false;
		// Determine default serializer
		$serializer=extension_loaded('igbinary')?'igbinary':'default';
		// Initial values of routes variables
		$routes=['Before'=>[],'Main'=>[],'After'=>[],'Notfound'=>null];
		// Assign default value to system variables
		$fw->hive=[
			'AJAX'=>$isajax,
			'BASE'=>$_SERVER['SERVER_NAME'].$base,
			'DEBUG'=>0,
			'ERROR'=>null,
			'EXCEPTION'=>null,
			'HEADERS'=>&$headers,
			'HOST'=>$_SERVER['SERVER_NAME'],
			'IP'=>$ip,
			'LIB'=>$fw->slash(__DIR__).'/',
			'MODULES'=>null,
			'PACKAGE'=>self::PACKAGE,
			'PROTO'=>$proto,
			'PORT'=>$port,
			'ROOT'=>$_SERVER['DOCUMENT_ROOT'].$base,
			'ROUTES'=>$routes,
			'SERIALIZER'=>$serializer,
			'SYSLOG'=>false,
			'TEMP'=>'tmp/',
			'TIME'=>&$_SERVER['REQUEST_TIME_FLOAT'],
			'TZ'=>@date_default_timezone_get(),
			'UA'=>$ua,
			'UI'=>'./',
			'URI'=>$uri,
			'VERB'=>$verb,
			'VERSION'=>self::VERSION,
		];
		// Set default timezone
		date_default_timezone_set($fw->hive['TZ']);
		// Register custom class-autoloader function
		spl_autoload_register([$fw,'autoloader']);
		// Register custom shutdown function
		register_shutdown_function([$fw,'shutdown'],getcwd());
	}
}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! Preview - lightweight template compiler engine
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class Preview extends \Factory {
    protected
		// UI directory
    	$ui,
		// Template blocks
		$block,
		// Template stacks
		$stack;

	// Class constructor
    function __construct() {
        $fw=\Alit::instance();
		$this->block=[];
		$this->stack=[];
        $this->ui=str_replace('/',DS,$fw->get('ROOT').str_replace('./','',$fw->get('UI')));
    }

	/**
	*	Add file to include
	*	@param   $name   string
	*	@return  string
	*/
    protected function tpl($name) {
        return preg_replace('/\s\s+/','',$this->ui.$name);
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
		$data=empty($data)?[]:extract($data);
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
	*	@param   $overwrite  bool
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
//! Warehouse - Container for singular object instances
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
final class Warehouse {
	// Object table
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
