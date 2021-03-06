<?php
/**
 * Core class of alitphp
 * @package     alitphp
 * @subpackage  none
 * @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
 * @license     <https://opensource.org/licenses/MIT> The MIT License (MIT).
 * @author      Suyadi <suyadi.1992@gmail.com>
 */
// Define framework constant to prohibit direct file access
defined('DS') or define('DS',DIRECTORY_SEPARATOR);



//!=============================================================================
//! Alit - The core class
//!=============================================================================
final class Alit extends \Factory implements \ArrayAccess {

    const
        // Package and version info
        PACKAGE='alitphp',
        VERSION='1.0.0',
        TAGLINE='Simple, lightweight php microframework';

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
        // System error messages
        E_UI="Can't find view file: '%s' (%s)",
        E_TYPE="Invalid route type: %s",
        E_ROUTE="Can't find route handler: '%s'",
        E_METHOD="Invalid method supplied: '%s'",
        E_FORWARD="Can't forward route handler: '%s'",
        E_REDIRECT="Can't redirect to specified url: '%s'",
        E_NOTFOUND="The page you have requested can not be found on this server.",
        E_MIDDLEWARE="Can't execute %s-route middleware in '%s'";

    const
        // Valid request methods
        METHODS='CONNECT|DELETE|GET|HEAD|OPTIONS|PATCH|POST|PUT',
        // Default directory permission
        CHMOD=0755;

    protected
        // Stores all framework variables
        $hive;

    /**
     * Set a before-route middleware and a handling function to be -
     * executed when accessed using one of the specified methods.
     * @param   string           $req
     * @param   string|callable  $fn
     * @return  $this
     */
    function before($req,$fn) {
        $this->pair($req,$fn,'before');
        // It's chainable!
        return $this;
    }

   /**
    * Set a after-route middleware and a handling function to be -
    * executed when accessed using one of the specified methods
    * @param   string           $req
    * @param   string|callable  $fn
    * @return  $this
    */
    function after($req,$fn) {
        $this->pair($req,$fn,'after');
        // It's chainable!
        return $this;
    }

   /**
    * Store a route and a handling function to be executed -
    * when accessed using one of the specified methods.
    * @param   string           $req
    * @param   string|callable  $fn
    * @return  $this
    */
    function route($req,$fn) {
        $this->pair($req,$fn,'main');
        // It's chainable!
        return $this;
    }
    
   /**
    * Pair a route and the appropriate handler function to hive
    * @param   string           $req
    * @param   string|callable  $fn
    * @param   string           $type
    * @return  $this
    */
    private function pair($req,$fn,$type) {
    	if (!in_array($type,array('before','main','after')))
    		trigger_error(sprintf(self::E_TYPE,$type));
        $req=preg_split('/ /',$this->tab2space($req),NULL,PREG_SPLIT_NO_EMPTY);
        $mtds=$this->split($req[0]);
        if ($type==='main') {
        	foreach ($mtds as $mtd) {
        		if (!in_array($mtd,$this->split(self::METHODS)))
        			trigger_error(sprintf(self::E_METHOD,$mtd));
          		$this->push('ROUTES.Main.'.$mtd,array('uri'=>$req[1],'fn'=>is_string($fn)?trim($fn):$fn));
          	}
        }
        else {
        	foreach ($mtds as $mtd)
        		$this->push('ROUTES.'.ucfirst($type).'.'.$mtd,array('uri'=>$req[1],'fn'=>is_string($fn)?trim($fn):$fn));
        }
    }
    
   /**
    * Set the page not found (404) handling function
    * @param   string|callable  $fn
    * @return  $this
    */
    function notfound($fn=NULL) {
        $this->set('ROUTES.Notfound',is_string($fn)?trim($fn):$fn);
        if (is_callable($fn)) {
            call_user_func($fn);
            exit();
        }
        // It's chainable!
        return $this;
    }

