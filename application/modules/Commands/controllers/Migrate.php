<?php

/**
 * @name MigrateController
 * @author liujianchun
 * @desc db migrate command
 */
class MigrateController extends TCControllerBase {
  private static $folder = null;
  private $migrated_migrations = array();
  private $migration_table_name = "__tc_migrations";

  public function init() {
    parent::init();
    if(self::$folder === null) self::$folder = dirname(__DIR__) . "/migrations";
  }

  public function createAction($name) {
    if(empty($name)) {
      echo "\033[31mError: Please provide the name of the new migration\033[39m\n\n";
    } else {
      $migration_class_name = "M" . date('Ymd_His') . "_" . $name;
      $filepath = self::$folder . "/" . $migration_class_name . ".php";
      $class_source = <<<TEMPLATE
<?php

class {$migration_class_name} extends TCMigrationBase {
  public function up() {
    // \$this->getDbConnection()->exec("create table *tbl_name* () engine innodb");
    // \$this->getDbConnection()->exec("create index *index_name* on *tbl_name*(*index_col_name*)");
  }
  
  public function down() {
    // \$this->getDbConnection()->exec("drop table *tbl_name*");
    // \$this->getDbConnection()->exec("drop index *index_name* on *tbl_name*");
  }
}
TEMPLATE;
      if(!is_dir(self::$folder)) mkdir(self::$folder);
      file_put_contents($filepath, $class_source);
      echo "migration file created at:\n  ", $filepath, "\n\n";
    }
  }

  public function downAction() {
    if(!is_dir(self::$folder)) {
      echo "Error: no migrations found under ", self::$folder, "\n\n";

      return;
    }
    $class_names = $this->listAllMigrationClassNames();
    for($i = count($class_names) - 1; $i >= 0; $i--) {
      $class_name = $class_names[$i];
      /**
       * @var TCMigrationBase $instance
       */
      $instance = new $class_name();
      if($this->isMigrated($instance)) {
        //add confirm to migrate down
        echo "Current migration to be reverted:" . PHP_EOL;
        echo "\t{$class_name}" . PHP_EOL . PHP_EOL;
        fwrite(STDOUT, "Revert the above migration? (yes|no) [no]:");
        $arg = trim(fgets(STDIN));
        $arg = strtolower($arg);
        if($arg != "yes") return;
        $instance->down();
        $error_info = $instance->getDbConnection()->errorInfo();
        if(!empty($error_info[2])) {
          echo "\033[31mError: {$error_info[2]}\033[39m\n";
          echo "  when execute migration ", $class_name, "\n\n";
        } else {
          echo "migration ", $class_name, " down execute completed\n";
          $this->removeMigrated($instance);
        }
        $this->regenerateAttributesForInsertForModelClasses();
        $this->regenerateFieldCommentsAndConstructForModelClasses();

        return;
      }
    }
    echo "no migrations have been migrated under", self::$folder, "\n\n";
  }

  public function upAction() {
  }


  private function listAllMigrationClassNames() {
    if(!is_dir(self::$folder)) return array();
    $names = array();
    $it = new DirectoryIterator(self::$folder);
    while($it->valid()) {
      if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
        $filepath = $it->getPath() . "/" . $it->getFilename();
        include_once $filepath;
        $names[] = substr($it->getFilename(), 0, -4);
      }
      $it->next();
    }
    sort($names);

