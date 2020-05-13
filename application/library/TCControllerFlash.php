<?php

/**
 * @name TCControllerFlash
 * @author liujianchun
 * @property string $notice
 * @property string $warning
 * @property string $error
 * The flash provides a way to pass temporary values between actions.
 * Anything you place in the flash will be exposed to the very next action and then cleared out.
 */
class TCControllerFlash implements ArrayAccess{
  protected $_vars = array();
  protected $_new_vars = array();
  const SESSION_KEY = '__TCControllerFlash__';
  
  public function __construct(){
    // load flash vars from temp session
    if(!empty($_SESSION[self::SESSION_KEY])){
      $this->_vars = $_SESSION[self::SESSION_KEY];
      unset($_SESSION[self::SESSION_KEY]);
    }
  }
  
  public function __get($name){
    if(isset($this->_new_vars[$name])) return $this->_new_vars[$name];
    if(isset($this->_vars[$name])) return $this->_vars[$name];
    return null;
  }
  
  public function __set($name, $value){
    $this->_new_vars[$name] = $value;
    $_SESSION[self::SESSION_KEY] = $this->_new_vars;
  }
  
  public function __isset($name){
    if(isset($this->_vars[$name])) return true;
    return false;
  }
  
  public function __unset($name){
    unset($this->_vars[$name]);
    unset($this->_new_vars[$name]);
  }
  

  public function offsetExists($offset){
    return $this->__isset($offset);
  }
  public function offsetGet($offset){
    return $this->__get($offset);
  }
  public function offsetSet($offset, $value){
    return $this->__set($offset, $value);
  }
  public function offsetUnset($offset){
    return $this->__unset($offset);
  }
}

