<?php
define('APPLICATION_PATH', dirname(__FILE__));
$environ = PHP_OS === 'Darwin' ? 'dev' : 'product';
if(get_cfg_var('datacenter') === "test") $environ = "test";
$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini", $environ);
define('APPLICATION_DIRECTORY', $application->getConfig()->application->directory);

// for composer support
if(file_exists(__DIR__ . '/vendor/autoload.php')) {
  include_once __DIR__ . '/vendor/autoload.php';
}

$application->bootstrap()->run();
