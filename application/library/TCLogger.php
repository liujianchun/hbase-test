<?php

/**
 * 文件日志记录
 */
class TCLogger {

  /**
   * write the exception info to log file
   * @param Exception $exception
   */
  public static function writeExceptionLog($exception) {
    $message = $exception->getMessage() . "\n";
    $message .= "Stack trace:\n";
    foreach($exception->getTrace() as $i => $item) {
      $message .= "#" . $i . " " . $item["file"] . "(" . $item["line"] . "): " .
        $item["class"] . "->" . $item["function"] . "()" .
        "\n";
    }
    self::writeLogMessage($message);
  }

  /**
   * write some message to log file
   * @param string $message
   */
  public static function writeLogMessage($message) {
    $message = date("Y-m-d H:i:s") . " " .
      $_SERVER["REQUEST_METHOD"] . " " .
      $_SERVER["SERVER_PROTOCOL"] . " " .
      $_SERVER["HTTP_HOST"] . " " .
      $_SERVER["REQUEST_URI"] . "\n" .
      $message . "\n\n";

    $log_folderpath = APPLICATION_DIRECTORY . "/runtime/";
    $log_filepath = $log_folderpath . "application.log";
    if(!@file_put_contents($log_filepath, $message, FILE_APPEND)) {
      if(!@file_exists($log_folderpath)) {
        if(@mkdir($log_folderpath, 0777, true)) {
          @file_put_contents($log_filepath, $message, FILE_APPEND);
        }
      }
    }
    self::swapLogFileIfNecessary($log_filepath);
  }

  public static function swapLogFileIfNecessary($log_filepath) {
    $keep_log_file_count = 5;
    $max_log_file_size = 1024 * 1024 * 5;
    if(filesize($log_filepath) >= $max_log_file_size) {
      self::swapLogFileToNext($log_filepath, 0, $keep_log_file_count);
    }
  }

  private static function swapLogFileToNext($log_filepath, $index, $max) {
    $filepath = $index > 0 ? $log_filepath . '.' . $index : $log_filepath;
    $nex_filepath = $log_filepath . '.' . ($index + 1);
    if(!file_exists($filepath)) return;
    if($index == $max) {
      unlink($log_filepath . '.' . $index);
    } else {
      self::swapLogFileToNext($log_filepath, $index + 1, $max);
      rename($filepath, $nex_filepath);
    }
  }

}