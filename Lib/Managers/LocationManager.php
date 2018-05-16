<?
namespace Famous\Lib\Managers;
use Famous\Lib\Utils\Constant as Constant;
class LocationManager{
	
	const CONTROLLER_PART = "controller";
	const ACTION_PART = "action";
	
	private $DEFAULT_PARTS;
	private $parts;
	private $ETALON_PATH;
	
	private static $lm;
	
	private function __construct(){
		$this->DEFAULT_PARTS = array("controller"=>Constant::CONTROLLER_MAIN, "action"=>Constant::ACTION_INDEX);
		$this->ETALON_PATH = self::CONTROLLER_PART . "/" . self::ACTION_PART;
		$this->initParts();
	}
	
	public static function getInstance(){
		if(self::$lm === null){
			self::$lm = new LocationManager();
		}
		return self::$lm;
	}
	
	/*
		parse an etalon string and associate part to their positions, fills an array
		example for string "lang/controller/action": 
			lang => 0,
			controller => 1,
			action => 2
	*/
	private function initParts(){
		$this->parts = array();
		$arr = explode('/', $this->ETALON_PATH);
		for($i=0; $i<count($arr); $i++){
			$this->parts[$arr[$i]] = $i;
		}
	}
	
	public function getPart($routes, $part){
		if(empty($routes) || !is_array($routes) || !array_key_exists($part, $this->parts)){
			throw new InvalidArgumentException ('Invalid arguments for part parser!');
		}
		
		return $routes[$this->parts[$part]];
	}
	
	public function getPartNum($part){
		if(!array_key_exists($part, $this->parts))
			throw new InvalidArgumentException ('Invalid arguments for part!');
		$this->parts[$part];
	}
	
	public function getPartName($num){
		foreach($this->parts as $k => $v){
			if($v == $num)
				return $k;
		}
		throw new InvalidArgumentException ('Unknown part number');
	}
	
	/*
		get routes
		if $url do not contains some parts then their will be added
	*/
	public function getRoutes($url){
		$tempRoutes = explode('/', $url);
		for($i=0; $i<count($tempRoutes); $i++){
			if(!empty($tempRoutes[$i])){
				$routes[] = $tempRoutes[$i];
			}
		}
		for($i = count($routes); $i < count($this->parts); $i++){
			$routes[] = $this->DEFAULT_PARTS[$this->getPartName($i)];
		}
				
		return $routes;
	}

	public static function prepareFileName($prefix, $name){
		return $prefix . ucfirst($name);
	}
}