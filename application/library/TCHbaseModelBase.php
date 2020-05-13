<?php

use Luffy\Thrift2Hbase\TResult;
use Luffy\Thrift2Hbase\TScan;

/**
 * @name TCHbaseModelBase
 * 只考虑单列族情况
 * @property string $row_key
 */
abstract class TCHbaseModelBase {

  protected $_vars;

  public function family() {
    return 'f';
  }

  public abstract function getHbaseColumnToAttributeNames(): array;

  public abstract function getHbaseColumnTypes(): array;

  public function getAttributeNameToHbaseColumns(): array {
    return array_flip(static::getHbaseColumnToAttributeNames());
  }

  /**
   * @var AliHbaseThriftService
   */
  private $aliHbaseThriftService;

  /**
   * @var \Luffy\Thrift2Hbase\THBaseServiceClient
   */
  private $client;

  public function __construct($config)
  {
    $this->aliHbaseThriftService = new AliHbaseThriftService('172.22.0.6', 6004, 'root', 'root');
    $this->client = $this->aliHbaseThriftService->getClient();
  }

  /**
   * 子类需要实现该方法
   * @return String
   * @throws
   */
  public static function tableName() {
    throw new RuntimeException("function not implemented", "500");
  }

  /**
   * 根据主键查询一行数据
   * @param $primary_key
   * @return static
   */
  public static function findByPrimaryKey($primary_key) {
    $row = static::hbase()->getRow(static::tableName(), $primary_key);
    $model = null;
    if($row) {
      $model = new static();
      $model->row_key = $primary_key;
      $model->initWithRow($row);
    }
    return $model;
  }


  public function initWithRow($row): void {
    $column_types = static::getHbaseColumnTypes();
    $attribute_names = static::getHbaseColumnToAttributeNames();
    foreach($row as $k => $v) {
      if(empty($attribute_names[$k])) continue;
      $column_type = $column_types[$k] ?? 'string';
      $attribute_name = $attribute_names[$k];
      switch($column_type) {
        case 'int':
          $this->_vars[$attribute_name] = unpack('N', $v)[1];
          break;
        case 'long':
          $this->_vars[$attribute_name] = unpack('J', $v)[1];
          break;
        default:
          $this->_vars[$attribute_name] = $v;
          break;
      }
    }
  }

  protected function convertToHbaseRow(): array {
    $row = [];
    $hbase_column_names = static::getAttributeNameToHbaseColumns();
    $column_types = static::getHbaseColumnTypes();
    foreach($this->_vars as $k => $v) {
      if(empty($hbase_column_names[$k])) continue;
      $column_name = $hbase_column_names[$k];
      $column_type = $column_types[$column_name] ?? 'string';
      switch($column_type) {
        case 'int':
          $row[$column_name] = pack('N', intval($v));
          break;
        case 'long':
          $row[$column_name] = pack('J', intval($v));
          break;
        default:
          $row[$column_name] = $v;
          break;
      }
    }
    return $row;
  }

  /**
   * 根据一批主键查询数据
   * @param string[] $primary_keys
   * @return static[]
   */
  public static function findAllByPrimaryKeys($primary_keys) {
    $results = static::hbase()->getRowMultiple(static::tableName(), $primary_keys);
    $models = [];
    foreach($results as $key => $row) {
      $item = new static();
      $item->row_key = $key;
      $item->initWithRow($row);
      $models[] = $item;
    }
    return $models;
  }

  public function save() {
    static::hbase()->putValue(static::tableName(), $this->row_key, $this->family(), $this->convertToHbaseRow());
  }

  public function delete() {
    static::hbase()->deleteByRowKey(static::tableName(), $this->row_key);
  }


  /**
   * @param static[] $models
   * @throws \Luffy\Thrift2Hbase\TIOError
   */
  public static function batchInsert($models) {
    if(!$models) return;
    $params = [];
    foreach($models as $model) {
      $item = ['row' => $model->row_key, 'family' => $model->family()];
      $item['columns'] = $model->convertToHbaseRow();
      $params[] = $item;
    }
    static::hbase()->putMultiple(static::tableName(), $params);
  }

  /**
   * @return static[]
   * @param string $start_row_key 查询的起始 row key，包含这条
   * @param string $end_row_key 查询的结束 row key，不包含这条
   * @param int $limit 最多取出多少行
   * @return static[]
   */
  public static function findAllByRowKeyRange(string $start_row_key, string $end_row_key, int $limit = 100000) {
    $scan = new TScan();
    $scan->startRow = $start_row_key;
    $scan->stopRow = $end_row_key;
    $client = static::hbase()->getClient();
    $results = $client->getScannerResults(static::tableName(), $scan, $limit);
    $models = [];
    foreach($results as $result_item) {
      /** @var TResult $result_item */
      $model = new static();
      $model->row_key = $result_item->row;
      $row = [];
      foreach($result_item->columnValues as $column_value) {
        $row[$column_value->qualifier] = $column_value->value;
      }
      $model->initWithRow($row);
      $models[] = $model;
    }
    return $models;
  }


  public static function deleteByPrimaryKey($primary_key) {
    static::hbase()->deleteByRowKey(static::tableName(), $primary_key);
  }

  public function __get($name) {
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
    $this->_vars[$name] = $value;
  }

  public function __isset($name) {
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
}
