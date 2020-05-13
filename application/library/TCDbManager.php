<?php
/**
 * @name TCDbManager
 * @property TCMasterSlavePDO $db
 * @author liujianchun
 */
class TCDbManager {
  private static $_instance;
  private $_dbs = array();

  private final function __construct() {
  }

  public static function getInstance() {
    if(!self::$_instance) {
      self::$_instance = new TCDbManager();
    }

    return self::$_instance;
  }


  /**
   * 从汇景通用的数据库配置文件中获取数据库配置并连接到数据库
   * @param $db_name
   * @return TCMasterSlavePDO
   */
  public function getConnectionFromCommonDbConfig($db_name) {
    $config_file_path = '/dev/shm/common-db-config.php';
    if(!file_exists($config_file_path)) {
      $config_file_path = '/tmp/common-db-config.php';
    }
    if(!file_exists($config_file_path)) return null;

    $config = require $config_file_path;
    if(empty($config) || empty($config[$db_name])) return null;
    $connection = null;
    if($config[$db_name]['master']) {
      $connection = new TCMasterSlavePDO(
        $config[$db_name]['master']['connectionString'],
        $config[$db_name]['master']['username'],
        $config[$db_name]['master']['password']
      );
    } else {
      $connection = new TCMasterSlavePDO();
    }
    if(!empty($config[$db_name]['slaves'])) {
      foreach($config[$db_name]['slaves'] as $slave_config) {
        $connection->addSlave(
          $slave_config['connectionString'],
          $slave_config['username'],
          $slave_config['password']
        );
      }
    }

    return $connection;
  }


  public function __get($name) {
    if(!isset($this->_dbs[$name])) {
      $config = Yaf_Application::app()->getConfig();
      if(isset($config->$name)) {
        if(!empty($config->$name->connectionString) &&
          preg_match('/dbname=([^;]+)/', $config->$name->connectionString, $matches)
        ) {
          $db_name = $matches[1];
          $this->_dbs[$name] = $this->getConnectionFromCommonDbConfig($db_name);
          if(!empty($this->_dbs[$name])) return $this->_dbs[$name];
        }
        $this->_dbs[$name] = new TCMasterSlavePDO(
          $config->$name->connectionString,
          $config->$name->username,
          $config->$name->password,
          $config->$name->options
        );
        if(!empty($config->$name->slaves)) {
          foreach($config->$name->slaves as $slave_config) {
            $weight = $slave_config->weight ? $slave_config->weight : 1;
            $this->_dbs[$name]->addSlave(
              $slave_config->connectionString,
              $slave_config->username,
              $slave_config->password,
              $slave_config->options,
              $weight
            );
          }
        }
      } else {
        throw new Exception("database config of {$name} not found");
      }
    }

    return $this->_dbs[$name];
  }

  public function __set($name, $db) {
    $this->_dbs[$name] = $db;
  }

}

