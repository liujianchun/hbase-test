<?php

/**
 * @name TCFilterBase
 * @author liujianchun
 */
class TCFilterBase {

  /**
   * @var TCControllerBase
   */
  private $controller;

  /**
   * @return bool if return false, then the request will stop and action will not run
   */
  public function preFilter() {
    return true;
  }

  /**
   * run some code after action have execute completed
   */
  public function postFilter() {
  }

  /**
   * TCFilterBase constructor.
   * @param TCControllerBase $controller
   * @param array $attributes
   */
  public function __construct($controller, $attributes) {
    $this->controller = $controller;
    foreach($attributes as $key => $value) {
      if(property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }
}

