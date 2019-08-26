<?php
/**
 * Omnisales clone© 2019
 *
 */
// Load from wffdata.php


class Config {

	const MONGO_DB_HOST           = 'localhost';
	const MONGO_DB_PORT           = 27017;
	const MONGO_DB_NAME           = 'worldfone4xs';
	const CUSTOMER_SECRET_KEY     = 'a357e8e5fbce92dd44269146416b0b4d';
	const CUSTOMER_TYPE     = '2';

	public static function OMNI_WEBHOOK_SOCKET_URL(){
		$getConfig = Config::getConfig();
		if ($getConfig->wff_env=='DEV') {
			return 'http://192.168.16.130:8001';
		}else{
			return 'http://10.17.254.31:8001';
		}
	}
	private function getConfig(){
		$file = $_SERVER['DOCUMENT_ROOT'] . "/system/config/wffdata.json";
		$fd = fopen ($file, 'r');
		$content = filesize($file) ? fread($fd, filesize($file)) : "";
		if($content){
			$content = json_decode($content);
			return $content;
		}
	}

}

// var_dump(Config::OMNI_WEBHOOK_SOCKET_URL());



	