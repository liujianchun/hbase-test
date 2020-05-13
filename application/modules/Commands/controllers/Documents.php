<?php

/**
 * @author liujianchun
 */
class DocumentsController extends TCControllerBase {


  /**
   * 根据接口文档创建接口模型类
   * @param string $platform
   */
  public function generateApiModelClassesAction($platform = 'android') {
    $classes = array();
    $folder = APPLICATION_DIRECTORY . '/modules/Api/controllers';
    $it = new DirectoryIterator($folder);
    while($it->valid()) {
      if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
        include_once $it->getPath() . '/' . $it->getFilename();
        $controller = lcfirst(substr($it->getFilename(), 0, -4));

        $rc = new ReflectionClass(ucfirst($controller) . 'Controller');
        ApiClassModel::loadClass($classes, $rc);
      }
      $it->next();
    }


    // render all classes models
    $view = new Yaf_View_Simple(dirname(__DIR__) . '/views/documents');
    $folder = dirname(__DIR__) . '/api.models/' . $platform . '/';
    @exec("rm -f '{$folder}*'");
    if(!is_dir($folder)) mkdir($folder, 0777, true);
    foreach($classes as $name => $class) {
      $view->assign(array('class' => $class));
      if($platform == 'android') {
        $class_src = $view->render($platform . '.tpl.php');
        $class_filepath = $folder . $class->name . '.java';
        file_put_contents($class_filepath, $class_src);
      } elseif($platform == 'ios') {
        $class_src = $view->render($platform . '.h.tpl.php');
        $class_filepath = $folder . $class->getIosName() . '.h';
        file_put_contents($class_filepath, $class_src);
        $class_src = $view->render($platform . '.m.tpl.php');
        $class_filepath = $folder . $class->getIosName() . '.m';
        file_put_contents($class_filepath, $class_src);
      }
    }
    // zip the model class folder
    $zip_filepath = dirname(__DIR__) . '/api.models/' . $platform . '.zip';
    @unlink($zip_filepath);
    $zip = new ZipArchive();
    $zip->open($zip_filepath, ZipArchive::CREATE || ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($folder),
      RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach($files as $name => $file) {
      $filepath = $file->getRealPath();
      if(is_dir($filepath)) {
        $zip->addEmptyDir(substr($filepath, strlen($folder)));
      } else
        $zip->addFile($filepath, substr($filepath, strlen($folder)));
    }
    echo "Api model classes created at: \n\t", $zip_filepath, "\n";
  }


}


class ApiClassModel {
  public $name;
  public $propertiesHash;

  public function getIosName() {
    return Yaf_Application::app()->getConfig()->get('api.model.ios.prefix') . $this->name;
  }

  /**
   * @param array $classes all classes that already loaded
   * @param ReflectionClass $rc the controller class that to be loaded
   */
  public static function loadClass(&$classes, $rc) {
    foreach($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
      if(substr($method->name, -6) == 'Action') {
        self::loadMethod($classes, $method);
      }
    }
  }

  /**
   * @param array $classes all classes that already loaded
   * @param ReflectionMethod $method the action method that to be loaded
   */
  private static function loadMethod(&$classes, $method) {
    $interface_name = substr($method->name, 0, -6);
    $document = self::getDocumentOfReflection($method);
    if(preg_match('/@json: *(\{.*(?R)*\})/s', $document, $matches)) {
      $document_json = $matches[1];
      if($interface_name == 'index') {
        $class_name = substr($method->class, 0, -10) . 'Result';
      } else {
        $class_name = substr($method->class, 0, -10) . ucfirst($interface_name) . 'Result';
      }


      $class_names = array($class_name);
      $current_depth = 0;
      $prev_property = null; // 记录上一个属性是为了能够处理多行注释的问题
      foreach(explode("\n", $document_json) as $line) {
        // deal with the documented json line by line
        $class_name = null;
        for($i = 0; !$class_name; $i++) {
          $class_name = $class_names[$current_depth - $i];
        }
        if(!$class_name) continue;
        if(empty($classes[$class_name])) {
          $class = new ApiClassModel();
          $class->name = $class_name;
          $class->propertiesHash = array();
          $classes[$class_name] = $class;
        } else $class = $classes[$class_name];

        if($prev_property && preg_match('/^ *\/\/ *(.*) *$/', $line, $matches)) {
          // deal with multi lines comment
          $prev_property->comment .= "\n" . $matches[1];
          continue;
        }

        $property = new ApiClassPropertyModel($line);
        $prev_class_name = empty($class_names[$current_depth]) ?
          $class_names[$current_depth - 1] :
          $class_names[$current_depth];
        if($property->type == 'array' && empty($property->arrayValueClass)) {
          if(empty($prev_class_name)) {
            echo "cannot get item class name in method ", $method->class, ':', $method->name, " of line: \n", $line, "\n\n";
            exit;
          }
          $property->arrayValueClass = $property->name[-1] === 's' ?
            $prev_class_name . ucfirst(substr($property->name, 0, -1)) :
            $prev_class_name . ucfirst($property->name) . 'Item';
        } elseif($property->type == 'dictionary' && empty($property->dictValueClass)) {
          if(empty($prev_class_name)) {
            echo "cannot get class name in method ", $method->class, ':', $method->name, " of line: \n", $line, "\n\n";
            exit;
          }
          $property->type = $prev_class_name . ucfirst($property->name);
        }
        if(empty($property)) continue;
        if(empty($property->type) && empty($property->value)) {
          echo "document error in method ", $method->class, ':', $method->name, " of line:\n", $line, "\n\n";
          exit(1);
        }
        if($property->name) $class->propertiesHash[$property->name] = $property;
        if($property->value == '[') {
          $current_depth++;
          $class_names[$current_depth] = $property->arrayValueClass;
        } elseif($property->value == '{') {
          $current_depth++;
          if($property->dictValueClass) $class_names[$current_depth] = $property->dictValueClass;
          else $class_names[$current_depth] = $property->type;
        } elseif($property->value == '}' || $property->value == ']') {
          $current_depth--;
          if($current_depth < 0) {
            echo "document error in method ", $method->class, ':', $method->name, "\n\n";
            exit(1);
          }
        }
        $prev_property = $property;
      }
    }
  }


