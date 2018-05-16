<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Cash as Cash;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Managers\BalanceManager as BalanceManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
use \Exception as Exception;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class CashManager
{
    //get user cash
    public static function getCash($user = null){
        if(Manager::$user->isAuth() || $user){
            if($user){
                $uid = $user;
            }else{
                $uid = Manager::$user->getId();
            }

            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT deposit FROM `".Constant::USERS_TABLE."` WHERE id = :id");
            $stmt->bindParam(":id", $uid, PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $deposit = $stmt->fetchColumn();
                /*FOR GODS*/
                if(Manager::$user->isGod()){
                    $deposit = 999999;
                }
                /*FOR GODS*/
                $cash = new Cash($deposit);

                $data = new Response("getCash", Constant::OK_STATUS, "", array("cash"=>$cash->getArr()));
            }else{
                $data = new Response("getCash", Constant::ERR_STATUS, "DB error");
            }
        }else{
            $data = new Response("getCash", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public static function order($type, $target_id, $real_id, $meta, $head, $target_count){
        if(Manager::$user->isAuth()){
            $user_id = Manager::$user->getId();
            $priority = Manager::$user->getPriority();
            $db = DB::getInstance();
            $task_table = TableManager::getTaskTable($type);
            $info = TableManager::getInfoTable($type);
            $utime = time();

            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT  id FROM `$info` WHERE user_id = :user_id AND target_id = :target_id");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":target_id", $target_id);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $id = $stmt->fetchColumn();
                $stmt = $dbh->prepare("UPDATE `".$info."` SET target_count = target_count + :target_count, utime = :utime, real_id = :real_id, status = :status WHERE id = :id");
                $stmt->bindParam(":target_count", $target_count, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);
                $status = Constant::ACTIVE_TASK_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $stmt->bindParam(":real_id", $real_id);
                $stmt->execute();

                if($stmt->rowCount() > 0){
                    try{
                        $stmt = $dbh->prepare("INSERT INTO `$task_table`  (task_id, priority) VALUES(:task_id, :priority)");
                        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                        $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
                        $stmt->execute();
                    }catch(Exception $e){
                        $stmt = $dbh->prepare("UPDATE `$task_table` SET priority = :priority WHERE task_id = :task_id");
                        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                        $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                    BalanceManager::depositBalance($type, $target_count);
                    $data = new Response("order", Constant::OK_STATUS);
                }else{
                    $data = new Response("order", Constant::ERR_STATUS, "DB error 2");
                }
            }else{
                $stmt = $dbh->prepare("INSERT INTO `$info` (target_id, real_id, meta, head, utime, target_count, user_id)
									VALUES(:target_id, :real_id, :meta, :head, :utime, :target_count, :user_id)");
                $stmt->bindParam(":target_id", $target_id);
                $stmt->bindParam(":real_id", $real_id);
                $stmt->bindParam(":meta", $meta);
                $stmt->bindParam(":head", $head);
                $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);

                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":target_count", $target_count, PDO::PARAM_INT);
                $stmt->execute();

                if($stmt->rowCount() > 0){
                    $task_id = $dbh->lastInsertId();
                    $stmt = $dbh->prepare("INSERT INTO `$task_table`  (task_id, priority) VALUES(:task_id, :priority)");
                    $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                    $stmt->bindParam(":task_id", $task_id, PDO::PARAM_INT);
                    $stmt->execute();
                    if($stmt->rowCount() > 0){
                        BalanceManager::depositBalance($type, $target_count);
                        $data = new Response("order", Constant::OK_STATUS);
                    }else{
                        $data = new Response("order", Constant::ERR_STATUS, "DB error 1");
                    }
                }else{
                    $data = new Response("order", Constant::ERR_STATUS, "DB error 2");
                }
            }
        }else{
            $data = new Response("order", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public static function pay(&$dbh, $user_id, $id, $type, $price, $needPay){
        $info = TableManager::getInfoTable($type);
        $table = TableManager::getTaskTable($type);

        if($needPay){
            $depositData = self::deposit($price, $user_id);
            $res = $depositData->getStatus() == Constant::OK_STATUS;
        }
        else{
            $res = true;
        }

        if($res){
            $utime = time();
            if($needPay){
                $stmt = $dbh->prepare("UPDATE $info SET ready_count = ready_count + 1, reports = 0, utime = :utime, status = :status  WHERE id = :id");
                $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $status = Constant::ACTIVE_TASK_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->rowCount();


            }
            else{
                $res = true;
            }

            HelpManager::checkTask($dbh, $id, $type);
            if($res){
                BalanceManager::depositReadyBalance($type, 1, $user_id);
                BalanceManager::depositSum($type, $price, $user_id);

                $redis = Redis::getInstance()->getClient();
                $key = Constant::PREFIX . $user_id.":".$id;
                $res = $redis->set($key, 1);
                $redis->expire($key , TableManager::getExpireTime($type));

                if(strtolower($res) === Constant::OK_STATUS){
                    $data = new Response("pay", Constant::OK_STATUS);
                }else{
                    $data = new Response("pay", Constant::ERR_STATUS, "DB error add to ready");
                }
            }else{
                $data = new Response("pay", Constant::ERR_STATUS, "DB error pay");
            }
        }else{
            $data = new Response("pay", Constant::ERR_STATUS, "DB error earn");
        }

        return $data;
    }


    public static function getTaskPrice($type){
        /*$op = rand(0,100);
        $likePrice = Constant::LIKE_PRICE_MIN;
        $subscribePrice = Constant::SUBSCRIBE_PRICE_MIN;

        if($op > Constant::PRICE_BORDER){
            $likePrice = Constant::LIKE_PRICE;
            $subscribePrice = Constant::SUBSCRIBE_PRICE;
        }*/

        $like_price = Constant::LIKE_PRICE;
        $subscribe_price = Constant::SUBSCRIBE_PRICE;
        if(Manager::$user->getBalanceType() == BalanceManager::BALANCE_MIN){
            $like_price = Constant::LIKE_PRICE_MIN;
            $subscribe_price = Constant::SUBSCRIBE_PRICE_MIN;
        }

        $priceList = array(Constant::LIKE_TYPE =>$like_price , Constant::SUBSCRIBE_TYPE => $subscribe_price);
        return $priceList[$type];
    }

    public static function getTaskPriceBid($type){
        $priceList = array(Constant::LIKE_TYPE => Constant::LIKE_PRICE_BID, Constant::SUBSCRIBE_TYPE => Constant::SUBSCRIBE_PRICE_BID);
        return $priceList[$type];
    }

    public static function getDiamonds($id){
        $products = array( "ipack_1" => 1000, "ipack_2" => 2000, "pack_3" => 5000, "pack_4" => 10000, "pack_5" => 20000, "pack_1_vip"=>100000, "pack_2_vip"=>500000, "pack_3_vip"=>1000000,
            "pack_rf_1" => 1000, "pack_rf_2" => 2000, "pack_rf_3" => 5000, "pack_rf_4" => 10000, "pack_rf_5" => 20000, "pack_rf_1_vip"=>100000, "pack_rf_2_vip"=>500000, "pack_rf_3_vip"=>1000000,
            "sb_pack_rf_1" => 1000, "sb_pack_rf_2" => 2000, "sb_pack_rf_3" => 5000, "sb_pack_rf_4" => 10000, "sb_pack_rf_5" => 20000, "sb_pack_rf_1_vip"=>100000, "sb_pack_rf_2_vip"=>500000, "sb_pack_rf_3_vip"=>1000000,
            "gsm_rf_pack_1" => 1000, "gsm_rf_pack_2" => 2000, "gsm_rf_pack_3" => 5000, "gsm_rf_pack_4" => 10000, "gsm_rf_pack_5" => 20000, "gsm_rf_pack_1_vip"=>100000, "gsm_rf_pack_2_vip"=>500000, "gsm_rf_pack_3_vip"=>1000000,
            "rfp_naut_pack_1" => 1000, "rfp_naut_pack_2" => 2000, "rfp_naut_pack_3" => 5000, "rfp_naut_pack_4" => 10000, "rfp_naut_pack_5" => 20000, "rfp_naut_pack_1_vip"=>100000, "rfp_naut_pack_2_vip"=>500000, "rfp_naut_pack_3_vip"=>1000000,
            "fugs_rlt_pack_1" => 1000, "fugs_rlt_pack_2" => 2000, "fugs_rlt_pack_3" => 5000, "fugs_rlt_pack_4" => 10000, "fugs_rlt_pack_5" => 20000, "fugs_rlt_pack_1_vip"=>100000, "fugs_rlt_pack_2_vip"=>500000, "fugs_rlt_pack_3_vip"=>1000000,
            "thnd_rft_pack_1" => 1000, "thnd_rft_pack_2" => 2000, "thnd_rft_pack_3" => 5000, "thnd_rft_pack_4" => 10000, "thnd_rft_pack_5" => 20000, "thnd_rft_pack_1_vip"=>100000, "thnd_rft_pack_2_vip"=>500000, "thnd_rft_pack_3_vip"=>1000000,
            "pack_f_1" => DonateManager::getWithPercents(100 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_2" => DonateManager::getWithPercents(200 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_3" => DonateManager::getWithPercents(500 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_4" => DonateManager::getWithPercents(1000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_5" => DonateManager::getWithPercents(2000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_6" => DonateManager::getWithPercents(3000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_7" => DonateManager::getWithPercents(4000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_8" => DonateManager::getWithPercents(5000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_9" => DonateManager::getWithPercents(10000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_f_10" => DonateManager::getWithPercents(20000 * Constant::SUBSCRIBE_PRICE_BID),
            "pack_l_1" => DonateManager::getWithPercents(100 * Constant::LIKE_PRICE_BID),
            "pack_l_2" => DonateManager::getWithPercents(200 * Constant::LIKE_PRICE_BID),
            "pack_l_3" => DonateManager::getWithPercents(500 * Constant::LIKE_PRICE_BID),
            "pack_l_4" => DonateManager::getWithPercents(1000 * Constant::LIKE_PRICE_BID),
            "pack_l_5" => DonateManager::getWithPercents(2000 * Constant::LIKE_PRICE_BID),
            "pack_l_6" => DonateManager::getWithPercents(3000 * Constant::LIKE_PRICE_BID),
            "pack_l_7" => DonateManager::getWithPercents(4000 * Constant::LIKE_PRICE_BID),
            "pack_l_8" => DonateManager::getWithPercents(5000 * Constant::LIKE_PRICE_BID),
            "pack_l_9" => DonateManager::getWithPercents(10000 * Constant::LIKE_PRICE_BID),
            "pack_l_10" => DonateManager::getWithPercents(20000 * Constant::LIKE_PRICE_BID),
            "pack_free_likes"=>DonateManager::getWithPercents(DonateManager::FREE_LIKES_PACK * Constant::LIKE_PRICE_BID),
            "pack_free_followers"=>DonateManager::getWithPercents(DonateManager::FREE_SUBSCRIBERS_PACK * Constant::SUBSCRIBE_PRICE_BID));

        if(key_exists($id, $products)){
            return $products[$id];
        }

        return null;
    }

    /*
     * get sum from user's deposit
     * */
    public static function withdraw($sum, $to = null){
        return self::operation($sum, $to, "-");
    }

    /*
     * add sum to user's deposit
     * */
    public static function deposit($sum, $to = null){
        return self::operation($sum, $to, "+");
    }

    private static function operation($sum, $to = null, $sign){
        if(Manager::$user->isAuth() || $to ){

            if($to){
                $uid = $to;
            }
            else{
                $uid = Manager::$user->getId();
                /*FOR GODS*/
                if(Manager::$user->isGod()){
                    return new Response("operation", Constant::OK_STATUS);
                }
                /*FOR GODS*/
            }

            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET deposit = deposit ".$sign." :sum WHERE id = :id");
            $stmt->bindParam(":sum", $sum, PDO::PARAM_INT);
            $stmt->bindParam(":id", $uid, PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $data = new Response("operation", Constant::OK_STATUS);
            }else{
                $data = new Response("operation", Constant::ERR_STATUS, "DB error");
            }
        }else{
            $data = new Response("operation", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public static function getBonusPay($num){
        $bonuses = array(0, Constant::TURBO_GREEN_BONUS, Constant::TURBO_BLUE_BONUS, Constant::TURBO_RED_BONUS, Constant::TURBO_FREE_BONUS, Constant::TURBO_DARK_BONUS);
        return $bonuses[$num];
    }
}