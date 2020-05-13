<?php
/**
 * @name TCMemcachedManager
 * @property Memcached $cache
 * @author liujianchun
 */
class TCMemcachedManager{

	private static $_instance;
	private $_caches = array();
	
	private final function __construct() {}
	
	public static function getInstance(){
		if(!self::$_instance){
			self::$_instance = new TCMemcachedManager();
		}
		return self::$_instance;
	}
	
	public function __get($name){
		if(!isset($this->_caches[$name])){
			$config = Yaf_Application::app()->getConfig();
			if(isset($config->$name)){
				if($config->$name->persistent) $m = new Memcached($name);
				else $m = new Memcached();
				$m->setOption(Memcached::OPT_CONNECT_TIMEOUT,50);
				$m->addServer($config->$name->host, $config->$name->port?$config->$name->port:11211);
				$this->_caches[$name] = $m;
			}else{
				throw new Exception("memecached config of {$name} not found");
			}
		}
		return $this->_caches[$name];
	}
	
	public function __set($name, $cache){
		$this->_caches[$name] = $cache;
	}
}