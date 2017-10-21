<?php
/**
*   Tiny PDO-Based SQL Query Builder for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.DB.SQL
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
namespace DB;
// Prohibit direct access to file
if (!defined('ALIT')) die('Direct file access is not allowed.');


class SQL {

    protected
        // Class properties
        $numrows=0,
        $result=[],
        $from=null,
        $join=null,
        $select='*',
        $where=null,
        $cache=null,
        $limit=null,
        $query=null,
        $error=null,
        $offset=null,
        $prefix=null,
        $having=null,
        $querycount=0,
        $orderby=null,
        $groupby=null,
        $insertid=null,
        $cachedir=null,
        $grouped=false;
    const
        // Error messages
        E_Connection="Cannot connect to Database.<br><br>%s",
        E_LastError="<h3>Database Error</h3><b>Query:</b><pre>%s</pre><br><b>Error:</b><pre>%s</pre><br>";
    const
        // Comparison operators
        OPERATORS='=|!=|<|>|<=|>=|<>';
    public
        // Database connection object
        $conn=null;

    // Class constructor
    function __construct(array $config) {
        $config['driver']=(isset($config['driver'])?$config['driver']:'mysql');
        $config['host']=(isset($config['host'])?$config['host']:'localhost');
        $config['charset']=(isset($config['charset'])?$config['charset']:'utf8');
        $config['collation']=(isset($config['collation'])?$config['collation']:'utf8_general_ci');
        $config['port']=(strstr($config['host'],':')?explode(':',$config['host'])[1]:'');
        $this->prefix=(isset($config['prefix'])?$config['prefix']:'');
        $this->cachedir=(isset($config['cachedir'])?$config['cachedir']:\Alit::instance()->get('TEMP'));
        $dsn='';
        if ($config['driver']=='mysql'
        ||$config['driver']==''
        ||$config['driver']=='pgsql')
            $dsn=$config['driver'].':host='.$config['host'].';'.
                (($config['port'])!=''?'port='.$config['port'].';':'').'dbname='.$config['database'];
        elseif ($config['driver']=='sqlite')
            $dsn='sqlite:'.$config['database'];
        elseif ($config['driver']=='oracle')
            $dsn='oci:dbname='.$config['host'].'/'.$config['database'];
        try {
            $this->conn=new \PDO($dsn,$config['username'],$config['password']);
            $this->conn->exec("SET NAMES '".$config["charset"]."' COLLATE '".$config["collation"]."'");
            $this->conn->exec("SET CHARACTER SET '".$config["charset"]."'");
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_OBJ);
        }
        catch(\PDOException $e) {
            trigger_error(vsprintf(self::E_Connection,[$e->getMessage()]),E_ERROR);
        }
        return $this->conn;
    }

    /**
    *   Table to aperate
    *   @param  $table  string
    */
    function table($table) {
        if (is_array($table)) {
            $f='';
            foreach ($table as $key)
                $f.=$this->prefix.$key.', ';
            $this->from=rtrim($f,', ');
        }
        else $this->from=$this->prefix.$table;
        return $this;
    }

    /**
    *   Execute SELECT statement
    *   @param  $fields  string
    */
    function select($fields) {
        $select=(is_array($fields)?implode(', ',$fields):$fields);
        $this->select=($this->select=='*'?$select:$this->select.', '.$select);
        return $this;
    }

    /**
    *   Build MAX statement
    *   @param  $fields  string
    *   @param  $name    string|null
    */
    function max($field,$name=null) {
        $func='MAX('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
    *  Build  MIN statement
    *   @param  $fields  string
    *   @param  $name    string|null
    */
    function min($field,$name=null) {
        $func='MIN('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
    *   Build SUM statement
    *   @param  $fields  string
    *   @param  $name    string|null
    */
    function sum($field,$name=null) {
        $func='SUM('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
    *   Build COUNT statement
    *   @param  $fields  string
    *   @param  $name    string|null
    */
    function count($field,$name=null) {
        $func='COUNT('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
    *   Build AVG statement
    *   @param  $fields  string
    *   @param  $name    string|null
    */
    function avg($field,$name=null) {
        $func='AVG('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
    *   Build JOIN statement
    *   @param  $table   string
    *   @param  $field1  string|null
    *   @param  $op      string|null
    *   @param  $field2  string|null
    *   @param  $type    string|null
    */
    function join($table,$field1=null,$op=null,$field2=null,$type='') {
        $on=$field1;
        $table=$this->prefix.$table;
        if (!is_null($op))
            $on=(!in_array($op,\Alit::instance()->split(self::OPERATORS))
                ?$this->prefix.$field1.' = '.$this->prefix.$op
                :$this->prefix.$field1.' '.$op.' '.$this->prefix.$field2);
        if (is_null($this->join))
            $this->join=' '.$type.'JOIN '.$table.' ON '.$on;
        else $this->join=$this->join.' '.$type.'JOIN '.$table.' ON '.$on;
        return $this;
    }

    /**
    *   Build INNER JOIN statement
    *   @param  $table   string
    *   @param  $field1  string|null
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function inner($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'INNER ');
        return $this;
    }

    /**
    *   Build LEFT JOIN statement
    *   @param  $table   string
    *   @param  $field1  string|null
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function left($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'LEFT ');
        return $this;
    }

    /**
    *   Build RIGHT JOIN statement
    *   @param  $table   string
    *   @param  $field1  string
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function right($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'RIGHT ');
        return $this;
    }

    /**
    *   Build FULL OUTER JOIN statement
    *   @param  $table   string
    *   @param  $field1  string
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function full_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'FULL OUTER ');
        return $this;
    }

    /**
    *   Build LEFT JOIN statement
    *   @param  $table   string
    *   @param  $field1  string
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function left_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'LEFT OUTER ');
        return $this;
    }

    /**
    *   Build RIGHT JOIN statement
    *   @param  $table   string
    *   @param  $field1  string
    *   @param  $op      string|null
    *   @param  $field2  string|null
    */
    function right_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'RIGHT OUTER ');
        return $this;
    }

    /**
    *   Build WEHRE statement
    *   @param  $where   string
    *   @param  $op      string|null
    *   @param  $val     string|null
    *   @param  $type    string|null
    *   @param  $type    string|null
    *   @param  $and_or  string|null
    */
    function where($where,$op=null,$val=null,$type='',$and_or='AND') {
        if (is_array($where)) {
            $_where=[];
            foreach ($where as $column=>$data)
                $_where[]=$type.$column.'='.$this->escape($data);
            $where=implode(' '.$and_or.' ',$_where);
        }
        else {
            if (is_array($op)) {
                $x=explode('?',$where);
                $w='';
                foreach ($x as $k=>$v)
                    if (!empty($v))
                        $w.=$type.$v.(isset($op[$k])?$this->escape($op[$k]):'');
                $where=$w;
            }
            elseif (!in_array($op,\Alit::instance()->split(self::OPERATORS))||$op==false)
                $where=$type.$where.' = '.$this->escape($op);
            else $where=$type.$where.' '.$op.' '.$this->escape($val);
        }
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=false;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
    *   Build OR WEHRE statement
    *   @param  $where   string
    *   @param  $op      string|null
    *   @param  $val     string|null
    */
    function or_where($where,$op=null,$val=null) {
        $this->where($where,$op,$val,'','OR');
        return $this;
    }

    /**
    *   Build WEHRE NOT statement
    *   @param  $where   string
    *   @param  $op      string|null
    *   @param  $val     string|null
    */
    function not_where($where,$op=null,$val=null) {
        $this->where($where,$op,$val,'NOT ','AND');
        return $this;
    }

    /**
    *   Build WEHRE NOT statement
    *   @param  $where   string
    *   @param  $op      string|null
    *   @param  $val     string|null
    */
    function ornot_where($where,$op=null,$val=null) {
        $this->where($where,$op,$val,'NOT ','OR');
        return $this;
    }

    /**
    *   Grouping statement
    *   @param  $obj  object
    */
    function grouped(\Closure $obj) {
        $this->grouped=true;
        call_user_func_array($obj,[$this]);
        $this->where.=')';
        return $this;
    }

    /**
    *   Build WEHRE IN statement
    *   @param  $field   string
    *   @param  $keys    array
    *   @param  $type    string|null
    *   @param  $and_or  string|null
    */
    function in($field,array $keys,$type='',$and_or='AND') {
        if (is_array($keys)) {
            $_keys=[];
            foreach ($keys as $k=>$v)
                $_keys[]=(is_numeric($v)?$v:$this->escape($v));
            $keys=implode(', ',$_keys);
            $where=$field.' '.$type.'IN ('.$keys.')';
            if ($this->grouped) {
                $where='('.$where;
                $this->grouped=false;
            }
            if (is_null($this->where))
                $this->where=$where;
            else $this->where=$this->where.' '.$and_or.' '.$where;
        }
        return $this;
    }

    /**
    *   Build WEHRE NOT IN statement
    *   @param  $field   string
    *   @param  $keys    array
    */
    function not_in($field,array $keys) {
        $this->in($field,$keys,'NOT ','AND');
        return $this;
    }

    /**
    *   Build OR WEHRE IN statement
    *   @param  $field   string
    *   @param  $keys    array
    */
    function or_in($field,array $keys) {
        $this->in($field,$keys,'','OR');
        return $this;
    }

    /**
    *   Build OR WEHRE NOT IN statement
    *   @param  $field   string
    *   @param  $keys    array
    */
    function ornot_in($field,array $keys) {
        $this->in($field,$keys,'NOT ','OR');
        return $this;
    }

    /**
    *   Build BERWEEN statement
    *   @param  $field   string
    *   @param  $value1  mixed
    *   @param  $value2  mixed
    *   @param  $tyoe    string|null
    *   @param  $and_or  string|null
    */
    function between($field,$value1,$value2,$type='',$and_or='AND') {
        $where=$field.' '.$type.'BETWEEN '.$this->escape($value1).' AND '.$this->escape($value2);
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=false;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
    *   Build NOT BERWEEN statement
    *   @param  $field   string
    *   @param  $value1  mixed
    *   @param  $value2  mixed
    */
    function not_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'NOT ','AND');
        return $this;
    }

    /**
    *   Build OR BERWEEN statement
    *   @param  $field   string
    *   @param  $value1  mixed
    *   @param  $value2  mixed
    */
    function or_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'','OR');
        return $this;
    }

    /**
    *   Build OR NOT BERWEEN statement
    *   @param  $field   string
    *   @param  $value1  mixed
    *   @param  $value2  mixed
    */
    function ornot_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'NOT ','OR');
        return $this;
    }

    /**
    *   Build LIKE statement
    *   @param  $field   string
    *   @param  $data    mixed
    *   @param  $tyoe    string|null
    *   @param  $and_or  string
    */
    function like($field,$data,$type='',$and_or='AND') {
        $like=$this->escape($data);
        $where=$field.' '.$type.'LIKE '.$like;
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=false;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
    *   Build OR LIKE statement
    *   @param  $field   string
    *   @param  $data    mixed
    */
    function or_like($field,$data) {
        $this->like($field,$data,'','OR');
        return $this;
    }

    /**
    *   Build NOT LIKE statement
    *   @param  $field   string
    *   @param  $data    mixed
    */
    function not_like($field,$data) {
        $this->like($field,$data,'NOT ','AND');
        return $this;
    }

    /**
    *   Build OR NOT LIKE statement
    *   @param  $field   string
    *   @param  $data    mixed
    */
    function ornot_like($field,$data) {
        $this->like($field,$data,'NOT ','OR');
        return $this;
    }

    /**
    *   Build LIMIT statement
    *   @param  $limit      int
    *   @param  $limit_end  int
    */
    function limit($limit,$limit_end=null) {
        if (!is_null($limit_end))
            $this->limit=$limit.', '.$limit_end;
        else $this->limit=$limit;
        return $this;
    }

    /**
    *   Build OFFSET statement
    *   @param  $offset  int
    */
    function offset($offset) {
        $this->offset=$offset;
        return $this;
    }

    /**
    *   Paginate query result
    *   @param  $perpage  int
    *   @param  $page     int
    */
    function pagination($perpage,$page) {
        $this->limit=$perpage;
        $this->offset=($page-1)*$perpage;
        return $this;
    }

    /**
    *   Build ORDER BY statement
    *   @param  $orderby    int
    *   @param  $order_dir  int
    */
    function order_by($orderby,$order_dir=null) {
        if (!is_null($order_dir))
            $this->orderby=$orderby.' '.strtoupper($order_dir);
        else {
            if (stristr($orderby,' ')||$orderby=='RAND()')
                $this->orderby=$orderby;
            else $this->orderby=$orderby.' ASC';
        }
        return $this;
    }

    /**
    *   Build GROUP BY statement
    *   @param  $groupby  string
    */
    function group_by($groupby) {
        if (is_array($groupby))
            $this->groupby=implode(', ',$groupby);
        else $this->groupby=$groupby;
        return $this;
    }

    /**
    *   Build HAVING statement
    *   @param  $field  string
    *   @param  $op     string|null
    *   @param  $val    mixed|null
    */
    function having($field,$op=null,$val=null) {
        if (is_array($op)) {
            $x=explode('?',$field);
            $w='';
            foreach ($x as $k=>$v)
                if (!empty($v))
                    $w.=$v.(isset($op[$k])?$this->escape($op[$k]):'');
            $this->having=$w;
        }
        elseif (!in_array($op,\Alit::instance()->split(self::OPERATORS)))
            $this->having=$field.'> '.$this->escape($op);
        else $this->having=$field.' '.$op.' '.$this->escape($val);
        return $this;
    }

    // Get number of affected rows
    function num_rows() {
        return $this->numrows;
    }

    // Get last insert id
    function insert_id() {
        return $this->insertid;
    }

    // Get last database error message
    function error() {
        $msg='<h3>Database Error</h3>';
        $msg.='<b>Query:</b><pre>'.$this->query.'</pre><br/>';
        $msg.='<b>Error:</b><pre>'.$this->error.'</pre><br/>';
        if (Alit::instance()->get('ROOT')>0)
            trigger_error(vsprintf(self::E_LastError,[$this->query,$this->error]),E_ERROR);
        else trigger_error(vsprintf("%s. (%s)",[$this->error,$this->query]),E_ERROR);
    }

    /**
    *   Get only one row af data
    *   @param   $type  bool
    *   @return  array|object
    */
    function one($type=false) {
        $this->limit=1;
        $query=$this->many(true);
        if ($type===true)
            return $query;
        else return $this->query($query,false,(($type=='array')?true:false));
    }

    /**
    *   Get all data of affected rows
    *   @param   $type  bool
    *   @return  array|object
    */
    function many($type=false) {
        $query='SELECT '.$this->select.' FROM '.$this->from;
        if (!is_null($this->join))
            $query.=$this->join;
        if (!is_null($this->where))
            $query.=' WHERE '.$this->where;
        if (!is_null($this->groupby))
            $query.=' GROUP BY '.$this->groupby;
        if (!is_null($this->having))
            $query.=' HAVING '.$this->having;
        if (!is_null($this->orderby))
            $query.=' ORDER BY '.$this->orderby;
        if (!is_null($this->limit))
            $query.=' LIMIT '.$this->limit;
        if (!is_null($this->offset))
            $query.=' OFFSET '.$this->offset;
        if ($type===true)
            return $query;
        else return $this->query($query,true,(($type=='array')?true:false));
    }

    /**
    *   Execute INSERT statement
    *   @param   $data  array
    */
    function insert($data) {
        $columns=array_keys($data);
        $column=implode(',',$columns);
        $val=implode(', ',array_map([$this,'escape'],$data));
        $query='INSERT INTO '.$this->from.' ('.$column.') VALUES ('.$val.')';
        $query=$this->query($query);
        if ($query) {
            $this->insertid=$this->conn->lastInsertId();
            return $this->insert_id();
        }
        else return false;
    }

    /**
    *   Execute UPDATE statement
    *   @param   $data  array
    */
    function update($data) {
        $query='UPDATE '.$this->from.' SET ';
        $values=[];
        foreach ($data as $column=>$val)
            $values[]=$column.'='.$this->escape($val);
        $query.=(is_array($data)?implode(',',$values):$data);
        if (!is_null($this->where))
            $query.=' WHERE '.$this->where;
        if (!is_null($this->orderby))
            $query.=' ORDER BY '.$this->orderby;
        if (!is_null($this->limit))
            $query.=' LIMIT '.$this->limit;
        return $this->query($query);
    }

    /**
    *   Execute DELETE statement
    *   @return  bool
    */
    function delete() {
        $query='DELETE FROM '.$this->from;
        if (!is_null($this->where))
            $query.=' WHERE '.$this->where;
        if (!is_null($this->orderby))
            $query.=' ORDER BY '.$this->orderby;
        if (!is_null($this->limit))
            $query.=' LIMIT '.$this->limit;
        if ($query=='DELETE FROM '.$this->from)
            $query='TRUNCATE TABLE '.$this->from;
        return $this->query($query);
    }

    // Analyze table
    function analyze() {
        return $this->query('ANALYZE TABLE '.$this->from);
    }

    // Check table
    function check() {
        return $this->query('CHECK TABLE '.$this->from);
    }

    // Checksum table
    function checksum() {
        return $this->query('CHECKSUM TABLE '.$this->from);
    }

    // Optimize table
    function optimize() {
        return $this->query('OPTIMIZE TABLE '.$this->from);
    }

    // Repair table
    function repair() {
        return $this->query('REPAIR TABLE '.$this->from);
    }

    /**
    *   Execute sql statement
    *   @param   $query  string
    *   @param   $all    string
    *   @param   $array  string
    *   @return  mixed
    */
    function query($query,$all=true,$array=false) {
        $this->reset();
        if (is_array($all)) {
            $x=explode('?',$query);
            $q='';
            foreach ($x as $k=>$v)
                if (!empty($v))
                    $q.=$v.(isset($all[$k])?$this->escape($all[$k]):'');
            $query=$q;
        }
        $this->query=preg_replace("/\s\s+|\t\t+/",' ',trim($query));
        $str=false;
        foreach (['select','optimize','check','repair','checksum','analyze'] as $value) {
            if (stripos($this->query,$value)===0) {
                $str=true;
                break;
            }
        }
        $cache=false;
        if (!is_null($this->cache))
            $cache=$this->cache->getcache($this->query,$array);
        if (!$cache&&$str) {
            $sql=$this->conn->query($this->query);
            if ($sql) {
                $this->numrows=$sql->rowCount();
                if (($this->numrows>0)) {
                    if ($all) {
                        $q=[];
                        while ($result=($array==false)?$sql->fetchAll(\PDO::FETCH_OBJ):$sql->fetchAll(\PDO::FETCH_ASSOC))
                            $q[]=$result;
                        $this->result=$q[0];
                    }
                    else {
                        $q=($array==false)?$sql->fetch(\PDO::FETCH_OBJ):$sql->fetch(\PDO::FETCH_ASSOC);
                        $this->result=$q;
                    }
                }
                if (!is_null($this->cache))
                    $this->cache->setcache($this->query,$this->result);
                $this->cache=null;
            }
            else {
                $this->cache=null;
                $this->error=$this->conn->errorInfo();
                $this->error=$this->error[2];
                return $this->error();
            }
        }
        elseif ((!$cache&&!$str)||($cache&&!$str)) {
            $this->cache=null;
            $this->result=$this->conn->exec($this->query);
            if ($this->result===false) {
                $this->error=$this->conn->errorInfo();
                $this->error=$this->error[2];
                return $this->error();
            }
        }
        else {
            $this->cache=null;
            $this->result=$cache;
        }
        $this->querycount++;
        return $this->result;
    }

    /**
    *   Add quote to sql query for sql-injection prevention
    *   @param   $data   mixed
    *   @return  string
    */
    function escape($data) {
        if ($data===NULL)
            return 'NULL';
        if (is_null($data))
            return null;
        return $this->conn->quote(trim($data));
    }

    /**
    *   Database caching
    *   @param   $time  int|float
    */
    function cache($time) {
        $this->cache=new \DB\SQLCache($this->cachedir,$time);
        return $this;
    }

    // Count executed queries
    function query_count() {
        return $this->querycount;
    }

    // Get builded strung of sql query
    function sql() {
        return $this->query;
    }

    // Reset class properties
    protected function reset() {
        $this->result=[];
        $this->numrows=0;
        $this->from=null;
        $this->join=null;
        $this->select='*';
        $this->error=null;
        $this->where=null;
        $this->query=null;
        $this->limit=null;
        $this->offset=null;
        $this->having=null;
        $this->orderby=null;
        $this->groupby=null;
        $this->grouped=false;
        $this->insertid=null;
        return;
    }

    // Class destructor
    function __destruct() {
        $this->conn=null;
    }
}




