<?php


/**
 * @author liujianchun
 * @property int $id
 * @property string $path relative path of this interface
 */
class PerformanceMonitorApiUrlModel extends TCModelBase {

  public function __construct() {
  }
  
  protected function attributesForInsert() {
    return array('path');
  }
  
  public static function createIfNotExists($path) {
    $params = [':path' => $path];
    $sql = "insert ignore into " . self::tableName() . " (path) values (:path)
            on duplicate key update id=last_insert_id(id)";
    if(!static::exec($sql, $params)) return null;
    $model = new self();
    $model->path = $path;
    $model->id = self::db()->lastInsertId();

    return $model;
  }
  

  public static function tableName(){
    return 'performance_monitor_api_urls';
  }

}

