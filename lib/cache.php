<?php
/**
*   Tiny Cache Library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Cache
*   @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('DS')) die('Direct file access is not allowed.');



class Cache extends \Factory {

	protected
		// Connection string
		$dsn,
		// Memcache or redis object
		$obj,
		// Prefix for cache entries
		$prefix;

	/**
	*	Return timestamp and lifetime of cache entry or FALSE if not found
	*	@param   $key         string
	*	@param   $val         mixed
	*	@return  array|false
	*/
	function exists($key,&$val=null) {
		$fw=\Alit::instance();
		if (!$this->dsn)
			return false;
		$ndx=$this->prefix.'.'.$key;
		$parts=explode('=',$this->dsn,2);
		switch ($parts[0]) {
			case 'apc':
			case 'apcu':      $raw=call_user_func($parts[0].'_fetch',$ndx); break;
			case 'redis':     $raw=$this->obj->get($ndx);                   break;
			case 'memcache':  $raw=memcache_get($this->obj,$ndx);           break;
			case 'memcached': $raw=$this->obj->get($ndx);                   break;
			case 'wincache':  $raw=wincache_ucache_get($ndx);               break;
			case 'xcache':    $raw=xcache_get($ndx);                        break;
			case 'folder':    $raw=$fw->read($parts[1].$ndx);               break;
		}
		if (!empty($raw)) {
			list($val,$time,$ttl)=(array)$fw->unserialize($raw);
			if ($ttl===0||$time+$ttl>microtime(true))
				return [$time,$ttl];
			$val=null;
			$this->erase($key);
		}
		return false;
	}

	/**
	*	Store value in cache
	*	@param   $key         string
	*	@param   $val         mixed
	*	@param   $ttl         int
	*	@return  mixed|false
	*/
	function set($key,$val,$ttl=0) {
		$fw=\Alit::instance();
		if (!$this->dsn)
			return true;
		$ndx=$this->prefix.'.'.$key;
		$time=microtime(true);
		if ($cached=$this->exists($key))
			list($time,$ttl)=$cached;
		$data=$fw->serialize([$val,$time,$ttl]);
		$parts=explode('=',$this->dsn,2);
		switch ($parts[0]) {
			case 'apc':
			case 'apcu':      return call_user_func($parts[0].'_store',$ndx,$data,$ttl);
			case 'redis':     return $this->obj->set($ndx,$data,$ttl?['ex'=>$ttl]:[]);
			case 'memcache':  return memcache_set($this->obj,$ndx,$data,0,$ttl);
			case 'memcached': return $this->obj->set($ndx,$data,$ttl);
			case 'wincache':  return wincache_ucache_set($ndx,$data,$ttl);
			case 'xcache':    return xcache_set($ndx,$data,$ttl);
			case 'folder':    return $fw->write($parts[1].$ndx,$data);
		}
		return false;
	}

	/**
	*	Retrieve value of cache entry
	*	@param   $key         string
	*	@return  mixed|false
	*/
	function get($key) {
		return $this->dsn&&$this->exists($key,$data)?$data:false;
	}

	/**
	*	Erase cache entry
	*	@param   $key  string
	*	@return  bool
	*/
	function erase($key) {
		if (!$this->dsn)
			return;
		$ndx=$this->prefix.'.'.$key;
		$parts=explode('=',$this->dsn,2);
		switch ($parts[0]) {
			case 'apc':
			case 'apcu':      return call_user_func($parts[0].'_delete',$ndx);
			case 'redis':     return $this->obj->del($ndx);
			case 'memcache':  return memcache_delete($this->obj,$ndx);
			case 'memcached': return $this->obj->delete($ndx);
			case 'wincache':  return wincache_ucache_delete($ndx);
			case 'xcache':    return xcache_unset($ndx);
			case 'folder':    return @unlink($parts[1].$ndx);
		}
		return false;
	}

	/**
	*	Clear contents of cache backend
	*	@param   $suffix  string
	*	@return  bool
	*/
	function reset($suffix=null) {
		if (!$this->dsn)
			return true;
		$regex='/'.preg_quote($this->prefix.'.','/').'.+'.
			preg_quote($suffix,'/').'/';
		$parts=explode('=',$this->dsn,2);
		switch ($parts[0]) {
			case 'apc':
			case 'apcu':
				$info=call_user_func($parts[0].'_cache_info',$parts[0]=='apcu'?false:'user');
				if (!empty($info['cache_list'])) {
					$key=array_key_exists('info',$info['cache_list'][0])?'info':'key';
					foreach ($info['cache_list'] as $item)
						if (preg_match($regex,$item[$key]))
							call_user_func($parts[0].'_delete',$item[$key]);
				}
				return true;
			case 'redis':
				$keys=$this->obj->keys($this->prefix.'.*'.$suffix);
				foreach($keys as $key)
					$this->obj->del($key);
				return true;
			case 'memcache':
				foreach (memcache_get_extended_stats($this->obj,'slabs') as $slabs)
					foreach (array_filter(array_keys($slabs),'is_numeric') as $id)
						foreach (memcache_get_extended_stats($this->obj,'cachedump',$id) as $data)
							if (is_array($data))
								foreach (array_keys($data) as $key)
									if (preg_match($regex,$key))
										memcache_delete($this->obj,$key);
				return true;
			case 'memcached':
				foreach ($this->obj->getallkeys()?:[] as $key)
					if (preg_match($regex,$key))
						$this->obj->delete($key);
				return true;
			case 'wincache':
				$info=wincache_ucache_info();
				foreach ($info['ucache_entries'] as $item)
					if (preg_match($regex,$item['key_name']))
						wincache_ucache_delete($item['key_name']);
				return true;
			case 'xcache':
				xcache_unset_by_prefix($this->prefix.'.');
				return true;
			case 'folder':
				if ($glob=@glob($parts[1].'*'))
					foreach ($glob as $file)
						if (preg_match($regex,basename($file)))
							@unlink($file);
				return true;
		}
		return false;
	}

	/**
	*	Load/auto-detect cache backend
	*	@param   $dsn    bool|string
	*	@param   $seed   bool|string
	*	@return  string
	*/
	function load($dsn,$seed=null) {
		$fw=\Alit::instance();
		if ($dsn=trim($dsn)) {
			if (preg_match('/^redis=(.+)/',$dsn,$parts)
			&&extension_loaded('redis')) {
				list($host,$port,$db)=explode(':',$parts[1])+[1=>6379,2=>null];
				$this->obj=new \Redis;
				if(!$this->obj->connect($host,$port,2))
					$this->obj=null;
				if(isset($db))
					$this->obj->select($db);
			}
			elseif (preg_match('/^memcache=(.+)/',$dsn,$parts)
			&&extension_loaded('memcache')) {
				foreach ($fw->split($parts[1]) as $server) {
					list($host,$port)=explode(':',$server)+[1=>11211];
					if (empty($this->obj))
						$this->obj=@memcache_connect($host,$port)?:null;
					else memcache_add_server($this->obj,$host,$port);
				}
			}
			elseif (preg_match('/^memcached=(.+)/',$dsn,$parts)
			&&extension_loaded('memcached')) {
				foreach ($fw->split($parts[1]) as $server) {
					list($host,$port)=explode(':',$server)+[1=>11211];
					if (empty($this->obj))
						$this->obj=new \Memcached();
					$this->obj->addServer($host,$port);
				}
			}
			if (empty($this->obj)
			&&!preg_match('/^folder\h*=/',$dsn))
				$dsn=($grep=preg_grep('/^(apc|wincache|xcache)/',array_map('strtolower',get_loaded_extensions())))
					?current($grep):('folder='.$fw->get('TEMP').'cache/');
			if (preg_match('/^folder\h*=\h*(.+)/',$dsn,$parts)
			&&!is_dir($parts[1]))
				mkdir($parts[1],0755,true);
		}
		$this->prefix=$seed?:$fw->get('SEED');
		return $this->dsn=$dsn;
	}

	/**
	*	Class constructor
	*	@param  $dsn  bool|string
	*/
	function __construct($dsn=false) {
		if ($dsn)
			$this->load($dsn);
	}
}