   /**
    * Run the framework: Loop all defined before and/or after middleware's and the main routes, -
    * and then execute the handler function if a route was found.
    * @return  boolean
    */
    function run() {
        // Send some headers
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        if (!empty($this->hive['PACKAGE']))
            header('X-Powered-By: '.$this->get('PACKAGE'));
        if (!empty($this->hive['XFRAME']))
            header('X-Frame-Options: '.$this->get('XFRAME'));
        // Get routes info
        $before=$this->get('ROUTES.Before');
        $main=$this->get('ROUTES.Main');
        $after=$this->get('ROUTES.After');
        $notfound=$this->get('ROUTES.Notfound');
        $mtd=$this->get('METHOD');
        // Execute before-route middleware if any
        if (isset($before[$mtd]))
            $this->execute($before[$mtd]);
        $executed=0;
        // Execute main routes
        if (isset($main[$mtd]))
            $executed=$this->execute($main[$mtd],TRUE);
        // No route specified or failed to execute routes. Give 404 error
        if ($executed===0) {
            // Default 404 handler
            if (empty($notfound))
                $this->abort(404,self::E_NOTFOUND);
            // Custom 404 hadler
            else {
                // 404 handler is a callable function
                if (is_callable($notfound)) {
                    call_user_func($notfound);
                    exit();
                }
                // 404 handler is a 'Class@method' string
                if (is_string($notfound)&&stripos($notfound,'@')!==FALSE) {
                    list($controller,$fn)=explode('@',$notfound);
                    if (class_exists($controller))
                        call_user_func(array(new $controller,$fn));
                    // Class doesn't exists
                    else trigger_error(sprintf(self::E_ROUTE,$controller.'@'.$fn));
                }
                // Invalid 404 handler
                else user_error(sprintf(self::E_ROUTE,$notfound));
            }
        }
        // Execute after-route middleware if any
        else if (isset($after[$mtd]))
            $this->execute($after[$mtd]);
        // Clean output buffer if it's a HEAD request
        if ($mtd=='HEAD')
            ob_end_clean();
        return ($executed>0);
    }

   /**
    * Execute a set of routes: if a route is found, invoke the relating handling function
    * @param   array    $routes
    * @param   boolean  $break
    * @return  integer
    */
    private function execute($routes,$break=FALSE) {
        $mtd=$this->get('METHOD');
        $uri=$this->get('URI');
        $executed=0;
        foreach ($routes as $route) {
            if (preg_match_all('~^'.$route['uri'].'$~',$uri,$matches,PREG_OFFSET_CAPTURE)) {
                $matches=array_slice($matches,1);
                $args=array_map(function($match,$index) use($matches) {
                    if (isset($matches[$index+1])
                    &&isset($matches[$index+1][0])
                    &&is_array($matches[$index+1][0]))
                        return trim(substr($match[0][0],0,$matches[$index+1][0][1]-$match[0][1]),'/');
                    else return (isset($match[0][0])?trim($match[0][0],'/'):NULL);
                },$matches,array_keys($matches));
                // Execute directly if route handler is a callable function
                if (is_callable($route['fn']))
                    call_user_func_array($route['fn'],$args);
                // But, if it's a string
                elseif (stripos($route['fn'],'@')!==FALSE) {
                    // Find the appropriate class and method
                    list($controller,$fn)=explode('@',$route['fn']);
                    // Check existence of the class
                    if (class_exists($controller)) {
                        $class=new $controller;
                        // Check existence of before-route middleware first
                        if (method_exists($class,'before')) {
                            // Assign before-route middleware info to hive
                            $this->push('ROUTES.Before.'.$mtd,array('uri'=>$uri,'fn'=>$controller.'@before'));
                            // Execute before-route middleware inside controller class
                            if (call_user_func(array($class,'before'))===FALSE)
                                trigger_error(sprintf(self::E_MIDDLEWARE,'before',$controller.'@'.$fn));
                        }
                        // Assign after-route middleware info to hive if exists
                        if (($after=method_exists($class,'after')))
                            $this->push('ROUTES.After.'.$mtd,array('uri'=>$uri,'fn'=>$controller.'@after'));
                        // Execute main route handler
                        if (call_user_func_array(array($class,$fn),$args)===FALSE)
                            if (forward_static_call_array(array($controller,$fn),$args)===FALSE)
                                trigger_error(sprintf(self::E_FORWARD,$route['fn']));
                        // If after-route middleware exists
                        if ($after===TRUE)
                            // Execute after-route middleware inside controller class
                            if (call_user_func(array($class,'after'))===FALSE)
                                trigger_error(sprintf(self::E_MIDDLEWARE,'after',$controller.'@'.$fn));
                    }
                    // Class doesn't exists
                    else trigger_error(sprintf(self::E_ROUTE,$controller.'@'.$fn));
                }
                // Invalid route handler detected!
                else trigger_error(sprintf(self::E_ROUTE,$route['fn']));
                $executed++;
                if ($break===TRUE)
                    break;
            }
        }
        return $executed;
    }

