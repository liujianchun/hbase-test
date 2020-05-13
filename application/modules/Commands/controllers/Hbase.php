<?php
/**
 * HBase导入数据测试
 */

class HBaseController extends TCControllerBase {
  public function importAction() {
    ini_set('memory_limit','2G');
    $file_path = APPLICATION_DIRECTORY . '/data/user_opened_gashapon.txt';
    if(!file_exists($file_path)) return;
    foreach(explode("\n", file_get_contents($file_path)) as $line) {
      $line = trim($line);
      if(empty($line)) continue;
      $data = explode("\t", $line);
      $user_id = intval($data[0]);
      $date = $data[1];
      $coupons_count = intval($data[2]);
      $continuous_days = intval($data[3]);
      $extra_data = $data[4];
      echo $user_id . ":" . $date . ":" . $coupons_count . ":" . $continuous_days . ":" . $extra_data . "\n";
    }
  }
}