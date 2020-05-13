<?php


/**
 * @author liujianchun
 * @property int $id
 * @property string $date
 * @property int $hour
 * @property int $api_url_id
 * @property int $count access count of this interface at this hour
 * @property int $average average time cost of this interface, in milliseconds
 * @property int $max max time cost of this interface, in milliseconds
 * @property int $percentage_99 max time of 99% access cost of this interface, 1/100 in milliseconds
 * @property int $percentage_95 max time of 95% access cost of this interface, 1/100 in milliseconds
 * @property int $percentage_90 max time of 90% access cost of this interface, 1/100 in milliseconds
 * @property int $percentage_60 max time of 60% access cost of this interface, 1/100 in milliseconds
 * @property string $api
 */
class PerformanceMonitorHourlyApiPerformanceDataModel extends TCModelBase {

  public function __construct() {
    $this->api_url_id = 0;
    $this->count = 0;
    $this->average = 0;
    $this->max = 0;
    $this->percentage_99 = 0;
    $this->percentage_95 = 0;
    $this->percentage_90 = 0;
    $this->percentage_60 = 0;
  }

  protected function attributesForInsert() {
    return array('date', 'hour', 'api_url_id', 'count', 'average', 'max', 'percentage_99',
      'percentage_95', 'percentage_90', 'percentage_60');
  }


  public static function tableName() {
    return 'performance_monitor_hourly_api_performance_data';
  }

  public function getApi() {
    $this->_vars['api'] = PerformanceMonitorApiUrlModel::findById($this->api_url_id)->path;

    return $this->_vars['api'];
  }

}