   /**
    * Trigger error message
    * @param   integer      $code
    * @param   string|NULL  $reason
    * @param   string|NULL  $file
    * @param   string|NULL  $line
    * @param   mixed|NULL   $trace
    * @param   integer      $level
    * @return  void
    */
    function abort($code,$reason=NULL,$file=NULL,$line=NULL,$trace=NULL,$level=0) {
        if ($code) {
            $status=@constant('self::HTTP_'.$code);
            error_log($code.' '.$status);
            $trace=$this->backtrace($trace);
            $logs=explode("\n",$trace);
            foreach ($logs as $log)
                if ($log)
                    error_log($log);
            $this->set('ERROR',array(
                'status'=>$status,
                'code'=>$code,
                'file'=>$file,
                'line'=>$line,
                'reason'=>$reason,
                'trace'=>$trace,
                'level'=>$level
            ));
            // Write error message to file if 'LOG' directive is activated
            $debug='[ERROR]'.PHP_EOL;
            $debug.='---------------------------------------------'.PHP_EOL;
            if ($this->get('LOG')) {
                $err=$this->get('ERROR');
                unset($err['level'],$err['trace']);
                foreach ($err as $k=>$v)
                    $debug.=ucfirst(((strlen($k)<=8)?$k.
                        str_repeat(' ',(8-strlen($k))):$k)).': '.$v.PHP_EOL;
                $this->log($debug,$this->get('TMP').'alit.log');
            }
            ob_start();
            if (!headers_sent()) {
                header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$status);
                if (!empty($this->hive['PACKAGE']))
                    header('X-Powered-By: '.$this->get('PACKAGE'));
            }
            if ($this->get('ISAJAX')) {
                if (!headers_sent())
                    header('Content-Type: application/json');
                echo json_encode(array('status'=>500,'data'=>$this->get('ERROR')));
            }
            else {
                echo "<!DOCTYPE html>\n<html>"
                    ."\n\t<head>\n\t\t<title>$code $status</title>\n\t</head>"
                    ."\n\t<body style=\"color: #555\">\n"
                    ."\t\t<h1>$code $status</h1>\n"
                    ."\t\t<p>Error: $reason</p>\n";
                echo (!empty($file)&&!empty($line))
                    ?"\t\t<pre>$file:<font color=\"red\">$line</font></pre><br>\n":"";
                // Show debug backtrace if 'DEBUG' directive is activated
                if ($this->get('DEBUG')>0)
                    echo "\t\t<b>Back Trace:</b><br>\n\t\t<pre>$trace</pre>\n";
                echo "\t</body>\n</html>";
            }
            ob_end_flush();
            die(1);
        }
    }

   /**
    * Return filtered stack trace as a formatted string or array
    * @param   array|NULL    $trace
    * @param   boolean       $format
    * @return  string|array   
    */
    function backtrace(array $trace=NULL,$format=TRUE) {
        if (!$trace) {
            $trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $stack=$trace[0];
            if (isset($stack['file'])&&$stack['file']==__FILE__)
                array_shift($trace);
        }
        $debug=$this->get('DEBUG');
        $trace=array_filter($trace,function($stack) use($debug) {
            return isset($stack['file'])
                &&($debug>1||($stack['file']!=__FILE__||$debug)&&(empty($stack['function'])
                ||!preg_match('~^(?:(?:trigger|user)_error|__call|call_user_func)~',$stack['function'])));
        });
        if ($format===FALSE)
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
            $src=$this->fwslash(str_replace($_SERVER['DOCUMENT_ROOT'].'/','',$stack['file']))
                .':<font color="red">'.$stack['line'].'</font>';
            $out.='['.$src.'] '.$line.PHP_EOL;
        }
        return $out;
    }

   /**
    * Custom shutdown function for error handling purpose
    * @param   string  $cwd
    * @return  void
    */
    function shutdown($cwd) {
        chdir($cwd);
        $active=(php_sapi_name()!=='cli')?(version_compare(PHP_VERSION,'5.4.0','>=')
            ?(session_status()===PHP_SESSION_ACTIVE):(session_id()!=='')):FALSE;
        if (!($error=error_get_last())&&$active)
            session_commit();
        $errors=array(E_ERROR,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING);
        if ($error&&in_array($error['type'],$errors))
            $this->abort(500,$error['message'],$error['file'],$error['line']);
    }

   /**
    * Redirect to specified URI
    * @param   string   $url
    * @param   boolean  $permanent
    * @return  void
    */
    function redirect($url=NULL,$permanent=TRUE) {
        $url=filter_var($url,FILTER_SANITIZE_URL);
        $url=is_null($url)?$this->baseurl():(filter_var($url,FILTER_VALIDATE_URL)?$url:$this->baseurl().$url);
        try {
            ob_start();
            header('Location: '.$url,TRUE,$permanent?302:307);
            ob_end_flush();
            exit();
        }
        catch (\Exception $ex) {
            trigger_error(sprintf(self::E_REDIRECT,$url));
        }
    }

   /**
    * Render view using native PHP template
    * @param   string  $name
    * @param   array   $data
    * @return  void
    */
    function render($name,$data=NULL) {
        $file=$this->get('ROOT').
            str_replace(array('./','.'),array('','/'),$this->get('UI').$name).'.ui.php';
        if (!file_exists($file))
            trigger_error(sprintf(self::E_UI,$name,$file));
        ob_start();
        if (is_array($data))
            extract($data);
        require $file;
        echo trim(ob_get_clean());
    }

   /**
    * Parse ini-stryle config file and store to hive
    * @param   string|array  $source
    * @return  object
    */
    function config($source) {
        // You can pass a comma-, semicolon-, or pipe-separated string of config file at once!
        // Eg.: user.ini|database.cfg,routes.conf;test.cfg
        if (is_string($source))
            $source=$this->split($source);
        foreach ($source as $file) {
            preg_match_all(
                '~(?<=^|\n)(?:\[(?<flag>.+?)\]|(?<key>[^\h\r\n;].*?)\h*=\h*'
                .'(?<val>(?:\\\\\h*\r?\n|.+?)*))(?=\r?\n|$)~',
                $this->read($file),$matches,PREG_SET_ORDER
            );
            if ($matches) {
                $flag='global';
                $fn=array();
                foreach ($matches as $match) {
                    if ($match['flag']) {
                        $flag=$match['flag'];
                        if (preg_match('~^(?!(?:global|config|route)\b)((?:\.?\w)+)~i',$flag,$child)
                        &&!$this->exists($child[0],$this->hive()))
                            $this->set($child[0],NULL);
                        preg_match('~^(config|route)\b|^((?:\.?\w)+)\s*\>\s*(.*)~i',$flag,$fn);
                        continue;
                    }
                    if (!empty($fn))
                        call_user_func_array(
                            // Call method based on config flag (route/config)
                            array($this,$fn[1]),
                            // Array to be passed in
                            array_merge(array($match['key']),str_getcsv($match['val']))
                        );
                    else {
                        $val=preg_replace('~\\\\\h*(\r?\n)~','\1',$match['val']);
                        if (preg_match('~^(.+)\|\h*(\d+)$~',$val,$tmp)) {
                            array_shift($tmp);
                            list($val)=$tmp;
                        }
                        // Remove invisible characters
                        $val=$this->tab2space(preg_replace('~[[:cntrl:]]~u','',$val));
                        // Mark quoted strings with 0x00 whitespace
                        str_getcsv(preg_replace('~(?<!\\\\)(")(.*?)\1~',"\\1\x00\\2\\1",trim($val)));
                        preg_match('~^(?<flag>[^:]+)?~',$flag,$node);
                        $custom=(strtolower($node['flag']!='global'));
                        $key=($custom?($node['flag'].'.'):'').preg_replace('/\s\s+/','',$match['key']);
                        // Set config array to hive
                        call_user_func_array(array($this,'set'),array_merge(array($key),array($val)));
                    }
                }
            }
        }
        // It's also chainable!
        return $this;
    }

   /**
    * Read from file (with option to apply Unix LF as standard line ending)
    * @param   string   $file
    * @param   boolean  $lf
    * @return  string
    */
    function read($file,$lf=FALSE) {
        $file=file_get_contents($file);
        return $lf?preg_replace('~\r\n|\r~',"\n",$file):$file;
    }

    /**
     * Write to file (or append if $append is TRUE)
     * @param   string   $file
     * @param   mixed    $data
     * @param   boolean  $append
     * @return  boolean
     */
    function write($file,$data,$append=FALSE) {
        return file_put_contents($file,$data,LOCK_EX|($append?FILE_APPEND:0));
    }

   /**
    * Write log message to file
    * @param   string   $data
    * @param   string   $file
    * @param   boolean  $lf
    * @return  boolean
    */
    function log($data,$file,$lf=FALSE) {
        $ts=time();
        $date=new \DateTime('now',new \DateTimeZone($this->get('TZ')));
        $date->setTimestamp($ts);
        return $this->write($file,'['.$date->format('Y/m/d H:i:s').']'.
            ($lf?PHP_EOL:' ').$data.PHP_EOL,file_exists($file));
    }

   /**
    * Return link to base url
    * @param   string|NULL  $suffix
    * @return  string
    */
    function baseurl($suffix=NULL) {
        $base=rtrim($this->get('PROTOCOL').'://'.$this->get('BASE'),'/');
        return empty($suffix)?$base:$base.(is_string($suffix)?$suffix:NULL);
    }

   /**
    * Class autoloader
    * @param   string   $class
    * @return  boolean
    */
    protected function autoload($class) {
        $class=$this->fwslash(ltrim($class,'\\'));
        $fn=NULL;
        if (is_array($path=$this->hive['AUTOLOAD'])
        &&isset($path[1])&&is_callable($path[1]))
            list($path,$fn)=$path;
        $dirs=$this->split($this->hive['FW'].'|./|'.$path);
        foreach ($dirs as $dir)
            if ($fn&&is_file($file=$fn($dir.$class).'.php')
            ||is_file($file=$dir.$class.'.php')
            ||is_file($file=$dir.strtolower($class).'.php')
            ||is_file($file=strtolower($dir.$class).'.php'))
                return require($file);
    }

    /**
     * Return string representation of PHP value
     * @param   mixed   $data
     * @return  string
     */
    function serialize($data) {
        return ($this->get('SERIALIZER')=='igbinary')
            ?igbinary_serialize($data):serialize($data);
    }

    /**
     * Return PHP value derived from string
     * @param   string  $data
     * @return  mixed
     */
    function unserialize($data) {
        return ($this->get('SERIALIZER')=='igbinary')
            ?igbinary_unserialize($data):unserialize($data);
    }

   /**
    * Recursively convert array to object
    * @param   array         $arr
    * @return  object|FALSE
    */
    function arr2obj($arr) {
        return is_array($arr)?json_decode(json_encode($arr,JSON_FORCE_OBJECT),FALSE):FALSE;
    }

   /**
    * Recursively convert object to array
    * @param   object       $obj
    * @return  array|FALSE
    */
    function obj2arr($obj) {
        return is_object($obj)?json_decode(json_encode($obj),TRUE):FALSE;
    }

    /**
     * Replace tab to space in given string
     * @param   string        $var
     * @return  string|FALSE
     */
    function tab2space($str) {
        return is_string($str)?preg_replace('/\s\s+/',' ',$str):FALSE;
    }

    /**
     * Perform XSS sanitation
     * @param   array  $data
     * @return  array
     */
    function sanitize(array $data) {
        foreach ($data as $k=>$v) {
            $data[$k]=str_replace(array('&amp;','&lt;','&gt;'),array('&amp;amp;','&amp;lt;','&amp;gt;'),$v);
            $data[$k]=preg_replace('~(&#*\w+)[\x00-\x20]+;~u','$1;',$v);
            $data[$k]=preg_replace('~(&#x*[0-9A-F]+);*~iu','$1;',$v);
            $data[$k]=html_entity_decode($v,ENT_COMPAT,'UTF-8');
            $data[$k]=preg_replace('~(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>~iu','$1>',$k);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)'
                .'[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s'
                .'[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t'
                .'[\x00-\x20]*:~iu','$1=$2nojavascript...',$v);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v'
                .'[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i'
                .'[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:~iu','$1=$2novbscript...',$v);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*'
                .'-moz-binding[\x00-\x20]*:~u','$1=$2nomozbinding...',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'
                .'*.*?expression[\x00-\x20]*\([^>]*+>~i','$1>',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'
                .'*.*?behaviour[\x00-\x20]*\([^>]*+>~i','$1>',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'
                .'*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p'
                .'[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>~iu','$1>',$v);
            $data[$k]=preg_replace('~</*\w+:\w[^>]*+>~i','',$v);
            do {
                $old=$data[$k];
                $data[$k]=preg_replace('~</*(?:applet|b(?:ase|gsound|link)|embed'
                    .'|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)'
                    .'|title|xml)[^>]*+>~i','',$data[$k]);
            } while ($old!==$data[$k]);
        }
        return filter_var($data[$k],FILTER_SANITIZE_STRING);
    }

   /**
    * Retrieve POST data
    * @param   string|NULL   $key
    * @param   boolean       $sanitize
    * @return  string|FALSE
    */
    function post($key=NULL,$sanitize=TRUE) {
        if (is_null($key)) {
            $out=array();
            if ($sanitize===TRUE)
                foreach ($_POST as $k=>$v)
                    $out[$k]=$this->sanitize(array($v));
            return ($sanitize===TRUE)?$out:$_POST;
        }
        elseif (isset($_POST[$key]))
            return ($sanitize===TRUE)?$this->sanitize(array($_POST[$key])):$_POST[$key];
        return FALSE;
    }

    /**
    * Retrieve FILES data
    * @param   string|NULL   $key
    * @param   boolean       $sanitize
    * @return  string|FALSE
    */
    function files($key=NULL,$sanitize=TRUE) {
        if (is_null($key)) {
            $out=array();
            if ($sanitize===TRUE)
                foreach ($_FILES as $k=>$v)
                    $out[$k]=$this->sanitize(array($v));
            return ($sanitize===TRUE)?$out:$_FILES;
        }
        elseif (isset($_FILES[$key]))
            return ($sanitize===TRUE)?$this->sanitize(array($_FILES[$key])):$_FILES[$key];
        return FALSE;
    }

   /**
    * Retrieve COOKIE data
    * @param   string|NULL   $key
    * @param   boolean       $sanitize
    * @return  string|FALSE
    */
    function cookie($key=NULL,$sanitize=TRUE) {
        if (is_null($key)) {
            $out=array();
            if ($sanitize===TRUE)
                foreach ($_COOKIE as $k=>$v)
                    $out[$k]=$this->sanitize(array($v));
            return ($sanitize===TRUE)?$out:$_COOKIE;
        }
        elseif (isset($_COOKIE[$key]))
            return ($sanitize===TRUE)?$this->sanitize(array($_COOKIE[$key])):$_COOKIE[$key];
        return FALSE;
    }

   /**
    * Set a cookie
    * @param   string   $key
    * @param   mixed    $val
    * @param   integer  $expiry
    * @return  boolran
    */
    function setcookie($key,$val,$expiry=NULL) {
        return setcookie($key,$val,(empty($expiry)?(time()+(60*60*24)):$expiry),$this->get('TMP'));
    }

   /**
    * Return a segment of URI
    * @param   integer      $key
    * @param   string|NULL  $default
    * @return  string
    */
    function segment($key,$default=NULL) {
        $uri=array_map('trim',preg_split('~/~',$app->get('URI'),NULL,PREG_SPLIT_NO_EMPTY));
        return array_key_exists($key,$uri)?$uri[$key]:$default;
    }

   /**
    * Replace backslash with forward slash
    * @param   string  $str
    * @return  string
    */
    function fwslash($str) {
        return $str?strtr($str,'\\','/'):$str;
    }

   /**
    * Split comma-, semicolon-, or pipe-separated string
    * @param   string   $str
    * @param   boolean  $noempty
    * @return  array
    */
    function split($str,$noempty=TRUE) {
        return array_map('trim',preg_split('/[,;|]/',$str,NULL,(bool)$noempty?PREG_SPLIT_NO_EMPTY:0));
    }


    //!=============================================================================
    //! Hive - Methods to play around with framework variables
    //!=============================================================================

   /**
    * Set a value to hive or rewrite it if already exist
    * @param   string|array  $key
    * @param   mixed         $val
    * @return  void
    */
    function set($key,$val=NULL) {
        if (is_string($key)) {
            if (is_array($val)&&!empty($val))
                foreach ($val as $k=>$v)
                    $this->set($key.'.'.$k,$v);
            else {
                $keys=preg_split('/\./',(string)$key,NULL,PREG_SPLIT_NO_EMPTY);
                $hive=&$this->hive;
                foreach ($keys as $key) {
                    if (!isset($hive[$key])||!is_array($hive[$key]))
                        $hive[$key]=array();
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
    * Get a value from hive or default value if path doesn't exist
    * @param   string      $key
    * @param   mixed|NULL  $default
    * @return  mixed|NULL
    */
    function get($key,$default=NULL) {
        $keys=preg_split('/\./',(string)$key,NULL,PREG_SPLIT_NO_EMPTY);
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$hive))
                return $default;
            $hive=&$hive[$key];
        }
        return $hive;
    }

   /**
    * Add a value or array of value to hive
    * $pop is a helper to pop out the last key if the value is an array
    * @param   string|array  $key
    * @param   mixed         $val
    * @param   boolean       $pop
    * @return  void
    */
    function add($key,$val=NULL,$pop=FALSE) {
        if (is_string($key)) {
            if (is_array($val))
                foreach ($val as $k=>$v)
                    $this->add($key.'.'.$k,$v,TRUE);
            else {
                $keys=preg_split('/\./',(string)$key,NULL,PREG_SPLIT_NO_EMPTY);
                $hive=&$this->hive;
                if ($pop===TRUE)
                    array_pop($keys);
                foreach ($keys as $key) {
                    if (!isset($hive[$key])||!is_array($hive[$key]))
                        $hive[$key]=array();
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
    * Check if key exists in hive
    * @param   string   $key
    * @return  boolean
    */
    function has($key) {
        $keys=preg_split('/\./',(string)$key,NULL,PREG_SPLIT_NO_EMPTY);
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($key,$hive))
                return FALSE;
            $hive=&$hive[$key];
        }
        return TRUE;
    }

   /**
    * Determine if the given key exists in the provided array
    * @param   string   $key
    * @param   array    $arr
    * @return  boolean
    */
    function exists($key,$arr) {
        if ($arr instanceof \ArrayAccess)
            return isset($arr[$key]);
        return array_key_exists($key,(array)$arr);
    }

   /**
    * Clear a values of given hive key or keys
    * @param   string|array  $key
    * @return  void
    */
    function clear($key) {
        if (is_string($key))
            $this->set($key,array());
        elseif (is_array($key))
            foreach ($key as $k)
                $this->clear($k);
    }

   /**
    * Erase a hive path or array of hive paths
    * @param   string|array  $key
    * @return  void
    */
    function erase($key) {
        if (is_string($key)) {
            $keys=preg_split('/\./',(string)$key,NULL,PREG_SPLIT_NO_EMPTY);
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
    * Check if given key or keys are empty
    * @param   string|array  $key
    * @return  boolean
    */
    function dry($key) {
        if (is_string($key))
            return empty($this->hive[$key]);
        elseif (is_array($key))
            foreach ($key as $k)
                if (!empty($this->hive[$k]))
                    return FALSE;
        return TRUE;
    }

   /**
    * Merge a given array with the given key
    * @param   string|array  $key
    * @param   mixed|NULL    $val
    * @return  void
    */
    function merge($key,$val=NULL) {
        if (is_array($key))
            $this->hive=array_merge($this->hive,$key);
        elseif (is_string($key)) {
            $item=(array)$this->get($key);
            $val=array_merge($item,($val instanceof \ArrayAccess||is_array($val))?$val:(array)$val);
            $this->set($key,$val);
        }
    }

   /**
    * Return the value of given key and delete the actual key
    * @param   string       $key
    * @param   string|NULL  $default
    * @return  mixed
    */
    function pull($key,$default=NULL) {
        if (!is_string($key))
            return FALSE;
        $val=$this->get($key,$default);
        $this->erase($key);
        return $val;
    }

   /**
    * Push a given array to the end of the array in a given key
    * @param   string      $key
    * @param   mixed|NULL  $val
    * @return  void
    */
    function push($key,$val=NULL) {
        if (is_null($val)) {
            $this->set($key,NULL);
            return;
        }
        $item=$this->get($key);
        if (is_array($val)||is_null($item)) {
            $item[]=$val;
            $this->set($key,$item);
        }
    }

   /**
    * Sort the values of a hive path or the entire hive
    * @param   string|NULL  $key
    * @return  array
    */
    function sort($key=NULL) {
        if (is_string($key)) 
            return is_null($arr=$this->get($key))?NULL:$this->arrsort($arr);
        elseif (is_null($key))
            return $this->arrsort($this->hive);
    }

   /**
    * Recursively sort the values of a hive path or all the stored values
    * @param   string|NULL  $key
    * @param   array|NULL   $arr
    * @return  array
    */
    function recsort($key=NULL,$arr=NULL) {
        if (is_array($arr)) {
            foreach ($arr as &$val)
                if (is_array($val))
                    $val=$this->recsort(NULL,$val);
            return $this->arrsort($arr);
        }
        elseif (is_string($key))
            return $this->recsort(NULL,(array)$this->get($key));
        elseif (is_null($key))
            return $this->recsort(NULL,$this->hive);
    }

   /**
    * Sort the given array
    * @param   array  $arr
    * @return  array
    */
    function arrsort($arr) {
        $this->isassoc($arr)?ksort($arr):sort($arr);
        return $arr;
    }

   /**
    * Determine whether the given value is array accessible
    * @param   array    $arr
    * @return  boolean
    */
    function accessible($arr) {
        return is_array($arr)||$arr instanceof \ArrayAccess;
    }

   /**
    * Determine if an array is associative
    * @param   array|NULL  $arr
    * @return  boolean
    */
    function isassoc($arr=NULL) {
        $keys=is_array($arr)?array_keys($arr):array_keys($this->hive);
        return array_keys($keys)!==$keys;
    }

   /**
    * Store a key as reference
    * @param   string  $key
    * @param   mixed   &$val
    * @return  void
    */
    function ref($key,&$val=NULL) {
        $this->hive[$key]=&$val;
    }

   /**
    * Get all stored values in hive
    * @return  array
    */
    function hive() {
        return $this->hive;
    }


    //!=============================================================================
    //! ArrayAccess Interface Methods
    //!=============================================================================
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


    //!=============================================================================
    //! PHP Magic Methods
    //!=============================================================================
    function __set($key,$val=NULL) {
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

    // Prohibit cloning of this class
    private function __clone() {}
    // Prohibit unserialization of this class
    private function __wakeup() {}

    // Class constructor
    public function __construct() {
        // Set default charset
        ini_set('default_charset',$charset='UTF-8');
        if (extension_loaded('mbstring'))
            mb_internal_encoding($charset);
        // Turn-off default error reporting
        ini_set('display_errors',0);
        // Deprecated directives as of PHP 5.4+
        @ini_set('magic_quotes_gpc',0);
        @ini_set('register_globals',0);
        $fw=$this;
        // Override default PHP exception handler
        set_exception_handler(function($ex) use($fw) {
            $fw->set('EXCEPTION',$ex);
            $fw->abort(500,
                $ex->getMessage(),
                $ex->getFile(),
                $ex->getLine(),
                $ex->getTrace(),
                $ex->getCode()
            );
        });
        // Override default PHP error handler
        set_error_handler(function($level,$reason,$file,$line,$context) use($fw) {
            if ($level & error_reporting())
                $fw->abort(500,$reason,$file,$line,$context,$level);
        });
        // Determine base url
        $base=NULL;
        if (empty($fw->hive['URI']))
            $base=implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/';
        // Generate 64bit/base36 hash seed
        $seed=$_SERVER['SERVER_NAME'].$base;
        $seed=str_pad(base_convert(substr(sha1($seed),-16),16,36),11,'0',STR_PAD_LEFT);
        // Determine current URI
        $uri=substr($_SERVER['REQUEST_URI'],strlen($base));
        $uri='/'.trim((strstr($uri,'?')?substr($uri,0,strpos($uri,'?')):$uri),'/');
        // Get all headers
        $headers=array();
        if (function_exists('getallheaders')) {
        	$all=getallheaders();
            foreach ($all as $key=>$val) {
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
            $keys=array_keys($_SERVER);
            foreach ($keys as $key)
                if (substr($key,0,5)=='HTTP_') {
                    $prefix=strtr(ucwords(strtolower(strtr(substr($key,5),'_',' '))),' ','-');
                    $headers[$prefix]=&$_SERVER[$key];
                }
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
		    	?($ip=explode(',',$headers['X-Forwarded-For'])&&$ip=$ip[0])
		        :(isset($_SERVER['REMOTE_ADDR'])
		            ?$_SERVER['REMOTE_ADDR']:''));
        // Get server protocol
        $protocol=isset($_SERVER['HTTPS'])
            &&$_SERVER['HTTPS']=='on'
            ||isset($headers['X-Forwarded-Proto'])
            &&$headers['X-Forwarded-Proto']=='https'?'https':'http';
        // Get request method
        // if it's a HEAD request, override it to being GET and prevent any output
        // Reference: http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        $method=$_SERVER['REQUEST_METHOD'];
        if ($method=='HEAD') {
            ob_start();
            $method='GET';
        }
        // If it's a POST request, check for a method override header
        elseif ($method=='POST') {
            if (isset($headers['X-HTTP-Method-Override'])
            &&in_array($headers['X-HTTP-Method-Override'],array('PUT','DELETE','PATCH')))
                $method=$headers['X-HTTP-Method-Override'];
        }
        // Determine server port
        $port=isset($headers['X-Forwarded-Port'])
            ?$headers['X-Forwarded-Port']
            :(isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80);
        // Determine ajax request
        $isajax=(isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            &&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest');
        // Determine default serializer
        $serializer=extension_loaded('igbinary')?'igbinary':'default';
        // Initial values of route variables
        $routes=array('Before'=>array(),'Main'=>array(),'After'=>array(),'Notfound'=>NULL);
        // Assign default value to system variables
        $fw->hive=array(
            'AUTOLOAD'=>NULL,
            'BASE'=>$_SERVER['SERVER_NAME'].$base,
            'CACHE'=>FALSE,
            'DEBUG'=>0,
            'ERROR'=>NULL,
            'EXCEPTION'=>NULL,
            'FW'=>$fw->fwslash(__DIR__).'/',
            'HEADERS'=>&$headers,
            'HOST'=>$_SERVER['SERVER_NAME'],
            'IP'=>$ip,
            'ISAJAX'=>$isajax,
            'LOG'=>FALSE,
            'METHOD'=>$method,
            'PACKAGE'=>self::PACKAGE,
            'PROTOCOL'=>$protocol,
            'PORT'=>$port,
            'ROOT'=>$_SERVER['DOCUMENT_ROOT'].$base,
            'ROUTES'=>$routes,
            'SEED'=>$seed,
            'SERIALIZER'=>$serializer,
            'TAGLINE'=>self::TAGLINE,
            'TIME'=>&$_SERVER['REQUEST_TIME_FLOAT'],
            'TMP'=>'tmp/',
            'TZ'=>@date_default_timezone_get(),
            'UA'=>$ua,
            'UI'=>'./',
            'URI'=>$uri,
            'VERSION'=>self::VERSION,
            'XFRAME'=>'SAMEORIGIN'
        );
        // Set default timezone
        date_default_timezone_set($fw->hive['TZ']);
        // Register custom class-autoloader function
        spl_autoload_register(array($fw,'autoload'));
        // Register custom shutdown function
        register_shutdown_function(array($fw,'shutdown'),getcwd());
    }
}

//!=============================================================================
//! Factory - A factory class for single-instance objects
//!=============================================================================
abstract class Factory {

   /**
    * Return class instance
    * @return  static
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

//!=============================================================================
//! Warehouse - Container for singular object instances
//!=============================================================================
final class Warehouse {
    
    private static
        // Object table
        $table;

   /**
    * Return TRUE if object exists in table
    * @param   string   $key
    * @return  boolean
    */
    static function exists($key) {
        return isset(self::$table[$key]);
    }

   /**
    * Add object to table
    * @param  string   $key
    * @param  object   $obj
    * @return boolean
    */
    static function set($key,$obj) {
        return self::$table[$key]=$obj;
    }

   /**
    * Retrieve object from table
    * @param   string  $key
    * @return  object
    */
    static function get($key) {
        return self::$table[$key];
    }

   /**
    * Delete object from table
    * @param   string  $key
    * @return  void
    */
    static function clear($key) {
        self::$table[$key]=NULL;
        unset(self::$table[$key]);
    }

    // Prohibit cloning of this class
    private function __clone() {}
    // Prohibit instantiation of this class
    private function __construct() {}
}
// Return framework instance on file inclusion
return \Alit::instance();