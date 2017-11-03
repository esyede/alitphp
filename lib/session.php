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
if (!defined('DS')) die('Direct file access is not allowed.');


class Session {

    protected
        // Database connection object
        $db,
        // Table name
        $table,
        // Cookie name
        $cookie,
        // Session data
        $data=[],
        // Data exist?
        $existed,
        // Session start indicator
        $started=false;

    const
        // Error messages
        E_Database="You must pass database connection object to use session library",
        E_TableOrCookie="Table and cookie param can only accept string";

    /**
    *   Class constructor
    *   @param  $db      object
    *   @param  $table   string
    *   @param  $cookie  string
    */
    function __construct($db,$table='session',$cookie='cookies') {
        if (!is_object($db))
            \Alit::instance()->abort(500,self::E_Database);
        if (!is_string($table)||!is_string($cookie))
            \Alit::instance()->abort(500,self::E_TableOrCookie);
        $this->db=$db;
        $this->table=$table;
        $this->start();
        $this->maketable();
        $this->cookie=$cookie;
        $this->existed=false;
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

    // Create a session token
    protected function create() {
        $this->data['token']=substr(sha1(base64_encode(md5(utf8_encode(microtime(1))))),0,20);
    }

    // Checking session existance
    protected function check() {
        $cookie=$this->cookie($this->cookie);
        if ($cookie===false)
            return false;
        $token=base64_decode($cookie);
        $res=(array)$this->db->table($this->table)
            ->where('token',$token)
            ->one();
        if ($this->db->num_rows()>0) {
            $this->existed=true;
            $res['userdata']=unserialize($res['userdata']);
            $this->data['token']=$res['token'];
            if ($res['ip']==\Alit::instance()->get('IP')) {
                if (count($res['userdata'])>0)
                    foreach($res['userdata'] as $key=>$val)
                        $this->set($key,$val);
                $this->data['accessed']=time();
                return true;
            }
            else $this->destroy();
        }
        return false;
    }

    // Destroy session and remove user data from database
    function destroy() {
        $this->setcookie($this->cookie,base64_encode($this->data['token']),time()-1);
            $this->db->table($this->table)
            ->where('token',$this->data['token'])
            ->delete();
        $this->data=[];
        $this->started=false;
        session_destroy();
        $this->start();
        $this->create();
    }

    /**
    *   Get session data from database
    *   @param   $key   string
    *   @return  mixed
    */
    function get($key) {
        if (isset($this->data[$key]))
            return $this->data[$key];
        return null;
    }

    /**
    *   Set/store session to database
    *   @param   $key  string
    *   @param   $val  mixed
    */
    function set($key,$val=null) {
        if (is_array($key))
            foreach ($key as $k=>$v)
                $this->data[$k]=$v;
        else $this->data[$key]=$val;
        $cdata=base64_encode($this->data['token']);
        $this->setcookie($this->cookie,$cdata);
        $data=[
            'token'=>$this->data['token'],
            'ip'=>\Alit::instance()->get('IP'),
            'accessed'=>time()
        ];
        if ($this->existed==false) {
            $data['userdata']=serialize($this->data);
            $this->db->table($this->table)->insert($data);
            $res=$this->db->num_rows();
        }
        else return $this->renew();
        if ($res>0)
            return true;
        return false;
    }

    /**
    *   Check session existance
    *   @param   $key  string
    *   @return  bool
    */
    function exists($key) {
        return array_key_exists($key,$this->data);
    }

    /**
    *   Erase/unset session
    *   @param   $key  string
    *   @return  bool
    */
    function erase($key) {
        unset($this->data[$key]);
    }

    // Renew/update session data
    protected function renew() {
        $data=['accessed'=>time(),'userdata'=>serialize($this->data)];
        return $this->db->table($this->table)
            ->where('token',$this->data['token'])
            ->update($data);
    }

    // Get all session data
    function data() {
        return $this->data;
    }

    // Create session table if not exists
    private function maketable() {
        $sql="CREATE TABLE IF NOT EXISTS `{$this->table}` (
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
    *   @param   $key     string
    *   @return  string
    */
    function cookie($key) {
        if (isset($_COOKIE[$key]))
            return htmlentities($_COOKIE[$key]);
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
        if ($ttl===null)
            $ttl=time()+(60*60*24);
        setcookie($key,$val,$ttl,\Alit::instance()->get('TEMP'));
    }
}
