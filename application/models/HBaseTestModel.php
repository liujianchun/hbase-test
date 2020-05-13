<?php
/**
 * Created by PhpStorm.
 * User: liujianchun
 * Date: 2020/5/13
 * Time: 下午4:40
 */

class HBaseTestModelModel extends TCHbaseModelBase {
  public function getHbaseColumnToAttributeNames(): array {
    return [
      'a' => 'user_id',
      'b' => 'date',
      'c' => 'coupons_count',
      'd' => 'continuous_days',
      'e' => 'extra_data',
    ];
  }

  public function getHbaseColumnTypes(): array {
    return [
      'a' => 'int',
      'b' => 'string',
      'c' => 'int',
      'd' => 'int',
      'e' => 'string',
    ];
  }


  public static function tableName() {
    return "test";
  }
}