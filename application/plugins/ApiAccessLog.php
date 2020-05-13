<?php

/**
 * @name SimpleAccessControlPlugin
 * @author liujianchun
 */
class ApiAccessLogPlugin extends Yaf_Plugin_Abstract {

  public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
    if(strtolower($request->module) != 'api') return; // not api interfaces
    if(Yaf_Application::app()->getConfig()->get('api.access.sign.check')) {
      $ip = $_SERVER['REMOTE_ADDR'];
      if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      if($ip == '127.0.0.1' || $ip == '::1') return; // don't do sign check for localhost access
      if(empty($_GET['sign'])) throw new Exception("sign error", 403);
      $params = $_GET;
      unset($params['sign']);
      foreach($params as $k => $v) {
        // if the value is array type,
        if(gettype($v) == 'array') {
          foreach($v as $kk => $vv) {
            $kk = $k . '[' . $kk . ']';
            $params[$kk] = $vv;
          }
          unset($params[$k]);
        }
      }
      ksort($params);
      $params_string = "";
      foreach($params as $k => $v) {
        if(empty($params_string))
          $params_string = urlencode($k) . '=' . str_replace('+', '%20', urlencode($v));
        else
          $params_string .= '&' . urlencode($k) . '=' . str_replace('+', '%20', urlencode($v));
      }
      $relative_uri = substr($request->getRequestUri(), strlen($request->getBaseUri()));
      $sign_check_string = $relative_uri . $params_string . Yaf_Application::app()->getConfig()->get('api.access.sign.secret');
      $sign = md5($sign_check_string);

      if($sign !== $_GET['sign']) {
        // 对括号进行兼容（安卓）
        $sign_check_string = str_replace('%28', '(', $sign_check_string);
        $sign_check_string = str_replace('%29', ')', $sign_check_string);
        $sign = md5($sign_check_string);
        if($sign !== $_GET['sign']) {
          throw new Exception("sign error", 403);
        }
      }
    }
  }

  public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
    if(strtolower($request->module) != 'api') return;
    $body = $response->getBody();
    if(!empty($body)) {
      echo $body;
      $response->clearBody();
      if(function_exists("fastcgi_finish_request")) fastcgi_finish_request();
    }
    $execute_time = sprintf('%.2f', (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
    $ip = $_SERVER['REMOTE_ADDR'];
    $route_path = $request->module . '/' . $request->controller . '/' . $request->action;
    $params = array_merge($_GET, $request->getParams());
    unset($params['sign']); // not write sign parameter to log
    unset($params['_']); // not write timestamp parameter to log
    $data = $request->method == 'POST' ? $_POST : array();
    $logline = date('Y-m-d H:i:s') . "\t$ip\t$route_path\t" .
      json_encode($params) . "\t" . json_encode($data) . "\t$execute_time";
    $performance_summary = TCPerformanceTracer::summary();
    if(empty($performance_summary)) $logline .= "\t{}";
    else $logline .= "\t" . json_encode($performance_summary);
    $logline .= "\n";
    $filepath = APPLICATION_DIRECTORY . '/api_access_logs/' . date('Y-m-d') . '/' . date('H') . '.log';
    if(!@file_put_contents($filepath, $logline, FILE_APPEND)) {
      @mkdir(dirname($filepath), 0777, true);
      @file_put_contents($filepath, $logline, FILE_APPEND);
    }
  }
}
