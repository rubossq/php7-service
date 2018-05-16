<?php
namespace Famous\Lib\Utils;
class Validator{

	const MAX_NICK_LENGTH = 64;
	const MIN_LENGTH = 3;

	public static function clear($var){
		return addslashes(htmlspecialchars(strip_tags(trim($var))));
	}

	public static function isValidNick($str){
		if(strlen($str) > self::MAX_NICK_LENGTH || strlen($str) < self::MIN_LENGTH)
			return false;
		if(!preg_match("/^[a-zA-Z0-9]+[.]{0,1}[a-zA-Z0-9_]+$/i", $str))
			return false;

		return true;
	}

	public static function isValidPostLink($str){

		if(!preg_match("/www\\.instagram\\.com\\/p\\/[a-zA-Z0-9]+/i", $str))
			return false;

		return true;
	}
}

?>