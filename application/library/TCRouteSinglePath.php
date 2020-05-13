<?php

/**
 * route a single path to a an action
 * @author liujianchun
 */
class TCRouteSinglePath implements Yaf_Route_Interface {
  private $path;
  private $module;
  private $controller;
  private $action;
  private $case_sensitive;

  public function __construct($path, $module, $controller, $action = 'index', $case_sensitive = false) {
    $this->path = $path;
    $this->module = $module;
    $this->controller = $controller;
    $this->action = $action;
    $this->case_sensitive = $case_sensitive;
  }

  /**
   * @see Yaf_Route_Interface::assemble()
   * @param array $info
   * @param array $query
   * @return string
   */
  public function assemble(array $info, array $query = null) {
  }


  /**
   * @see Yaf_Route_Interface::route()
   * @param Yaf_Request_Abstract $request
   * @return bool
   */
  public function route($request) {
    $path = substr($request->getRequestUri(), strlen($request->getBaseUri()));
    $path = rtrim($path, '/');
    if($this->case_sensitive) {
      $is_match = $path === $this->path;
    } else {
      $is_match = strtolower($path) === strtolower($this->path);
    }
    if($is_match) {
      $request->module = $this->module;
      $request->controller = $this->controller;
      $request->action = $this->action;
    }

    return $is_match;
  }
}

