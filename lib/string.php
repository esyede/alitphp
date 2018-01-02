<?php
/**
 * Tiny String Manipulation Library for Alit PHP
 * @package     Alit
 * @subpackage  String
 * @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
 * @license     <https://opensource.org/licenses/MIT> The MIT License (MIT).
 * @author      Suyadi <suyadi.1992@gmail.com>
 */
// Prohibit direct access to file
defined('DS') or die('Direct file access is not allowed.');



class String extends \Factory implements \Serializable {

    const
        // Error messages
        E_PATTERN="Only string can be passed as a pattern";

    private
        // Hold string manipulation result
        $str;

    /**
     * Set initial string value
     * @param   string  $str
     * @return  object
     */
    function from($str) {
        $this->str=(string)$str;
        return $this;
    }

    /**
     * Returns internal string value
     * @return  string
     */
    function get() {
        return $this->str;
    }

    /**
     * Append the string
     * @param   string  $str
     * @return  object
     */
    function append($str) {
        $this->str.=(string)$str;
        return $this;
    }

    /**
     * Prepend the string
     * @param   string  $str
     * @return  object
     */
    function prepend($str) {
        $this->str=(string)$str.$this->str;
        return $this;
    }

    /**
     * Surround text with given string
     * @param   string  $start
     * @param   string  $end
     * @return  object
     */
    function wrap($start,$end=NULL) {
        $this->str=$start.$this->str.(is_null($end)?$start:$end);
        return $this;
    }

    /**
     * Make string lowercase
     * @return  object
     */
    function lower() {
        $this->str=strtolower($this->str);
        return $this;
    }

