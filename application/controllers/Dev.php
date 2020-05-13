<?php

/**
 * @name DevController
 * @author liujianchun
 */
class DevController extends TCControllerBase {

  public function init() {
    parent::init();
    $this->getView()->assign("active_nav_item", Constant::NAV_ITEM_DEV);
  }


  /**
   * 列举访问较慢的接口
   */
  public function slowApisAction() {
    $date = date('Y-m-d', time() - 3600);
    $hour = intval(date('H', time() - 3600));
    if(!empty($_GET['date'])) $date = $_GET['date'];
    if(!empty($_GET['hour'])) $hour = intval($_GET['hour']);
    if($hour < 0 || $hour > 23) $hour = -1;

    $sql = "select
                api_url_id,
                sum(average*`count`) as `sum`,
                sum(average*`count`)/sum(`count`) as average,
                max(max) as max,
                max(percentage_99) as percentage_99,
                max(percentage_95) as percentage_95,
                max(percentage_90) as percentage_90,
                max(percentage_60) as percentage_60,
                sum(`count`) as `count`
            from " . PerformanceMonitorHourlyApiPerformanceDataModel::tableName() . "
            where date=:date";
    if($hour >= 0) $sql .= " and hour={$hour}";
    $sql .= " group by api_url_id";
    $sql .= " order by sum desc";
    $models = PerformanceMonitorHourlyApiPerformanceDataModel::findAllBySql($sql, [':date' => $date]);
    $this->getView()->assign('models', $models);
    $this->getView()->assign('date', $date);
    $this->getView()->assign('hour', $hour);
  }


  public function performanceMonitorAction() {
    $api_url_id = empty($_GET['api_url_id']) ? -1 : intval($_GET['api_url_id']);
    $chart_type = empty($_GET['chart_type']) ? 'count' : $_GET['chart_type'];
    $available_chart_types = array('count', 'max', 'average',
      'percentage_99', 'percentage_95', 'percentage_90', 'percentage_60');
    if(!in_array($chart_type, $available_chart_types)) $chart_type = 'count';
    $this->getView()->assign("api_url_id", $api_url_id);
    $this->getView()->assign("chart_type", $chart_type);


    $chart_dates[] = date('Y-m-d', time());
    $chart_dates[] = date('Y-m-d', time() - 86400);
    $chart_dates[] = date('Y-m-d', time() - 86400 * 2);
    $chart_dates[] = date('Y-m-d', time() - 86400 * 7);
    $chart_dates[] = date('Y-m-d', time() - 86400 * 30);
    $chart_values = array();
    $chart_sums = array();
    foreach($chart_dates as $date) {
      $chart_values[$date] = array();
      $chart_sums[$date] = array();
      for($i = 0; $i < 24; $i++) {
        $chart_values[$date][] = 0;
        $chart_sums[$date][] = 0;
      }
    }

    $chart_dates_in_sql = array();
    foreach($chart_dates as $date) $chart_dates_in_sql[] = "'{$date}'";
    $sql = "select `date`, hour, {$chart_type} as v ";
    if($chart_type == 'average') $sql .= ", `count` ";
    $sql .= " from " .
      PerformanceMonitorHourlyApiPerformanceDataModel::tableName() .
      " where `date` in (" . join(',', $chart_dates_in_sql) . ")";
    if($api_url_id > 0) $sql .= " and api_url_id={$api_url_id}";
    $stmt = PerformanceMonitorHourlyApiPerformanceDataModel::db()->query($sql);
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) != null) {
      if($chart_type == 'count') {
        $chart_values[$row['date']][$row['hour']] += $row['v'];
      } elseif($chart_type == 'average') {
        $chart_sums[$row['date']][$row['hour']] += $row['v'] * $row['count'];
        $chart_values[$row['date']][$row['hour']] += $row['count'];
      } elseif($row['v'] > $chart_values[$row['date']][$row['hour']] * 100) {
        $chart_values[$row['date']][$row['hour']] = $row['v'] / 100;
      }
    }
    if($chart_type == 'average') {
      foreach($chart_dates as $date) {
        for($i = 0; $i < 24; $i++) {
          if($chart_values[$date][$i] == 0) continue;
          $chart_values[$date][$i] = $chart_sums[$date][$i] / $chart_values[$date][$i] / 100;
        }
      }
    }
    $this->getView()->assign("chart_values", $chart_values);
    $this->getView()->assign("chart_dates", $chart_dates);
    $this->getView()->assign("chart_date_names", array('今天', '昨天', '前天', '一周前', '30天前'));
    $this->getView()->assign("chart_visibles", array(true, true, true, false, false));
  }

  public function dailyAccessCountAction() {
    $from_time = empty($_GET['from']) ? time() - 86400 * 30 : strtotime($_GET['from']);
    $to_time = empty($_GET['to']) ? time() : strtotime($_GET['to']);
    $from = date('Y-m-d', $from_time);
    $to = date('Y-m-d', $to_time);
    $api_url_id = empty($_GET['api_url_id']) ? -1 : intval($_GET['api_url_id']);
    $this->getView()->assign("from", $from);
    $this->getView()->assign("to", $to);
    $this->getView()->assign("api_url_id", $api_url_id);
    $this->getView()->assign("api_url", PerformanceMonitorApiUrlModel::findById($api_url_id));

    $counts_group_by_date = array();
    $dates = array();
    for($time = $from_time; $time <= $to_time; $time += 86400) {
      $date = date('Y-m-d', $time);
      $dates[] = $date;
      $counts_group_by_date[$date] = 0;
    }
    $sql = "select `date`, `count` as c ";
    $sql .= "from " . PerformanceMonitorHourlyApiPerformanceDataModel::tableName();
    $sql .= " where 1";
    if($api_url_id > 0) $sql .= " and api_url_id=" . $api_url_id;
    $sql .= " and `date`>='{$from}' and `date`<='{$to}'";
    $stmt = PerformanceMonitorHourlyApiPerformanceDataModel::db()->query($sql);
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) != null) {
      $counts_group_by_date[$row['date']] += $row['c'];
    }
    $this->getView()->assign("dates", $dates);
    $this->getView()->assign("counts_group_by_date", $counts_group_by_date);
  }
}

