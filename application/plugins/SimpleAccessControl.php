<?php
/**
 * @name SimpleAccessControlPlugin
 * @author liujianchun
 */
class SimpleAccessControlPlugin extends Yaf_Plugin_Abstract {

  public static function isLogin() {
    return !empty($_SESSION['simple.access.control.user']);
  }

  public static function isFromManagement() {
    return self::isLogin() && (
        $_SESSION['simple.access.control.user'] === 'admin' || (
          isset($_SESSION['simple.access.control.from.management']) &&
          $_SESSION['simple.access.control.from.management'] === true
        ));
  }

  public static function currentUser() {
    return $_SESSION['simple.access.control.user'];
  }

  public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
    if($request->module == 'Api') return; // api interfaces
    session_start();
    $ip = $_SERVER['REMOTE_ADDR'];
    if(!empty($_GET['1kxun_management_signature']) && !empty($_GET['1kxun_project_id'])
      && !empty($_GET['1kxun_user_name'])
    ) { // set the management control for user
      if(!$ip) $ip = "1kxun-management-platform";
      if(in_array($ip, array('222.66.37.26', '116.228.139.242', '116.228.144.66')))
        $ip = '222.66.37.26';
      $signature = md5($ip . $_GET['1kxun_project_id'] . $_GET['1kxun_user_name'] . "bd70beceede3f9030c5053bcf7b69cfa");
      if($_GET['1kxun_management_signature'] == $signature) {
        $_SESSION['simple.access.control.user'] = $_GET['1kxun_user_name']; //default set the admin
        $_SESSION['simple.access.control.from.management'] = true;
      }
    }
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    if($ip == '127.0.0.1' || $ip == '::1'
      || $ip == '222.66.37.26' || $ip == '116.228.139.242' || $ip == '116.228.144.66'
      || substr($ip, 0, 8) == '192.168'
    ) return; // internal ip address, allow access

    $path = $request->module . '/' . $request->controller . '/' . $request->action;
    if(in_array($path, array(
      'Index/Index/index',
      'Index/Index/login',
    ))) return; // urls that can be access by anonymous users

    if(!empty($_SESSION['simple.access.control.user'])) {
      // this is a login user, allow access to all urls
      return;
    }
    $login_url = $request->getBaseUri() . "/index/login?redirect=" . urlencode($_SERVER['REQUEST_URI']);
    throw new Exception("<a href='{$login_url}'>login</a> required to access this page", 403);
  }

  public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
    if(Yaf_Application::app()->getConfig()->get('performance.trace.show.yaf.before.action')) {
      $time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
      TCPerformanceTracer::addTraceItem('yaf.before.action', $time);
    }
  }

  public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }

  public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
  }
}
