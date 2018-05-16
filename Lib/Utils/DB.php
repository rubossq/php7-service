<?
namespace Famous\Lib\Utils;
use \PDO as PDO;
	/**
	* Database class for work with mysql db 
	* with mysql_connect function, across SQL in UTF-8 charset
	* The class implement pattern "Singleton" - only one object
	* of this class can exist in one time
	*/
class DB{
	
	private static $db;
	private static $dbh;
	
	/**
    * constructor, initializing db connection
    * @access private
    */
	private function __construct(){
		try{
			//self::$dbh = new PDO("mysql:host=".Config::HOST_DB.";dbname=".Config::NAME_DB.";charset=utf8;", Config::LOGIN_DB, Config::PASS_DB);
			self::$dbh = new PDO("mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=".Config::NAME_DB.";charset=utf8;", Config::LOGIN_DB, Config::PASS_DB);
			if(Constant::CURRENT_MODE == Constant::DEBUG_MODE){
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}catch(\PDOException $e){
			//self::$dbh = new PDO("mysql:host=".Config::HOST_DB.";dbname=".Config::NAME_DB.";charset=utf8;", Config::LOGIN_DB, Config::PASS_DB);
			self::$dbh = new PDO("mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=".Config::NAME_DB.";charset=utf8;", Config::LOGIN_DB, Config::PASS_DB);
			if(Constant::CURRENT_MODE == Constant::DEBUG_MODE){
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}


	}
	
	/**
    * get current instance
	* @return object of class DB  
    * @access public
    */
	public static function getInstance(){
		if(self::$db === null){
			self::$db = new DB();
		}
		return self::$db;
	}

	/**
	 * get current instance
	 * @return object of class DB
	 * @access public
	 */
	public static function closeIfExist(){
		if(self::$db !== null){
			self::$db->close();
		}
	}

	/**
    * get current dbh
	* @return dbh object
    * @access public
    */
	public function getDBH(){
		return self::$dbh;
	}
	
	/**
    * close connection if open, set current instance to NULL
	* @access public
    */
	public function close(){
		self::$dbh = null;
		self::$db = null;
	}
	
	/**
    * close connection if open, set current instance to NULL
	* @access public
    */
	public function __destruct(){
		self::$dbh = null;
		self::$db = null;
	}

}
?>