<?php
/**
*   Tiny Benchmarking Library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Bench
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('DS')) die('Direct file access is not allowed.');



class Bench extends \Factory {

    protected
        // Started time
        $start=[],
        // Stopped time
        $stop=[];

    const
        // Error messages
        E_Key="Benchmark key '%s' not found";

    /**
    *   Start benchmark
    *   @param  $key  string
    */
    function start($key) {
        $this->start[$key]=microtime(true);
    }

    /**
    *   Get elapsed time
    *   @param   $key       string
    *   @param   $round     int
    *   @param   $stop      string
    *   @return  int|false
    */
    function elapsed($key,$round=3,$stop=false) {
        if (!isset($this->start[$key]))
            \Alit::instance()->abort(vsprintf(self::E_Key,[$key]));
        else {
            if (!isset($this->stop[$key])&&$stop==true)
                $this->stop[$key]=microtime(true);
            return round((microtime(true)-$this->start[$key]),$round);
        }
    }

    /**
    *   Get memory usage
    *   @return  int
    */
    function memory() {
        $mem=memory_get_usage(true);
        for ($i=0;$mem>=1024&&$i<4;$i++)
            $mem/=1024;
        return round($mem,2).[' B',' KB',' MB'][$i];
    }

    /**
    *   Stop benchmarking
    *   @param  $key  string
    */
    function stop($key) {
        $this->stop[$key]=microtime(true);
    }
}
