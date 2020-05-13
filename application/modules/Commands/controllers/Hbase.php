<?php
use Luffy\AliHbaseThrift\Serivce\AliHbaseThriftService;
use Luffy\Thrift2Hbase\TDelete;

/**
 * HBase导入数据测试
 */

class HBaseController extends TCControllerBase {

  public function importAction() {
    ini_set('memory_limit','1G');
    $file_path = APPLICATION_PATH . '/runtime/user_opened_gashapon.txt';
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

  public function deleteAction(){}

  public function putAction(){
    $aliHbaseThriftService = new AliHbaseThriftService('172.22.0.6', 6005, 'root', 'Sg123456');
    //$client = $aliHbaseThriftService->getClient();
    var_dump(1);
    $table_name = "test";
    $family = "f";
    $row_key = "1,2020-05-13";

    $putValueArr = [
      "coupons_count" => "1",
      "continuous_days" => "30",
      "extra_data" => '{"reward_id":1}',
    ];
    $aliHbaseThriftService->putValue($table_name, $row_key, $family, $putValueArr);
    var_dump(2);
    $get_row = $aliHbaseThriftService->getRow($table_name, $row_key);
    var_dump($get_row);

    $puts_data = [
      [
        "row" => "2,2020-05-13",
        "family" => $family,
        "columns" => [
          "coupons_count" => "2",
          "continuous_days" => "50",
          "extra_data" => '{"reward_id":2}',
        ]
      ],
      [
        "row" => "3,2020-05-13",
        "family" => $family,
        "columns" => [
          "coupons_count" => "2",
          "continuous_days" => "60",
          "extra_data" => '{"reward_id":10}',
        ]
      ],
    ];
    $aliHbaseThriftService->putMultiple($table_name, $puts_data);

    // 验证
    $gets_data = [
      [
        "row" => "2,2020-05-13",
      ],
      [
        "row" => "3,2020-05-13",
      ],
    ];
    $gets = $aliHbaseThriftService->getMultiple($table_name, $gets_data);
    var_dump($gets);
  }

  public function getAction(){}
}