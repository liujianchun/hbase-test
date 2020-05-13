<?php
use Luffy\AliHbaseThrift\Serivce\AliHbaseThriftService;
use Luffy\Thrift2Hbase\TDelete;

/**
 * HBase导入数据测试
 */

class HBaseController extends TCControllerBase {
  /**
   * @var AliHbaseThriftService
   */
  private $aliHbaseThriftService;

  /**
   * @var \Luffy\Thrift2Hbase\THBaseServiceClient
   */
  private $client;

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
    $this->aliHbaseThriftService = new AliHbaseThriftService('172.22.0.6', 6005, 'root', 'root');
    $this->client = $this->aliHbaseThriftService->getClient();
    $table_name = "test";
    $family = "f";
    $row_key = "1,2020-05-13";

    $putValueArr = [
      "coupons_count" => "1",
      "continuous_days" => "30",
      "extra_data" => '{"reward_id":1}',
    ];
    var_dump($this->aliHbaseThriftService->getClient());
    $this->aliHbaseThriftService->putValue($table_name, $row_key, $family, $putValueArr);
    $get_row = $this->aliHbaseThriftService->getRow($table_name, $row_key);
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
    $this->aliHbaseThriftService->putMultiple($table_name, $puts_data);

    // 验证
    $gets_data = [
      [
        "row" => "2,2020-05-13",
      ],
      [
        "row" => "3,2020-05-13",
      ],
    ];
    $gets = $this->aliHbaseThriftService->getMultiple($table_name, $gets_data);
    var_dump($gets);
  }

  public function getAction(){}
}