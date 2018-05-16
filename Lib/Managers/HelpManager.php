<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB as DB;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Validator;
use \PDO as PDO;
use \Exception as Exception;

use ReceiptValidator\GooglePlay\Validator as PlayValidator;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/25/2016
 * Time: 14:51
 */
class HelpManager
{
    public static function createUser($login, $lang, $iid){
        $data = new Response("createUser", Constant::ERR_STATUS, "No depth error");
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("INSERT INTO `".Constant::USERS_TABLE."` (deposit) VALUES (".Constant::START_BONUS.")");
        if($stmt->execute()){
            $user_id = $dbh->lastInsertId();
            $rtime = time();
            $stmt = $dbh->prepare("INSERT INTO `".Constant::DATA_TABLE."` (login, lang, rtime, user_id, ip, ctime, iid) VALUES(:login, :lang, :rtime, :user_id, :ip, :ctime, :iid)");
            $ip = Helper::getIp();
            $stmt->bindParam(":ip", $ip);
            $stmt->bindParam(":iid", $iid);
            $stmt->bindParam(":login", $login);
            $stmt->bindParam(":lang", $lang);
            $stmt->bindParam(":rtime", $rtime, PDO::PARAM_INT);
            $stmt->bindParam(":ctime", $rtime, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            if($stmt->execute()){
                NewsManager::addNewsAuth($db, $user_id);
                SettingsManager::initSettings($user_id);
                $data = new Response("createUser", Constant::OK_STATUS, "");
            }else{
                $data = new Response("createUser", Constant::ERR_STATUS, "User creating error 2");
            }
        }else{
            $data = new Response("createUser", Constant::ERR_STATUS, "User creating error 1");
        }

        return $data;
    }

    public static function topCompetition(&$dbh, $user_id)
    {
        $tdate = date("Y-m-d");
        $stmt = $dbh->prepare("SELECT id, count FROM `" . Constant::TOPS_TABLE . "` WHERE user_id = :user_id AND tdate = :tdate");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":tdate", $tdate);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $id = $arr['id'];
            $count = $arr['count'] + 1;
            $stmt = $dbh->prepare("UPDATE `" . Constant::TOPS_TABLE . "` SET count = :count WHERE id = :id");
            $stmt->bindParam(":count", $count, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $dbh->prepare("INSERT INTO `" . Constant::TOPS_TABLE . "` (user_id, tdate, count) VALUES(:user_id, :tdate, 1)");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":tdate", $tdate);
            $stmt->execute();
        }
    }

    public static function getTimes($type){
        $times = array(Constant::LIKE_TYPE => Constant::REMOVE_LIKE_DELAY, Constant::SUBSCRIBE_TYPE => Constant::REMOVE_SUBSCRIBE_DELAY);
        return $times[$type];
    }

    public static function checkTask(&$dbh, $id, $type){
        $info = TableManager::getInfoTable($type);
        $stmt = $dbh->prepare("SELECT user_id FROM $info
								WHERE id = :id AND ready_count > 0 AND target_count > 0
								AND ready_count = target_count");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt = $dbh->prepare("UPDATE $info SET ready_count = 0, target_count = 0, status = :status WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $status = Constant::READY_TASK_STATUS;
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->execute();

            self::taskDeleteHard($type, $id, Constant::READY_DELETE_REASON);

        }
    }

    public static function updateOnline(&$dbh=null, $user_id=null){
        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }
        $time = time();
        $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET last_visit = :time WHERE user_id = :user_id");
        $stmt->bindParam(":time", $time, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    public static function setPremium(&$dbh, $user_id, $premium){
        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET premium = :premium WHERE id = :user_id");
        $stmt->bindParam(":premium", $premium, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getEarnDelay(&$dbh = null, $user_id = null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }
        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT ftime FROM `".Constant::USERS_TABLE."` WHERE id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            //$delay = $stmt->fetchColumn() - time();
            $delay = -9;
            $data = new Response("getEarnDelay", Constant::OK_STATUS, "", array("delay"=>$delay));
        }else{
            $data = new Response("getEarnDelay", Constant::ERR_STATUS, "Get error");
        }

        return $data;
    }

    //only for free now
    public static function setTurbo($dbh, $user_id, $productId){
        $turbo = self::getTurbo($productId);
        $priority = self::getPriority($productId);
        $purchaseTime = time();
        $purchaseToken = "free for " . $user_id;
        $type = "free subscription";
        $status = Constant::OK_FREE_SUBSCRIBE_STATUS;
        $startTime = time();
        $expiryTime = time() + 86400 * 7;
        try{
            $stmt = $dbh->prepare("INSERT INTO `".Constant::SUBSCRIBES_TABLE."` (product_id, purchase_time, purchase_token, type, user_id, status, start_time, expiry_time, package_name)
						 VALUES (:productId, :purchaseTime, :purchaseToken, :type, :user_id, :status, :startTime, :expiryTime, :package_name)");
            $stmt->bindParam(":productId", $productId);
            $stmt->bindParam(":purchaseTime", $purchaseTime, PDO::PARAM_INT);
            $stmt->bindParam(":startTime", $startTime, PDO::PARAM_INT);
            $stmt->bindParam(":expiryTime", $expiryTime, PDO::PARAM_INT);
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":type", $type, PDO::PARAM_INT);
            $stmt->bindParam(":purchaseToken", $purchaseToken);
            $package_name = Manager::$user->getPackageName();
            $stmt->bindParam(":package_name", $package_name);

            $stmt->execute();
        }catch(Exception $e){

        }


        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET turbo = :turbo, priority = :priority WHERE id = :user_id");				//turbo_free
        $stmt->bindParam(":turbo", $turbo, PDO::PARAM_INT);
        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

    }

    public static function getPriority($id){
        $subscribes = array("turbos_1" => 1, "iturbos_2" => 2, "turbos_3" => 3, "turbos_5" => 10, "turbo_free" => 0, "turbo_balance"=>1,
                            "turbos_rf_1" => 1, "turbos_rf_2" => 2, "turbos_rf_3" => 3, "turbos_rf_4" => 10,
                            "sb_turbos_rf_1" => 1, "sb_turbos_rf_2" => 2, "sb_turbos_rf_3" => 3, "sb_turbos_rf_5" => 10,
                            "gsm_rf_turbos_1" => 1, "gsm_rf_turbos_2" => 2, "gsm_rf_turbos_3" => 3, "gsm_rf_turbos_5" => 10,
                            "rfp_naut_turbos_1" => 1, "rfp_naut_turbos_2.1" => 2, "rfp_naut_turbos_3" => 3, "rfp_naut_turbos_5" => 10,
                            "fugs_rlt_turbos_1" => 1, "fugs_rlt_turbos_2" => 2, "fugs_rlt_turbos_3" => 3, "fugs_rlt_turbos_5" => 10,
                            "thnd_rft_turbos_1" => 1, "thnd_rft_turbos_2" => 2, "thnd_rft_turbos_3" => 3, "thnd_rft_turbos_5" => 10);
        return $subscribes[$id];
    }

    public static function getProductType($id){
       if(self::getTurbo($id)){
           return Constant::SUBSCRIPTION_TYPE;
       }else{
           return Constant::CONSUMABLE_TYPE;
       }
    }

    public static function getTurbo($id){
        $subscribes = array("turbos_1" => 1, "iturbos_2" => 2, "turbos_3" => 3, "turbo_free" => 4, "turbos_5" => 5, "turbo_null"=>0,
                            "turbos_rf_1" => 1, "turbos_rf_2" => 2, "turbos_rf_3" => 3, "turbos_rf_4" => 5,
                            "sb_turbos_rf_1" => 1, "sb_turbos_rf_2" => 2, "sb_turbos_rf_3" => 3, "sb_turbos_rf_5" => 5,
                            "gsm_rf_turbos_1" => 1, "gsm_rf_turbos_2" => 2, "gsm_rf_turbos_3" => 3, "gsm_rf_turbos_5" => 5,
                            "rfp_naut_turbos_1" => 1, "rfp_naut_turbos_2.1" => 2, "rfp_naut_turbos_3" => 3, "rfp_naut_turbos_5" => 5,
                            "fugs_rlt_turbos_1" => 1, "fugs_rlt_turbos_2" => 2, "fugs_rlt_turbos_3" => 3, "fugs_rlt_turbos_5" => 5,
                            "thnd_rft_turbos_1" => 1, "thnd_rft_turbos_2" => 2, "thnd_rft_turbos_3" => 3, "thnd_rft_turbos_5" => 5);

        return $subscribes[$id];
    }

    public static function getSessionId(){
        $session_id = session_id();
        return array("session_id"=>$session_id);
    }

    public static function getHeaderPackage(){
        $headers = Helper::getallheaders();
        return array("header_package"=>$headers["X-Requested-With"]?:"");
    }

    public static function updateTablesPriority($user_id = null, $priority = null, &$dbh = null){
        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }
        if(!$user_id && !$priority){
            $user_id = Manager::$user->getId();
            $priority = Manager::$user->getPriority();
        }
        self::updateTableByType(Constant::LIKE_TYPE, $dbh, $user_id, $priority);
        self::updateTableByType(Constant::SUBSCRIBE_TYPE, $dbh, $user_id, $priority);
    }

    private static function updateTableByType($type, &$dbh, $user_id, $priority){
        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $stmt = $dbh->prepare("UPDATE `".$table."` t, `".$info."` i SET t.priority = :priority WHERE i.id = t.task_id AND i.user_id = :user_id");
        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function doVerdict($id, $type, $verdict, $suspicion){

        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $data = new Response("deleteTask", Constant::ERR_STATUS, "No depth value");
        $verdicts = explode(",", $verdict);
        $suspicions = explode(",", $suspicion);
        //order is important PRIVATE - SWITCH - EXIST
        if(count($suspicions) == count($verdicts)){
             for($i = 0; $i < count($suspicions); $i++){
                $data = self::suspision($dbh, $suspicions[$i], $verdicts[$i], $info, $table, $id, $type);
                if($data->getStatus() != Constant::OK_STATUS){
                    break;
                }
            }
        }else{
            $data = new Response("doVerdict", Constant::ERR_STATUS, "Count params error");
        }

        return $data;
    }

    private static function suspision(&$dbh, $suspicion, $verdict, $info, $table, $id, $type){
        $data = new Response("doVerdict", Constant::ERR_STATUS, "No depth value");
        switch($suspicion){
            case Constant::CHECK_FROZEN:
                $stmt = $dbh->prepare("SELECT u.priority, u.id, i.target_count, i.ready_count FROM `".$info."` i, `".Constant::USERS_TABLE."` u WHERE i.id = :id AND i.user_id = u.id AND i.status = :status");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $status = Constant::FROZEN_TASK_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->rowCount() > 0) {
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    $priority = $arr['priority'];
                    $user_id = $arr['id'];
                    $target_count = $arr['target_count'];
                    $ready_count = $arr['ready_count'];
                     //not frozen
                    if($verdict == Constant::OK_STATUS){
                        if($target_count > $ready_count){
                            $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status, reports = 0 WHERE id = :id");
                            $status = Constant::ACTIVE_TASK_STATUS;
                        }else{
                            $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status, reports = 0, target_count = 0, ready_count = 0 WHERE id = :id");
                            $status = Constant::READY_TASK_STATUS;
                        }
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                        $stmt->execute();

                        if($target_count > $ready_count){
                            try{
                                $stmt = $dbh->prepare("INSERT INTO `$table`  (task_id, priority) VALUES(:task_id, :priority)");
                                $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                                $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
                                $stmt->execute();
                            }catch(Exception $e){

                            }
                        }else{
                            self::checkTask($dbh, $id, $type);
                        }

                        $data = new Response("doVerdict", Constant::OK_STATUS);
                    }else{
                        $data = self::taskDelete($user_id, $type, $id, Constant::PHANTOM_DELETE_REASON);
                        NewsManager::refreshOrAddNews(10, $user_id, $dbh);
                    }
                }else{
                    $data = new Response("doVerdict", Constant::ERR_STATUS, "Not valid data any more");
                }
                break;
            case Constant::CHECK_SWITCH_TYPE:
                if($verdict == Constant::OK_STATUS){
                    $target_id = Validator::clear($_REQUEST['target_id']);
                    $src = Validator::clear($_REQUEST['src']);

                    $stmt = $dbh->prepare("SELECT u.priority, u.id, i.target_count, i.ready_count FROM `".$info."` i, `".Constant::USERS_TABLE."` u WHERE i.id = :id AND i.user_id = u.id AND i.status = :status");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $status = Constant::FROZEN_TASK_STATUS;
                    $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                    $stmt->execute();
                    if($stmt->rowCount() > 0) {
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $arr = $stmt->fetch();
                        $priority = $arr['priority'];
                        $user_id = $arr['id'];
                        $target_count = $arr['target_count'];
                        $ready_count = $arr['ready_count'];
                        //not frozen
                        if($verdict == Constant::OK_STATUS){
                            if($target_count > $ready_count){
                                $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status, reports = 0, target_id = :target_id, head = :head WHERE id = :id");
                                $status = Constant::ACTIVE_TASK_STATUS;
                            }else{
                                $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status, reports = 0, target_count = 0, ready_count = 0, target_id = :target_id, head = :head WHERE id = :id");
                                $status = Constant::READY_TASK_STATUS;
                            }
                            $src = strtok($src, '?');
                            $stmt->bindParam(":target_id", $target_id);
                            $stmt->bindParam(":head", $src);
                            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                            $stmt->execute();

                            if($target_count > $ready_count){
                                try{
                                    $stmt = $dbh->prepare("INSERT INTO `$table`  (task_id, priority) VALUES(:task_id, :priority)");
                                    $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                                    $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
                                    $stmt->execute();
                                }catch(Exception $e){

                                }
                            }else{
                                self::checkTask($dbh, $id, $type);
                            }

                            $data = new Response("doVerdict", Constant::OK_STATUS);
                        }else{
                            $data = self::taskDelete($user_id, $type, $id, Constant::PHANTOM_DELETE_REASON);
                            NewsManager::refreshOrAddNews(10, $user_id, $dbh);
                        }
                    }else{
                        $data = new Response("doVerdict", Constant::ERR_STATUS, "Not valid data any more");
                    }

                }
                $data = new Response("doVerdict", Constant::OK_STATUS, "");
                break;
            case Constant::CHECK_PRIVATE:
                if($verdict == Constant::OK_STATUS){
                    $data = new Response("doVerdict", Constant::OK_STATUS, "");
                }else{
                    //send news to user
                    $stmt = $dbh->prepare("SELECT u.priority, u.id, i.target_count, i.ready_count FROM `".$info."` i, `".Constant::USERS_TABLE."` u WHERE i.id = :id AND i.user_id = u.id AND i.status = :status");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $status = Constant::FROZEN_TASK_STATUS;
                    $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                    $stmt->execute();
                    if($stmt->rowCount() > 0) {
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $arr = $stmt->fetch();
                        $user_id = $arr['id'];
                        NewsManager::refreshOrAddNews(9, $user_id, $dbh);

                        $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status WHERE id = :id ");
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $status = Constant::HANG_TASK_STATUS;
                        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                        $stmt->execute();
                        $data = new Response("doVerdict", Constant::OK_STATUS, "Send news about privacy");
                        //$data = self::taskDelete($user_id, $type, $id);
                    }else{
                        $data = new Response("doVerdict", Constant::ERR_STATUS, "Not valid data any more");
                    }
                }

                break;
        }

        return $data;
    }

    public static function taskDelete($user_id, $type, $id, $reason = 0){
        $task_table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $price = CashManager::getTaskPriceBid($type);
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT target_count, ready_count FROM `$info` WHERE user_id = :user_id AND id = :id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $leftCount = ($arr['target_count'] - $arr['ready_count']);
            if($leftCount > 0){
                $orderLeft = $leftCount * $price;

                if(Manager::$user->isPremium()){
                    $return = 1 * $orderLeft;
                }
                else{
                    $return = Constant::CREDIT_PERCENT * $orderLeft;
                }

                $withdrawData = CashManager::deposit($return, $user_id);
                if($withdrawData->getStatus() == Constant::OK_STATUS){
                    $utime = time();

                    $stmt = $dbh->prepare("DELETE FROM `".$task_table."` WHERE task_id = :id");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $stmt->execute();

                    $stmt = $dbh->prepare("UPDATE `$info` SET utime = :utime, target_count = 0, ready_count = 0, status = :status, reports = 0 WHERE id = :id");

                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);
                    $status = Constant::READY_TASK_STATUS;
                    $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                    $stmt->execute();

                    BalanceManager::withdrawBalance($type, $leftCount, $user_id);


                    $cashData = CashManager::getCash($user_id);
                    if($cashData->getStatus() == Constant::OK_STATUS){
                        $data = new Response("deleteTask", Constant::OK_STATUS, "", $cashData->getObject());
                    }else{
                        $data = new Response("deleteTask", Constant::ERR_STATUS, "Get cash error");
                    }
                }else{
                    $data = new Response("deleteTask", Constant::ERR_STATUS, "Withdraw error");
                }
            }else{

                $utime = time();

                $stmt = $dbh->prepare("DELETE FROM `".$task_table."` WHERE task_id = :id");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $dbh->prepare("UPDATE `$info` SET utime = :utime, target_count = 0, ready_count = 0, status = :status, reports = 0 WHERE id = :id");

                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);
                $status = Constant::READY_TASK_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $stmt->execute();

                $cashData = CashManager::getCash($user_id);
                if($cashData->getStatus() == Constant::OK_STATUS){
                    $data = new Response("deleteTask", Constant::OK_STATUS, "Empty task", $cashData->getObject());
                }else{
                    $data = new Response("deleteTask", Constant::ERR_STATUS, "Get cash error");
                }
            }

        }else{
            $data = new Response("deleteTask", Constant::ERR_STATUS, "Task does not exist");
        }

        return $data;
    }

    public static function taskDeleteHard($type, $id, $reason = 0){

        $task_table = TableManager::getTaskTable($type);

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("DELETE FROM `".$task_table."` WHERE task_id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

    }

    public static function setAchieve($user_id, $achieve_id){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $htime = time();
        $stmt = $dbh->prepare("INSERT INTO `".Constant::HONORS_TABLE."` (achieve_id, user_id, htime) VALUES(:achieve_id, :user_id, :htime)");
        $stmt->bindParam(":achieve_id", $achieve_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":htime", $htime, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getVersion(){
        if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_METEOR){
            $version = Constant::APP_VERSION_METEOR;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_METEOR_BOOST){
            $version = Constant::APP_VERSION_METEOR_BOOST;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL){
            $version = Constant::APP_VERSION_REAL;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_ROYAL){
            $version = Constant::APP_VERSION_ROYAL;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL_VIP){
            $version = Constant::APP_VERSION_REAL_VIP;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_METEOR_GP){
            $version = Constant::APP_VERSION_REAL_VIP;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL_FLWRS){
            $version = Constant::APP_VERSION_REAL_FLWRS;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_ROYAL_LKS){
            $version = Constant::APP_VERSION_ROYAL_LKS;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL_LKS){
            $version = Constant::APP_VERSION_REAL_LKS;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_ROYAL_FLWRS){
            $version = Constant::APP_VERSION_ROYAL_FLWRS;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM){
            $version = Constant::APP_VERSION_REAL_FOLLOWERS_PREMIUM;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM){
            $version = Constant::APP_VERSION_ROYAL_LIKES_PREMIUM;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP){
            $version = Constant::APP_VERSION_ROYAL_FOLLOWERS_TOP;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_REAL_LIKES_TOP || Manager::$user->getPackageName() == Constant::PACKAGE_NAME_FREE_FLWRS){
            $version = Constant::APP_VERSION_REAL_LIKES_TOP;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_FLWRS_BOOST){
            $version = Constant::APP_VERSION_FLWRS_BOOST;
        }else if(Manager::$user->getPackageName() == Constant::PACKAGE_NAME_PHANTOM){
            $version = Constant::APP_VERSION_PHANTOM;
        }






        return $version;
    }

    public static function getPackages(){
        $packages = array(Constant::PACKAGE_NAME_METEOR, Constant::PACKAGE_NAME_REAL, Constant::PACKAGE_NAME_ROYAL, Constant::PACKAGE_NAME_REAL_VIP,
                          Constant::PACKAGE_NAME_METEOR_GP, Constant::PACKAGE_NAME_REAL_FLWRS, Constant::PACKAGE_NAME_ROYAL_LKS, Constant::PACKAGE_NAME_REAL_LKS, Constant::PACKAGE_NAME_ROYAL_FLWRS,
                            Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM, Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM, Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP,
                        Constant::PACKAGE_NAME_REAL_LIKES_TOP, Constant::PACKAGE_NAME_FLWRS_BOOST, Constant::PACKAGE_NAME_PHANTOM, Constant::PACKAGE_NAME_FREE_FLWRS,
                        Constant::PACKAGE_NAME_METEOR_BOOST);

        return $packages;
    }

    public static function getPlatforms(){
        $platforms = array(Constant::PLATFORM_ANDROID, Constant::PLATFORM_IOS, Constant::PLATFORM_WINDOWS, Constant::PLATFORM_MAC);
        return $platforms;
    }

    public static function getPlatformsExclude($platform){
        $platforms = self::getPlatforms();
        $p = array();

        for($i=0; $i < count($platforms); $i++){
            if($platform != $platforms[$i]){
                $p[] = $platforms[$i];
            }
        }

        return $p;
    }

    public static function getPlatformVerbal($platform){
        $paltforms = array(Constant::PLATFORM_ANDROID => Constant::PLATFORM_VERBAL_ANDROID, Constant::PLATFORM_IOS => Constant::PLATFORM_VERBAL_IOS,
            Constant::PLATFORM_WINDOWS => Constant::PLATFORM_VERBAL_WINDOWS, Constant::PLATFORM_MAC => Constant::PLATFORM_VERBAL_MAC);
        return $paltforms[$platform];
    }

    public static function getPlatform($verbal){
        $paltforms = array(Constant::PLATFORM_VERBAL_ANDROID => Constant::PLATFORM_ANDROID, Constant::PLATFORM_VERBAL_IOS =>  Constant::PLATFORM_IOS,
            Constant::PLATFORM_VERBAL_WINDOWS => Constant::PLATFORM_WINDOWS, Constant::PLATFORM_VERBAL_MAC => Constant::PLATFORM_MAC);
        return $paltforms[$verbal];
    }

    public static function getValidatorByPackage($packageName){
        $packageName = trim($packageName);

        if($packageName == Constant::PACKAGE_NAME_METEOR)
        {
           $client_id = "";
           $client_secret = "";
           $refresh_token = "";
        }else if($packageName == Constant::PACKAGE_NAME_METEOR_BOOST){
            $client_id = Constant::METEOR_BOOST_CLIENT_ID;
            $client_secret = Constant::METEOR_BOOST_CLIENT_SECRET;
            $refresh_token = Constant::METEOR_BOOST_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_REAL || $packageName == Constant::PACKAGE_NAME_OLD_REAL){
            $client_id = Constant::RF_CLIENT_ID;
            $client_secret = Constant::RF_CLIENT_SECRET;
            $refresh_token = Constant::RF_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_ROYAL || $packageName == Constant::PACKAGE_NAME_OLD_ROYAL || $packageName == Constant::PACKAGE_NAME_REAL_VIP){
            $client_id = Constant::RYF_CLIENT_ID;
            $client_secret = Constant::RYF_CLIENT_SECRET;
            $refresh_token = Constant::RYF_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_DONATE_1 || $packageName == Constant::PACKAGE_NAME_METEOR_GP || $packageName == Constant::PACKAGE_NAME_PHANTOM){
            $client_id = Constant::D1_CLIENT_ID;
            $client_secret = Constant::D1_CLIENT_SECRET;
            $refresh_token = Constant::D1_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_REAL_FLWRS || $packageName == Constant::PACKAGE_NAME_ROYAL_LKS
                || $packageName == Constant::PACKAGE_NAME_REAL_LKS || $packageName == Constant::PACKAGE_NAME_ROYAL_FLWRS){
            $client_id = Constant::REAL_FLWRS_CLIENT_ID;
            $client_secret = Constant::REAL_FLWRS_CLIENT_SECRET;
            $refresh_token = Constant::REAL_FLWRS_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM || $packageName == Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM || $packageName == Constant::PACKAGE_NAME_FLWRS_BOOST){
            $client_id = Constant::REAL_FOLLOWERS_PREMIUM_CLIENT_ID;
            $client_secret = Constant::REAL_FOLLOWERS_PREMIUM_CLIENT_SECRET;
            $refresh_token = Constant::REAL_FOLLOWERS_PREMIUM_REFRESH_TOKEN;
        }else if($packageName == Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP || $packageName == Constant::PACKAGE_NAME_REAL_LIKES_TOP || $packageName == Constant::PACKAGE_NAME_FREE_FLWRS){
            $client_id = Constant::ROYAL_FOLLOWERS_TOP_CLIENT_ID;
            $client_secret = Constant::ROYAL_FOLLOWERS_TOP_CLIENT_SECRET;
            $refresh_token = Constant::ROYAL_FOLLOWERS_TOP_REFRESH_TOKEN;
        }



        $validator = new PlayValidator([
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token
        ]);

        return $validator;
    }



    public static function getGCMKey($packageName){
        $packageName = trim($packageName);

        if($packageName == Constant::PACKAGE_NAME_METEOR)
        {
            $api_key = Constant::GCM_API_KEY_METEOR;
        }
        else if($packageName == Constant::PACKAGE_NAME_METEOR_BOOST)
        {
            $api_key = Constant::GCM_API_KEY_METEOR_BOOST;
        }else if($packageName == Constant::PACKAGE_NAME_REAL)
        {
            $api_key = Constant::GCM_API_KEY_REAL_FOLLOWERS;
        }else if($packageName == Constant::PACKAGE_NAME_ROYAL || $packageName == Constant::PACKAGE_NAME_REAL_VIP)
        {
            $api_key = Constant::GCM_API_KEY_ROYAL_FOLLOWERS;
        }else if($packageName == Constant::PACKAGE_NAME_METEOR_GP || $packageName == Constant::PACKAGE_NAME_PHANTOM){
            $api_key = Constant::GCM_API_KEY_ROYAL_FOLLOWERS;
        }else if($packageName == Constant::PACKAGE_NAME_REAL_FLWRS || $packageName == Constant::PACKAGE_NAME_ROYAL_LKS
                || $packageName == Constant::PACKAGE_NAME_REAL_LKS || Constant::PACKAGE_NAME_ROYAL_FLWRS){
            $api_key = Constant::GCM_API_KEY_ROYAL_FOLLOWERS;
        }else if($packageName == Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM || $packageName == Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM || $packageName == Constant::PACKAGE_NAME_FLWRS_BOOST){
            $api_key = Constant::GCM_API_KEY_ROYAL_FOLLOWERS;
        }else if($packageName == Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP || $packageName == Constant::PACKAGE_NAME_REAL_LIKES_TOP || $packageName == Constant::PACKAGE_NAME_FREE_FLWRS){
            $api_key = Constant::GCM_API_KEY_ROYAL_FOLLOWERS;
        }



        return $api_key;
    }

    public static function getNewsCount(&$dbh = null, $user_id = null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }
        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        //SecureManager::updateVerifyTemp();

        $time = time();
        $stmt = $dbh->prepare("SELECT COUNT(news_id) as cnt FROM `".Constant::FEEDS_TABLE."`
									WHERE user_id = :user_id
									AND fire_time <= :time
									AND is_complete = 0
									AND is_watched = 0");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":time", $time, PDO::PARAM_INT);

        $stmt->execute();
        if($stmt->rowCount() > 0){
            $cnt = $stmt->fetchColumn();
            $data = new Response("getNewsCount", Constant::OK_STATUS, "", array("count"=>$cnt));
        }else{
            $data = new Response("getNewsCount", Constant::ERR_STATUS, "Base error");
        }

        return $data;
    }
}