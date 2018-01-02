<?php
/**
 * Tiny PDO-Driven Session Library for Alit PHP
 * @package     Alit
 * @subpackage  Session
 * @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
 * @license     <https://opensource.org/licenses/MIT> The MIT License (MIT).
 * @author      Suyadi <suyadi.1992@gmail.com>
 */
// Prohibit direct access to file
defined('DS') or die('Direct file access is not allowed.');


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
        $started=FALSE;

    const
        // Error messages
        E_DBPARAM="Table and cookie parameter can only accept string with non-empty value";

    /**
     * Class constructor
     * @param   \DB\SQL  $db
     * @param   string   $table
     * @param   string   $cookie
     * @return  void
     */
    function __construct(\DB\SQL $db,$table='session',$cookie='cookies') {
        $fw=\Alit::instance();
        if ((!is_string($table)||(is_string($table)&&strlen(trim($table))==0))
        ||(!is_string($cookie)||(is_string($cookie)&&strlen(trim($cookie))==0)))
            $fw->abort(500,self::E_DBPARAM);
        $this->db=$db;
        $this->table=$table;
        $this->start();
        $this->maketable();
        $this->cookie=$cookie;
        $this->existed=FALSE;
        if (!$this->check())
            $this->create();
    }

    /**
     * Start a session if it's not already started
     * @return  void
     */
    protected function start() {
        if (!$this->started) {
            session_start();
            $this->started=TRUE;
        }
    }

    /**
     * Create a session token
     * @return void
     */
    protected function create() {
        $this->data['token']=substr(sha1(base64_encode(md5(utf8_encode(microtime(1))))),0,20);
    }

    /**
     * Check session existance
     * @return  boolean
     */
    protected function check() {
        $fw=\Alit::instance();
        $cookie=$fw->cookie($this->cookie);
        if ($cookie===FALSE)
            return FALSE;
        $token=base64_decode($cookie);
        $res=$this->db->table($this->table)
            ->where('token',$token)
            ->one();
        if ($this->db->num_rows()>0) {
            $this->existed=TRUE;
            $res->data=$fw->unserialize($res->data);
            $this->data['token']=$res->token;
            if ($res->ip==$fw->get('IP')) {
                $count=count($res->data);
                if ($count>0)
                    foreach($res->data as $key=>$val)
                        $this->set($key,$val);
                $this->data['seen']=time();
                return TRUE;
            }
            else $this->destroy();
        }
        return FALSE;
    }

    /**
     * Destroy session and remove user data from database
     * @return  void
     */
    function destroy() {
        $fw=\Alit::instance();
        $fw->setcookie($this->cookie,base64_encode($this->data['token']),time()-1);
        $this->db->table($this->table)
            ->where('token',$this->data['token'])
            ->delete();
        $this->data=[];
        $this->started=FALSE;
        session_destroy();
        $this->start();
        $this->create();
    }

    /**
     * Get session data from database
     * @param   string      $key
     * @param   mixed|NULL  $default
     * @return  mixed|NULL
     */
    function get($key,$default=NULL) {
        return isset($this->data[$key])?$this->data[$key]:$default;
    }

    /**
     * Set/store session to database
     * @param   string    $key
     * @param   mixed     $val
     * @return  boolean
     */
    function set($key,$val=NULL) {
        $fw=\Alit::instance();
        if (is_array($key))
            foreach ($key as $k=>$v)
                $this->data[$k]=$v;
        else $this->data[$key]=$val;
        $fw->setcookie($this->cookie,base64_encode($this->data['token']));
        $data=['token'=>$this->data['token'],'ip'=>$fw->get('IP'),'seen'=>time()];
        $res=0;
        if ($this->existed===FALSE) {
            $data['data']=$fw->serialize($this->data);
            $this->db->table($this->table)->insert($data);
            $res=$this->db->num_rows();
        }
        else return $this->renew();
        return ($res>0);
    }

    /**
     * Check session existance
     * @param   string   $key
     * @return  boolean
     */
    function exists($key) {
        return array_key_exists($key,$this->data);
    }

    /**
     * Erase/unset session
     * @param   string   $key
     * @return  boolean
     */
    function erase($key) {
        unset($this->data[$key]);
    }

    /**
     * Renew/update session data
     * @return  boolean
     */
    protected function renew() {
        $fw=\Alit::instance();
        return $this->db->table($this->table)
            ->where('token',$this->data['token'])
            ->update(['seen'=>time(),'data'=>$fw->serialize($this->data)]);
    }

    /**
     * Get all session data as array
     * @return  array
     */
    function data() {
        return $this->data;
    }

    /**
     * Create session table if not exists
     * @return  boolean
     */
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
