<?php

/**
 * @name TCModelBase
 * @property PDO $db
 * @author liujianchun
 */
abstract class TCModelBase {
  protected $_db;
  protected $_vars;
  private static $cache_timeout_in_next_find = false;

  protected abstract function attributesForInsert();

  protected function attributesForUpdate() {
    return $this->attributesForInsert();
  }

  /**
   * 子类需要实现该方法
   * @return String
   * @throws
   */
  public static function tableName() {
    throw new Exception("function not implemented", "500");
  }

  /**
   * 在下一个查询中使用缓存，这是一个非常简易的模型类缓存实现
   * @param int $timeout 缓存超时时间
   */
  public static function withCache($timeout) {
    self::$cache_timeout_in_next_find = $timeout;
  }


  /**
   * 加载数据库中的所有数据模型类
   * @return static[]
   */
  public static function all() {
    return self::findAllBySql('select * from ' . static::tableName() . ' order by id desc');
  }

  /**
   * 根据主键ID，加载出模型类
   * @param int $id
   * @return static
   */
  public static function findById($id) {
    $sql = "select * from " . static::tableName() . " where id=" . intval($id);

    return self::findBySql($sql);
  }

  /**
   * 根据主键ID数组，加载出模型类
   * @param int[] $ids
   * @return static[]
   */
  public static function findAllById($ids) {
    if(empty($ids)) return [];
    foreach($ids as $i => $id) {
      $ids[$i] = intval($id);
    }
    $sql = "select * from " . static::tableName() . " where id in (" . join(',', $ids) . ")";

    return self::findAllBySql($sql);
  }

  /**
   * 根据传入的属性值，加载出模型类
   * @param array $attributes
   * @param string $order
   * @return static
   */
  public static function findByAttributes($attributes, $order = null) {
    if(empty($attributes) || !is_array($attributes)) return null;
    $sql = "select * from " . static::tableName() . " where 1";
    $params = [];
    foreach($attributes as $k => $v) {
      // 对属性值传入数组的情况进行支持
      if(is_array($v) && !empty($v)) {
        $index = 0;
        $sql_in_items = [];
        foreach($v as $v_item) {
          $param = ":{$k}{$index}";
          $sql_in_items[] = $param;
          $params[$param] = $v_item;
          $index++;
        }
        $sql .= " and `{$k}` in (" . join(',', $sql_in_items) . ")";
      } else {
        $sql .= " and `{$k}`=:{$k}";
        $params[":{$k}"] = $v;
      }
    }
    if(!empty($order)) {
      $sql .= " order by {$order}";
    }
    $sql .= " limit 1";

    return static::findBySql($sql, $params);
  }

  /**
   * 根据传入的sql查询，加载出模型类
   * @param string $sql
   * @param array $params
   * @return static
   * @throws
   */
  public static function findBySql($sql, $params = array()) {
    // 从缓存中读取结果
    $cache_key = md5(__FUNCTION__ . $sql . json_encode($params));
    if(self::$cache_timeout_in_next_find !== false) {
      $cached_value = TCMemcachedManager::getInstance()->cache->get($cache_key);
      if(is_array($cached_value)) {
        $model = new static();
        $model->setAttributes($cached_value);
        self::$cache_timeout_in_next_find = false;

        return $model;
      }
    }

    // 缓存失效，进行数据库查询
    TCPerformanceTracer::start('model.sql');
    $stmt = static::db()->prepare($sql);
    if(!$stmt->execute($params)) throw new Exception(json_encode($stmt->errorInfo()), 500);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);
    if(!$row) return null;
    $model = new static();
    $model->setAttributes($row, self::getRowTypesOfStatement($stmt));

    // 把结果存储到缓存中
    if(self::$cache_timeout_in_next_find !== false) {
      $cached_value = $model->getAttributes();
      $result = TCMemcachedManager::getInstance()->cache->set(
        $cache_key, $cached_value, self::$cache_timeout_in_next_find);
      if($result === false) {
        TCLogger::writeExceptionLog(new Exception(
          "faied to save cache with error " .
          TCMemcachedManager::getInstance()->cache->getResultMessage()
        ));
      }
    }
    self::$cache_timeout_in_next_find = false;

