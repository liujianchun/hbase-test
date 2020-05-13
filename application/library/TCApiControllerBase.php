<?php

/**
 * @name TCApiControllerBase
 * @author liujianchun
 */
class TCApiControllerBase extends TCControllerBase {

  public function init() {
    parent::init();
    Yaf_Dispatcher::getInstance()->autoRender(false);
    $this->getView()->layout = null;
  }
}

