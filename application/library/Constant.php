<?php

/**
 * @name Constant
 * @author liujianchun
 */
class Constant{
	/**
	 * 用来执行邮箱地址格式验证的正则表达式
	 */
	const REG_EMAIL = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
  /**
   * 用来获取文件后缀的正则表达式
   */
  const REG_FILE_SUFFIX = '/\\.([a-zA-Z0-9]+)$/';
	
	
	const NAV_ITEM_HOME = 'home';
	const NAV_ITEM_DOCUMENTS = 'documents';
	const NAV_ITEM_DEV = 'dev';
	const NAV_ITEM_LOGIN = 'login';
	
	
	/**
	 * 服务器故障
	 */
	const ERROR_SERVER = -100;

	/**
	 * token过期
	 */
	const ERROR_TOKEN_EXPIRE = -1001;

}