    /**
     * Make string uppercase
     * @return  object
     */
    function upper() {
        $this->str=strtoupper($this->str);
        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string
     * Optionally, the stripped characters can also be specified using the chars parameter.
     * Simply list all characters that you want to be stripped.
     * With .. you can specify a range of characters
     * @param   string  $str
     * @return  object
     */
    function trim($chars=NULL) {
        $this->str=is_null($chars)?trim($this->str):trim($this->str,$chars);
        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the end of a string
     * Optionally, the stripped characters can also be specified using the chars parameter.
     * Simply list all characters that you want to be stripped.
     * With .. you can specify a range of characters
     * @param   string  $str
     * @return  object
     */    
    function rtrim($chars=NULL) {
        $this->str=is_null($chars)?rtrim($this->str):rtrim($this->str,$chars);
        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the beginning of a string
     * Optionally, the stripped characters can also be specified using the chars parameter.
     * Simply list all characters that you want to be stripped.
     * With .. you can specify a range of characters
     * @param   string  $str
     * @return  object
     */
    function ltrim($chars=NULL) {
        $this->str=is_null($chars)?ltrim($this->str):ltrim($this->str,$chars);
        return $this;
    }

    /**
     * Convert special characters to HTML entities
     * @param   integer  $opt
     * @return  object
     */
    function escape($opt=ENT_QUOTES) {
        $this->str=htmlspecialchars($this->str,$opt,'UTF-8',FALSE);
        return $this;
    }

    /**
     * Perform a regular expression search and replace
     * @param   string           $pattern
     * @param   string|callable  $replace
     * @return  object
     */
    function replace($pattern,$replace) {
        if (!is_string($pattern))
            \Alit::instance()->abort(500,self::E_PATTERN);
        if (is_callable($replace)) {
            $this->str=preg_replace_callback($pattern,function($found) use($replace) {
                $args=array_map(function($item) {
                    return new \String($item);
                },$found);
                return call_user_func_array($replace,$args);
            },$this->str);
        }
        else $this->str=preg_replace($pattern,$replace,$this->str);
        return $this;
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     * @param   string|array  $search
     * @param   string|array  $replace
     * @return  object
     */
    function strreplace($search,$replace) {
        $this->str=str_replace($search,$replace,$this->str);
        return $this;
    }

    /**
     * Add one level of line-leading spaces
     * @param   integer  $spaces
     * @return  object
     */
    function indent($spaces=4) {
        $this->replace('/^/m',str_repeat(' ',$spaces));
        return $this;
    }

    /**
     * Remove one level of line-leading tabs or spaces
     * @param   integer  $spaces
     * @return  object
     */
    function outdent($spaces=4) {
        $this->replace('/^(\t|[ ]{1,'.$spaces.'})/m','');
        return $this;
    }

    /**
     * Convert tabs to spaces
     * @param  integer  $spaces
     * @return  object
     */
    function detab($spaces=4) {
        $this->replace('/(.*?)\t/',function(\String $w,\String $str) use($spaces) {
            return $str.str_repeat(' ',$spaces-$str->length()%$spaces);
        });
        return $this;
    }

    /**
     * Determine whether a variable is empty
     * @return boolean
     */
    function dry() {
        return empty($this->str);
    }

    /**
     * Determine whether a variable is number or numeric string
     * @return boolean
     */
    function isnum() {
        return is_numeric($this->str);
    }

    /**
     * Perform a regular expression match
     * @param   string      $pattern
     * @param   array|NULL  &$found
     * @return  object
     */
    function match($pattern,&$found=NULL) {
        if (!is_string($pattern))
            \Alit::instance()->abort(500,self::E_PATTERN);
        return preg_match($pattern,$this->str,$found)>0;
    }

    /**
     * Split string by a regular expression (with optional flag)
     * @param   string        $pattern
     * @param   int           $flags
     * @return  array|object
     */
    function split($pattern,$flags=PREG_SPLIT_DELIM_CAPTURE) {
        if (!is_string($pattern))
            \Alit::instance()->abort(500,self::E_PATTERN);
        return array_map(function($item) {
            return new static($item);
        },preg_split($pattern,$this->str,-1,$flags));
    }

    /**
     * Split string by a line break
     * @param   string        $pattern
     * @return  array|object
     */
    function lines($pattern='/(\r?\n)/') {
        if (!is_string($pattern))
            \Alit::instance()->abort(500,self::E_PATTERN);
        $chunk=array_chunk(preg_split($pattern,$this->str,-1,PREG_SPLIT_DELIM_CAPTURE),2);
        $res=[];
        foreach ($chunk as $v)
            $res[]=new \String(implode('',$v));
        return $res;
    }

    /**
     * Convert a string to array
     * @return  array
     */
    function chars() {
        if (strlen($this->str)==$this->length())
            return str_split($this->str);
        return preg_split('//u',$this->str,-1,PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Apply a user function to every line of the string
     * @param   callable  $callback
     * @return  object
     */
    function eachline(callable $callback) {
        $ln=$this->lines();
        foreach ($ln as $k=>$v) {
            $v=new static($v);
            $ln[$k]=(string)call_user_func_array($callback,[$v,$k]);
        }
        $this->str=implode('',$ln);
        return $this;
    }

    /**
     * Gets the length of a string
     * @return  integer
     */
    function length() {
        if (function_exists('mb_strlen'))
            return mb_strlen($this->str,'UTF-8');
        return preg_match_all("/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/",$this->str);
    }

    /**
     * Returns the number of lines
     * @return  integer
     */
    function countlines() {
        $count=count($this->lines());
        return $count;
    }

    /**
     * Find the position of the first occurrence of a substring in a string
     * @param   string   $needle
     * @param   integer  $offset
     * @return  integer
     */
    function indexof($needle,$offset=0) {
        return strpos($this->str,$needle,$offset);
    }

    /**
     * Write a string to a file
     * @param   string    $path
     * @return  boolean
     */
    function write($path) {
        $fw=\Alit::instance();
        return $fw->write($path,$this->str);
    }

    /**
     * Magic method to get string result
     * @return  string
     */
    function __toString() {
        return $this->get();
    }

    /**
     * Serialize the string
     * @return  string
     */
    function serialize() {
        return \ALit::instance()->serialize($this->str);
    }

    /**
     * Unserialize the string
     * @param   string  $str
     * @return  mixed
     */
    function unserialize($str) {
        $this->str=\ALit::instance()->unserialize($str);
    }

    /**
     * Return chunk of dummy lorem ipsum text
     * @param   integer  $count
     * @param   integer  $max
     * @param   boolean  $std
     * @return  string
     */
    function lorem($count=1,$max=20,$std=TRUE) {
        $out='';
        if ($std)
            $out='Lorem ipsum dolor sit amet, consectetur adipisicing elit, '.
                'sed do eiusmod tempor incididunt ut labore et dolore magna '.
                'aliqua.';
        $rand=explode(' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam '.
            'architecto asperiores aspernatur assumenda at atque aut beatae '.
            'blanditiis cillum commodi consequatur corporis corrupti culpa '.
            'cum cupiditate debitis delectus deleniti deserunt dicta '.
            'dignissimos distinctio dolor ducimus duis ea eaque earum eius '.
            'eligendi enim eos error esse est eum eveniet ex excepteur '.
            'exercitationem expedita explicabo facere facilis fugiat harum '.
            'hic id illum impedit in incidunt ipsa iste itaque iure iusto '.
            'laborum laudantium libero magnam maiores maxime minim minus '.
            'modi molestiae mollitia nam natus necessitatibus nemo neque '.
            'nesciunt nihil nisi nobis non nostrum nulla numquam occaecati '.
            'odio officia omnis optio pariatur perferendis perspiciatis '.
            'placeat porro possimus praesentium proident quae quia quibus '.
            'quo ratione recusandae reiciendis rem repellat reprehenderit '.
            'repudiandae rerum saepe sapiente sequi similique sint soluta '.
            'suscipit tempora tenetur totam ut ullam unde vel veniam vero '.
            'vitae voluptas');
        for ($i=0,$add=$count-(int)$std;$i<$add;$i++) {
            shuffle($rand);
            $words=array_slice($rand,0,mt_rand(3,$max));
            $out.=' '.ucfirst(implode(' ',$words)).'.';
        }
        return $out;
    }
}