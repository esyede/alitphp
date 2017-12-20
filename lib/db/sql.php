<?php
/**
 * Tiny PDO-Based Query Builder Library for Alit PHP
 * @package     Alit
 * @subpackage  DB.SQL
 * @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
 * @license     <https://opensource.org/licenses/MIT> The MIT License (MIT).
 * @author      Suyadi <suyadi.1992@gmail.com>
 */
namespace DB;
// Prohibit direct access to file
defined('DS') or die('Direct file access is not allowed.');


class SQL {

    protected
        // Class properties
        $numrows=0,
        $result=[],
        $from=NULL,
        $join=NULL,
        $select='*',
        $where=NULL,
        $cache=NULL,
        $limit=NULL,
        $query=NULL,
        $error=NULL,
        $offset=NULL,
        $prefix=NULL,
        $having=NULL,
        $querycount=0,
        $orderby=NULL,
        $groupby=NULL,
        $insertid=NULL,
        $cachedir=NULL,
        $grouped=FALSE;

    const
        // Error messages
        E_DBACCOUNT="At least you must provide username, password, and database name to your db config",
        E_CONNECTION="Cannot connect to Database.<br><br>%s",
        E_PRODUCTION="There is an error on the database, please contact administrator.",
        E_LASTERROR="<b>DB Error:</b><pre>%s</pre><br><b>Query:</b><pre>%s</pre><br>";

    const
        // Comparison operators
        OPERATORS='=|!=|<|>|<=|>=|<>';

    public
        // Database connection object
        $conn=NULL;

