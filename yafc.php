<?php

/**
 * 在命令行模式下运行yaf的执行文件入口
 */

define('APPLICATION_PATH', dirname(__FILE__));

$environ = PHP_OS === 'Darwin' ? 'dev' : 'product';
$application = new Yaf_Application(APPLICATION_PATH . "/conf/application.cli.ini", $environ);
define('APPLICATION_DIRECTORY', $application->getConfig()->application->directory);
$application->getDispatcher()->autoRender(false);

// for composer support
if(file_exists(__DIR__ . '/vendor/autoload.php')) {
  include_once __DIR__ . '/vendor/autoload.php';
}


function echoControllerNotFound() {
  echo "Yaf command runner (based on yaf framework)\n";
  echo "Usage php ", __DIR__, "/yafc.php <controller> <action> --<parameter name>=<parameter value>\n\n";
  echo "The following command controllers are available:\n";
  foreach(listCommandControllersAvailable() as $controller) {
    echo " - {$controller}\n";
  }
}

function listCommandControllersAvailable() {
  $controllers = array();
  $folder = APPLICATION_DIRECTORY . "/modules/Commands/controllers";
  $it = new DirectoryIterator($folder);
  while($it->valid()) {
    if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
      $controller = lcfirst(substr($it->getFilename(), 0, -4));
      $controllers[] = $controller;
    }
    $it->next();
  }

  return $controllers;
}

function echoActionNotFound($request) {
  echo "\033[31mError: Unknown action: ", $request->action, " in controller ", $request->controller, "\033[39m\n\n";
  echo "The following actions are available:\n";
  $controller_class_name = $request->controller . 'Controller';
  foreach(listActionsAvailable($controller_class_name) as $method) {
    $action = substr($method->name, 0, -6);
    echo "    ", $action;
    foreach($method->getParameters() as $param) {
      echo " ";
      if($param->isOptional()) echo "[";
      echo "--", $param->name, "=";
      if($param->isDefaultValueAvailable()) echo $param->getDefaultValue();
      if($param->isOptional()) echo "]";
      else echo " ";
    }
    echo "\n";
  }
}

function echoActionArgumentNotMatch($request) {
  echo "\033[31mError: arguments of action: ", $request->action, " in controller ", $request->controller, " not match\033[39m\n\n";
  echo "The action arguments:\n";
  $action_name_lower = strtolower($request->action);
  $controller_class_name = $request->controller . 'Controller';
  foreach(listActionsAvailable($controller_class_name) as $method) {
    $action = substr($method->name, 0, -6);
    if($action_name_lower != strtolower($action)) continue;
    echo "    ", $action;
    foreach($method->getParameters() as $param) {
      echo " ";
      if($param->isOptional()) echo "[";
      echo "--", $param->name, "=";
      if($param->isDefaultValueAvailable()) echo $param->getDefaultValue();
      if($param->isOptional()) echo "]";
      else echo " ";
    }
    echo "\n";
  }
}

function listActionsAvailable($controller_class_name) {
  $rc = new ReflectionClass($controller_class_name);
  $actions_available = array();
  foreach($rc->getMethods() as $method) {
    if(substr($method->name, -6) === 'Action') {
      $actions_available[] = $method;
    }
  }

  return $actions_available;
}


function includeTCCommonExtensionFiles() {
  $controllers = array();
  $folder = APPLICATION_DIRECTORY . '/common-extensions/tccommons';
  if(!is_dir($folder)) return;
  $it = new DirectoryIterator($folder);
  while($it->valid()) {
    if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
      include_once $it->getPathname();
    }
    $it->next();
  }

  return $controllers;
}


if(count($argv) < 2) return echoControllerNotFound();
$controller = $argv[1];
$action = empty($argv[2]) ? "Index" : $argv[2];
$parameters = array();
if(substr($action, 0, 2) === '--') {
  $action = 'Index';
  if(preg_match("/--([^=]+)=(.*)/", $action, $matches))
    $parameters[$matches[1]] = $matches[2];
}
for($i = 3; $i < count($argv); $i++) {
  if(preg_match("/--([^=]+)=(.*)/", $argv[$i], $matches))
    $parameters[$matches[1]] = $matches[2];
  else $parameters[] = $argv[$i];
}

try {
  includeTCCommonExtensionFiles();
  $request = new Yaf_Request_Simple("CLI", "commands", $controller, $action, $parameters);
  $application->getDispatcher()->dispatch($request);
} catch(Yaf_Exception_LoadFailed_Action $e) {
  return echoActionNotFound($request);
} catch(ArgumentCountError $e) {
  return echoActionArgumentNotMatch($request);
}




