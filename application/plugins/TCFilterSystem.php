<?php

/**
 * @name TCFilterSystemPlugin
 * @author liujianchun
 */
class TCFilterSystemPlugin extends Yaf_Plugin_Abstract {
  private static $_instance;
  /**
   * @var TCControllerBase
   */
  private $controller;

  /**
   * @var TCFilterBase[]
   */
  public $filters;

  /**
   * @return static
   */
  public static function getInstance() {
    if(!self::$_instance) {
      self::$_instance = new static();
    }

    return self::$_instance;
  }

  private function __construct() {
  }

  /**
   * @param $controller
   */
  public function setCurrentController($controller) {
    $this->controller = $controller;
  }

  /**
   * @param TCFilterBase[] $filters
   */
  public function setCurrentFiltersFiltered($filters) {
    $this->filters = $filters;
  }

  /**
   * @link http://www.php.net/manual/en/yaf-plugin-abstract.postdispatch.php
   *
   * @param Yaf_Request_Abstract $request
   * @param Yaf_Response_Abstract $response
   *
   * @return bool true
   */
  public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
    // do post filter here
    if(!empty($this->filters)) {
      foreach($this->filters as $filter) {
        $filter->postFilter();
      }
    }

    return true;
  }


}