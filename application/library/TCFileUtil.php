<?php

/**
 * file utils
 * @author liujianchun
 */
class TCFileUtil {

  public static $image_suffixes = [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico',
  ];

  public static $compressed_archive_suffixes = [
    'zip', '.tar.gz', 'tar.bz2', 'gz', 'rar',
  ];

  /**
   * 从文件名中获取一个文件的后缀
   * @param string $filename
   * @return string
   */
  public static function getSuffixOfFilename($filename) {
    if(preg_match('/\\.([\\.a-z0-9A-Z]+)$/', $filename, $matches)) {
      return $matches[1];
    }
  }

  /**
   * 判断一个文件后缀是不是图片
   * @param string $suffix
   * @return boolean
   */
  public static function isFileSuffixImage($suffix) {
    return in_array(strtolower($suffix), self::$image_suffixes);
  }

  /**
   * 优化 jpg 文件
   * @param string $from
   * @param string $to
   * @return bool
   */
  public static function optimizeImageJPG($from, $to) {
    $command = "jpegtran -copy none -optimize -progressive -outfile '{$to}' '{$from}'";
    exec($command, $output, $result);

    return $result === 0 && is_file($to);
  }

  /**
   * 判断一个文件后缀是不是压缩包
   * @param string $suffix
   * @return boolean
   */
  public static function isFileSuffixCompressedArchive($suffix) {
    return in_array(strtolower($suffix), self::$compressed_archive_suffixes);
  }

  /**
   * 把文件解压缩出来并把所有文件平铺在最外层目录
   * @param string $filepath
   * @throws Exception
   * @return string|false
   */
  public static function uncompressAndFlatCompressedArchive($filepath) {
    $suffix = strtolower(self::getSuffixOfFilename($filepath));
    if(!self::isFileSuffixCompressedArchive($suffix)) return false;
    $target_folderpath = substr($filepath, 0, strlen($filepath) - strlen($suffix) - 1);
    $unar = null;
    if(file_exists('/usr/local/bin/unar')) $unar = '/usr/local/bin/unar';
    elseif(file_exists('/usr/bin/unar')) $unar = '/usr/bin/unar';
    if(empty($unar)) {
      $message = PHP_OS === 'Darwin' ?
        'use brew install unar to install unar' :
        'use yum install -y unar to install unar';
      throw new Exception($message);
    }
    $command = "export LANG='en_US.UTF-8';"
      . "{$unar} -force-overwrite -no-directory -output-directory '$target_folderpath' '$filepath'";
    @exec($command, $output, $return_var);
    if($return_var !== 0) return false;
    foreach(TCFileUtil::listAllFilesWithExtensionUnder($target_folderpath) as $f) {
      @rename($f->getPathname(), $target_folderpath . '/' . $f->getFilename());
    }
    // 删除掉子一级的空目录
    $it = new DirectoryIterator($target_folderpath);
    while($it->valid()) {
      if($it->isDot()) {
      } elseif($it->isDir()) {
        $path = $it->getPath() . DIRECTORY_SEPARATOR . $it->getFilename();
        @exec("rm -rf '{$path}'");
      }
      $it->next();
    }

    return $target_folderpath;
  }

  /**
   * 计算一个文件或者目录的字节大小
   * @param string $file_path 文件或者目录的路径
   * @return int
   */
  public static function calculateFileOrFolderSize($file_path) {
    $file_size = 0;
    if(is_file($file_path)) {
      $file_size += filesize($file_path);
    } elseif(is_dir($file_path)) {
      if($file_path[-1] !== '/') $file_path .= '/';
      $dir = opendir($file_path);
      while($d = readdir($dir)) {
        if($d == '.' || $d == '..') continue;
        $sub_file_path = $file_path . $d;
        $file_size += self::calculateFileOrFolderSize($sub_file_path);
      }
    }

    return $file_size;
  }

  /**
   * @param string $path the folder path
   * @param mixed $extension string or array, if specified, only files with theses extensions will be listed
   * @param int $depth the depth, default -1 means not limit depth of search, 1 means only current and no sub folders
   * @return \SplFileInfo[]
   */
  public static function listAllFilesWithExtensionUnder($path, $extension = null, $depth = -1) {
    if(!is_dir($path)) return array();
    if(is_string($extension))
      $extensions = array(strtolower($extension));
    elseif(is_array($extension)) {
      $extensions = array();
      foreach($extension as $item)
        $extensions[] = strtolower($item);
    } else $extensions = array();

    $files = array();
    $it = new DirectoryIterator($path);
    while($it->valid()) {
      if($it->isDot()) {
      } elseif($it->isFile()) {
        if(empty($extensions)) {
          $files[] = $it->getFileInfo();
        } elseif(in_array(strtolower($it->getExtension()), $extensions)) {
          $files[] = $it->getFileInfo();
        }
      } elseif($it->isDir()) {
        if($depth != 0) {
          $subpath = $it->getPath() . DIRECTORY_SEPARATOR . $it->getFilename();
          $subfiles = self::listAllFilesWithExtensionUnder($subpath, $extensions, $depth - 1);
          foreach($subfiles as $item) {
            $files[] = $item;
          }
        }
      }
      $it->next();
    }

    return $files;
  }

  /**
   * @param int $size
   * @return string
   */
  public static function formatFileSize($size) {
    if($size < 1024) return '<1K';
    if($size < 1024 * 1024) return (round($size * 10 / 1024) / 10) . 'K';
    if($size < 1024 * 1024 * 1024) return (round($size * 10 / 1024 / 1024) / 10) . 'M';

    return (round($size * 10 / 1024 / 1024 / 1024) / 10) . 'G';
  }

}
