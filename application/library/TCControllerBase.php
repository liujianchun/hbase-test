<?php

/**
 * @name TCControllerBase
 * @author liujianchun
 */
class TCControllerBase extends Yaf_Controller_Abstract {
  /**
   * The flash provides a way to pass temporary values between actions.
   * Anything you place in the flash will be exposed to the very next action and then cleared out.
   * @var TCControllerFlash
   */
  protected $flash;

  /**
   * return actions that only allow http post method
   */
  protected function postOnlyActions() {
    return array();
  }

  /**
   * @return string[] filter config array
   */
  protected function filters() {
    return array();
  }

  /**
   * @return TCFilterBase[]
   * @throws Exception
   */
  private function loadFiltersFiltered() {
    /**
     * @var TCFilterBase $filter
     */
    $filters = [];
    foreach($this->filters() as $item) {
      $filter_info = $this->loadFilterItemInfo($item);
      if($filter_info['operator'] === '-') {
        if(in_array(strtolower($this->getRequest()->getActionName()), $filter_info['actions'])) {
          // this filter should be excluded in this action
          continue;
        }
      }
      if($filter_info['operator'] === '+') {
        if(!in_array(strtolower($this->getRequest()->getActionName()), $filter_info['actions'])) {
          // this filter isn't included in this action
          continue;
        }
      }
      $class_filepath = APPLICATION_DIRECTORY . '/filters/' . $filter_info['class'] . '.php';
      if(!file_exists($class_filepath)) {
        throw new Exception('filter not exists: ' . $filter_info['class'], 500);
      }
      include_once $class_filepath;
      if(!class_exists($filter_info['class'])) {
        throw new Exception('filter class not defined: ' . $filter_info['class'], 500);
      }
      $filters[] = new $filter_info['class']($this, $filter_info['attributes']);
    }

    return $filters;
  }

  private function loadFilterItemInfo($item) {
    $filter_config = $item;
    if(is_array($item)) {
      $filter_config = array_unshift($item);
    }
    if(preg_match('/^([^-\\+]+)([-+]?)(.*)$/s', $filter_config, $matches)) {
      $info = ['class' => trim($matches[1]), 'actions' => [], 'attributes' => []];
      if(!empty($matches[2])) $info['operator'] = $matches[2];
      foreach(explode(',', $matches[3]) as $action) {
        $action = strtolower(trim($action));
        if(empty($action)) continue;
        $info['actions'][] = $action;
      }
      if(is_array($item)) $info['attributes'] = $item;
      if(!empty($info['class'])) return $info;
    }
    throw new Exception('filter config error of ' . __CLASS__, 500);
  }


  public function getActionUri($action, $params = array()) {
    $url = $this->getRequest()->getBaseUri() . "/" . $this->getRequest()->controller . '/' . $action;
    if(!empty($params)) {
      $params_strings = array();
      foreach($params as $key => $value) {
        $params_strings[] = urlencode($key) . "=" . urlencode($value);
      }
      $url = $url . '?' . join('&', $params_strings);
    }

    return $url;
  }

  public function getModuleActionUri($action, $params = array()) {
    $url = $this->getRequest()->getBaseUri() . "/" . $this->getModuleName() . "/" . $this->getRequest()->controller . '/' . $action;
    if(!empty($params)) {
      $params_strings = array();
      foreach($params as $key => $value) {
        $params_strings[] = urlencode($key) . "=" . urlencode($value);
      }
      $url = $url . '?' . join('&', $params_strings);
    }

    return $url;
  }


  public function createUri($uri, $params = array()) {
    $module_name = $this->getModuleName();
    $controller_name = $this->getRequest()->controller;
    $uri = explode("/", trim($uri, "/"));
    $action = $uri[0];
    if(count($uri) == 2) {
      $controller_name = $uri[0];
      $action = $uri[1];
    } elseif(count($uri) == 3) {
      $module_name = $uri[0];
      $controller_name = $uri[1];
      $action = $uri[2];
    }
    $url = $this->getRequest()->getBaseUri() . "/" . $module_name . "/" . $controller_name . "/" . $action;
    if(!empty($params)) {
      $params_strings = array();
      foreach($params as $key => $value) {
        $params_strings[] = urlencode($key) . "=" . urlencode($value);
      }
      $url = $url . '?' . join('&', $params_strings);
    }

    return $url;
  }

  /**
   * override parent controller's init to add flash and filter system support
   * @throws Exception
   */
  public function init() {
    $this->flash = new TCControllerFlash();
    foreach($this->postOnlyActions() as $action) {
      if(strtolower($action) == $this->getRequest()->action) {
        if($this->getRequest()->isGet()) {
          throw new Exception("Access Denied", 403);
        }
      }
    }
    if(!Yaf_Application::app()->getConfig()->get("is_cli")) {
      $this->getView()->assign("controller", $this);
      $this->getView()->assign("flash", $this->flash);
      $this->getView()->assign("controller_name", substr(get_class($this), 0, -10));
      $this->getView()->assign("breadcrumbs", []);
    }

    if(!empty($this->filters())) {
      // do pre filter
      $filters = $this->loadFiltersFiltered();
      foreach($filters as $filter) {
        if(!$filter->preFilter()) {
          throw new Exception('filter failed in ' . get_class($filter), 403);
        }
      }
      TCFilterSystemPlugin::getInstance()->setCurrentController($this);
      TCFilterSystemPlugin::getInstance()->setCurrentFiltersFiltered($filters);
    }
  }

  private function writeJsonResponse($data, $status = "success", $message = "", $extraData = array()) {
    header("Content-type: application/json;charset=utf-8");
    $result = array(
      "status" => $status,
      "timestamp" => time(),
    );
    if($data !== null) $result['data'] = TCModelBase::asJsonObjectForValue($data);
    if($message) $result['message'] = $message;
    if($extraData) {
      foreach($extraData as $key => $value) {
        $result[$key] = $value;
      }
    }
    if(isset($_GET['callback']))
      echo $_GET['callback'] . "(" . json_encode($result, JSON_UNESCAPED_UNICODE) . ")";
    else
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
    if(function_exists("fastcgi_finish_request")) fastcgi_finish_request();
  }

  protected function writeErrorJsonResponse($message = "", $error_code = false) {
    if($error_code === false) {
      return $this->writeJsonResponse(null, "error", $message, array());
    } else
      return $this->writeJsonResponse(null, "error", $message, array('error_code' => $error_code));
  }

  protected function writeErrorJsonResponseCaseAccessDenied($message = "") {
    return $this->writeErrorJsonResponse("access denied:" . $message);
  }

  protected function writeErrorJsonResponseCaseParamsError() {
    return $this->writeErrorJsonResponse("params error");
  }

  protected function writeSuccessJsonResponse($data = null, $extraData = array()) {
    $this->writeJsonResponse($data, "success", "", $extraData);
  }

  protected function writeJsonResponseWithJsonString($json_string) {
    header("Content-type: application/json;charset=utf-8");
    echo $json_string;
    if(function_exists("fastcgi_finish_request")) fastcgi_finish_request();
  }

  /**
   * @return TCLayoutViewAdapter
   */
  public function getView() {
    return parent::getView();
  }
}
