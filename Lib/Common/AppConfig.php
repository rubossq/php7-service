<?php
namespace Famous\Lib\Common;

use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\BotManager;
use Famous\Lib\Managers\HelpManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Common\Manager as Manager;


use Famous\Lib\Managers\SettingsManager;

class AppConfig{
	
	private $like_price;
	private $subscribe_price;
	
	private $like_price_bid;
	private $subscribe_price_bid;

	private $app_version;
	
	private $like_limit;
	private $subscribe_limit;
	
	private $hard_range;
	private $step_bonus_per_hard;
	private $legend_lvl_bonus_sum;
	private $news_demon_time;

	private $referal_percent;
	private $limit_referal_min;
	private $stay_referal_bonus;

	private $settings;

	private $min_total_likes;
	private $min_posts;
	private $min_followed;
	private $verify_demon_time;
	private $verify_required_time;
	
	public function __construct(){

		$like_price = Constant::LIKE_PRICE;
		$subscribe_price = Constant::SUBSCRIBE_PRICE;
		if(Manager::$user->getBalanceType() == BalanceManager::BALANCE_MIN){
			$like_price = Constant::LIKE_PRICE_MIN;
			$subscribe_price = Constant::SUBSCRIBE_PRICE_MIN;
		}
		$this->like_price = $like_price;
		$this->subscribe_price = $subscribe_price;
		
		$this->like_price_bid = Constant::LIKE_PRICE_BID;
		$this->subscribe_price_bid = Constant::SUBSCRIBE_PRICE_BID;


		$this->app_version = HelpManager::getVersion();

		$this->like_limit = Constant::LIKE_LIMIT;
		$this->subscribe_limit = Constant::SUBSCRIBE_LIMIT;

		$this->xp_per_lvl = Constant::XP_PER_LVL;
		$this->hard_range = Constant::HARD_RANGE;
		$this->step_bonus_per_hard = Constant::STEP_BONUS_PER_HARD;
		$this->legend_lvl_bonus_sum = Constant::LEGEND_LVL_BONUS_SUM;
		$this->news_demon_time = Constant::NEWS_DEMON_TIME * 1000;
		$this->verify_demon_time = SecureManager::VERIFY_DEMON_TIME * 1000;
		$this->verify_required_time = SecureManager::VERIFY_REQUIRED * 1000;


		$this->referal_percent = ReferalManager::REFERAL_PERCENT;
		$this->limit_referal_min = ReferalManager::LIMIT_REFERAL_MIN;
		$this->stay_referal_bonus = ReferalManager::STAY_REFERAL_BONUS;


		$this->min_total_likes = BotManager::MIN_TOTAL_LIKES;
		$this->min_posts = BotManager::MIN_POSTS;
		$this->min_followed = BotManager::MIN_FOLLOWED;

		$settingsData = SettingsManager::getSettingsValues();

		if($settingsData->getStatus() == Constant::OK_STATUS){
			$this->settings = $settingsData->getObject();
		}else{
			$this->settings = SettingsManager::getDefaultSettingsValues();
		}

	}

	public function getArr(){
		return array("like_price"=>$this->like_price, "subscribe_price"=>$this->subscribe_price,
		"like_price_bid"=>$this->like_price_bid, "subscribe_price_bid"=>$this->subscribe_price_bid,
		"app_version"=>$this->app_version, "like_limit"=>$this->like_limit, "subscribe_limit"=>$this->subscribe_limit, "xp_per_lvl"=>$this->xp_per_lvl,
		"hard_range"=>$this->hard_range, "step_bonus_per_hard"=>$this->step_bonus_per_hard, "legend_lvl_bonus_sum"=>$this->legend_lvl_bonus_sum,
		"news_demon_time"=>$this->news_demon_time, "settings"=>$this->settings, "referal_percent"=>$this->referal_percent, "limit_referal_min"=>$this->limit_referal_min,
		"stay_referal_bonus"=>$this->stay_referal_bonus, "min_total_likes"=>$this->min_total_likes, "min_posts"=>$this->min_posts, "min_followed"=>$this->min_followed,
		"verify_demon_time"=>$this->verify_demon_time, "verify_required_time" => $this->verify_required_time);
	}
}

?>
