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
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Validator;
use Famous\Models\Model_Task;
use \PDO as PDO;

class DonateManager
{

    const BONUS_LEAVE_PERCENT = 20;
    const DONATE_TABLE = "donate";
    const FREE_SUBSCRIBERS_PACK = 0;
    const FREE_LIKES_PACK = 20;

    public static function hasFreePacks(){

        $data = new Response("hasFreePacks", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['ip'])){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $user_id = Manager::$user->getId();
            $ip = Validator::clear($_REQUEST['ip']);

            $stmt = $dbh->prepare("SELECT dtime_likes, dtime_subscribes FROM `".self::DONATE_TABLE."` WHERE user_id = :user_id OR ip = :ip ORDER BY id DESC LIMIT 1");

            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":ip", $ip);

            $stmt->execute();

            $free_likes_delay = 0;
            $free_subscribes_delay = 0;

            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();

                $time = time();

                if($arr['dtime_likes'] > $time){
                    $free_likes_delay = $arr['dtime_likes'] - $time;
                }
                if($arr['dtime_subscribes'] > $time){
                    $free_subscribes_delay = $arr['dtime_subscribes'] - $time;
                }
            }

            $available_likes = true;
            $available_subscribers = false;

            $data = new Response("hasFreePacks", Constant::OK_STATUS, "", array("free_likes_delay"=>$free_likes_delay, "free_subscribes_delay"=>$free_subscribes_delay,
                "available_likes"=>$available_likes, "available_subscribes"=>$available_subscribers));
        }


        return $data;
    }

    public static function getWithPercents($diamonds){
        return $diamonds + $diamonds * (self::BONUS_LEAVE_PERCENT/100);
    }

    public static function orderFull(){
        if(Manager::$user->isAuth()){

            $data = new Response("bid", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['type']) && isset($_REQUEST['id']) && isset($_REQUEST['meta']) && isset($_REQUEST['head']) && isset($_REQUEST['target_count']) && isset($_REQUEST['real_id']) && isset($_REQUEST['pack'])) {

                $type = Validator::clear($_REQUEST['type']);
                $id =  Validator::clear($_REQUEST['id']);
                $head =  Validator::clear($_REQUEST['head']);
                $meta =  Validator::clear($_REQUEST['meta']);
                $real_id = Validator::clear($_REQUEST['real_id']);
                $pack = Validator::clear($_REQUEST['pack']);

                if($pack != "pack_free_likes" && $pack != "pack_free_followers"){
                    $parts = explode("_", $pack);

                    $val = $parts[2] <= 5 ? "turbos_3" : "turbos_5";
                    $turbo = HelpManager::getTurbo($val);
                    $priority = HelpManager::getPriority($val);
                    if (Manager::$user->getTurbo() == 0) {

                        $db = DB::getInstance();
                        $dbh = $db->getDBH();

                        HelpManager::setTurbo($dbh, Manager::$user->getId(), $val);


                        Manager::$user->setTurbo($turbo);
                        Manager::$user->setPriority($priority);
                        SessionManager::updateUser(Manager::$user);
                        HelpManager::updateTablesPriority();
                    }
                }

                $meta = base64_encode( mb_substr($meta, 0, Constant::MAX_META_LENGTH, "UTF-8"));			//need only for likes need to add function for each type?

                $targetData =  self::getMaxOrder($type, CashManager::getDiamonds($pack));
                if($targetData->getStatus() == Constant::OK_STATUS){
                    $obj = $targetData->getObject();
                    $target_count = $obj['max'];
                    $mt = new Model_Task();
                    $data = $mt->makeBid($type, $id, $head, $meta, $real_id, $target_count);
                }else{
                    $data = new Response("orderFull", Constant::ERR_STATUS, "Auth error");
                }
            }

        }else{
            $data = new Response("orderFull", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    private static function getMaxOrder($type, $diamonds){

        if($diamonds){
            $freeSum = $diamonds;

            $onePrice = CashManager::getTaskPriceBid($type);

            $max = floor($freeSum / $onePrice);

            $data = new Response("getMaxOrder", Constant::OK_STATUS, "", array("max"=>$max));
        }else{
            $cashData = CashManager::getCash();
            if($cashData->getStatus() == Constant::OK_STATUS){
                $obj = $cashData->getObject();
                $freeSum = $obj['cash']['deposit'];

                $onePrice = CashManager::getTaskPriceBid($type);

                $max = floor($freeSum / $onePrice);

                $data = new Response("getMaxOrder", Constant::OK_STATUS, "", array("max"=>$max));
            }else{
                $data = new Response("getMaxOrder", Constant::ERR_STATUS, "Cash error");
            }
        }

        return $data;
    }

    public static function getFreePack()
    {
        if(Manager::$user->isAuth()){


            $data = new Response("getFreePack", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['type']) && isset($_REQUEST['pack']) && isset($_REQUEST['ip']) ) {
                $pack = Validator::clear($_REQUEST['pack']);
                $type = Validator::clear($_REQUEST['type']);
                $ip = Validator::clear($_REQUEST['ip']);

                $freeData = self::hasFreePacks();
                $obj = $freeData->getObject();

                $canGet = true;

                if($type == Constant::LIKE_TYPE && (!($obj["free_likes_delay"] <= 0) || !$obj["available_likes"])){
                    $canGet = false;
                }else if($type == Constant::SUBSCRIBE_TYPE && (!($obj["free_subscribes_delay"] <= 0) || !$obj["available_subscribes"])){
                    $canGet = false;
                }

                if($canGet){
                    $diamonds = CashManager::getDiamonds($pack);

                    $withdrawData = CashManager::deposit($diamonds);
                    if($withdrawData->getStatus() == Constant::OK_STATUS){

                        self::updateFreeDonate($type, $ip);

                        $cashData = CashManager::getCash();
                        if($cashData->getStatus() == Constant::OK_STATUS){
                            $data = new Response("getFreePack", Constant::OK_STATUS, "", $cashData->getObject());
                        }else{
                            $data = new Response("getFreePack", Constant::ERR_STATUS, "Cash error");
                        }
                    }else{
                        $data = new Response("getFreePack", Constant::ERR_STATUS, "Withdraw error");
                    }
                }else{
                    $data = new Response("getFreePack", Constant::ERR_STATUS, "Do not have free packs");
                }
            }
        }else{
            $data = new Response("getFreePack", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }


    private static function updateFreeDonate($type, $ip){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $user_id = Manager::$user->getId();

        $dtime_likes = time();
        $dtime_subscribes = time();

        $stmt = $dbh->prepare("INSERT INTO `".self::DONATE_TABLE."` (dtime_likes, dtime_subscribes, user_id, ip) VALUES(:dtime_likes, :dtime_subscribes, :user_id, :ip)");


        if($type == Constant::LIKE_TYPE){
            $dtime_likes = time() + 86400;
        }else if($type == Constant::SUBSCRIBE_TYPE){
            $dtime_subscribes = time() + 86400;
        }

        $stmt->bindParam(":ip", $ip);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":dtime_subscribes", $dtime_subscribes, PDO::PARAM_INT);
        $stmt->bindParam(":dtime_likes", $dtime_likes, PDO::PARAM_INT);
        $stmt->execute();

    }

    public static function freeApp(){
        $data = new Response("freeApp", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['package'])) {
            $packageName = Validator::clear($_REQUEST['package']);

            $data = new Response("getFreePack", Constant::ERR_STATUS, "No depth value");

            if($packageName == Constant::PACKAGE_NAME_DONATE_1){
                $arr = array("name"=>"ROYAL LIKES", "package"=>"com.renewal.rfvip");
                $data = new Response("getFreePack", Constant::OK_STATUS, "", array("app"=>$arr));
            }
        }


        return $data;
    }

    public static function getAd(){

        $data = new Response("getAd", Constant::ERR_STATUS, "No depth value");
        $excepts = array();
        if (isset($_REQUEST['excepts']) && isset($_REQUEST['package'])){
            $packageName = Validator::clear($_REQUEST['package']);
            $tmp = explode(":", $_REQUEST['excepts']);
            if($tmp){
                $excepts = $tmp;
            }

            if($packageName == Constant::PACKAGE_NAME_DONATE_1){
                $arr = array();

                $arr[] = array("name"=>"Magical Stones", "desc"=>"Best magical bricks breaker!", "alias"=>"magical_stones", "package"=>"com.wayne.magicalstones");
                $arr[] = array("name"=>"Royal Ballz", "desc"=>"Ballz kill boxes - best time killer!", "alias"=>"royal_ballz", "package"=>"com.wayne.royalballz");
                $arr[] = array("name"=>"Jump Minimal", "desc"=>"The best game to challenge your reaction and timing skills.", "alias"=>"jump_minimal", "package"=>"com.wayne.jumpminimal");

                $data = new Response("getAd", Constant::OK_STATUS, "", array("ad"=>self::getRealAd($arr, $excepts)));
            }
        }

        return $data;
    }

    public static function getPrices(){

        $like_prices = array("1.99$", "2.99$", "5.99$", "9.99$", "18.99$", "24.99$", "29.99$", "35.99$", "59.99$", "109.99$");
        $subscribe_prices = array("2.99$", "4.99$", "9.99$", "17.99$", "33.99$", "47.99$", "62.99$", "75.99$", "139.99$", "249.99$");

        $data = new Response("getPrices", Constant::OK_STATUS, "", array("like_prices"=>$like_prices, "subscribe_prices"=>$subscribe_prices));

        return $data;
    }

    private static function getRealAd($arr, $excepts){
        $app = null;

        for($i=0; $i<count($arr); $i++){
            if($excepts){
                if(!in_array($arr[$i]["package"], $excepts)){
                    $app = $arr[$i];
                    break;
                }
            }else{
                $app = $arr[$i];
                break;
            }
        }

        return $app;
    }

}