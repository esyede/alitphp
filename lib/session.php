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
        $fw=\Alit::instance();
        if (!is_object($db))
            $fw->abort(500,self::E_Database);
        if (!is_string($table)||!is_string($cookie))
            $fw->abort(500,self::E_TableOrCookie);
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

    // Check session existance
    protected function check() {
        $fw=\Alit::instance();
        $cookie=$fw->cookie($this->cookie);
        if ($cookie===false)
            return false;
        $token=base64_decode($cookie);
        $res=$this->db->table($this->table)
            ->where('token',$token)
            ->one();
        if ($this->db->num_rows()>0) {
            $this->existed=true;
            $res->data=unserialize($res->data);
            $this->data['token']=$res->token;
            if ($res->ip==$fw->get('IP')) {
                if (count($res->data)>0)
                    foreach($res->data as $key=>$val)
                        $this->set($key,$val);
                $this->data['seen']=time();
                return true;
            }
            else $this->destroy();
        }
        return false;
    }

    // Destroy session and remove user data from database
    function destroy() {
        \Alit::instance()->setcookie($this->cookie,base64_encode($this->data['token']),time()-1);
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
    *   @param   $key      string
    *   @param   $default  string|null
    *   @return  mixed
    */
    function get($key,$default=null) {
        return isset($this->data[$key])?$this->data[$key]:$default;
    }

    /**
    *   Set/store session to database
    *   @param   $key  string
    *   @param   $val  mixed
    */
    function set($key,$val=null) {
        $fw=\Alit::instance();
        if (is_array($key))
            foreach ($key as $k=>$v)
                $this->data[$k]=$v;
        else $this->data[$key]=$val;
        $fw->setcookie($this->cookie,base64_encode($this->data['token']));
        $data=[
            'token'=>$this->data['token'],
            'ip'=>$fw->get('IP'),
            'seen'=>time()
        ];
        $res=0;
        if ($this->existed===false) {
            $data['data']=serialize($this->data);
            $this->db->table($this->table)->insert($data);
            $res=$this->db->num_rows();
        }
        else return $this->renew();
        return ($res>0);
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
        return $this->db->table($this->table)
            ->where('token',$this->data['token'])
            ->update(['seen'=>time(),'data'=>serialize($this->data)]);
    }

    // Get all session data as array
    function data() {
        return $this->data;
    }

    // Create session table if not exists
    private function maketable() {
        $sql="CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id`    INT(11)      NOT NULL AUTO_INCREMENT,
            `token` VARCHAR(25)  NOT NULL DEFAULT '',
            `ip`    VARCHAR(50)  DEFAULT NULL,
            `seen`  VARCHAR(50)  DEFAULT NULL,
            `data`  TEXT,
            PRIMARY KEY (`id`,`token`)
        );";
        return $this->db->query($sql);
    }
}
