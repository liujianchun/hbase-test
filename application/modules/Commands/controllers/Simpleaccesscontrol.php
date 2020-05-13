<?php

/**
 * commands for simple access control plugin
 * @author liujianchun
 */
class SimpleAccessControlController extends TCControllerBase {

  /**
   * generate a password for a user by username
   * @param string $username
   */
  public function randomPasswordForUserAction($username = 'admin') {
    $ini_filepath = APPLICATION_PATH . '/conf/application.ini';
    $ini_content = file_get_contents($ini_filepath);
    if(preg_match('/(\\[simple\\.access\\.control\\.accounts\\].*?)\n\\[/s', $ini_content, $matches)) {
      $password = $this->randomPassword();
      $new_account_config_line = $username . ' = ' . md5(md5($password));
      $accounts_config_content = trim($matches[1]);
      $position = strpos($accounts_config_content, $username . '=');
      if($position === false) {
        $position = strpos($accounts_config_content, $username . ' =');
      }
      if($position !== false) {
        $end = strpos($accounts_config_content, "\n", $position);
        if($end === false) $end = strlen($accounts_config_content);
        $account_config_line = substr($accounts_config_content, $position, $end - $position);
        $new_accounts_config_content = str_replace($account_config_line, $new_account_config_line, $accounts_config_content);
      } else {
        echo "User ", $username, " does not exists.\n";
        echo "Do you want to create it? [Y/N] ";
        $input = strtolower(trim(fgets(STDIN)));
        if($input == 'y' || $input == 'yes') {
          $new_accounts_config_content = $accounts_config_content . "\n" . $new_account_config_line;
        } else exit("\n");
      }
      $new_ini_content = str_replace($accounts_config_content, $new_accounts_config_content, $ini_content);
      echo "\033[36mYour new password of ", $username, " is shown at the line below. Remember please!\n";
      echo "\033[31m", $password, " \033[39m";
      echo "\n\n";
      file_put_contents($ini_filepath, $new_ini_content);
    }
  }


  private function randomPassword() {
    static $chars = "`~!@#$%^&*()_-+=[{]}\\|;:'\",<.>/?0123456789abcdefghijk";
    $password = "";
    for($i = 0; $i < 12; $i++) {
      $password .= $chars{rand(0, strlen($chars))};
    }

    return $password;
  }

}

