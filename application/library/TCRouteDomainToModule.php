<?php

/**
 * route a domain to a module
 * @author liujianchun
 */
class TCRouteDomainToModule implements Yaf_Route_Interface {
  private $domain;
  private $module;

  public function __construct($domain, $module) {
    $this->domain = $domain;
    $this->module = $module;
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
    if($_SERVER['HTTP_HOST'] != $this->domain) return false;
    $request->module = $this->module;
    $segments = explode('/', trim($request->getRequestUri(), '/'));
    if(count($segments) > 0)
      $request->controller = $segments[0];
    if(count($segments) > 1)
      $request->action = $segments[1];
    if(count($segments) > 2) {
      $request->module = $segments[0];
      $request->controller = $segments[1];
      $request->action = $segments[2];
    }

    return true;
  }
}

