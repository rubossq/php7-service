<?
namespace Famous\Core;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\LocationManager as LocationManager;
use Famous\Controllers\Controller_Proxy as Controller_Proxy;
use \Exception as Exception;

class Route{

    public static function start(){
       
		$controller = Constant::CONTROLLER_MAIN;
        $action = Constant::ACTION_INDEX;
		
		$locationManager = LocationManager::getInstance();
		$routes = $locationManager->getRoutes($_SERVER['REQUEST_URI']);

		//get parts from uri
		//action can be numeric
		try{
			$controller =  $locationManager->getPart($routes, LocationManager::CONTROLLER_PART);
			$action =  $locationManager->getPart($routes, LocationManager::ACTION_PART);
		}catch(Exception $e){
			self::errorPage404();
		}
		
		//prepare names
		$modelName = LocationManager::prepareFileName(Constant::MODEL_PREFIX, $controller);
        $controllerName = LocationManager::prepareFileName(Constant::CONTROLLER_PREFIX, $controller);

        //include model and controller if exist
		try{
			self::includeIfExist(Constant::MODELS_PATH . $modelName . '.php');
			self::includeIfExist(Constant::CONTROLLERS_PATH . $controllerName . '.php');
		}catch(Exception $e){
			self::errorPage404();
		}

		 //create controller and coll action
		$cn = Constant::CONTROLLERS_PATH_NS . '\\' . $controllerName;
        $controllerObj = new $cn;


		$actionName = Constant::ACTION_PREFIX . $action;
		
		if(method_exists($controllerObj, $actionName)){
			$controllerObj->$actionName();
		}else{
			self::errorPage404();
		}
	}
	
	
	static function includeIfExist($file_path){
		if(file_exists($file_path)){
            include $file_path;
        }else{
			throw new Exception("Can not include - no such file");
		}
	}

	public static function getUrl() {
		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	  	$url .= ( $_SERVER["SERVER_PORT"] != 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	  	return $url;
	}

	public function errorPage404(){
		$qs = "address=".self::getUrl();
		$controllerObj = new Controller_Proxy();
		$controllerObj->setQuery($qs);
		$controllerObj->action_not_found_404();
	}

}
?>