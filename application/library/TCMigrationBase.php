<?php

/**
 * @name TCMigrationBase
 * @author liujianchun
 */
abstract class TCMigrationBase {


  /**
   * @return PDO
   */
  public function getDbConnection() {
    return TCDbManager::getInstance()->db;
  }


  public abstract function up();

  public abstract function down();


  /**
   * 创建一个表
   * @param string $name 表名
   * @param array $columns 列名=>列信息组成的数组或者索引信息
   * @param string $options 建表的其他参数, 如 engine innodb
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function createTable($name, $columns, $options = null) {
    $sql = "create table `{$name}` (";
    $columns_sql = [];
    foreach($columns as $column_name => $column_info) {
      if(is_int($column_name)) {
        // 索引信息
        $columns_sql[] = $column_info;
      } else {
        // 字段信息
        $columns_sql[] = "`{$column_name}` $column_info";
      }
    }
    $sql .= join(',', $columns_sql);
    $sql .= ")";
    if(!empty($options)) $sql .= $options;

    return $this->getDbConnection()->exec($sql);
  }

  /**
   * 删除一个表
   * @param string $name 表名
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function dropTable($name) {
    $sql = "drop table `{$name}`";

    return $this->getDbConnection()->exec($sql);
  }

  /**
   * 创建一个索引
   * @param string $name 索引名称
   * @param string $table 表名
   * @param string|string[] $column 列名
   * @param bool $unique 是否是唯一索引
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function createIndex($name, $table, $column, $unique = false) {
    if(is_array($column)) {
      foreach($column as $i => $item) {
        $column[$i] = "`{$item}`";
      }
      $column = join(',', $column);
    } else $column = "`{$column}`";
    $sql = "create " . ($unique ? "unique" : "") .
      " index `{$name}` on `{$table}`({$column})";

    return $this->getDbConnection()->exec($sql);
  }

  /**
   * 删除一个索引
   * @param string $name 索引名称
   * @param string $table 表名
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function dropIndex($name, $table) {
    $sql = "drop index `{$name}` on `{$table}`";

    return $this->getDbConnection()->exec($sql);
  }

  /**
   * 给表增加一列
   * @param string $table 表名
   * @param string $name 列名
   * @param string $info 列的类型等信息
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function addColumn($table, $name, $info) {
    return $this->getDbConnection()->exec("alter table `{$table}` add column `{$name}` {$info}");
  }

  /**
   * 修改表的某一列
   * @param string $table 表名
   * @param string $name 列名
   * @param string $new_name 新的列名
   * @param string $info 列的类型等信息
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function changeColumn($table, $name, $new_name, $info) {
    return $this->getDbConnection()->exec("alter table `{$table}` change column `{$name}` `$new_name` {$info}");
  }

  /**
   * 给表增加一列, 函数 addColumn 的别名
   * @param string $table 表名
   * @param string $name 列名
   * @param string $info 列的类型等信息
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function createColumn($table, $name, $info) {
    return $this->addColumn($table, $name, $info);
  }

  /**
   * 给表删除一列
   * @param string $table 表名
   * @param string $name 列名
   * @return int returns the number of rows that were modified or deleted by the SQL statement you issued
   */
  public function dropColumn($table, $name) {
    return $this->getDbConnection()->exec("alter table `{$table}` drop column `{$name}`");
  }

}