    return $names;
  }


  public function indexAction() {
    if(!is_dir(self::$folder)) {
      echo "Error: no migrations found under ", self::$folder, "\n\n";

      return;
    }
    $class_names = $this->listAllMigrationClassNames();
    for($i = 0; $i <= count($class_names) - 1; $i++) {
      $class_name = $class_names[$i];
      /**
       * @var TCMigrationBase $instance
       */
      $instance = new $class_name();
      if(!$this->isMigrated($instance)) {
        try {
          $instance->up();
        } catch(Exception $ex) {
          $error_info = $instance->getDbConnection()->errorInfo();
        }
        if(!empty($error_info[2])) {
          echo "\033[31mError: {$error_info[2]}\033[39m\n";
          echo "  when execute migration ", $class_name, "\n\n";

          return;
        } else {
          echo "migration ", $class_name, " execute completed\n";
          $this->markMigrated($instance);
        }
      }
    }
    $this->regenerateAttributesForInsertForModelClasses();
    $this->regenerateFieldCommentsAndConstructForModelClasses();
  }

  /**
   * @param $instance TCMigrationBase
   */
  private function markMigrated($instance) {
    $class_name = get_class($instance);
    $sql = "insert into {$this->migration_table_name} 
    (version, apply_time) values (:version, unix_timestamp(now()))";
    $stmt = $instance->getDbConnection()->prepare($sql);
    $stmt->bindValue(":version", $class_name);
    $stmt->execute();
  }


  /**
   * @param $instance TCMigrationBase
   */
  private function removeMigrated($instance) {
    $class_name = get_class($instance);
    $sql = "delete from {$this->migration_table_name} 
    where version=:version";
    $stmt = $instance->getDbConnection()->prepare($sql);
    $stmt->bindValue(":version", $class_name);
    $stmt->execute();
  }


  /**
   * @param $instance TCMigrationBase
   * @return bool
   */
  private function isMigrated($instance) {
    $class_name = get_class($instance);
    $db = $instance->getDbConnection();
    $hash = spl_object_hash($db);
    if(!isset($this->migrated_migrations[$hash])) {
      $this->migrated_migrations[$hash] = array();
      try {
        $sql = "select * from " . $this->migration_table_name;
        $stmt = $db->query($sql);
        if(!$stmt) {
          if($db->errorCode() === "42S02") {
            $this->createMigrationTable($db);

            return false;
          } else {
            $error = $db->errorInfo();
            echo "Error: ", $error[2];
          }
        } else {
          foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->migrated_migrations[$hash][$row["version"]] = $row["apply_time"];
          }
        }
      } catch(Exception $ex) {
        if(strpos($ex->getMessage(), "42S02") !== false) {
          $this->createMigrationTable($db);

          return false;
        }
        echo "Error: ", $ex->getMessage();
      }
    }
    if(isset($this->migrated_migrations[$hash][$class_name]))
      return true;

    return false;
  }

  /**
   * @param $db PDO
   */
  private function createMigrationTable($db) {
    $sql = "create table if not exists {$this->migration_table_name} (
      version varchar(180) NOT NULL,
      apply_time int(11) DEFAULT NULL,
      PRIMARY KEY (`version`)
    )";
    $db->exec($sql);
  }


  /**
   * 根据数据库表字段更新模型类的 attributesForInsert 函数
   */
  private function regenerateAttributesForInsertForModelClasses() {
    $filenames = $this->listAllModelFileNames();
    foreach($filenames as $filename) {
      /**
       * @var TCModelBase $classname
       */
      $classname = $filename . 'Model';
      $tablename = $classname::tableName();
      try {
        $db = $classname::db();
      } catch(Exception $e) {
        echo "exception when connect to db for model ", $tablename, "\n";
        continue;
      }
      if(!$db) continue;
      $filepath = APPLICATION_DIRECTORY . '/models/' . $filename . '.php';
      $model_file_contents = file_get_contents($filepath);
      $function_content = null;
      if(preg_match("/protected function attributesForInsert\\(\\) *{.*?}/s", $model_file_contents, $matches)) {
        $function_content = $matches[0];
      }

      try {
        $stmt = $db->query("show full columns from " . $tablename);
        if(!$stmt) continue;
      } catch(PDOException $e) {
        echo "exception when query columns for model ", $tablename, "\n";
        if(!empty($e->errorInfo[2])) echo "\t", $e->errorInfo[2], "\n";
        continue;
      }
      $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if(!$columns) return;
      $function_lines = array();
      $current_line = "";
      foreach($columns as $column) {
        if($column['Field'] === 'id' && $column['Extra'] === 'auto_increment') continue;
        $current_line .= "'" . $column['Field'] . "', ";
        if(strlen($current_line) > 60) {
          $function_lines[] = trim($current_line);
          $current_line = "";
        }
      }
      if(empty($current_line) && !empty($function_lines)) {
        $function_lines[count($function_lines) - 1] = trim($function_lines[count($function_lines) - 1], ',');
      }
      $current_line = trim($current_line, ' ');
      $current_line = trim($current_line, ',');
      if(!empty($current_line))
        $function_lines[] = $current_line;
      $new_function_content = "protected function attributesForInsert() {\n    return array("
        . join("\n      ", $function_lines)
        . ");\n  }";
      if($function_content != $new_function_content) {
        // 需要替换
        $model_file_contents = str_replace($function_content, $new_function_content, $model_file_contents);
        file_put_contents($filepath, $model_file_contents);
        echo "function attributesForInsert of model updated: ", $filepath, "\n";
      }
    }
  }

  /**
   * 根据数据库表字段更新模型类的字段注释以及构造函数
   */
  private function regenerateFieldCommentsAndConstructForModelClasses() {
    $config_key = 'migrate.auto.regenerate.property.comments.of.models';
    if(!Yaf_Application::app()->getConfig()->get($config_key)) return;
    $filenames = $this->listAllModelFileNames();
    foreach($filenames as $filename) {
      $classname = $filename . 'Model';
      /**
       * @var TCModelBase $classname
       */
      $tablename = $classname::tableName();
      try {
        $db = $classname::db();
      } catch(Exception $e) {
        continue;
      }
      if(!$db) continue;
      $filepath = APPLICATION_DIRECTORY . '/models/' . $filename . '.php';
      $model_file_contents = file_get_contents($filepath);

      $class_comments_content = null;
      $old_class_comments_content = null;
      if(preg_match("/\\/\\*\\*.*?class {$classname}/s", $model_file_contents, $matches)) {
        $class_comments_content = $matches[0];
        $old_class_comments_content = $class_comments_content;
      } else {
        $class_comments_content = "/**\n */\n";
      }

      //查找模型类的构造函数
      $construct_content = null;
      if(preg_match("/public function __construct\\(\\) *{.*?}/s", $model_file_contents, $matches)) {
        $construct_content = $matches[0];
      }

      try {
        $stmt = $db->query("show full columns from " . $tablename);
        if(!$stmt) continue;
      } catch(PDOException $e) {
        continue;
      }
      $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if(!$columns) return;
      $construct_lines = array();
      foreach($columns as $column) {
        $mysql_type = preg_replace('/\\(.*\\).*/', '', $column['Type']);
        $php_type = "";
        switch($mysql_type) {
          case 'tinyint':
          case 'smallint':
          case 'mediumint':
          case 'int':
          case 'bigint':
            $php_type = "int";
            break;
          case 'varchar':
          case 'char':
          case 'text':
          case 'mediumtext':
          case 'blob':
          case 'date':
          case 'datetime':
          case 'timestamp':
          case 'binary':
            $php_type = "string";
            break;
          case 'decimal':
            $php_type = "float";
            break;
        }
        if(!$php_type) echo $column['Type'], "\n\n";
        //设置构造函数的默认值
        if($column['Default'] !== null && $column['Default'] !== "CURRENT_TIMESTAMP") {
          if($php_type == 'int' || $php_type == 'float')
            $construct_lines[] = '$this->' . $column['Field'] . ' = ' . $column['Default'] . ";";
          else
            $construct_lines[] = '$this->' . $column['Field'] . ' = ' . "'" . $column['Default'] . "';";
        }

        $field_comment_line = rtrim(" * @property {$php_type} \${$column['Field']} {$column['Comment']}") . "\n";
        $reg = "/ \\* @property[^\\\$]+\\\${$column['Field']}\\b[^\n]*\n/s";
        if(preg_match($reg, $class_comments_content, $matches)) {
          $class_comments_content = preg_replace($reg, $field_comment_line, $class_comments_content);
        } else {
          $class_comments_content = str_replace("\n */", "\n" . $field_comment_line . " */", $class_comments_content);
        }
      }
      $old_model_file_contents = $model_file_contents;
      if($old_class_comments_content) {
        $model_file_contents = str_replace($old_class_comments_content, $class_comments_content, $model_file_contents);
      } else {
        $model_file_contents = str_replace("class {$classname}", $class_comments_content . "class {$classname}", $model_file_contents);
      }
      if($old_model_file_contents != $model_file_contents) {
        file_put_contents($filepath, $model_file_contents);
        echo "property comments updated: ", $filepath, "\n";
      }

      //处理模型类的构造函数
      if(empty($construct_lines)) {
        $new_construct_content = "public function __construct() {\n  }";
      } else {
        $new_construct_content = "public function __construct() {\n    "
          . join("\n    ", $construct_lines)
          . "\n  }";
      }
      if(!$construct_content) { //则插入到顶部
        $construct_content = "/TCModelBase *{/";
        $new_construct_content = "TCModelBase {\n\n  " . $new_construct_content;
        $model_file_contents = preg_replace($construct_content, $new_construct_content, $model_file_contents);
        file_put_contents($filepath, $model_file_contents);
        echo "function construct inserted: ", $filepath, "\n";
      } elseif($construct_content != $new_construct_content) { // 需要替换
        $model_file_contents = str_replace($construct_content, $new_construct_content, $model_file_contents);
        file_put_contents($filepath, $model_file_contents);
        echo "function construct updated: ", $filepath, "\n";
      }

    }
  }

  private function listAllModelFileNames() {
    $folder = APPLICATION_DIRECTORY . '/models/';
    $filenames = array();
    $it = new DirectoryIterator($folder);
    while($it->valid()) {
      if(!$it->isDot() && $it->isFile() && $it->getExtension() === "php") {
        include_once $it->getPath() . '/' . $it->getFilename();
        $filenames[] = substr($it->getFilename(), 0, -4);
      }
      $it->next();
    }

    return $filenames;
  }
}
