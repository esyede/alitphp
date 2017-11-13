<?php
/**
*   Tiny string manipulation library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.String
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('DS')) die('Direct file access is not allowed.');



class String extends \Factory implements \Serializable {

    private
        // Hold string manipulation result
        $str;

    /**
    *   Set initial string value
    *   @param  $str  string
    */
    function from($str) {
        $this->str=(string)$str;
        return $this;
    }

    /**
    *   Returns internal string value
    *   @return  string
    */
    function get() {
        return $this->str;
    }

    /**
    *   Append the string
    *   @param  $str  string
    */
    function append($str) {
        $this->str.=(string)$str;
        return $this;
    }

    /**
    *   Prepend the string
    *   @param  $str  string
    */
    function prepend($str) {
        $this->str=(string)$str.$this->str;
        return $this;
    }

    /**
    *   Surround text with given string
    *   @param  $start  string
    *   @param  $end    string
    */
    function wrap($start,$end=null) {
        $this->str=$start.$this->str.(is_null($end)?$start:$end);
        return $this;
    }

    /**
    *   Make a string lowercase
    */
    function lower() {
        $this->str=strtolower($this->str);
        return $this;
    }

    /**
    * Make a string uppercase
    */
    function upper() {
        $this->str=strtoupper($this->str);
        return $this;
    }

    /**
    *   Strip whitespace (or other characters) from the beginning and end of a string
    *   Optionally, the stripped characters can also be specified using the chars parameter.
    *   Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
    *   @param  $chars  string
    */
    function trim($chars=null) {
        $this->str=is_null($chars)?trim($this->str):trim($this->str,$chars);
        return $this;
    }

    /**
    *   Strip whitespace (or other characters) from the end of a string
    *   You can also specify the characters you want to strip, by means of the chars parameter.
    *   Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
    *   @param $chars  string
    */
    function rtrim($chars=null) {
        $this->str=is_null($chars)?rtrim($this->str):rtrim($this->str,$chars);
        return $this;
    }

    /**
    *   Strip whitespace (or other characters) from the beginning of a string
    *   You can also specify the characters you want to strip, by means of the chars parameter.
    *   Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
    *   @param  $chars  string
    */
    function ltrim($chars=null) {
        $this->str=is_null($chars)?ltrim($this->str):ltrim($this->str,$chars);
        return $this;
    }

    /**
    *   Convert special characters to HTML entities
    *   @param  $opt  int
    */
    function escape($opt=ENT_QUOTES) {
        $this->str=htmlspecialchars($this->str,$opt,'UTF-8',false);
        return $this;
    }

    /**
    *   Perform a regular expression search and replace
    *   @param  $pattern  string
    *   @param  $replace  string|callable
    */
    function replace($pattern,$replace) {
        if (is_callable($replace)) {
            $this->str=preg_replace_callback($pattern,function ($found) use($replace) {
                $args=array_map(function ($item) {
                    return new \String($item);
                },$found);
                return call_user_func_array($replace,$args);
            },$this->str);
        }
        else $this->str=preg_replace($pattern,$replace,$this->str);
        return $this;
    }

    /**
    *   Replace all occurrences of the search string with the replacement string
    *   @param  $search   string|array
    *   @param  $replace  string|array
    */
    function strreplace($search,$replace) {
        $this->str=str_replace($search,$replace,$this->str);
        return $this;
    }

    /**
    *   Add one level of line-leading spaces
    *   @param  $spaces  int
    */
    function indent($spaces=4) {
        $this->replace('/^/m',str_repeat(' ',$spaces));
        return $this;
    }

    /**
    *   Remove one level of line-leading tabs or spaces
    *   @param  $spaces  int
    */
    function outdent($spaces=4) {
        $this->replace('/^(\t|[ ]{1,'.$spaces.'})/m','');
        return $this;
    }

    /**
    *   Convert tabs to spaces
    *   @param  $spaces  int
    */
    function detab($spaces=4) {
        $this->replace('/(.*?)\t/',function (\String $w,\String $str) use($spaces) {
            return $str.str_repeat(' ',$spaces-$str->length()%$spaces);
        });
        return $this;
    }

    /**
    *   Determine whether a variable is empty
    *   @return bool
    */
    function dry() {
        return empty($this->str);
    }

    /**
    *   Finds whether a variable is a number or a numeric string
    *   @return  bool
    */
    function isnum() {
        return is_numeric($this->str);
    }

    /**
    *   Perform a regular expression match
    *   @param   $pattern  string
    *   @param   $found    array|null
    *   @return  bool
    */
    function match($pattern,&$found=null) {
        return preg_match($pattern,$this->str,$found)>0;
    }

    /**
    *   Split string by a regular expression (with optional flag)
    *   @param   $pattern      string
    *   @param   $flags        int
    *   @return  object|array
    */
    function split($pattern,$flags=PREG_SPLIT_DELIM_CAPTURE) {
        return array_map(function ($item) {
            return new static($item);
        },preg_split($pattern,$this->str,-1,$flags));
    }

    /**
    *   Split string by a line break
    *   @param   $pattern      string
    *   @return  object|array
    */
    function lines($pattern='/(\r?\n)/') {
        $chunk=array_chunk(preg_split($pattern,$this->str,-1,PREG_SPLIT_DELIM_CAPTURE),2);
        $res=[];
        foreach ($chunk as $v)
            $res[]=new \String(implode('',$v));
        return $res;
    }

    /**
    *   Convert a string to an array
    *   @return  array
    */
    function chars() {
        if (strlen($this->str)==$this->length())
            return str_split($this->str);
        return preg_split('//u',$this->str,-1,PREG_SPLIT_NO_EMPTY);
    }

    /**
    *   Apply a user function to every line of the string
    *   @param  $callback  callable
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
    *   Gets the length of a string
    *   @return  int
    */
    function length() {
        if (function_exists('mb_strlen'))
            return mb_strlen($this->str,'UTF-8');
        return preg_match_all("/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/",$this->str);
    }

    /**
    *   Returns the number of lines
    *   @return  int
    */
    function countlines() {
        return count($this->lines());
    }

    /**
    *   Find the position of the first occurrence of a substring in a string
    *   @param   $needle  string
    *   @param   $offset  int
    *   @return  int
    */
    function indexof($needle,$offset=0) {
        return strpos($this->str,$needle,$offset);
    }

    /**
    *   Write a string to a file
    *   @param   $path        string
    *   @return  int|boolean
    */
    function save($path) {
        $fw=\Alit::instance();
        return $fw->write($path,$this->str);
    }

    /**
    *   @return  string
    */
    function __toString() {
        return $this->get();
    }

    /**
    *   String representation of object
    *   @return  string|null
    */
    function serialize() {
        return serialize($this->str);
    }

    /**
    *   Constructs the object
    *   @param  $str  string
    */
    function unserialize($str) {
        $this->str=unserialize($str);
    }

}
