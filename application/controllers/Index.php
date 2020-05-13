<?php

/**
 * @name IndexController
 * @author liujianchun
 */
class IndexController extends TCControllerBase {

  public function indexAction() {
    return true;
  }


  public function loginAction() {
    $this->getView()->assign("active_nav_item", Constant::NAV_ITEM_LOGIN);
    if($this->getRequest()->isPost()) {
      $errors = array();
      if(empty($_POST['username'])) $errors[] = "user name cannot be empty";
      elseif(empty($_POST['password'])) $errors[] = "password cannot be empty";
      else {
        // check user name and password
        $ini = new Yaf_Config_Ini(APPLICATION_PATH . "/conf/application.ini", 'simple.access.control.accounts');
        $accounts = $ini->toArray();
        $username = $_POST['username'];

        if(!empty($accounts[$username]) && md5(md5($_POST['password'])) == $accounts[$username]) {
          $_SESSION['simple.access.control.user'] = $username;
          $redirect = $this->getRequest()->getBaseUri() . '/';
          if(!empty($_GET['redirect'])) $redirect = $_GET['redirect'];
          $this->flash->notice = '登录成功';
          $this->redirect($redirect);

          return false;
        }
        $errors[] = 'user name or password error';
      }
      $this->getView()->assign('errors', $errors);
    }
  }


  public function logoutAction() {
    unset($_SESSION['simple.access.control.user']);
    $this->redirect($this->getRequest()->getBaseUri() . '/');

    return false;
  }
}
