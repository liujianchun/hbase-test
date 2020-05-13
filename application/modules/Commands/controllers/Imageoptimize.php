<?php

/**
 * 图片压缩优化相关命令
 */
class ImageOptimizeController extends TCControllerBase {
  /**
   * @var PDO
   */
  private $pdo;


  public function init() {
    parent::init();
    $dbFilepath = APPLICATION_DIRECTORY . '/runtime/image-optimize.sqlite';
    if (!file_exists($dbFilepath)) {
      $this->pdo = new PDO("sqlite:" . $dbFilepath);
      $this->pdo->exec("create table if not exists optimized_images(path varchar(255))");
      $this->pdo->exec("create unique index if not exists path on optimized_images(path)");
    } else
      $this->pdo = new PDO("sqlite:" . $dbFilepath);
  }

  /**
   * 从数据库中查询一个图片是否已经被优化过了
   * @param $filepath
   * @return boolean
   */
  private function isImageOptimized($filepath) {
    $sql = "select * from optimized_images where path=:path";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(array(':path' => $filepath));
    return !empty($stmt->fetch(PDO::FETCH_ASSOC));
  }


  /**
   * 在数据库中把一个图片标记为已经优化过了
   * @param $filepath
   */
  private function markOptimized($filepath) {
    $sql = "insert into optimized_images (path) values (:path)";
    $stmt = $this->pdo->prepare($sql);
    if(!$stmt) {
      echo "ERROR occurred\n";
      var_dump($this->pdo->errorInfo());
      exit;
    }
    $stmt->execute(array(':path' => $filepath));
  }

  /**
   * 压缩优化一个目录下的所有图片
   * @param $path
   */
  public function optimizeAllImagesUnderFolderAction($path = null) {
    if (empty($path)) $path = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'images';
    if (!is_dir($path)) return;
    $it = new DirectoryIterator($path);
    while ($it->valid()) {
      $subpath = $it->getPath() . DIRECTORY_SEPARATOR . $it->getFilename();
      if ($it->isDot()) {
      } elseif ($it->isFile()) {
        if (!$this->isImageOptimized($subpath)) {
          if ($this->optimizeImage($subpath)) {
            $this->markOptimized($subpath);
          }
        }
      } elseif ($it->isDir()) {
        $this->optimizeAllImagesUnderFolderAction($subpath);
      }
      $it->next();
    }
  }

  /**
   * 压缩优化一张图片
   * @param $filepath 图片的路径
   * @return boolean
   */
  private function optimizeImage($filepath) {
    if (!is_file($filepath)) return false;
    if (filesize($filepath) > 1024 * 1024 * 10) {
      echo "file size too big of path {$filepath}\n";
      return false;
    }
    $image = imagecreatefromstring(file_get_contents($filepath));
    if (empty($image)) return false;
    unset($image);

    if (!preg_match('/\\.([a-z0-9]+)$/', $filepath, $matches)) return false;
    $suffix = strtolower($matches[1]);

    $output = array();
    $result = -1;
    if ($suffix == 'png') {
      $command = "optipng -strip all -quiet -o7 '{$filepath}'";
      exec($command, $output, $result);
      if ($result === 0) {
        echo "optimized: {$filepath}\n";
        return true;
      }
    } elseif ($suffix == 'jpg' || $suffix == 'jpeg') {
      $tempfile = APPLICATION_DIRECTORY . "/runtime/image-optimize.{$suffix}";
      @unlink($tempfile);
      if (is_file($tempfile)) {
        echo "cannot delete temp file {$tempfile}\n";
        return false;
      }
      $command = "jpegtran -copy none -optimize -progressive -outfile '{$tempfile}' '{$filepath}'";
      exec($command, $output, $result);
      if ($result === 0 && is_file($tempfile)) {
        $image = imagecreatefromjpeg($tempfile);
        if (!empty($image)) {
          unset($image);
          if (@copy($tempfile, $filepath)) {
            echo "optimized: {$filepath}\n";
            return true;
          } else {
            echo "cannot copy optimized file to {$filepath}\n";
          }
        }
      }
    }

    return false;
  }


}
