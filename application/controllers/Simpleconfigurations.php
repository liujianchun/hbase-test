<?php

/**
 * 存储在数据库中的简易配置系统
 */
class SimpleConfigurationsController extends TCControllerBase {

  /**
   * @menu 配置
   * @submenu 列表
   */
  public function indexAction() {
    $this->getView()->assign('breadcrumbs', [
      '首页' => '/',
      '配置管理',
    ]);
    $params = [];
    $sql = "select * from " . SimpleConfigurationModel::tableName() . ' where 1';
    if(!empty($_GET['word'])) {
      $params[':word'] = '%' . addcslashes($_GET['word'], '%_') . '%';
      $sql .= " and (`key` like :word or name like :word)";
    }
    $page_size = 50;
    $current_page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $sql .= ' order by `key`';
    $sql .= ' limit ' . ($current_page * $page_size) . ',' . $page_size;
    $models = SimpleConfigurationModel::findAllBySql($sql, $params);
    $models_count = SimpleConfigurationModel::countBySql($sql, $params);
    $page_count = $models_count % $page_size == 0 ? $models_count / $page_size : floor($models_count / $page_size) + 1;
    $this->getView()->assign('models_count', $models_count);
    $this->getView()->assign('page_count', $page_count);
    $this->getView()->assign('models', $models);
  }

  /**
   * @submenu 新增
   */
  public function newAction() {
    $this->getView()->assign('breadcrumbs', [
      '首页' => '/',
      '配置管理' => '/SimpleConfigurations/index',
      '新增',
    ]);
    $model = new SimpleConfigurationModel();
    if($this->getRequest()->isPost()) {
      $this->saveFromPost($model);

      return $this->redirect("index");
    }
    $this->getView()->assign('model', $model);
  }


  public function editAction() {
    $model = SimpleConfigurationModel::findById($_GET['id']);
    if(empty($model)) {
      return $this->redirect('index');
    }
    $this->getView()->assign('breadcrumbs', [
      '首页' => '/',
      '配置管理' => '/SimpleConfigurations/index',
      $model->name,
      '编辑',
    ]);
    if($this->getRequest()->isPost()) {
      if($this->saveFromPost($model)) {
        return $this->redirect(empty($_POST['referer']) ? 'index' : $_POST['referer']);
      }
    }
    $this->getView()->assign('model', $model);
  }

  /**
   * @param SimpleConfigurationModel $model
   * @return bool
   */
  private function saveFromPost($model) {
    $model->type = intval($_POST['SimpleConfigurationModel']['type']);
    $model->key = trim($_POST['SimpleConfigurationModel']['key']);
    $model->name = trim($_POST['SimpleConfigurationModel']['name']);
    switch($model->type) {
      case SimpleConfigurationModel::TYPE_INTEGER:
        $model->value = intval($_POST['SimpleConfigurationModel']['value']);
        break;
      case SimpleConfigurationModel::TYPE_DOUBLE:
        $model->value = doubleval($_POST['SimpleConfigurationModel']['value']);
        break;
      case SimpleConfigurationModel::TYPE_TEXT:
        $model->value = trim($_POST['SimpleConfigurationModel']['value']);
        break;
      case SimpleConfigurationModel::TYPE_IMAGE:
      case SimpleConfigurationModel::TYPE_FILE:
        if(empty($_FILES['SimpleConfigurationModel']['tmp_name']['value-file'])) {
          $this->getView()->assign('error', '您没有上传文件');

          return false;
        }
        $temp_file_path = $_FILES['SimpleConfigurationModel']['tmp_name']['value-file'];
        $suffix = '';
        if(preg_match(Constant::REG_FILE_SUFFIX, $_FILES['SimpleConfigurationModel']['name']['value-file'], $matches)) {
          $suffix = $matches[1];
        }
        if(in_array($suffix, ['php', 'php3', 'php4'])) {
          $this->getView()->assign('error', '禁止的文件类型');

          return false;
        }
        $model->value = '/images/simple-configurations/' . md5(uniqid()) . '.' . $suffix;
        $file_path = APPLICATION_PATH . $model->value;
        $folder_path = dirname($file_path);
        if(!is_dir($folder_path)) {
          if(!@mkdir($folder_path)) {
            $this->getView()->assign('error', '无法创建目录: ' . $folder_path);

            return false;
          }
        }


        if($model->type === SimpleConfigurationModel::TYPE_IMAGE) {
          $image = @imagecreatefromstring(file_get_contents($temp_file_path));
          if(empty($image)) {
            $this->getView()->assign('error', '您没有上传文件或者上传的文件不是图片');

            return false;
          }
        }
        if(!move_uploaded_file($_FILES['SimpleConfigurationModel']['tmp_name']['value-file'], $file_path)) {
          $this->getView()->assign('error', '无法写入文件');
        }
        break;
    }
    $model->save();
    TCMemcachedManager::getInstance()->cache->delete($model->key);

    return true;
  }

}