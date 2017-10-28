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



class Session {

    protected
        // Database object
        $db,
        // Table name
        $table,
        // Session existance indicator
        $exists,
        // Session start indicator
        $started;

    // Class constructor
    function __construct($db,$table='_session',$cookie='_cookie') {
        $this->db=$db;
        $this->table=$table;
        \Alit::instance()->set('SESSION.cookie',$cookie);
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
        \Alit::instance()->set('SESSION.token',substr($token,0,20));
    }

    // Checking session existance
    protected function check() {
        $fw=\Alit::instance();
        $cookie=$this->cookie($fw->get('SESSION.cookie'));
        if ($cookie==false)
            return false;
        $token=base64_decode($cookie);
        $res=$this->db->table($this->table)
            ->where('token',$token)
            ->one();
        if (count($res)>0) {
            $res->userdata=unserialize($res->userdata);
            $fw->set('SESSION.token',$res->token);
            if ($res->ip==$fw->ip()) {
                if (count($res->userdata)>0) {
                    $this->exists=true;
                    foreach ($res->userdata as $key=>$val) {
                        if (is_array($key))
                            foreach ($key as $k=>$v)
                                $fw->set('SESSION.'.$k,$v);
                        else $fw->get('SESSION.'.$key,$val);
                    }
                }
                $fw->set('SESSION.accessed',time());
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
        $fw=\Alit::instance();
        if (is_array($name))
            foreach ($name as $key=>$val)
                $fw->set('SESSION.'.$k,$val);
        else $fw->set('SESSION.'.$name,$val);
        $cookie=base64_encode($fw->get('SESSION.token'));
        $this->setcookie($fw->get('SESSION.cookie'),$cookie);
        $data=['token'=>$fw->get('SESSION.token'),'ip'=>$fw->ip(),'accessed'=>time()];
        if ($this->exists==false) {
            $data['userdata']=serialize($fw->get('SESSION'));
            $this->db->table($this->table)->insert($data);
        }
        else return $this->update();
        if ($this->db->num_rows()>0)
            return true;
        return false;
    }

    // Destroy session and remove user data from database
    function destroy() {
        $fw=\Alit::instance();
        $id=base64_encode($fw->get('SESSION.token'));
        $this->setcookie($fw->get('SESSION.cookie'),$id,time()-1);
        $this->db->table($this->table)
            ->where('token',$fw->get('SESSION.token'))
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
        $fw=\Alit::instance();
        if (null!==($fw->get('SESSION.'.$name)))
            return $fw->get('SESSION.'.$name);
        return null;
    }

    /**
    *   Erase/unset session
    *   @param   $name  string
    *   @return  bool
    */
    function erase($name) {
        $fw=\Alit::instance();
        if (is_array($name))
            foreach ($name as $k)
                $fw->erase('SESSION.'.$k);
        $fw->erase('SESSION.'.$name);
    }

    // Update session data
    protected function update() {
        $fw=\Alit::instance();
        $id=serialize($fw->get('SESSION'));
        $data=['accessed'=>time(),'userdata'=>$id];
        return $this->db->table($this->table)
            ->where('token',$fw->get('SESSION.token'))
            ->update($data);
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
            $time=(time()+(60*60*24));
        setcookie($name,$val,$time,Alit::instance()->get('TEMP'));
    }
}
