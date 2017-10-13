<?php
/**
*   Tiny PDO-Based Session Manager Library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Session
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('ALIT')) die('Direct file access is not allowed.');



class Session extends \Factory {

    protected
        $fw,
        $db,
        $table,
        $exists,
        $started;

    // Class constructor
    function __construct($db,$table='_session',$cookie='_cookie') {
        $fw=\Alit::instance();
        $this->fw=$fw;
        $this->db=$db;
        $this->table=$table;
        $this->fw->hive['SESSION']['cookie']=$cookie;
        $this->exists=false;
        $this->started=false;
        $this->start();
        $this->maketable();
        if (!$this->check())
            $this->create();
    }

    //  Start a session if it's not already started
    protected function start() {
        if (!$this->started) {
            session_start();
            $this->started=true;
        }
    }

    // Create a session data
    protected function create() {
        $token=sha1(base64_encode(md5(utf8_encode(microtime(true)))));
        $this->fw->hive['SESSION']['token']=substr($token,0,20);
    }

    // Checking session existance
    protected function check() {
        $cookie=$this->cookie($this->fw->hive['SESSION']['cookie']);
        if ($cookie==false)
            return false;
        $token=base64_decode($cookie);
        $res=(array)$this->db->table($this->table)
            ->where('token',$token)
            ->one();
        if ($res!==false) {
            $res['userdata']=unserialize($res['userdata']);
            $this->fw->hive['SESSION']['token']=$res['token'];
            if ($res['ip']==$this->fw->ip()) {
                if (count($res['userdata'])>0) {
                    $this->exists=true;
                    foreach ($res['userdata'] as $key=>$val) {
                        if (is_array($key))
                            foreach ($key as $k=>$v)
                                $this->fw->hive['SESSION'][$k]=$v;
                        else $this->fw->hive['SESSION'][$key]=$val;
                    }
                }
                $this->fw->hive['SESSION']['accessed']=time();
                return true;
            }
            else $this->destroy();
        }
        return false;
    }

    /**
    *   Set/store session to database
    *   @param   $name  string
    *   @param   $val   mixed
    */
    function set($name,$val=null) {
        if (is_array($name))
            foreach ($name as $key=>$val)
                $this->fw->hive['SESSION'][$key]=$val;
        else $this->fw->hive['SESSION'][$name]=$val;
        $cookie=base64_encode($this->fw->hive['SESSION']['token']);
        $this->setcookie($this->fw->hive['SESSION']['cookie'],$cookie);
        $data=[
            'token'=>$this->fw->hive['SESSION']['token'],
            'ip'=>$this->fw->ip(),
            'accessed'=>time()
        ];
        if ($this->exists==false) {
            $data['userdata']=serialize($this->fw->hive['SESSION']);
            $this->db->table($this->table)
                ->insert($data);
        }
        else return $this->update();
        if ($this->db->num_rows()>0)
            return true;
        return false;
    }

    // Destroy session and remove user data from database
    function destroy() {
        $id=base64_encode($this->fw->hive['SESSION']['token']);
        $this->setcookie($this->fw->hive['SESSION']['cookie'],$id,time()-1);
        $this->db->table($this->table)
            ->where('token',$this->fw->hive['SESSION']['token'])
            ->delete();
        $this->data=[];
        $this->started=false;
        session_destroy();
        $this->start();
        $this->create();
    }

    /**
    *   Get session data from database
    *   @param   $name   string
    *   @return  mixed
    */
    function get($name) {
        if (isset($this->fw->hive['SESSION'][$name]))
            return $this->fw->hive['SESSION'][$name];
        return null;
    }

    /**
    *   Erase/unset session
    *   @param   $name  string
    *   @return  bool
    */
    function erase($name) {
        if (is_string($name))
            $this->fw->split($name);
        foreach ($name as $k)
            unset($this->fw->hive['SESSION'][$k]);
    }

    // Update session data
    protected function update() {
        $id=serialize($this->fw->hive['SESSION']);
        $data=[
            'accessed'=>time(),
            'userdata'=>$id
        ];
        return $this->db->table($this->table)
            ->where('token',$this->fw->hive['SESSION']['token'])
            ->update($data);
    }

    // Create session table if not exists
    private function maketable() {
        $table=$this->table;
        $sql="CREATE TABLE IF NOT EXISTS `{$table}` (
          `id`       INT(11)      NOT NULL AUTO_INCREMENT,
          `token`    VARCHAR(25)  NOT NULL DEFAULT '',
          `ip`       VARCHAR(50)  DEFAULT NULL,
          `accessed` VARCHAR(50)  DEFAULT NULL,
          `userdata` TEXT,
          PRIMARY KEY (`id`,`token`)
        ) DEFAULT CHARSET=utf8;";
        return $this->db->query($sql);
    }

    /**
    *   Get cookie data from global $_COOKIE
    *   @param   $name    string
    *   @return  string
    */
    function cookie($name) {
        if (isset($_COOKIE[$name]))
            return htmlentities($_COOKIE[$name]);
        return false;
    }

    /**
    *   Set a cookie
    *   @param   $name   string
    *   @param   $val    mixed
    *   @param   $time   int|null
    *   @return  string
    */
    function setcookie($name,$val,$time=null) {
        if($time===null)
            $time=(time()+(60*60*24*365));
        setcookie($name,$val,$time,$this->fw->hive['TEMP']);
    }
}