  /**
   * @param ReflectionMethod|ReflectionClass $reflection
   * @return string
   */
  private static function getDocumentOfReflection($reflection) {
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


class ApiClassPropertyModel {
  public $jsonName = '';
  public $name = '';
  public $type;
  public $comment;
  public $value;
  /** 如果该属性是一个数组，那么记录下数组所存储的类型 */
  public $arrayValueClass;
  /** 如果该属性是一个字典，那么记录下字典所存储的索引类型 */
  public $dictKeyClass;
  /** 如果该属性是一个字典，那么记录下字典所存储的值类型 */
  public $dictValueClass;

  public function getAndroidType() {
    if($this->type == 'string') return 'String';
    if($this->type == 'bool') return 'boolean';
    if($this->type == 'array') return 'ArrayList<' . $this->getAndroidArrayValueType() . '>';

    return $this->type;
  }

  public function getAndroidArrayValueType() {
    if($this->arrayValueClass == 'string') return 'String';
    elseif($this->arrayValueClass == 'boolean') return 'Boolean';
    elseif($this->arrayValueClass == 'int') return 'Integer';
    elseif($this->arrayValueClass == 'long') return 'Long';
    elseif($this->arrayValueClass == 'float') return 'Float';
    elseif($this->arrayValueClass == 'double') return 'Double';

    return $this->arrayValueClass;
  }

  public function getIosType() {
    if(!$this->type) return null;
    if($this->type == 'string') return 'NSString';
    elseif($this->type == 'int') return 'NSInteger';
    elseif($this->type == 'bool') return 'bool';
    elseif($this->type == 'float') return 'CGFloat';
    elseif($this->type == 'array') return 'NSMutableArray';

    return Yaf_Application::app()->getConfig()->get('api.model.ios.prefix') . $this->type;
  }

  public function getIosArrayValueClass() {
    if(!$this->arrayValueClass) return null;
    if($this->arrayValueClass == 'string') return 'NSString';
    elseif($this->arrayValueClass == 'int') return 'NSNumber';
    elseif($this->arrayValueClass == 'bool') return 'NSNumber';
    elseif($this->arrayValueClass == 'float') return 'NSNumber';
    elseif($this->arrayValueClass == 'double') return 'NSNumber';

    return Yaf_Application::app()->getConfig()->get('api.model.ios.prefix') . $this->arrayValueClass;
  }


  public function __construct($line) {
    $line = trim(trim($line), ",");
    $reg = '/^"([^"]+)":([^\/]+)(\/\/)?(.*)$/';
    if(preg_match($reg, $line, $matches)) {
      $this->value = trim(trim($matches[2]), ',');
      $this->comment = trim($matches[4]);
      $this->jsonName = $matches[1];
      foreach(explode('_', $this->jsonName) as $i => $segment) {
        if($i) $this->name .= ucfirst($segment);
        else $this->name = $segment;
      }

      if($this->value == '[' || $this->value == '[]') {
        $this->type = 'array';
      } elseif($this->value == '{' || $this->value == '{}') {
        $this->type = 'dictionary';
      } elseif(is_numeric($this->value)) {
        $this->type = 'int';
        if(intval($this->value) != $this->value)
          $this->type = 'float';
      } elseif($this->value === 'true' || $this->value === 'false') {
        $this->type = 'bool';
      } elseif($this->value[0] === '"') {
        $this->type = 'string';
      }

      if(preg_match('/^([ a-zA-Z0-9]+),/', $this->comment, $matches)) {
        $this->comment = trim(str_replace($matches[0], '', $this->comment));
        $this->arrayValueClass = $matches[1];
        if($this->value == '{' || $this->value == '{}') {
          $this->type = $matches[1];
        }
        if($this->type == 'array') {
          $this->arrayValueClass = $matches[1];
        }
      } elseif(preg_match('/^([a-zA-Z0-9]+)=>([a-zA-Z0-9]+),/', $this->comment, $matches)) {
        $this->dictKeyClass = $matches[1];
        $this->dictValueClass = $matches[2];
      }
    } elseif($line == '{' || $line == '}' || $line == '[' || $line == ']') {
      $this->value = $line;
    }
  }
}



