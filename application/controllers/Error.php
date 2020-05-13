<?php

/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author liujianchun
 */
class ErrorController extends TCControllerBase {

  /**
   * 从2.1开始, errorAction支持直接通过参数获取异常
   * @param Exception $exception
   */
  public function errorAction($exception) {
    $protocol = (isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0");
    $request = $this->getRequest();
    switch($exception->getCode()) {
      case 403:
        header($protocol . " 403 Forbidden");
        break;
      case 500:
        header($protocol . " 500 Internal Server Error");
        break;
      case 501:
        header($protocol . " 501 Not Implemented");
        break;
      case 502:
        header($protocol . " 502 Bad Gateway");
        break;
      case YAF_ERR_NOTFOUND_MODULE:
      case YAF_ERR_NOTFOUND_CONTROLLER:
      case YAF_ERR_NOTFOUND_ACTION:
      case YAF_ERR_NOTFOUND_VIEW:
        header($protocol . " 404 Not Found");
        break;
      default:
        header($protocol . " 500 Internal Server Error");
        break;
    }
    $relative_uri = substr($request->getRequestUri(), strlen($request->getBaseUri()));
    if(strtolower(substr($relative_uri, 0, 4)) == "/api") {
      // for api interfaces
      $this->writeErrorJsonResponse($exception->getMessage(), $exception->getCode());
      TCLogger::writeExceptionLog($exception);

      return false;
    }

    $this->getView()->assign("exception", $exception);
    TCLogger::writeExceptionLog($exception);
  }
}
