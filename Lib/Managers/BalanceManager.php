<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-09-21
 * Time: 13:42
 */

namespace Famous\Lib\Managers;
use Famous\Lib\Common\Response;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
use \DateTime as DateTime;

class BalanceManager
{
    const BALANCE_ACTIVATE_LIMIT = 200;
    const BALANCE_ACTIVATE_READY_LIMIT = 400;
    const SHIFT_LIMIT = 15;
    const MIN_BALANCE_VALUE = 20;
    const BALANCE_NEWS_DELAY = 604800;  // 1 week

    const CALCULATE_PRICE_LIMIT = 40;
    const OVER_PERCENT_LIMIT = 5;
    const BALANCE_MAX = 1;
    const BALANCE_MIN = 2;

    public static function getBalance(){

        $data = new Response("getBalance", Constant::OK_STATUS, "default", array("balance"=>array("balance_like"=>100, "balance_subscribe"=>100)));



        if(!Manager::$user->isPremium() && !Manager::$user->getTurbo()){
            $user_id = Manager::$user->getId();

            $settingsData = SettingsManager::getSettingsValues();
            if($settingsData->getStatus() == Constant::OK_STATUS){
                $settings = $settingsData->getObject();
                //$settings['SUBSCRIBE_SETTING'] = 1;                             //!!!!!!!!!!!! TEMP
                if($settings[SettingsManager::SUBSCRIBE_SETTING]){
                    $db = DB::getInstance();
                    $dbh = $db->getDBH();

                    $stmt = $dbh->prepare("SELECT likes_count, subscribes_count, likes_ready_count, subscribes_ready_count
                               FROM `".Constant::BALANCE_TABLE."`
                               WHERE user_id = :user_id AND bmonth = :bmonth
                               AND (likes_count >= :likes_count OR subscribes_count >= :subscribes_count)
                               AND (likes_ready_count >= :likes_ready_count AND subscribes_ready_count >= :subscribes_ready_count)");

                    $likes_count = $subscribes_count = self::BALANCE_ACTIVATE_LIMIT;
                    $likes_ready_count = $subscribes_ready_count = self::BALANCE_ACTIVATE_READY_LIMIT;

                    $stmt->bindParam(":likes_count", $likes_count, PDO::PARAM_INT);
                    $stmt->bindParam(":subscribes_count", $subscribes_count, PDO::PARAM_INT);
                    $stmt->bindParam(":likes_ready_count", $likes_ready_count, PDO::PARAM_INT);
                    $stmt->bindParam(":subscribes_ready_count", $subscribes_ready_count, PDO::PARAM_INT);

                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $now = new DateTime('now');
                    $bmonth = intval($now->format('m'));
                    $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);

                    $stmt->execute();

                    if($stmt->rowCount() > 0){
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $arr = $stmt->fetch();

                        $total =  $arr['subscribes_count'] + $arr['likes_count'];
                        $subscribes = ceil(($arr['subscribes_count'] / $total) * 100);
                        $likes = 100 - $subscribes;

                        $total_ready =  $arr['subscribes_ready_count'] + $arr['likes_ready_count'];
                        $subscribes_ready = ceil(($arr['subscribes_ready_count'] / $total_ready) * 100);
                        $likes_ready = 100 - $subscribes_ready;

                        //echo "likes_ready = $likes_ready / likes = $likes / subscribes_ready = $subscribes_ready / subscribes = $subscribes";
                        //echo "</br>";

                        $shift = abs($likes_ready - $likes);


                        //echo $shift;
                        //echo "</br>";

                        if($shift >= self::SHIFT_LIMIT){
                            $balance_subscribe = 100;
                            $balance_like = 100;
                            $shift = $likes_ready - $likes;


                            if($shift < 0){
                                $coef =  100 / $subscribes_ready;
                                $val = abs($shift * $coef);
                                $balance_subscribe = ceil($balance_subscribe - $val);
                            }else{
                                $coef = 100 / $likes_ready;
                                $val = abs($shift * $coef);
                                $balance_like = ceil($balance_like - $val);
                            }

                            if($balance_like < self::MIN_BALANCE_VALUE){
                                $balance_like = self::MIN_BALANCE_VALUE;
                            }

                            if($balance_subscribe < self::MIN_BALANCE_VALUE){
                                $balance_subscribe = self::MIN_BALANCE_VALUE;
                            }

                            $balance = array("balance_like"=>$balance_like, "balance_subscribe"=>$balance_subscribe);

                            self::sendBalanceNews($user_id, $bmonth, $balance, $dbh);

                            $data = new Response("getBalance", Constant::OK_STATUS, "default", array("balance"=>$balance));
                        }
                    }
                }
            }
        }

        return $data;
    }

