<?
	use Famous\Lib\Common\Manager as Manager;
	use Famous\Core\Route as Route;
	use Famous\Lib\Utils\Constant as Constant;
	use Famous\Lib\Utils\DB as DB;

	function shutdown(){
		DB::closeIfExist();
	}

	register_shutdown_function('shutdown');

	Manager::startSession();

	if(Constant::CURRENT_MODE == Constant::DEBUG_MODE){
		ini_set('display_errors', 1);
	}else{
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
		//error_reporting(0);
	}



	Manager::init();	//user init
	Manager::parseRequest();


	Route::start(); // запускаем маршрутизатор
?>