<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-11-05
 * Time: 14:01
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Manager;
use Famous\Lib\Common\Response;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use \PDO as PDO;

class ReferalManager
{
    const REFERAL_LINK_TABLE = "referal_links";
    const REFERAL_TABLE = "referals";
    const REFERAL_PERCENT = 15;
    const MULTIPLE = 1000;
    const LIMIT_REFERAL_MIN = 10;
    const STAY_REFERAL_BONUS = 100;

    public static function getReferalLink(){
        $package = Manager::$user->getPackageName();
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT link FROM `".self::REFERAL_LINK_TABLE."` WHERE package_name = :package_name");
        $stmt->bindParam(":package_name", $package);

        $stmt->execute();

        if($stmt->rowCount() > 0){
           $link = $stmt->fetchColumn();
           $data = new Response("getReferalLink", Constant::OK_STATUS, "", array("link"=>$link));
        }else{
           $data = new Response("getReferalLink", Constant::ERR_STATUS, "No link for package");
        }

        return $data;
    }

    public static function getReferalData($user_id = null, $dbh = null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        $stmt = $dbh->prepare("SELECT d.login FROM `".self::REFERAL_TABLE."` r, `".Constant::DATA_TABLE."` d  WHERE r.referal_id = :user_id AND r.user_id = d.user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $isReferal = 0;
        $nick = "";

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $isReferal = 1;
            $nick = $arr['login'];
        }

        return new Response("getReferalData", Constant::OK_STATUS, "", array("is_referal"=>$isReferal, "nick"=>$nick));
    }

    public static function stayReferal($referal_id){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $user_id = Manager::$user->getId();

        if($user_id != $referal_id){
            $stmt = $dbh->prepare("SELECT id FROM `".self::REFERAL_TABLE."` WHERE referal_id = :user_id_my OR (user_id = :user_id AND referal_id = :referal_id)");

            $stmt->bindParam(":user_id_my", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id , PDO::PARAM_INT);
            $stmt->bindParam(":referal_id", $referal_id, PDO::PARAM_INT);

            $stmt->execute();

            if($stmt->rowCount() == 0){
                $stmt = $dbh->prepare("SELECT d.login FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d WHERE u.id = d.user_id AND u.id = :referal_id");

                $stmt->bindParam(":referal_id", $referal_id, PDO::PARAM_INT);
                $stmt->execute();

                if($stmt->rowCount() > 0){
                    $login = $stmt->fetchColumn();
                    $stmt = $dbh->prepare("INSERT INTO `".self::REFERAL_TABLE."` (user_id, referal_id, deposit) VALUES(:user_id, :referal_id, 0)");
                    $stmt->bindParam(":user_id", $referal_id, PDO::PARAM_INT);
                    $stmt->bindParam(":referal_id", $user_id, PDO::PARAM_INT);

                    if($stmt->execute()){
                        Manager::$user->setReferal(1);

                        SessionManager::updateUser(Manager::$user);

                        self::payReferalStay($user_id, $referal_id, $dbh);

                        $cashData = CashManager::getCash();
                        if($cashData->getStatus() == Constant::OK_STATUS){
                            $data = new Response("stayReferal", Constant::OK_STATUS, "", array_merge(array("login"=>$login), $cashData->getObject()));
                        }else{
                            $data = new Response("stayReferal", Constant::ERR_STATUS, "Can not get cash");
                        }
                    }else{
                        $data = new Response("stayReferal", Constant::ERR_STATUS, "Can not set");
                    }

                }else{
                    $data = new Response("stayReferal", Constant::ERR_STATUS, "No user for package");
                }
            }else{
                $data = new Response("stayReferal", Constant::ERR_STATUS, "Stay before");
            }
        }else{
            $data = new Response("stayReferal", Constant::ERR_STATUS, "Self referal");
        }

        return $data;
    }

