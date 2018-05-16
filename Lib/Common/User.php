<?php
namespace Famous\Lib\Common;
	use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant;

class User{
		
		private $id;
		private $login;
		private $priority;
		private $isPremium;
		private $turbo;
		private $rtime;
		private $realRtime;
		private $isAuth;
		private $xpInfo;
		private $lang;

		private $etime;
		private $email;

		private $needEmail;
		private $packageName;

		private $appVersion;
		private $platform;
		private $god;

		private $referal;
		private $preauth;
		private $device_name;
		private $is_updated;
		private $ip;

		private $vtime;
		private $iid;

		private $balanceType;
		/**
		 * User constructor.
		 */
		public function __construct($arr=null)
		{
			if($arr && is_array($arr)){
				$this->isAuth = true;
				$this->id = $arr['id'];
				$this->login = $arr['login'];
				$this->priority = $arr['priority'];
				$this->isPremium = $arr['premium'];
				$this->turbo = $arr['turbo'];
				$this->device_name = $arr['device_name'];
				$this->is_updated = $arr['is_updated'];
				$this->ip = $arr['ip'];
				$this->iid = $arr['iid'];
				$this->rtime = time() - $arr['rtime'];
				$this->realRtime = $arr['rtime'];
				$this->lang = $arr['lang'];
				$this->etime = $arr['etime'];
				$this->vtime = 0;		//$arr['vtime'];  must verify everytime
				$this->email = $arr['email'];
				$this->xpInfo = new XpInfo($arr['xp']);
				$this->packageName = $arr['package_name'];
				if(time() - $this->etime > Constant::EMAIL_CHECK_TIME){
					$this->needEmail = 1;
				}else{
					$this->needEmail = 0;
				}
				$this->balanceType = BalanceManager::BALANCE_MIN;
			}
			else{
				$this->isAuth = false;
			}
			$this->god = false;
		}

		/**
		 * @return int
		 */
		public function getBalanceType()
		{
			return $this->balanceType;
		}

		/**
		 * @param int $balanceType
		 */
		public function setBalanceType($balanceType)
		{
			$this->balanceType = $balanceType;
		}

		/**
		 * @return mixed
		 */
		public function getLang()
		{
			return $this->lang;
		}

		/**
		 * @param mixed $lang
		 */
		public function setLang($lang)
		{
			$this->lang = $lang;
		}

		/**
		 * @return mixed
		 */
		public function getId()
		{
			return $this->id;
		}

		/**
		 * @param mixed $id
		 */
		public function setId($id)
		{
			$this->id = $id;
		}

		/**
		 * @return mixed
		 */
		public function getLogin()
		{
			return $this->login;
		}

		/**
		 * @param mixed $login
		 */
		public function setLogin($login)
		{
			$this->login = $login;
		}

		/**
		 * @return mixed
		 */
		public function getPriority()
		{
			return $this->priority;
		}

		/**
		 * @param mixed $priority
		 */
		public function setPriority($priority)
		{
			$this->priority = $priority;
		}

		/**
		 * @return mixed
		 */
		public function getReferal()
		{
			return $this->referal;
		}

		/**
		 * @param mixed $referal
		 */
		public function setReferal($referal)
		{
			$this->referal = $referal;
		}


		/**
		 * @return mixed
		 */
		public function isPremium()
		{
			return $this->isPremium;
		}

		/**
		 * @return mixed
		 */
		public function setPremium($isPremium)
		{
			return $this->isPremium = $isPremium;
		}

		/**
		 * @return XpInfo
		 */
		public function getXpInfo()
		{
			return $this->xpInfo;
		}

		/**
		 * @param XpInfo $xpInfo
		 */
		public function setXpInfo($xpInfo)
		{
			$this->xpInfo = $xpInfo;
		}


		/**
		 * @return mixed
		 */
		public function getTurbo()
		{
			return $this->turbo;
		}

		/**
		 * @param mixed $turbo
		 */
		public function setTurbo($turbo)
		{
			$this->turbo = $turbo;
		}

		/**
		 * @return mixed
		 */
		public function getRtime()
		{
			return $this->rtime;
		}

		/**
		 * @param mixed $rtime
		 */
		public function setRtime($rtime)
		{
			$this->rtime = $rtime;
		}

		/**
		 * @return mixed
		 */
		public function isAuth()
		{
			return $this->isAuth;
		}

		/**
		 * @return mixed
		 */
		public function getEtime()
		{
			return $this->etime;
		}

		/**
		 * @param mixed $etime
		 */
		public function setEtime($etime)
		{
			$this->etime = $etime;
		}

		/**
		 * @return mixed
		 */
		public function getEmail()
		{
			return $this->email;
		}

		/**
		 * @param mixed $email
		 */
		public function setEmail($email)
		{
			$this->email = $email;
		}

		/**
		 * @return mixed
		 */
		public function getPackageName()
		{
			return $this->packageName;
		}

		/**
		 * @param mixed $packageName
		 */
		public function setPackageName($packageName)
		{
			$this->packageName = $packageName;
		}

		/**
		 * @return mixed
		 */
		public function getAppVersion()
		{
			return $this->appVersion;
		}

		/**
		 * @param mixed $appVersion
		 */
		public function setAppVersion($appVersion)
		{
			$this->appVersion = $appVersion;
		}

		/**
		 * @return boolean
		 */
		public function isGod()
		{
			return $this->god;
		}


		/**
		 * @param boolean $isGod
		 */
		public function setGod($god)
		{
			$this->god = $god;
		}

		/**
		 * @return mixed
		 */
		public function getPlatform()
		{
			return $this->platform;
		}

		/**
		 * @param mixed $platform
		 */
		public function setPlatform($platform)
		{
			$this->platform = $platform;
		}

		/**
		 * @return boolean
		 */
		public function isIsAuth()
		{
			return $this->isAuth;
		}

		/**
		 * @param boolean $isAuth
		 */
		public function setIsAuth($isAuth)
		{
			$this->isAuth = $isAuth;
		}

		/**
		 * @return mixed
		 */
		public function getPreauth()
		{
			return $this->preauth;
		}

		/**
		 * @param mixed $preauth
		 */
		public function setPreauth($preauth)
		{
			$this->preauth = $preauth;
		}

		/**
		 * @return mixed
		 */
		public function getVtime()
		{
			return $this->vtime;
		}

		/**
		 * @param mixed $vtime
		 */
		public function setVtime($vtime)
		{
			$this->vtime = $vtime;
		}

		public function isVerify(){
			return $this->vtime > (time() - SecureManager::VERIFY_EXPIRE);
		}

		/**
		 * @return mixed
		 */
		public function getDeviceName()
		{
			return $this->device_name;
		}

		/**
		 * @param mixed $device_name
		 */
		public function setDeviceName($device_name)
		{
			$this->device_name = $device_name;
		}

		/**
		 * @return mixed
		 */
		public function getIp()
		{
			return $this->ip;
		}

		/**
		 * @param mixed $ip
		 */
		public function setIp($ip)
		{
			$this->ip = $ip;
		}

		/**
		 * @return mixed
		 */
		public function getIid()
		{
			return $this->iid;
		}

		/**
		 * @param mixed $iid
		 */
		public function setIid($iid)
		{
			$this->iid = $iid;
		}



		public function getArr(){
			return array("user_id"=>$this->id, "realRtime"=>$this->realRtime, "rtime"=>$this->rtime, "turbo"=>$this->turbo, "premium"=>$this->isPremium,
						 "xp_info"=>$this->xpInfo->getArr(), "need_email"=>$this->needEmail, "is_updated"=>$this->is_updated);
		}

	}
?>
