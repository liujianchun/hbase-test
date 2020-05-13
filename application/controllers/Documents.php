<?php


/**
 * @name DocumentsController
 * @author liujianchun
 */
class DocumentsController extends TCControllerBase {

  public function init() {
    parent::init();
    $this->getView()->assign("active_nav_item", Constant::NAV_ITEM_DOCUMENTS);
  }


  public function apiAction() {
    $controllers = $this->listAllApiControllers();
    $this->getView()->assign('controllers', $controllers);
    $page = isset($_GET['page']) ? strtolower(trim($_GET['page'])) : "";
    $apis = $this->getApiDocumentForController($page);
    $this->getView()->assign("page", $page);
    if(!empty($apis)) {
      $this->getView()->assign("apis", $apis);
      $this->getView()->assign("controller_title", $controllers[$page]['title']);
      $this->getView()->assign('breadcrumbs', [
        '首页' => '/',
        'API文档' => '/documents/api',
        $controllers[$page]['title'],
      ]);
    } else {
      $this->getView()->assign('breadcrumbs', [
        '首页' => '/',
        'API文档',
      ]);
      $this->getView()->assign("apis", array());
      $this->getView()->assign("controller_title", null);

      // 列举出已生成并压缩的接口模型类列表
      $platform_generated = array();
      $folder = APPLICATION_DIRECTORY . '/modules/Commands/api.models/';
      if(is_dir($folder)) {
        $it = new DirectoryIterator($folder);
        while($it->valid()) {
          if(!$it->isDot() && $it->isFile() && $it->getExtension() === "zip") {
            $platform = substr($it->getFilename(), 0, -4);
            $change_time = date('Y-m-d H:i:s', $it->getCTime());
            $platform_generated[$platform] = $change_time;
          }
          $it->next();
        }
      }
      $this->getView()->assign('platform_generated', $platform_generated);
    }
  }


  /**
   * 下载接口模型类
   */
  public function downloadApiClassModelAction() {
    if(preg_match('/^[a-zA-Z0-9]+$/', $_GET['platform'])) {
      $filepath = APPLICATION_DIRECTORY . '/modules/Commands/api.models/' . $_GET['platform'] . '.zip';
      if(!file_exists($filepath)) {
        throw new Exception('model classes of this platform is not created', 404);
      }
      header("Content-type: application/octet-stream");
      header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
      if(Yaf_Application::app()->getConfig()->xsendfile->enable) {
        header("X-Sendfile: $filepath");
        header("X-Accel-Redirect: $filepath");
        header("X-LIGHTTPD-send-file: $filepath");
      } else {
        echo file_get_contents($filepath);
      }
    }

    return false;
  }


  /**
   * 刷新模型类
   */
  public function refreshApiModelAction() {
    Yaf_Dispatcher::getInstance()->autoRender(false);
    $bin = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
    $binary = PHP_BINARY . DIRECTORY_SEPARATOR . 'php';
    $bin_path = '';
    if(!empty($binary) && file_exists($bin_path) && is_file($bin_path)) {
      $bin_path = $binary;
    } elseif(!empty($bin) && file_exists($bin) && is_file($bin)) {
      $bin_path = $bin;
    }
    if(!empty($bin_path)) {
      $loaded_path = php_ini_loaded_file();
      $command = $bin_path . ' -c ' . $loaded_path;
      $yafc_path = '  ' . APPLICATION_PATH . '/yafc.php';
      $command = $command . $yafc_path . ' documents generateApiModelClasses';
      @exec($command);
      $command = $command . $yafc_path . ' documents generateApiModelClasses --platform=ios';
      @exec($command);
    }

    return $this->redirect('api');
  }