    private static function payReferalStay($user_id, $referal_id, &$dbh){

        $stmt = $dbh->prepare("SELECT params FROM `".Constant::FEEDS_TABLE."` WHERE news_id = :news_id AND user_id = :user_id AND is_complete = 0");
        $stmt->bindParam(":user_id", $referal_id, PDO::PARAM_INT);
        $news_id = 16;
        $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
        $stmt->execute();

        $diamonds = self::STAY_REFERAL_BONUS;
        $count = 1;

        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $params = $stmt->fetchColumn();
            parse_str($params, $arr);

            $diamonds += $arr['unbalanced_diamonds'];
            $count += $arr['count'];
            $isComplete = 0;
        }else{
            $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::NEWS_NOTIFICATION);
            NotificationManager::sendNotification($notif_id, $referal_id);
            $isComplete = 1;
        }
        $params = "unbalanced_diamonds=".$diamonds."&count=".$count;

        NewsManager::refreshOrAddNews($news_id, $referal_id, $dbh, true, $params, $isComplete);

        //pay to user
        CashManager::deposit(self::STAY_REFERAL_BONUS);
    }

    public static function referalDeposit($total, $to = null){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        if(!$to){
            $user_id = Manager::$user->getId();
        }else{
            $user_id = $to;
        }

        if(Manager::$user->getReferal()){
            $deposit = floor(($total * (self::REFERAL_PERCENT / 100)) * self::MULTIPLE);

            $stmt = $dbh->prepare("UPDATE `".self::REFERAL_TABLE."` SET deposit = deposit + :deposit WHERE referal_id = :user_id");
            $stmt->bindParam(":deposit", $deposit, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    public static function getReferals($user_id, &$dbh = null){

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        $stmt = $dbh->prepare("SELECT r.deposit, d.login
                               FROM `".self::REFERAL_TABLE."` r, `".Constant::DATA_TABLE."` d
                               WHERE r.user_id = :user_id AND d.user_id = r.referal_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();
        $referals = array();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();
            foreach($dt as $arr){
                $referals[] = array("login"=>$arr['login'], "deposit"=>floor($arr['deposit'] / self::MULTIPLE));
            }
        }
        $tdate = date("Y-m-d");
        $midnight = strtotime($tdate.' 00:00:00') + 86400;	//midnight tomorrow
        $left = $midnight - time();

        $stmt = $dbh->prepare("SELECT SUM(deposit) as diamonds FROM `".self::REFERAL_TABLE."` WHERE user_id = :user_id AND deposit >= :limit GROUP BY user_id");
        $limit = self::LIMIT_REFERAL_MIN * self::MULTIPLE;
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $diamonds = 0;
        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $diamonds = $stmt->fetchColumn();
            $diamonds = (floor($diamonds / self::MULTIPLE));
        }

        $data = new Response("getReferals", Constant::OK_STATUS, "", array("referals" => $referals, "left"=>$left, "diamonds"=>$diamonds));

        return $data;
    }

    public static function getReferalsDiamonds($user_id = null, &$dbh = null){

        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        $stmt = $dbh->prepare("SELECT SUM(deposit) as diamonds FROM `".self::REFERAL_TABLE."` WHERE user_id = :user_id AND deposit >= :limit GROUP BY user_id");
        $limit = self::LIMIT_REFERAL_MIN * self::MULTIPLE;
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $diamonds = 0;
        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $diamonds = $stmt->fetchColumn();
            $diamonds = (floor($diamonds / self::MULTIPLE));
        }

        $data = new Response("getReferalsDiamonds", Constant::OK_STATUS, "", array("referal_diamonds"=>$diamonds));

        return $data;
    }

    public static function referalPay(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT user_id, SUM(deposit) as diamonds FROM `".self::REFERAL_TABLE."` WHERE deposit >= :limit GROUP BY user_id");
        $limit = self::LIMIT_REFERAL_MIN * self::MULTIPLE;
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();
            $news_id = 17;
            foreach($dt as $arr){
                $user_id = $arr['user_id'];

                $params = "unbalanced_diamonds=".(floor($arr['diamonds'] / self::MULTIPLE));
                $time = time();
                $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time, params) VALUES ($user_id, $news_id, $time, '$params')");
                $stmt->execute();

                $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::NEWS_NOTIFICATION);
                NotificationManager::sendNotification($notif_id, $user_id);
            }

        }

        $stmt = $dbh->prepare("UPDATE `".self::REFERAL_TABLE."` SET deposit = 0");

        $stmt->execute();

        $data = new Response("referalPay", Constant::OK_STATUS);
        return $data;
    }

}