    /**
     * Class constructor
     * @param  array  $config
     */
    function __construct(array $config) {
        $fw=\Alit::instance();
        $config['driver']=(isset($config['driver'])?$config['driver']:'mysql');
        $config['host']=(isset($config['host'])?$config['host']:'localhost');
        $config['charset']=(isset($config['charset'])?$config['charset']:'utf8');
        $config['collation']=(isset($config['collation'])?$config['collation']:'utf8_general_ci');
        $config['port']=(strstr($config['host'],':')?explode(':',$config['host'])[1]:'');
        $this->prefix=(isset($config['prefix'])?$config['prefix']:'');
        $this->cachedir=(isset($config['cachedir'])?$config['cachedir']:$fw->get('TEMP'));
        if (!isset($config['username'])
        ||!isset($config['password'])
        ||!isset($config['database']))
            $fw->abort(500,self::E_DBACCOUNT);
        $dsn='';
        if ($config['driver']=='mysql'||$config['driver']==''||$config['driver']=='pgsql')
            $dsn=$config['driver'].':host='.$config['host'].';'.
                (($config['port']!='')?'port='.$config['port'].';':'').'dbname='.$config['database'];
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
            $fw->abort(500,sprintf(self::E_CONNECTION,$e->getMessage()));
        }
        return $this->conn;
    }

    /**
     * Set table ro operate
     * @param   string  $table
     * @return  object
     */
    function table($table) {
        if (is_array($table)) {
            $frm='';
            foreach ($table as $key)
                $frm.=$this->prefix.$key.', ';
            $this->from=rtrim($frm,', ');
        }
        else $this->from=$this->prefix.$table;
        return $this;
    }

    /**
     * Execute SELECT statement
     * @param   string  $fields
     * @return  object
     */
    function select($fields) {
        $select=(is_array($fields)?implode(', ',$fields):$fields);
        $this->select=($this->select=='*'?$select:$this->select.', '.$select);
        return $this;
    }

    /**
     * Build MAX statement
     * @param   string       $field
     * @param   string|null  $name
     * @return  object
     */
    function max($field,$name=NULL) {
        $func='MAX('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
     * Build MIN statement
     * @param   string       $field
     * @param   string|null  $name
     * @return  object
     */
    function min($field,$name=NULL) {
        $func='MIN('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
     * Build SUM statement
     * @param   string       $field
     * @param   string|null  $name
     * @return  object
     */
    function sum($field,$name=NULL) {
        $func='SUM('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
     * Build COUNT statement
     * @param   string       $field
     * @param   string|null  $name
     * @return  object
     */
    function count($field,$name=NULL) {
        $func='COUNT('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
     * Build AVG statement
     * @param   string       $field
     * @param   string|null  $name
     * @return  object
     */
    function avg($field,$name=NULL) {
        $func='AVG('.$field.')'.(!is_null($name)?' AS '.$name:'');
        $this->select=($this->select=='*'?$func:$this->select.', '.$func);
        return $this;
    }

    /**
     * Build JOIN statement
     * @param   string       $table
     * @param   string|null  $field1
     * @param   string|null  $op
     * @param   string|null  $field2
     * @param   string       $type
     * @return  object
     */
    function join($table,$field1=NULL,$op=NULL,$field2=NULL,$type='') {
        $on=$field1;
        $table=$this->prefix.$table;
        if (!is_null($op))
            $on=(!in_array($op,explode('|',self::OPERATORS))
                ?$this->prefix.$field1.' = '.$this->prefix.$op
                :$this->prefix.$field1.' '.$op.' '.$this->prefix.$field2);
        if (is_null($this->join))
            $this->join=' '.$type.'JOIN '.$table.' ON '.$on;
        else $this->join=$this->join.' '.$type.'JOIN '.$table.' ON '.$on;
        return $this;
    }

    /**
     * Build INNER JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function inner($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'INNER ');
        return $this;
    }

    /**
     * Build LEFT JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function left($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'LEFT ');
        return $this;
    }

    /**
     * Build RIGHT JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function right($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'RIGHT ');
        return $this;
    }

    /**
     * Build FULL OUTER JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function full_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'FULL OUTER ');
        return $this;
    }

    /**
     * Build LEFT OUTER JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function left_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'LEFT OUTER ');
        return $this;
    }

    /**
     * Build RIGHT OUTER JOIN statement
     * @param   string  $table
     * @param   string  $field1
     * @param   string  $op 
     * @param   string  $field2
     * @return  object
     */
    function right_outer($table,$field1,$op='',$field2='') {
        $this->join($table,$field1,$op,$field2,'RIGHT OUTER ');
        return $this;
    }

    /**
     * Build WHERE statement
     * @param   string       $where
     * @param   string|null  $op
     * @param   string|null  $val
     * @param   string|null  $type
     * @param   string       $and_or
     * @return  object
     */
    function where($where,$op=NULL,$val=NULL,$type='',$and_or='AND') {
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
            elseif (!in_array($op,explode('|',self::OPERATORS))||$op==FALSE)
                $where=$type.$where.' = '.$this->escape($op);
            else $where=$type.$where.' '.$op.' '.$this->escape($val);
        }
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=FALSE;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
     * Build OR WHERE statement
     * @param   string       $where
     * @param   string|null  $op
     * @param   string|null  $val
     * @return  object
     */
    function or_where($where,$op=NULL,$val=NULL) {
        $this->where($where,$op,$val,'','OR');
        return $this;
    }

    /**
     * Build NOT WHERE statement
     * @param   string       $where
     * @param   string|null  $op
     * @param   string|null  $val
     * @return  object
     */
    function not_where($where,$op=NULL,$val=NULL) {
        $this->where($where,$op,$val,'NOT ','AND');
        return $this;
    }

    /**
     * Build OR NOT WHERE statement
     * @param   string       $where
     * @param   string|null  $op
     * @param   string|null  $val
     * @return  object
     */
    function ornot_where($where,$op=NULL,$val=NULL) {
        $this->where($where,$op,$val,'NOT ','OR');
        return $this;
    }

    /**
     * Grouping
     * @param   Closure  $obj
     * @return  object
     */
    function grouped(\Closure $obj) {
        $this->grouped=TRUE;
        call_user_func_array($obj,[$this]);
        $this->where.=')';
        return $this;
    }

    /**
     * Build WHERE IN statement
     * @param   string       $field
     * @param   array        $keys 
     * @param   string|null  $type 
     * @param   string       $and_or
     * @return  object        
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
                $this->grouped=FALSE;
            }
            if (is_null($this->where))
                $this->where=$where;
            else $this->where=$this->where.' '.$and_or.' '.$where;
        }
        return $this;
    }

    /**
     * Build WEHRE NOT IN statement
     * @param   string  $field
     * @param   array   $keys 
     * @return  object
     */
    function not_in($field,array $keys) {
        $this->in($field,$keys,'NOT ','AND');
        return $this;
    }

    /**
     * Build OR WEHRE IN statement
     * @param   string  $field
     * @param   array   $keys 
     * @return  object
     */
    function or_in($field,array $keys) {
        $this->in($field,$keys,'','OR');
        return $this;
    }

    /**
     * Build OR WEHRE NOT IN statement
     * @param   string  $field
     * @param   array   $keys 
     * @return  object
     */
    function ornot_in($field,array $keys) {
        $this->in($field,$keys,'NOT ','OR');
        return $this;
    }

    /**
     * Build BETWEEN statement
     * @param   string       $field
     * @param   string       $value1
     * @param   string       $value2
     * @param   string|null  $type
     * @param   string       $and_or
     * @return  object
     */
    function between($field,$value1,$value2,$type='',$and_or='AND') {
        $where=$field.' '.$type.'BETWEEN '.$this->escape($value1).' AND '.$this->escape($value2);
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=FALSE;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
     * Build NOT BERWEEN statement
     * @param   string  $field
     * @param   string  $value1
     * @param   string  $value2
     * @return  object      
     */
    function not_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'NOT ','AND');
        return $this;
    }

    /**
     * Build OR BERWEEN statement
     * @param   string  $field
     * @param   string  $value1
     * @param   string  $value2
     * @return  object      
     */
    function or_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'','OR');
        return $this;
    }

    /**
     * Build OR NOT BERWEEN statement
     * @param   string  $field
     * @param   string  $value1
     * @param   string  $value2
     * @return  object      
     */
    function ornot_between($field,$value1,$value2) {
        $this->between($field,$value1,$value2,'NOT ','OR');
        return $this;
    }

    /**
     * Build LIKE statement
     * @param   string       $field
     * @param   mixed        $data 
     * @param   string|null  $type 
     * @param   string       $and_or
     * @return  object
     */
    function like($field,$data,$type='',$and_or='AND') {
        $where=$field.' '.$type.'LIKE '.$this->escape($data);
        if ($this->grouped) {
            $where='('.$where;
            $this->grouped=FALSE;
        }
        if (is_null($this->where))
            $this->where=$where;
        else $this->where=$this->where.' '.$and_or.' '.$where;
        return $this;
    }

    /**
     * Build OR LIKE statement
     * @param   string  $field
     * @param   mixed   $data 
     * @return  object
     */
    function or_like($field,$data) {
        $this->like($field,$data,'','OR');
        return $this;
    }

    /**
     * Build NOT LIKE statement
     * @param   string  $field
     * @param   mixed   $data 
     * @return  object
     */
    function not_like($field,$data) {
        $this->like($field,$data,'NOT ','AND');
        return $this;
    }

    /**
     * Build OR NOT LIKE statement
     * @param   string  $field
     * @param   mixed   $data 
     * @return  object
     */
    function ornot_like($field,$data) {
        $this->like($field,$data,'NOT ','OR');
        return $this;
    }

    /**
     * Buils LIMIT statement
     * @param   integer       $limit
     * @param   integer|null  $limit_end
     * @return  object
     */
    function limit($limit,$limit_end=NULL) {
        if (!is_null($limit_end))
            $this->limit=$limit.', '.$limit_end;
        else $this->limit=$limit;
        return $this;
    }

    /**
     * Build OFFSET statement
     * @param   integer  $offset
     * @return  object
     */
    function offset($offset) {
        $this->offset=$offset;
        return $this;
    }

    /**
     * Paginate query results
     * @param   integer  $perpage
     * @param   integer  $page
     * @return  object
     */
    function paginate($perpage,$page) {
        $this->limit=$perpage;
        $this->offset=($page-1)*$perpage;
        return $this;
    }

    /**
     * Build ORDER BY statement
     * @param   string       $orderby
     * @param   string|null  $sorting
     * @return  object
     */
    function order_by($orderby,$sorting=NULL) {
        if (!is_null($sorting))
            $this->orderby=$orderby.' '.strtoupper($sorting);
        else {
            if (stristr($orderby,' ')||$orderby=='RAND()')
                $this->orderby=$orderby;
            else $this->orderby=$orderby.' ASC';
        }
        return $this;
    }

    /**
     * Build GROUP BY statement
     * @param   string  $groupby
     * @return  object
     */
    function group_by($groupby) {
        if (is_array($groupby))
            $this->groupby=implode(', ',$groupby);
        else $this->groupby=$groupby;
        return $this;
    }

    /**
     * Build HAVING statement
     * @param   string       $field
     * @param   string|null  $op
     * @param   mixed|null   $val
     * @return  object
     */
    function having($field,$op=NULL,$val=NULL) {
        if (is_array($op)) {
            $x=explode('?',$field);
            $w='';
            foreach ($x as $k=>$v)
                if (!empty($v))
                    $w.=$v.(isset($op[$k])?$this->escape($op[$k]):'');
            $this->having=$w;
        }
        elseif (!in_array($op,explode('|',self::OPERATORS)))
            $this->having=$field.'> '.$this->escape($op);
        else $this->having=$field.' '.$op.' '.$this->escape($val);
        return $this;
    }

    /**
     * Get number of affected rows
     * @return  integer
     */
    function num_rows() {
        return $this->numrows;
    }

    /**
     * Get last insert id
     * @return  integer
     */
    function insert_id() {
        return $this->insertid;
    }

    /**
     * Prints last database error messages
     * @return  void
     */
    function error() {
        $fw=\Alit::instance();
        if ($fw->get('DEBUG')==0)
            $fw->abort(500,self::E_PRODUCTION);
        elseif ($fw->get('DEBUG')==1)
            $fw->abort(500,sprintf("%s",$this->error));
        elseif ($fw->get('DEBUG')>1)
            $fw->abort(500,sprintf(self::E_LASTERROR,$this->error,$this->query));
    }

    /**
     * Get only one/first row of query as array or object
     * @param   boolean|string  $type
     * @return  array|object
     */
    function one($type=FALSE) {
        $this->limit=1;
        $query=$this->many(TRUE);
        if ($type===TRUE)
            return $query;
        else return $this->query($query,FALSE,(($type=='array')?TRUE:FALSE));
    }


    /**
     * Get all affected rows of query as array or object
     * @param   boolean|string  $type
     * @return  array|object
     */
    function many($type=FALSE) {
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
        if ($type===TRUE)
            return $query;
        else return $this->query($query,TRUE,(($type=='array')?TRUE:FALSE));
    }

    /**
     * Execute INSERT statement
     * @param   array   $data
     * @return  object
     */
    function insert($data) {
        $val=implode(', ',array_map([$this,'escape'],$data));
        $query='INSERT INTO '.$this->from.' ('.implode(',',array_keys($data)).') VALUES ('.$val.')';
        $query=$this->query($query);
        if ($query) {
            $this->insertid=$this->conn->lastInsertId();
            return $this->insert_id();
        }
        else return FALSE;
    }

    /**
     * Execute UPDATE statement
     * @param   array   $data
     * @return  object
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
     * Execute DELETE statement
     * @return  boolean
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

    /**
     * Analyze table
     * @return  mixed
     */
    function analyze() {
        return $this->query('ANALYZE TABLE '.$this->from);
    }

    /**
     * Check table
     * @return  mixed
     */
    function check() {
        return $this->query('CHECK TABLE '.$this->from);
    }

    /**
     * Checksum table
     * @return  boolean
     */
    function checksum() {
        return $this->query('CHECKSUM TABLE '.$this->from);
    }

    /**
     * Optimize table
     * @return  boolean
     */
    function optimize() {
        return $this->query('OPTIMIZE TABLE '.$this->from);
    }

    /**
     * Repair table
     * @return  mixed
     */
    function repair() {
        return $this->query('REPAIR TABLE '.$this->from);
    }

    /**
     * Execute sql queries
     * @param   string   $query
     * @param   boolean  $all
     * @param   boolean  $array
     * @return  mixed
     */
    function query($query,$all=TRUE,$array=FALSE) {
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
        $str=FALSE;
        foreach (['select','optimize','check','repair','checksum','analyze'] as $value) {
            if (stripos($this->query,$value)===0) {
                $str=TRUE;
                break;
            }
        }
        $cache=FALSE;
        if (!is_null($this->cache))
            $cache=$this->cache->get($this->query,$array);
        if (!$cache&&$str) {
            $sql=$this->conn->query($this->query);
            if ($sql) {
                $this->numrows=$sql->rowCount();
                if (($this->numrows>0)) {
                    if ($all) {
                        $q=[];
                        while ($result=($array==FALSE)
                        ?$sql->fetchAll(\PDO::FETCH_OBJ)
                        :$sql->fetchAll(\PDO::FETCH_ASSOC))
                            $q[]=$result;
                        $this->result=$q[0];
                    }
                    else {
                        $q=($array==FALSE)?$sql->fetch(\PDO::FETCH_OBJ):$sql->fetch(\PDO::FETCH_ASSOC);
                        $this->result=$q;
                    }
                }
                if (!is_null($this->cache))
                    $this->cache->set($this->query,$this->result);
                $this->cache=NULL;
            }
            else {
                $this->cache=NULL;
                $this->error=$this->conn->errorInfo();
                $this->error=$this->error[2];
                return $this->error();
            }
        }
        elseif ((!$cache&&!$str)||($cache&&!$str)) {
            $this->cache=NULL;
            $this->result=$this->conn->exec($this->query);
            if ($this->result===FALSE) {
                $this->error=$this->conn->errorInfo();
                $this->error=$this->error[2];
                return $this->error();
            }
        }
        else {
            $this->cache=NULL;
            $this->result=$cache;
        }
        $this->querycount++;
        return $this->result;
    }

    /**
     * Add quote to sql query for sql-injection prevention
     * @param   string  $data
     * @return  string
     */
    function escape($data) {
        if ($data===NULL)
            return 'NULL';
        if (is_null($data))
            return NULL;
        return $this->conn->quote(trim($data));
    }

    /**
     * Cache database query
     * @param   integer|float  $time
     * @return  object
     */
    function cache($time) {
        $this->cache=new \DB\SQLCache($this->cachedir,$time);
        return $this;
    }

    /**
     * Count executed queries
     * @return  integer
     */
    function query_count() {
        return $this->querycount;
    }

    /**
     * Get sql query string
     * @return  string
     */
    function sql() {
        return $this->query;
    }

    /**
     * Reset class properties
     * @return  void
     */
    protected function reset() {
        $this->result=[];
        $this->numrows=0;
        $this->from=NULL;
        $this->join=NULL;
        $this->select='*';
        $this->error=NULL;
        $this->where=NULL;
        $this->query=NULL;
        $this->limit=NULL;
        $this->offset=NULL;
        $this->having=NULL;
        $this->orderby=NULL;
        $this->groupby=NULL;
        $this->grouped=FALSE;
        $this->insertid=NULL;
        return;
    }

    /**
     * Class destructor
     * @return  void
     */
    function __destruct() {
        $this->conn=NULL;
    }
}



