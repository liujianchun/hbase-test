<?php

/**
 * @name TCRedisManager
 * @property Redis $redis
 * @author liujianchun
 */
class TCRedisManager {
  private static $_instance;
  private $_dbs = array();

  private final function __construct() {
  }

  public static function getInstance() {
    if(!self::$_instance) {
      self::$_instance = new TCRedisManager();
    }

    return self::$_instance;
  }


  public function __get($name) {
    if(!isset($this->_dbs[$name])) {
      $config = Yaf_Application::app()->getConfig();
      if(isset($config->$name)) {
        $this->_dbs[$name] = new Redis();
        $host = $config->$name->host ? $config->$name->host : '127.0.0.1';
        $port = $config->$name->port ? $config->$name->port : 6379;
        $this->_dbs[$name]->connect($host, $port);
      } else {
        throw new Exception("redis config of {$name} not found");
      }
    }

    return $this->_dbs[$name];
  }

  public function __set($name, $db) {
    $this->_dbs[$name] = $db;
  }

}

