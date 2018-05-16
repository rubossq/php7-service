<?php
	namespace Famous\Lib\Common;

	use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
	use Famous\Lib\Utils\Secure as Secure;

	class Manager{
		
		public static $user;
		
		public static function init(){
			if(isset($_SESSION['user'])){
				self::$user = unserialize($_SESSION['user']);
			}else{
				self::$user = new User();
			}

		}
		
		public static function destroySession() {
			if ( session_id() ) {
				// Если есть активная сессия, удаляем куки сессии,
				setcookie(session_name(), session_id(), time()-60*60*24);
				// и уничтожаем сессию
				session_unset();
				session_destroy();
			}
		}
		
		public static function startSession() {
			header('Access-Control-Allow-Credentials: true');
			$host = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"] : "";
			//file_put_contents("test.txt", "host-".$host."-");
			if(empty($host)){
				$host = "null";
			}else{
				$host = substr($host, 0, strlen($host)-1);
			}
			header('Access-Control-Allow-Origin: '.$host);


			
			// Таймаут отсутствия активности пользователя (в секундах)
			$sessionLifetime = Constant::SESSION_LIFE_TIME;
			if ( session_id() ) return true;
			//ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'] . Constant::SESSIONS_PATH);
			// Устанавливаем время жизни куки
			ini_set('session.cookie_lifetime', $sessionLifetime);
			// Если таймаут отсутствия активности пользователя задан, устанавливаем время жизни сессии на сервере
			ini_set('session.gc_maxlifetime', $sessionLifetime);
			if ( session_start() ) {
				setcookie(session_name(), session_id(), time()+$sessionLifetime, "/");
				return true;
			}
			else return false;
		}
		
		public static function getExecuteTime(){
			return (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
		}
		
		public static function parseRequest(){
			if(isset($_REQUEST['request'])){
				$requestStr = urldecode(Secure::decrypt($_REQUEST['request']));
				$params = explode("&", $requestStr);
				foreach($params as $param){
					$arr = explode("=", $param);
					if(count($arr) > 1){
						$key = $arr[0];
						$val = $arr[1];
						$_REQUEST[$key] = $val;
					}
				}
			}
		}
	}
?>