  private function getApiDocumentForController(&$controller) {
    if(empty($controller)) return null;
    $class_name = 'API\\' . $controller . 'Controller';
    $rc = new ReflectionClass($class_name);
    $controller = substr($rc->name, 4, -10);
    $document = $this->getDocumentOfReflection($rc);
    $lines = explode("\n", $document);
    $controller_title = $lines[0];
    $apis = array();
    $request = new Yaf_Request_Simple('', '', '');
    $response = new Yaf_Response_Cli();
    $view = new Yaf_View_Simple('/tmp');
    $instance = new $class_name($request, $response, $view);
    $post_actions = array();
    foreach($instance->postOnlyActions() as $action) {
      $post_actions[] = strtolower($action);
    }
    unset($instance);

    foreach($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
      if(substr($method->name, -6) === 'Action') {
        $interface_name = substr($method->name, 0, -6);
        $document = $this->getDocumentOfReflection($method);
        if(empty($document)) continue;
        $api = new stdClass();
        $api->params = array();

        if(preg_match('/@json: *(\{.*(?R)*\})/s', $document, $matches)) {
          $api->result = str_replace("\t", '  ', $matches[1]);
          $document = str_replace($matches[0], '', $document);
        }
        if(preg_match_all('/@param ([^$]*)\$([^ ]+) (.*)/', $document, $matches)) {
          foreach($matches[0] as $i => $match) {
            $document = str_replace($match . "\r\n", '', $document);
            $document = str_replace($match . "\n", '', $document);
            $document = str_replace($match, '', $document);
            $param = new stdClass();
            $param->name = $matches[2][$i];
            $param->description = $matches[3][$i];
            $api->params[] = $param;
          }
        }
        if(preg_match_all('/@path (.+)/', $document, $matches)) {
          // 这个接口做了路径转换，转换成别的接口路径地址了
          foreach($matches[1] as $i => $item) {
            $segs = explode(" ", $item, 2);
            $path = $segs[0];
            $api->paths[] = [$path, $segs[1]];
            $match = $matches[0][$i];
            $document = str_replace($match . "\r\n", '', $document);
            $document = str_replace($match . "\n", '', $document);
            $document = str_replace($match, '', $document);
          }
        }


        $lines = explode("\n", $document);
        $title = array_shift($lines);
        $api->title = $title;
        $api->fragment = $api->interface = lcfirst($interface_name);
        $api->description = trim(join("\n", $lines));
        $api->method = 'GET';
        if(in_array(strtolower($interface_name), $post_actions))
          $api->method = 'POST';
        if(!empty($api->paths)) {
          // 单 action 映射多接口路径的情况
          foreach($api->paths as $path) {
            $api->path = $path[0];
            $api->fragment = str_replace("/", '-', trim(trim($api->path, '/')));
            if(!empty($path[1])) $api->title = $path[1];
            $apis[] = clone($api);
          }
        } else
          $apis[] = $api;
      }
    }
    foreach($apis as $api) {
      $api->description = $this->actionFormatApiCommentDescription($api->description, $apis);
    }

    return $apis;
  }


  private function actionFormatApiCommentDescription($description, $apis) {
    if(preg_match_all('/\{@link ([^\}]+)\}/', $description, $matches)) {
      foreach($matches[0] as $i => $match) {
        $matches[1][$i] = trim($matches[1][$i]);
        if(preg_match('/^([^\[]+)\[([^\]]+)\]/', $matches[1][$i], $m)) {
          $replace = "<a href=\"{$m[1]}\">{$m[2]}</a>";
        } else {
          $title = $matches[1][$i];
          if($title[0] === '#') {
            $interface = substr($title, 1);
            foreach($apis as $api) {
              if($api->interface == $interface) {
                $title = $api->title;
                break;
              }
            }
          }
          $replace = "<a href=\"{$matches[1][$i]}\">{$title}</a>";
        }
        $description = str_replace($match, $replace, $description);
      }
    }
    $description = str_replace("\n", "<br/>", trim($description));

    return $description;
  }


  private function listAllApiControllers() {
    $controllers = array();
    $folder = APPLICATION_DIRECTORY . '/modules/Api/controllers';
    $it = new DirectoryIterator($folder);
    while($it->valid()) {
      if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
        $controller_file_path = $it->getPath() . '/' . $it->getFilename();
        $controller_file_content = file_get_contents($controller_file_path);
        $controller_file_content = preg_replace('/^<\\?php/', 'namespace API;', $controller_file_content);
        $controller_file_content = preg_replace('/extends +([^ ]+)/', 'extends \\\\\\1', $controller_file_content);
        $controller_file_content = preg_replace('/include_once[^;]+;/', '', $controller_file_content);
        $controller_file_content = preg_replace('/require_once[^;]+;/', '', $controller_file_content);
        eval($controller_file_content);

        $controller = lcfirst(substr($it->getFilename(), 0, -4));
        $rc = new ReflectionClass('API\\' . ucfirst($controller) . 'Controller');
        $controller = substr($rc->name, 4, -10);
        $lines = explode("\n", $this->getDocumentOfReflection($rc));
        $controller_info = ['title' => array_shift($lines)];
        foreach($lines as $line) {
          if(empty($line)) continue;
          if($line[0] !== '@') continue;
          if(substr($line, 0, 7) === '@ignore') {
            $controller_info['ignore'] = true;
          } elseif(substr($line, 0, 11) === '@deprecated') {
            $controller_info['deprecated'] = true;
          } elseif(substr($line, 0, 9) === '@internal') {
            $controller_info['internal'] = true;
          }
        }
        if(!isset($controller_info['ignore'])) {
          $controllers[$controller] = $controller_info;
        }
      }
      $it->next();
    }
    ksort($controllers);

    return $controllers;
  }


  /**
   * @param ReflectionMethod|ReflectionClass $reflection
   * @return string
   */
  private function getDocumentOfReflection($reflection) {
    $document = $reflection->getDocComment();
    $document = str_replace("\t", "  ", $document);
    $lines = array();
    foreach(explode("\n", $document) as $line) {
      $line = trim($line);
      if($line == '/**') continue;
      if($line == '*/') continue;
      $line = trim($line, '*');
      $line = substr($line, 1);
      $lines[] = $line;
    }
    $document = join("\n", $lines);

    return $document;
  }

}

