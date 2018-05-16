<?php

class Complite{


	public static function comp(&$db, $user_id, $news_name){
		$res = false;
		switch($news_name){
			case "bonus":
				$res = true;
				break;
			case "rate":
				$res = true;
				break;
			case "topwin":
				$res = true;
				break;
			case "getlvl":
				$res = true;
				break;
			case "noadv":
				$res = true;
				break;
			case "atnight":
				$res = true;
				break;
			case "getturbo":
				$res = true;
				break;
			case "email":
				$res = true;
				break;
		}
		return $res;
	}	
}


?>
