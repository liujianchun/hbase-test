<?php

/**
 * user agent utils
 * @author liujianchun
 */
class TCUserAgentUtil {

  public static function getOS($user_agent) {
    if(preg_match('/Windows NT ([0-9\\.]+)/', $user_agent, $matches)) {
      switch($matches[1]) {
        case '4.0':
          return 'Windows NT 4.0';
        case '5.0':
          return 'Windows 2000';
        case '5.01':
          return 'Windows 2000, Service Pack 1 (SP1)';
        case '5.1':
          return 'Windows XP';
        case '5.2':
          return 'Windows Server 2003; Windows XP x64 Edition';
        case '6.0':
          return 'Windows Vista';
        case '6.1':
          return 'Windows 7';
        case '6.2':
          return 'Windows 8';
        case '6.3':
          return 'Windows 8.1';
        case '10.0':
          return 'Windows 10';
      }
    }
    if(preg_match('/(Windows Phone OS [0-9\\.]+)/', $user_agent, $matches)) return $matches[0];
    if(strpos($user_agent, 'Windows 98; Win 9x 4.90') !== false) return 'Windows Me';
    if(strpos($user_agent, 'Windows 98') !== false) return 'Windows 98';
    if(strpos($user_agent, 'Windows 95') !== false) return 'Windows 95';
    if(strpos($user_agent, 'Windows CE') !== false) return 'Windows CE';
    if(preg_match('/OS ([0-9_\\.]+) like Mac OS X/', $user_agent, $matches)){
      return 'iOS ' . str_replace('_', '.', $matches[1]);
    }
    if(strpos($user_agent, 'Mac OS X') !== false) return 'Mac OS X';
    if(preg_match('/(Android [0-9\\.]+)/', $user_agent, $matches)) return $matches[0];
  }


  /**
   * get browser info from user agent
   * @param $user_agent
   * @return string
   * refer link https://developer.mozilla.org/en-US/docs/Web/HTTP/Browser_detection_using_the_user_agent
   */
  public static function getBrowser($user_agent) {
    if(preg_match('/MS(IE [0-9\\.]+)/', $user_agent, $matches)) {
      return $matches[1];
    }
    if(preg_match('/Seamonkey\\/[0-9\\.]+/', $user_agent, $matches)) {
      return $matches[0];
    }
    if(preg_match('/Firefox\\/[0-9\\.]+/', $user_agent, $matches)) {
      return $matches[0];
    }
    if(preg_match('/Chromium\\/[0-9\\.]+/', $user_agent, $matches)) {
      return $matches[0];
    }
    if(preg_match('/Chrome\\/[0-9\\.]+/', $user_agent, $matches)) {
      return $matches[0];
    }
    if(preg_match('/Safari\\/[0-9\\.]+/', $user_agent, $matches)) {
      return $matches[0];
    }
    if(preg_match('/OPR\\/([0-9\\.]+)/', $user_agent, $matches)) {
      return 'Opera/' . $matches[1];
    }
    if(preg_match('/Opera\\/([0-9\\.]+)/', $user_agent, $matches)) {
      return 'Opera/' . $matches[1];
    }
    if(strpos($user_agent, 'Opera Mobi') !== false) return 'Opera Mobi';

  }

}