    return $model;
  }

  /**
   * 根据传入的属性值，加载出模型类
   * @param array $attributes
   * @param string $order 排序参数
   * @param string $limit 分页参数
   * @return static[]
   */
  public static function findAllByAttributes($attributes, $order = null, $limit = null) {
    if(empty($attributes) || !is_array($attributes)) return null;
    $sql = "select * from " . static::tableName() . " where 1";
    $params = [];
    foreach($attributes as $k => $v) {
      // 对属性值传入数组的情况进行支持
      if(is_array($v) && !empty($v)) {
        $index = 0;
        $sql_in_items = [];
        foreach($v as $v_item) {
          $param = ":{$k}{$index}";
          $sql_in_items[] = $param;
          $params[$param] = $v_item;
          $index++;
        }
        $sql .= " and `{$k}` in (" . join(',', $sql_in_items) . ")";
      } else {
        $sql .= " and `{$k}`=:{$k}";
        $params[":{$k}"] = $v;
      }
    }
    if(!empty($order)) {
      $sql .= " order by {$order}";
    }
    if(!empty($limit)) {
      $sql .= " limit $limit";
    }

    return static::findAllBySql($sql, $params);
  }

  /**
   * 根据传入的属性值，计算总共有多少条数据库记录
   * @param array $attributes
   * @return static[]
   */
  public static function countByAttributes($attributes) {
    if(empty($attributes) || !is_array($attributes)) return null;
    $sql = "select count(*) from " . static::tableName() . " where 1";
    $params = [];
    foreach($attributes as $k => $v) {
      $sql .= " and `{$k}`=:{$k}";
      $params[":{$k}"] = $v;
    }

    return static::countBySql($sql, $params);
  }

  /**
   * 根据传入的sql查询，加载出模型类
   * @param string $sql 查询 sql 语句
   * @param array $params 查询参数
   * @return static[]
   */
  public static function findAllBySql($sql, $params = array()) {
    $models = array();

    // 从缓存中读取结果
    $cache_key = md5(__FUNCTION__ . $sql . json_encode($params));
    if(self::$cache_timeout_in_next_find !== false) {
      $cached_value = TCMemcachedManager::getInstance()->cache->get($cache_key);
      if(is_array($cached_value)) {
        foreach($cached_value as $item) {
          $model = new static();
          $model->setAttributes($item);
          $models[] = $model;
        }
        self::$cache_timeout_in_next_find = false;

        return $models;
      }
    }

    // 缓存失效
    TCPerformanceTracer::start('model.sql');
    $stmt = static::db()->prepare($sql);
    if($stmt->execute($params)) {
      $types = self::getRowTypesOfStatement($stmt);
      while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) != null) {
        $model = new static();
        $model->setAttributes($row, $types);
        $models[] = $model;
      }
    }
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);

    // 把结果存储到缓存中
    if(self::$cache_timeout_in_next_find !== false) {
      $cached_value = [];
      foreach($models as $model) {
        $cached_value[] = $model->getAttributes();
      }
      $result = TCMemcachedManager::getInstance()->cache->set(
        $cache_key, $cached_value, self::$cache_timeout_in_next_find);
      if($result === false) {
        TCLogger::writeExceptionLog(new Exception(
          "faied to save cache with error " .
          TCMemcachedManager::getInstance()->cache->getResultMessage()
        ));
      }
    }
    self::$cache_timeout_in_next_find = false;

    return $models;
  }

  /**
   * 把传入的select查询sql转换为count查询，并返回该查询的结果个数
   * @return integer
   */
  public static function countBySql($sql, $params = array()) {
    $sql = trim($sql);
    $sql = preg_replace('/^select(.*?)from/s', 'select count(*) as c from', $sql);
    $sql = preg_replace('/^(.*?)order by.*/s', '$1', $sql);
    $sql = preg_replace('/^(.*?)limit.*/s', '$1', $sql);
    TCPerformanceTracer::start('model.sql');
    $stmt = static::db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);
    if($row) return intval($row['c']);
    else return 0;
  }


  /**
   * 在数据库中删除这个模型数据
   */
  public function delete() {
    if(!$this->id) {
      throw new Exception("table may have no primary key field id", "500");
    }
    $sql = "delete from " . static::tableName() . " where id=:id";
    static::exec($sql, array(":id" => $this->id));
  }

  /**
   * 根据传入的属性值，在数据库中删除相对应的数据
   * @param array $attributes
   */
  public static function deleteAllByAttributes($attributes) {
    if(empty($attributes) || !is_array($attributes) || empty($attributes)) return;
    $sql = "delete from " . static::tableName() . " where 1";
    $params = [];
    foreach($attributes as $k => $v) {
      $sql .= " and `{$k}`=:{$k}";
      $params[":{$k}"] = $v;
    }

    static::exec($sql, $params);
  }

  /**
   * 把这个模型数据保存到数据库中
   * 当主键ID存在时，执行更新，否则执行插入
   */
  public function save() {
    if($this->id) return $this->update();
    else return $this->insert();
  }

  /**
   * 把一些字段更新到数据库中
   */
  public function saveAttributes($attributes) {
    if(empty($attributes)) return false;

    $updates = array();
    $params = array();
    foreach($attributes as $key => $value) {
      $params[":" . $key] = $value;
      $updates[] = "`" . $key . "`" . "=:" . $key;
      $this->$key = $value;
    }
    $params[":id"] = $this->id;
    $sql = "update " . static::tableName() . " set " . join(",", $updates) . " where id=:id";

    return static::exec($sql, $params);
  }

  /**
   * 把这个模型新插入到数据库当中去
   */
  public function insert() {
    $attributes = $this->attributesForInsert();
    if(empty($attributes)) return false;

    $fields = array();
    $params = array();
    foreach($attributes as $attribute) {
      if(is_null($this->$attribute)) continue;
      $fields[] = "`" . $attribute . "`";
      $params[":" . $attribute] = $this->$attribute;
    }
    $tableName = static::tableName();
    $columnNamesSql = join(",", $fields);
    $columnValuesSql = join(",", array_keys($params));
    $sql = "insert into {$tableName} ({$columnNamesSql}) values ({$columnValuesSql})";
    if(!static::exec($sql, $params)) return false;
    if(!$this->id)
      $this->id = $this->db->lastInsertId();

    return true;
  }

  /**
   * 更新这个模型到数据库中去
   */
  public function update() {
    $attributes = $this->attributesForUpdate();
    if(empty($attributes)) return false;

    $updates = array();
    $params = array();
    foreach($attributes as $attribute) {
      $params[":" . $attribute] = $this->$attribute;
      $updates[] = "`" . $attribute . "`" . "=:" . $attribute;
    }
    $params[":id"] = $this->id;
    $sql = "update " . static::tableName() . " set " . join(",", $updates) . " where id=:id";

    return static::exec($sql, $params);
  }

  /**
   * 使用当前的数据库连接执行一个sql语句
   * @return boolean
   */
  public static function exec($sql, $params = array()) {
    TCPerformanceTracer::start('model.sql');
    $stmt = static::db()->prepare($sql);
    if(!$stmt->execute($params)) {
      TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);
      throw new Exception(json_encode($stmt->errorInfo()), 500);

      return false;
    }
    TCPerformanceTracer::end(__METHOD__, __FILE__, __LINE__);

    return true;
  }

  /**
   * 从pdo statement中获取字段类型信息
   * @param PDOStatement $stmt
   */
  protected static function getRowTypesOfStatement($stmt) {
    $types = [];
    for($i = $stmt->columnCount() - 1; $i >= 0; $i--) {
      $meta = $stmt->getColumnMeta($i);
      $types[$meta['name']] = $meta['native_type'];
    }

    return $types;
  }

  /**
   * 批量设置属性值
   * @param array $attributes
   * @param array $attribute_pdo_types
   */
  public function setAttributes($attributes, $attribute_pdo_types = []) {
    if(empty($attributes)) return;
    foreach($attributes as $k => $v) {
      $this->_vars[$k] = $v;
      if(isset($attribute_pdo_types[$k])) {
        if($attribute_pdo_types[$k] === 'LONG'
          || $attribute_pdo_types[$k] === 'TINY'
          || $attribute_pdo_types[$k] === 'SHORT'
        ) {
          $this->_vars[$k] = intval($v);
        } elseif($attribute_pdo_types[$k] === 'NEWDECIMAL') {
          $this->_vars[$k] = floatval($v);
        }
      }
    }
  }

  /**
   * 获取所有数据库字段属性的值
   * @return array
   */
  public function getAttributes() {
    $attributes = [];
    if(isset($this->_vars['id']))
      $attributes['id'] = $this->_vars['id'];
    foreach($this->attributesForInsert() as $attribute) {
      if(isset($this->_vars[$attribute])) {
        $attributes[$attribute] = $this->_vars[$attribute];
      }
    }

    return $attributes;
  }

  /**
   * @return PDO
   */
  public static function db() {
    return TCDbManager::getInstance()->db;
  }

  public function __get($name) {
    if($name == "db") {
      if(!$this->_db) $this->_db = static::db();

      return $this->_db;
    }
    if(isset($this->_vars[$name])) return $this->_vars[$name];
    $method_name = 'get' . ucfirst($name);
    if(method_exists($this, $method_name)) {
      return $this->$method_name();
    }

    return null;
  }

  public function __set($name, $value) {
    $method_name = 'set' . ucfirst($name);
    if(method_exists($this, $method_name)) {
      return $this->$method_name($value);
    }
    if($name == "db") {
      $this->_db = $value;
    } else {
      $this->_vars[$name] = $value;
    }
  }

  public function __isset($name) {
    if($name == "db") return isset($this->_db);
    if(isset($this->_vars[$name])) return true;
    $method_name = 'get' . ucfirst($name);
    if(method_exists($this, $method_name)) {
      return true;
    }

    return false;
  }

  public function __unset($name) {
    unset($this->_vars[$name]);
  }

  /**
   * 子类需要重载该方法以返回模型类在被转换成json数据时，需要输出的字段列表
   * @return array
   */
  public function attributesForJson() {
    return array();
  }

  /**
   * 把该模型类转换为json数据
   * @return stdClass
   */
  public function asJsonObject() {
    $result = new stdClass();
    foreach($this->attributesForJson() as $attribute) {
      $result->$attribute = self::asJsonObjectForValue($this->$attribute);
    }

    return $result;
  }

  /**
   * 把传入的值转换为json数据
   */
  public static function asJsonObjectForValue($value) {
    if($value === true) return true;
    if($value === false) return false;
    if(is_array($value) || is_a($value, "stdClass")) {
      $result = array();
      foreach($value as $key => $item) {
        $result[$key] = self::asJsonObjectForValue($item);
      }

      return $result;
    } elseif(is_string($value) || is_numeric($value)) {
      return $value;
    } elseif(is_a($value, "TCModelBase")) {
      return $value->asJsonObject();
    }
  }
}
