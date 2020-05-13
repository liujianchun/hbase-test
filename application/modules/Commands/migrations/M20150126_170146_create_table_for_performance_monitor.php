<?php
class M20150126_170146_create_table_for_performance_monitor extends TCMigrationBase{
	public function up(){
  	$this->getDbConnection()->exec("
  	create table performance_monitor_api_urls(
  			id smallint unsigned not null primary key auto_increment,
  			path varchar(255) character set utf8 not null comment 'relative path of this interface',
  			unique key path(path)
  	) engine myisam");
  	$this->getDbConnection()->exec("
  	create table performance_monitor_hourly_api_performance_data (
  			id integer unsigned not null primary key auto_increment,
  			`date` date not null,
  			hour tinyint not null,
  			api_url_id smallint unsigned not null default 0,
  			count integer not null default 0 comment 'access count of this interface at this hour',
  			average mediumint unsigned not null default 0 comment 'average time cost of this interface, in milliseconds',
  			max mediumint unsigned not null default 0 comment 'max time cost of this interface, in milliseconds',
  			percentage_99 mediumint unsigned not null default 0 comment 'max time of 99% access cost of this interface, 1/100 in milliseconds',
  			percentage_95 mediumint unsigned not null default 0 comment 'max time of 95% access cost of this interface, 1/100 in milliseconds',
  			percentage_90 mediumint unsigned not null default 0 comment 'max time of 90% access cost of this interface, 1/100 in milliseconds',
  			percentage_60 mediumint unsigned not null default 0 comment 'max time of 60% access cost of this interface, 1/100 in milliseconds',
  			unique key api_url_id_date_hour(api_url_id, `date`, hour),
  			key `date`(`date`)
		) engine myisam");
	}
	public function down(){
		$this->getDbConnection()->exec("drop table performance_monitor_api_urls");
		$this->getDbConnection()->exec("drop table performance_monitor_hourly_api_performance_data");
	}
}