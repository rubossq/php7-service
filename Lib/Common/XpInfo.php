<?php
namespace Famous\Lib\Common;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\XpManager as XpManager;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;

class XpInfo{

	private $xp;
	private $lvl;

	public function __construct($xp){
		$this->xp = (int)($xp % Constant::XP_PER_LVL);
		$this->lvl = (int)($xp / Constant::XP_PER_LVL);

	}
	
	public function getArr(){
		return array("xp"=>$this->xp, "lvl"=>$this->lvl);
	}
	
	
	public static function getXp(){
		return 0;
	}
	
	public static function getLvl(){
		return 0;
	}
		
	public function upXp($user_id){
		//echo $this->xp . " " . $this->lvl;
		$db = DB::getInstance();
		$dbh = $db->getDBH();
		$up = intval($this->getXpForLvl($this->lvl));

		$this->xp += $up;

		if($this->xp >= Constant::XP_PER_LVL){
			$this->lvl++;
			$this->xp = $this->xp % Constant::XP_PER_LVL;
			$params = $this->lvlGifts($this->lvl);
			$time = time();
			$stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time, params) VALUES (:user_id, 4, :time, :params)");
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
			$stmt->bindParam(":time", $time, PDO::PARAM_INT);
			$stmt->bindParam(":params", $params);
			$stmt->execute();
			$notif_id = NotificationManager::getNotifId($dbh, NotificationManager::LVL_NOTIFICATION);
			NotificationManager::sendNotification($notif_id, $user_id);
		}

		$stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET xp = xp + :up WHERE id = :user_id");
		$stmt->bindParam(":up", $up, PDO::PARAM_INT);
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->execute();

		return $up;
	}
	
	private function getXpForLvl($lvl){
		
		if($lvl <= 40){
			$hard = ceil($lvl / Constant::HARD_RANGE);
			$tasks = $hard * Constant::STEP_TASKS_PER_HARD;
			
			$up = floor(Constant::XP_PER_LVL / $tasks);
			
		}else{
			$up = Constant::XP_PER_LVL / Constant::LEGEND_LVL_TASK_NUM;
		}
		
		if($up < 1)
			$up = 1;
		return $up;
	}

	public function lvlGifts($lvl){
		$box = 0;
		$premium = 0;
		$turbo = 0;
		$achieve = 0;
		
		$diamonds = $this->getBonusForLvl($lvl);

		if($lvl < 10){
			;
		}else if($lvl < 20){
			$box = 1;
		}else if($lvl == 20){
			$diamonds = 0;
			$turbo = "turbo_free";
		}else if($lvl < 40){
			$box = 1;
		}else if($lvl == 40){
			$diamonds = 0;
			$premium = 1;
		}else{
			$box = 1;
		}

		$rand = rand(1, 10);

		if($box){
			$box = $rand * 10;
		}
		
		if($rand <= 3){
			$user_id = Manager::$user->getId();
			$achieve = XpManager::getAchieve($user_id);
		}

		$params = "diamonds=$diamonds&box=$box&premium=$premium&turbo=$turbo&lvl=$lvl&achieve=$achieve";
		return $params;
	}

	private function getBonusForLvl($lvl){
		if($lvl <= 40){
			$hard = ceil($lvl / Constant::HARD_RANGE);
			$bonus = $hard * Constant::STEP_BONUS_PER_HARD;
		}else{
			$bonus = Constant::LEGEND_LVL_BONUS_SUM;
		}
		return $bonus;
	}
}

?>
