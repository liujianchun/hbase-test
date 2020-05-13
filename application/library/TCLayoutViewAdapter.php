<?php

/**
 * @name TCLayoutViewAdapter
 * 在Yaf_View_Simple类的基础上增加了layout、css、js文件缓存清除注册支持
 * @author liujianchun
 */
class TCLayoutViewAdapter extends Yaf_View_Simple {
  public $layout = "default";
  private $css_files = array();
  private $js_files = array();
  public $pageTitle = 'hbase_test';
  public $pageDescription = '';
  public $pageKeywords = '';
  private $menus = null;

  /**
   * 获取管理后台的菜单信息
   */
  public function getMenus() {
    if($this->menus === null) {
      $current_action = Yaf_Dispatcher::getInstance()->getRequest()->getActionName();
      $current_controller = Yaf_Dispatcher::getInstance()->getRequest()->getControllerName();
      $current_path = strtolower($current_controller . '/' . $current_action);
      $this->menus = [];
      foreach($this->getAllAdminControllers() as $controller) {
        foreach($this->getActionsOfController($controller) as $action) {
          $lines = $this->getDocumentLinesOfReflection($action);
          if(empty($lines)) continue;
          foreach($lines as $line) {
            if(substr($line, 0, 6) !== '@menu ') continue;
            $line = substr($line, 6);
            $segs = explode("=>", $line);
            $controller_name = lcfirst(substr($controller->getName(), 0, -10));
            $action_name = lcfirst(substr($action->getName(), 0, -6));
            $path = $controller_name . '/' . $action_name;
            $is_current = $current_path == strtolower($path); // 处于活跃状态

            $temp_item = &$this->menus;
            foreach($segs as $i => $seg) {
              $seg = trim($seg);
              if(empty($seg)) continue;
              if(!isset($temp_item[$seg])) $temp_item[$seg] = [];
              if($is_current) $temp_item[$seg]['is_current'] = true;
              if($i == count($segs) - 1) {
                $temp_item[$seg]['path'] = $path;
              } else {
                if(!isset($temp_item[$seg]['children'])) {
                  $temp_item[$seg]['children'] = [];
                }
                $temp_item = &$temp_item[$seg]['children'];
              }
            }
            break;
          }
        }
      }
    }

    return $this->menus;
  }

  /**
   * @return ReflectionClass[]
   */
  private function getAllAdminControllers() {
    $controllers = array();
    $folder = APPLICATION_DIRECTORY . '/controllers';
    $it = new DirectoryIterator($folder);
    while($it->valid()) {
      if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
        include_once $it->getPath() . '/' . $it->getFilename();
        $controller = lcfirst(substr($it->getFilename(), 0, -4));

        $rc = new ReflectionClass(ucfirst($controller) . 'Controller');
        $controllers[] = $rc;
      }
      $it->next();
    }
    sort($controllers);

    return $controllers;
  }

  /**
   * @param ReflectionClass $controller
   * @return ReflectionMethod[]
   */
  private function getActionsOfController($controller) {
    $actions = [];
    foreach($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
      if(substr($method->name, -6) === 'Action') {
        $actions[] = $method;
      }
    }

    return $actions;
  }


  /**
   * @param ReflectionMethod|ReflectionClass $reflection
   * @return string[]
   */
  private function getDocumentLinesOfReflection($reflection) {
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

    return $lines;
  }


  /**
   * 在html的头部注册一个css文件
   * @param string $css
   */
  public function registerCss($css) {
    $this->css_files[$css] = $css;
  }

  /**
   * 在html的尾部注册一个js文件
   * @param string $js
   */
  public function registerJavaScript($js) {
    $this->js_files[$js] = $js;
  }

  /**
   * 在后台网页中渲染出右边栏的子级菜单
   * @param TCControllerBase $controller
   * @param int $cols bootstrap 布局中所占据的列数
   * @return string
   */
  public function renderSubmenu($controller, $cols = 2) {
    $html = '<div class="col-sm-' . $cols . '"><div class="list-group">';
    $rc = new ReflectionClass($controller);
    $actions = $this->getActionsOfController($rc);
    $base_uri = $controller->getRequest()->getBaseUri();
    foreach($actions as $action) {
      $lines = $this->getDocumentLinesOfReflection($action);
      $submenu = null;
      foreach($lines as $line) {
        if(substr($line, 0, 8) == '@submenu') {
          $submenu = trim(substr($line, 9));
        }
      }
      if(empty($submenu)) continue;
      $action_name = strtolower(substr($action->name, 0, -6));
      if($controller->getModuleName() !== 'Index') {
        $href = $base_uri . '/' . $controller->getModuleName() . '/'
          . substr($action->class, 0, -10) . '/' . $action_name;
      } else {
        $href = $base_uri . '/' . substr($action->class, 0, -10) . '/' . $action_name;
      }
      $html .= '<a href="' . $href . '" class="list-group-item '
        . ($controller->getRequest()->action == $action_name ? 'active' : '')
        . '">' . $submenu . '</a>';
    }
    $html .= "</div></div>";

    return $html;
  }

  /**
   * @param string $tpl
   * @param array $tpl_vars
   * @return string|void
   */
  public function render($tpl, $tpl_vars = null) {
    $output = parent::render($tpl, $tpl_vars);
    if(!empty($this->layout)) {
      // move defer javascript codes to body end
      $defer_script_codes = "";
      if(preg_match_all('|<script[^>]+defer[^>]*>.*?</script>|s', $output, $matches)) {
        foreach($matches[0] as $item) {
          $output = str_replace($item, '', $output);
          $defer_script_codes .= $item;
        }
      }


      $this->assign("content", $output);
      $output = parent::render("layouts/" . $this->layout . ".php", $tpl_vars);

      // deal with css files
      if(!empty($this->css_files)) {
        $css_links = "";
        foreach($this->css_files as $name => $temp) {
          if($name[0] !== '/') $path = "/css/" . $name;
          else $path = $name;
          $filepath = dirname(dirname(__DIR__)) . $path;
          $modified_time = filemtime($filepath);
          if($modified_time) {
            $css_links .= "<link href=\"{$this->base_uri}{$path}?{$modified_time}\" rel=\"stylesheet\"/>";
          }
        }
        if(!empty($css_links)) {
          $end_head_position = strpos($output, '</head>');
          $first_script_position = strpos($output, '<script');
          if($end_head_position && $first_script_position
            && $first_script_position < $end_head_position
          ) {
            // always put styles before scripts inside html header
            // for better load performance
            $output = substr_replace($output, $css_links, $first_script_position, 0);
          } elseif($end_head_position) {
            $output = substr_replace($output, $css_links, $end_head_position, 0);
          }
        }
      }
      // deal with javascript files
      if(!empty($this->js_files)) {
        $js_links = "";
        foreach($this->js_files as $name => $temp) {
          if($name[0] !== '/') $path = "/js/" . $name;
          else $path = $name;
          $filepath = dirname(dirname(__DIR__)) . $path;
          $modified_time = filemtime($filepath);
          if($modified_time) {
            $js_links .= "<script src=\"{$this->base_uri}{$path}?{$modified_time}\"></script>";
          }
        }
        if(!empty($js_links)) $output = str_replace('</body>', $js_links . '</body>', $output);
      }

      // move defer javascript codes to body end
      if(!empty($defer_script_codes)) {
        $output = str_replace('</body>', '</body>' . $defer_script_codes, $output);
      }
    }

    return $output;
  }
}

