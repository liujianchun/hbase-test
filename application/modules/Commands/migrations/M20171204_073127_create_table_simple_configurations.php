<?php

class M20171204_073127_create_table_simple_configurations extends TCMigrationBase {
  public function up() {
    $this->createTable('simple_configurations', [
      'id' => 'integer not null primary key auto_increment',
      'key' => 'varchar(100) not null',
      'name' => 'varchar(100) not null comment "该配置的可读的名称"',
      'type' => 'tinyint not null default 0 comment "配置的类型"',
      'value' => 'text',
      'unique key `key`(`key`)',
    ]);
  }

  public function down() {
    $this->dropTable('simple_configurations');
  }
}