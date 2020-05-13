<?php

/**
 * add master slave support to PDO
 * @author liujianchun
 */
class TCMasterSlavePDO {
  /**
   * @var PDO
   */
  private $master;
  /**
   * @var PDO
   */
  private $slave;
  private $master_config;
  private $slaves_config;
  private $connection_last_active;
  private $is_force_use_master = false;

  /**
   * TCMasterSlavePDO constructor.
   * @param string $dsn master 连接对应的 connection string
   * @param string $username master 连接的用户名
   * @param string $password master 连接的密码
   * @param array $options PDO 连接参数
   */
  public function __construct($dsn = null, $username = null, $password = null, $options = null) {
    if(empty($options)) $options = array();
    if(!isset($options[PDO::ATTR_TIMEOUT])) {
      $options[PDO::ATTR_TIMEOUT] = 1;
    }
    if(!isset($options[PDO::ATTR_ERRMODE])) {
      $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    }
    $this->master_config = array(
      'dsn' => $dsn,
      'username' => $username,
      'password' => $password,
      'options' => $options,
    );
  }

  public function addSlave($dsn, $username, $password, $options = null, $weight = 1) {
    if(!$this->slaves_config) $this->slaves_config = array();
    if(empty($options)) $options = array();
    if(!isset($options[PDO::ATTR_TIMEOUT])) {
      $options[PDO::ATTR_TIMEOUT] = 1;
    }
    if(!isset($options[PDO::ATTR_ERRMODE])) {
      $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    }
    $this->slaves_config[] = array(
      'dsn' => $dsn,
      'username' => $username,
      'password' => $password,
      'options' => $options,
      'weight' => intval($weight),
    );
  }

  public function getMaster() {
    if(!$this->master) {
      $this->master = new PDO(
        $this->master_config['dsn'],
        $this->master_config['username'],
        $this->master_config['password'],
        $this->master_config['options']);
      $this->master->exec("SET NAMES utf8mb4");
    }
    $this->connection_last_active = $this->master;

    return $this->master;
  }

  public function getSlave() {
    if($this->is_force_use_master) return $this->getMaster();
    if($this->slave) {
      $this->connection_last_active = $this->slave;

      return $this->slave;
    }
    while(true) {
      $slave_config = $this->randomAndRemoveASlaveConfig();
      if($slave_config === null) break;
      try {
        $this->slave = new PDO(
          $slave_config['dsn'],
          $slave_config['username'],
          $slave_config['password'],
          $slave_config['options']);
        $this->slave->exec("SET NAMES utf8mb4");
        $this->connection_last_active = $this->slave;

        return $this->slave;
      } catch(PDOException $e) {
        TCLogger::writeLogMessage("failed to connect database: " . $slave_config['dsn']);
      }
    }

    return $this->getMaster();
  }

  private function randomAndRemoveASlaveConfig() {
    if(empty($this->slaves_config)) return null;
    if(count($this->slaves_config) === 1) {
      return array_pop($this->slaves_config);
    }
    $sum_weight = 0;
    foreach($this->slaves_config as $config) $sum_weight += $config['weight'];
    $random = rand(0, $sum_weight - 1);
    foreach($this->slaves_config as $i => $config) {
      $random -= $config['weight'];
      if($random < 0) {
        unset($this->slaves_config[$i]);

        return $config;
      }
    }

    return null;
  }


  /**
   * @param $sql
   * @param $driver_options
   * @return PDOStatement
   */
  public function prepare($sql, $driver_options = array()) {
    if(strtolower(substr($sql, 0, 6)) === 'select') {
      return $this->getSlave()->prepare($sql, $driver_options);
    }

    return $this->getMaster()->prepare($sql, $driver_options);
  }

  /**
   * @return PDOStatement
   */
  public function query($sql) {
    if(strtolower(substr($sql, 0, 6)) === 'select') {
      return $this->getSlave()->query($sql);
    }

    return $this->getMaster()->query($sql);
  }

  /**
   * @return bool
   */
  public function beginTransaction() {
    return $this->getMaster()->beginTransaction();
  }

  /**
   * @return bool
   */
  public function commit() {
    return $this->getMaster()->commit();
  }

  /**
   * @return bool
   */
  public function rollBack() {
    return $this->getMaster()->rollBack();
  }

  /**
   * @return bool
   */
  public function inTransaction() {
    return $this->getMaster()->inTransaction();
  }

  /**
   * @return int
   */
  public function exec($sql) {
    return $this->getMaster()->exec($sql);
  }


  /**
   * @return string
   */
  public function lastInsertId($name = null) {
    return $this->getMaster()->lastInsertId($name);
  }

  /**
   * @return mixed
   */
  public function errorCode() {
    if($this->connection_last_active)
      return $this->connection_last_active->errorCode();

    return null;
  }

  /**
   * @return array
   */
  public function errorInfo() {
    if($this->connection_last_active)
      return $this->connection_last_active->errorInfo();

    return null;
  }

  /**
   * @return string
   */
  public function quote($string, $parameter_type = null) {
    if($this->connection_last_active)
      return $this->connection_last_active->quote($string, $parameter_type);

    return $this->getMaster()->quote($string, $parameter_type);
  }

  /**
   * 设置在接下来的数据库连接中是否强制使用 master 数据库
   * @param bool $force_use_master
   */
  public function forceUseMaster($force_use_master = true) {
    $this->is_force_use_master = $force_use_master;
  }
}

