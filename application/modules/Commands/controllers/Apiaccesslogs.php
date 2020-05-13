<?php

/**
 * 日志处理后台命令
 */
class ApiaccesslogsController extends TCControllerBase {

  /**
   * 合并然后压缩某些天的 API 接口请求日志
   * @param $from string 从这一天开始
   * @param $to string 到这一天结束
   */
  public function combineAndCompressLogsAction($from, $to) {
    $from_time = strtotime($from);
    $to_time = strtotime($to);
    for($time = $from_time; $time <= $to_time; $time += 86400) {
      $this->combineAndCompressLogAction(date('Y-m-d', $time));
    }
  }

  /**
   * 合并然后压缩某一天的 API 接口请求日志
   * @param $date
   */
  public function combineAndCompressLogAction($date = 'yesterday') {
    if($date == 'today') $date = date('Y-m-d');
    elseif($date == 'yesterday') $date = date('Y-m-d', time() - 86400);
    $folder_path = APPLICATION_DIRECTORY . '/api_access_logs/' . $date;
    if(!is_dir($folder_path)) {
      echo 'api log folder not exists: ', $folder_path, "\n";

      return;
    }

    $command = "cat $folder_path/*.log | gzip > $folder_path.gz";
    @exec($command, $output, $result);
    if($result !== 0) {
      echo "failed to execute command: ", $command, "\n";
    }
  }
}