//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//! SQLCache - File-based caching class
//!~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class SQLCache {

    private
        // SQL Cache directory
        $cachedir=NULL,
        // Cache time
        $cache=NULL,
        // Elapsed cache time
        $elapsed=NULL;

    /**
     * Class constructor
     * @param   string|null  $dir
     * @param   integer      $time
     * @return  void
     */
    function __construct($dir=NULL,$time=0) {
        if (!file_exists($dir))
            mkdir($dir,0755);
        $this->cachedir=$dir;
        $this->cache=$time;
        $this->elapsed=time()+$time;
    }

    /**
     * Write cache data to file
     * @param   string  $sql
     * @param   mixed   $result
     * @return  void
     */
    function set($sql,$result) {
        if (is_null($this->cache))
            return FALSE;
        $target=$this->cachedir.md5($this->filename($sql)).'.db.cache';
        $target=fopen($target,'w');
        if ($target)
            fputs($target,json_encode(['data'=>$result,'elapsed'=>$this->elapsed]));
        return;
    }

    /**
     * Read cache data from file
     * @param   string  $sql
     * @param   boolean $array
     * @return  void|boolean
     */
    function get($sql,$array=FALSE) {
        if (is_null($this->cache))
            return FALSE;
        $target=$this->cachedir.md5($this->filename($sql)).'.db.cache';
        if (file_exists($target)) {
            $cache=json_decode(\Alit::instance()->read($target),$array);
            if ((($array===TRUE)?$cache['elapsed']:$cache->finish)<time()) {
                unlink($target);
                return;
            }
            else return (($array===TRUE)?$cache['data']:$cache->data);
        }
        return FALSE;
    }

    /**
     * Set cache filename (MD5 encrypted)
     * @param   string  $name
     * @return  string
     */
    private function filename($name) {
        return md5($name);
    }
}
