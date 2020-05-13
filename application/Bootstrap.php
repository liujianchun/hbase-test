<?php
/**
 * @name Bootstrap
 * @author liujianchun
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract{

  public function _initConfig() {
    $arrConfig = Yaf_Application::app()->getConfig();
    Yaf_Registry::set('config', $arrConfig);
  }

  public function _initPlugin(Yaf_Dispatcher $dispatcher) {
    $dispatcher->registerPlugin(new ApiAccessLogPlugin());
    $dispatcher->registerPlugin(TCFilterSystemPlugin::getInstance());
    // please put SimpleAccessControlPlugin last to get more accurate performance monitor data
    $dispatcher->registerPlugin(new SimpleAccessControlPlugin());
  }

  public function _initRoute(Yaf_Dispatcher $dispatcher) {
  	// the following codes just show how to use domain module route
//   	$route = new TCRouteDomainToModule('api.your.domain', 'api');
//   	$dispatcher->getRouter()->addRoute('domain-to-module', $route);
  }
  
  public function _initView(Yaf_Dispatcher $dispatcher){
  	$view = new TCLayoutViewAdapter(null);
  	$view->assign("request", $dispatcher->getRequest());
    $view->assign("base_uri", $dispatcher->getRequest()->getBaseUri());
    $view->assign("active_nav_item", null);
    $dispatcher->setView($view);
  }
}

