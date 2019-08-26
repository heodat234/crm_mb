<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Language_model extends CI_Model {

	private $collection = "Language";

	function __construct() {
		parent::__construct();
        $this->load->library('mongo_private');
    }

    function translate($content, $type = "", $language = "", $sub = "", $char = "@", $use_cache = TRUE) {
    	if(!$sub) $sub = set_sub_collection("");
    	if(!$language) $language = $this->session->userdata("language");
    	$language = strtoupper($language);
    	$where = array("language" => $language);
    	if($type) $where["type"] = $type;

    	if($use_cache) {
	    	// Cache file
	    	$params_string =  $sub . $type . "_" . $language;
	    	$time_cache     = $this->config->item("wff_time_cache");
	    	$this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
	    	$file_name = $params_string . "_languages";
	    	$languages = array();
			if ( !$languages = $this->cache->get($file_name) ) {
	            $this->load->library("mongo_private");
	            $languages = $this->mongo_private->where($where)->get($sub . $this->collection);
	            $this->cache->save($file_name, $languages, $time_cache);
	        }
        } else {
        	$this->load->library("mongo_private");
	        $languages = $this->mongo_private->where($where)->get($sub . $this->collection);
        }

		$keys = array();
		$values = array();
		if($languages) {
			foreach($languages as $lang) {
				if(isset($lang["key"], $lang["value"])) {
					$keys[] = $char . $lang["key"] . $char;
					$values[] = $lang["value"];
				}
			}
		}

		$type = is_array($content) ? "array" : "string";
		if($type == "array")
			$content_str = json_encode($content);
		else $content_str = (string) $content;

		$content_replace = str_replace($keys, $values, $content_str);
		// Xu ly dac biet ngon ngu tieng anh
		if($language == "ENG") {
			$where["language"] = "VIE";
			$languages = $this->mongo_private->where($where)->get($sub . $this->collection);
			$keys = array();
			$values = array();
			if($languages) {
				foreach($languages as $lang) {
					if(isset($lang["key"])) {
						$keys[] = $char . $lang["key"] . $char;
						$values[] = $lang["key"];
					}
				}
			}
			$content_replace = str_replace($keys, $values, $content_replace);
		}

		return ($type == "array") ? json_decode($content_replace, TRUE) : $content_replace;
    }
}