class SQLCache {

    private
        $cachedir=null,
        $cache=null,
        $finish=null;

    // Class constructor
    function __construct($dir=null,$time=0) {
        if (!file_exists($dir))
            mkdir($dir,0755);
        $this->cachedir=$dir;
        $this->cache=$time;
        $this->finish=time()+$time;
    }

    /**
    *   Set cache data to file
    *   @param  $sql     string
    *   @param  $result  string
    */
    function setcache($sql,$result) {
        if (is_null($this->cache))
            return false;
        $target=$this->cachedir.$this->filename($sql).'.cache';
        $target=fopen($target,'w');
        if ($target)
            fputs($target,json_encode(['data'=>$result,'finish'=>$this->finish]));
        return;
    }

    /**
    *   Get cached data
    *   @param  $sql    string
    *   @param  $array  bool
    */
    function getcache($sql,$array=false) {
        if (is_null($this->cache))
            return false;
        $target=$this->cachedir.$this->filename($sql).'.cache';
        if (file_exists($target)) {
            $cache=json_decode(\Alit::instance()->read($target),$array);
            if (($array?$cache['finish']:$cache->finish)<time()) {
                unlink($target);
                return;
            }
            else return ($array?$cache['data']:$cache->data);
        }
        return false;
    }

    /**
    *   Set cache filename (MD5 encrypted)
    *   @param  $sql    string
    *   @param  $array  bool
    */
    private function filename($name) {
        return md5($name);
    }
}
