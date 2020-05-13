<?php

/**
 * API接口示例
 */
class SampleController extends TCControllerBase {

  /**
   * 示例接口
   * @param $platform 客户端所属平台，iOS传入ios，安卓传入android
   * @param $package_name 包名，或者 iOS 的 Bundle Identifier
   * @json:{
   *   "status": "success",          // 接口返回状态，sucess表示成功，error表示失敗
   *   "message": "error message",   // 失败原因
   *   "error_code": -100,           // 失败代码
   *   "data": [                     // SampleResultItem, 一个子模型类数组示例
   *     {
   *       "name": "abc",              // 一个字符串类型的属性
   *       "id": 123,                  // 一个整数类型的属性
   *       "score": 123.5,             // 一个浮点类型的属性
   *       "urls": [],                 // string, 一个字符串数组类型的属性示例
   *       "children_ids": [],         // int, 一个整数数组类型的属性示例
   *       "children_scores": [],      // float, 一个浮点数组类型的属性示例
   *       "children_booleans": [],    // boolean, 一个 boolean 数组类型的属性示例
   *     }
   *   ],
   * }
   */
  public function indexAction() {
    $this->writeSuccessJsonResponse(array(
      array(
        "name" => "abc",
        "id" => 125,
        "score" => 5.52,
        "urls" => array("a", "b"),
        "children_ids" => array(10, 24),
        "children_scores" => array(1.2, 3.5),
        "children_booleans" => array(true, false),
      ),
    ));

    return false;
  }

  /**
   * 展示如何编写 link 注释
   * 返回数据参照 {@link #index}
   */
  public function linkAction() {
  }
}
