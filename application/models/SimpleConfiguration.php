<?php

/**
 * @property int $id
 * @property string $key
 * @property string $name 该配置的可读的名称
 * @property int $type 配置的类型
 * @property string $value
 */
class SimpleConfigurationModel extends TCModelBase {

  const TYPE_INTEGER = 1;
  const TYPE_DOUBLE = 2;
  const TYPE_TEXT = 3;
  const TYPE_IMAGE = 4;
  const TYPE_FILE = 5;

  public static $types = [
    self::TYPE_INTEGER => '整数',
    self::TYPE_DOUBLE => '小数',
    self::TYPE_TEXT => '文本',
    self::TYPE_IMAGE => '图片',
    self::TYPE_FILE => '文件',
  ];

  public function __construct() {
    $this->type = 0;
  }

  public static function get($key) {
    $cache = TCMemcachedManager::getInstance()->cache;
    $cache_key = __CLASS__ . ':' . $key;
    $cached_value = $cache->get($cache_key);
    if($cached_value !== false) return $cached_value;
    $model = self::findByAttributes(['key' => $key]);
    if(!$model) return null;
    $value = $model->value;
    if($model->type === self::TYPE_INTEGER) $value = intval($model->value);
    if($model->type === self::TYPE_DOUBLE) $value = doubleval($model->value);
    $cache->set($cache_key, $value, 600);
    return $value;
  }

  public static function tableName() {
    return 'simple_configurations';
  }

  protected function attributesForInsert() {
    return array('key', 'name', 'type', 'value');
  }
}