    public static function calculatePrices(&$dbh=null){

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }
        $now = new DateTime('now');
        $bmonth = intval($now->format('m'));
        $user_id = Manager::$user->getId();
        $turbo = Manager::$user->getTurbo();

        $stmt = $dbh->prepare("SELECT likes_sum, subscribes_sum, likes_ready_count, subscribes_ready_count
                               FROM `".Constant::BALANCE_TABLE."`
                               WHERE user_id = :user_id AND bmonth = :bmonth
                               AND (likes_ready_count >= :likes_ready_count OR subscribes_ready_count >= :subscribes_ready_count)");


        $likes_ready_count = self::CALCULATE_PRICE_LIMIT;
        $subscribes_ready_count = self::CALCULATE_PRICE_LIMIT;

        $stmt->bindParam(":likes_ready_count", $likes_ready_count, PDO::PARAM_INT);
        $stmt->bindParam(":subscribes_ready_count", $subscribes_ready_count, PDO::PARAM_INT);
        $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        $balanceType = self::BALANCE_MAX;


        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();

            $likes_sum = $arr['likes_sum'];
            $subscribes_sum = $arr['subscribes_sum'];
            $likes_ready_count = $arr['likes_ready_count'];
            $subscribes_ready_count = $arr['subscribes_ready_count'];

            $defSum = $likes_ready_count * Constant::LIKE_PRICE +
                      $likes_ready_count * CashManager::getBonusPay($turbo) +
                      $subscribes_ready_count * Constant::SUBSCRIBE_PRICE +
                      $subscribes_ready_count * CashManager::getBonusPay($turbo);
            $curSum = $likes_sum + $subscribes_sum;

            $overPercent  = ($curSum / $defSum) * 100 - 100;

            //echo $overPercent;

            if($overPercent > self::OVER_PERCENT_LIMIT){
                $balanceType = self::BALANCE_MIN;
            }
        }

        Manager::$user->setBalanceType($balanceType);
        SessionManager::updateUser(Manager::$user);
    }

    private static function sendBalanceNews($user_id, $bmonth, $balance, &$dbh){
        $stmt = $dbh->prepare("SELECT id FROM `".Constant::BALANCE_TABLE."` WHERE user_id = :user_id AND bmonth = :bmonth AND ntime < :ntime");
        $ntime = self::BALANCE_NEWS_DELAY;
        $stmt->bindParam(":ntime", $ntime, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $id = $stmt->fetchColumn();
            $stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time, params) VALUES (:user_id, 14, :time, :params)");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $time = time();
            $stmt->bindParam(":time", $time, PDO::PARAM_INT);
            $params = "balance_like=".$balance['balance_like']."&balance_subscribe=".$balance['balance_subscribe'];
            $stmt->bindParam(":params", $params);
            if($stmt->execute()){
                $stmt = $dbh->prepare("UPDATE `".Constant::BALANCE_TABLE."` SET ntime = :ntime WHERE id = :id");
                $time = time();
                $stmt->bindParam(":ntime", $time, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    public static function depositSum($type, $sum, $to = null){
        self::depositReadyBalance($type, $sum, $to, true);
    }

    public static function depositBalance($type, $sum, $to = null){
        self::balance($type, $sum, "+", $to);
    }

    public static function withdrawBalance($type, $sum, $to = null){
        self::balance($type, $sum, "-", $to);
    }

    private static function balance($type, $sum, $sign, $to = null){
        if($to){
            $user_id = $to;
        }
        else{
            $user_id = Manager::$user->getId();
        }

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id FROM `".Constant::BALANCE_TABLE."` WHERE user_id = :user_id AND bmonth = :bmonth");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $now = new DateTime('now');
        $bmonth = intval($now->format('m'));
        $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);
        $stmt->execute();

        $likes_count = 0;
        $subscribes_count = 0;

        if($type == Constant::LIKE_TYPE){
            $likes_count = $sum;
        }else{
            $subscribes_count = $sum;
        }

        $needExec = true;

        $likeField = "likes_count";
        $subscribeField = "subscribes_count";

        if($stmt->rowCount() > 0){
            $stmt = $dbh->prepare("UPDATE `".Constant::BALANCE_TABLE."` SET $likeField = $likeField ".$sign." :$likeField, $subscribeField = $subscribeField ".$sign." :$subscribeField WHERE user_id = :user_id AND bmonth = :bmonth");
        }else{
            if($sign === "-"){
                $needExec = false;
            }else{
                $stmt = $dbh->prepare("INSERT INTO `".Constant::BALANCE_TABLE."` ($likeField, $subscribeField, bmonth, user_id) VALUES(:$likeField, :$subscribeField, :bmonth, :user_id)");
            }
        }

        if($needExec){
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);
            $stmt->bindParam(":$likeField", $likes_count, PDO::PARAM_INT);
            $stmt->bindParam(":$subscribeField", $subscribes_count, PDO::PARAM_INT);

            $stmt->execute();
        }

    }


    public static function depositReadyBalance($type, $sum, $to = null, $isSum = false){
        if($to){
            $user_id = $to;
        }
        else{
            $user_id = Manager::$user->getId();
        }

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id FROM `".Constant::BALANCE_TABLE."` WHERE user_id = :user_id AND bmonth = :bmonth");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $now = new DateTime('now');
        $bmonth = intval($now->format('m'));
        $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);
        $stmt->execute();

        $likes_ready_count = 0;
        $subscribes_ready_count = 0;

        if($type == Constant::LIKE_TYPE){
            $likes_ready_count = $sum;
        }else{
            $subscribes_ready_count = $sum;
        }

        $needExec = true;

        $likeField = "likes_ready_count";
        $subscribeField = "subscribes_ready_count";

        if($isSum){
            $likeField = "likes_sum";
            $subscribeField = "subscribes_sum";
        }

        if($stmt->rowCount() > 0){
            $stmt = $dbh->prepare("UPDATE `".Constant::BALANCE_TABLE."`
                                   SET $likeField = $likeField + :$likeField, $subscribeField = $subscribeField + :$subscribeField
                                   WHERE user_id = :user_id AND bmonth = :bmonth");
        }else{
            $stmt = $dbh->prepare("INSERT INTO `".Constant::BALANCE_TABLE."` ($likeField, $subscribeField, bmonth, user_id) VALUES(:$likeField, :$subscribeField, :bmonth, :user_id)");
        }

        if($needExec){
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":bmonth", $bmonth, PDO::PARAM_INT);
            $stmt->bindParam(":$likeField", $likes_ready_count, PDO::PARAM_INT);
            $stmt->bindParam(":$subscribeField", $subscribes_ready_count, PDO::PARAM_INT);

            $stmt->execute();
        }
    }

    public static function priorityBalance(){
        $user_id = Manager::$user->getId();
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET priority = :priority
                               WHERE id = :user_id AND priority = 0 AND (turbo = 0 OR turbo = 4)");

        $priority = HelpManager::getPriority("turbo_balance");

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            HelpManager::updateTablesPriority($user_id, $priority);
        }

    }

}