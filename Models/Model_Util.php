<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\CashManager as CashManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Managers\TableManager as TableManager;
use Famous\Lib\Managers\UtilsManager;
use Famous\Lib\Managers\VeryManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Secure;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;

use Famous\Lib\Utils\Redis as Redis;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */
class Model_Util extends Model
{
    public function getCash(){

        if(Manager::$user->isAuth()){
            $cashData = CashManager::getCash();
            if($cashData->getStatus() == Constant::OK_STATUS){
                $data = new Response("getCash", Constant::OK_STATUS, "", $cashData->getObject());
            }else{
                $data = new Response("getCash", Constant::ERR_STATUS, "Get cash error");
            }
        }else{
            $data = new Response("getCash", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function version(){
        $data = new Response("version", Constant::ERR_STATUS, "No depth value");
        if(Manager::$user->isAuth()){
            $version = HelpManager::getVersion();
            $data = new Response("version", Constant::OK_STATUS, "", array("version"=>$version));
        }else{
            $data = new Response("version", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function getNewsCount(){
         if(Manager::$user->isIsAuth()){
            $user_id = Manager::$user->getId();

            $db = DB::getInstance();
            $dbh = $db->getDBH();
            HelpManager::updateOnline($dbh, $user_id);
            BalanceManager::priorityBalance();

            $data = HelpManager::getNewsCount($dbh, $user_id);
        }else{
            $data = new Response("getNewsCount", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function watchAllNews(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $user_id = Manager::$user->getId();

            $ftime = time();
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("UPDATE `".Constant::FEEDS_TABLE."` SET is_watched = 1 WHERE user_id = :user_id AND fire_time <= :ftime");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);
            if($stmt->execute()){   //execute not rowCount() !important
                $data = new Response("watchAllNews", Constant::OK_STATUS);
            }else{
                $data = new Response("watchAllNews", Constant::ERR_STATUS, "Watch error");
            }
        }else{
            $data = new Response("watchAllNews", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function generateCaptcha(){
        if(Manager::$user->isAuth()){
            $data = SecureManager::captchaGenerate(Manager::$user->getId());
        }else{
            $data = new Response("generateCaptcha", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function checkCaptcha(){
        $data = SecureManager::checkCaptcha();
        return $data;
    }

    public function viewAd(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = new Response("viewAd", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['ad_id']) && isset($_REQUEST['status'])){
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $ad_id = Validator::clear($_REQUEST['ad_id']);
                $user_id = Manager::$user->getId();
                $status = Validator::clear($_REQUEST['status']);;
                $stmt = $dbh->prepare("SELECT id, repeat_cnt FROM `".Constant::ADS_FEED_TABLE."` WHERE user_id = :user_id AND ad_id = :ad_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":ad_id", $ad_id, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->rowCOunt() > 0){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    $repeat_cnt = $arr['repeat_cnt'] + 1;
                    $id = $arr["id"];
                    $stmt = $dbh->prepare("UPDATE `".Constant::ADS_FEED_TABLE."` SET status = :status, repeat_cnt = :repeat_cnt WHERE id = :id");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                }else{
                    $stmt = $dbh->prepare("INSERT INTO `".Constant::ADS_FEED_TABLE."` (user_id, ad_id, repeat_cnt, status) VALUES(:user_id, :ad_id, :repeat_cnt, :status)");
                    $repeat_cnt = 1;
                    $stmt->bindParam(":ad_id", $ad_id, PDO::PARAM_INT);
                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                }

                $stmt->bindParam(":repeat_cnt", $repeat_cnt, PDO::PARAM_INT);
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);

                $stmt->execute();

                $data = new Response("viewAd", Constant::OK_STATUS, "");

            }
        }else{
            $data = new Response("viewAd", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function setData(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = new Response("setData", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['email']) && isset($_REQUEST['country_code']) && isset($_REQUEST['phone_number'])
                && isset($_REQUEST['birthday']) && isset($_REQUEST['biography']) && isset($_REQUEST['gender'])
                && isset($_REQUEST['first_name'])  && isset($_REQUEST['last_name']) && isset($_REQUEST['external_url'])){
                $db = DB::getInstance();
                $dbh = $db->getDBH();

                $email = Validator::clear($_REQUEST['email']);
                $country_code = Validator::clear($_REQUEST['country_code']);
                $phone_number = Validator::clear($_REQUEST['phone_number']);
                $birthday = Validator::clear($_REQUEST['birthday']);
                $biography = Validator::clear($_REQUEST['biography']);
                $last_name = Validator::clear($_REQUEST['last_name']);
                $first_name = Validator::clear($_REQUEST['first_name']);
                $gender = Validator::clear($_REQUEST['gender']);
                $external_url = Validator::clear($_REQUEST['external_url']);

                $follows_count = Validator::clear($_REQUEST['follows_count']);
                $followed_count = Validator::clear($_REQUEST['followed_count']);
                $post_count = Validator::clear($_REQUEST['post_count']);

                $botnet = Validator::clear($_REQUEST['botnet']);

                $etime = time();
                $user_id = Manager::$user->getId();

                $params = array("email"=>":email", "etime"=>":etime", "country_code"=>":country_code", "gender"=>":gender");

                if($phone_number){
                    $params['phone_number'] = ":phone_number";
                }if($birthday && $birthday !== "null"){
                    $params['birthday'] = ":birthday";
                }if($biography){
                    $params['biography'] = ":biography";
                }if($last_name){
                    $params['last_name'] = ":last_name";
                }if($first_name){
                    $params['first_name'] = ":first_name";
                }if($external_url){
                    $params['external_url'] = ":external_url";
                }if($follows_count){
                    $params['follows_count'] = ":follows_count";
                }if($followed_count){
                    $params['followed_count'] = ":followed_count";
                }if($post_count){
                    $params['post_count'] = ":post_count";
                }if($botnet){
                    $params['botnet'] = ":botnet";
                }

                $query = implode(", ", array_map(
                                    function($k, $v){
                                        return sprintf("%s = %s", $k, $v);
                                    },
                                    array_keys($params),
                                    $params
                                  )
                         );

                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET ".$query." WHERE user_id = :user_id");

                if(key_exists("phone_number", $params)){
                    $stmt->bindParam(":phone_number", $phone_number);
                }if(key_exists("birthday", $params)){
                    $stmt->bindParam(":birthday", $birthday);
                }if(key_exists("biography", $params)){
                    $stmt->bindParam(":biography", $biography);
                }if(key_exists("last_name", $params)){
                    $stmt->bindParam(":last_name", $last_name);
                }if(key_exists("first_name", $params)){
                    $stmt->bindParam(":first_name", $first_name);
                }if(key_exists("external_url", $params)){
                    $stmt->bindParam(":external_url", $external_url);
                }if(key_exists("follows_count", $params)){
                    $stmt->bindParam(":follows_count", $follows_count);
                }if(key_exists("followed_count", $params)){
                    $stmt->bindParam(":followed_count", $followed_count);
                }if(key_exists("post_count", $params)){
                    $stmt->bindParam(":post_count", $post_count);
                }if(key_exists("botnet", $params)){
                    $stmt->bindParam(":botnet", $botnet);
                }

                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":country_code", $country_code);
                $stmt->bindParam(":etime", $etime, PDO::PARAM_INT);
                $stmt->bindParam(":gender", $gender, PDO::PARAM_INT);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();

                if($stmt->rowCount() > 0){
                    $data = new Response("setData", Constant::OK_STATUS, "");
                }else{
                    $data = new Response("setData", Constant::ERR_STATUS, "Update error");
                }
            }
        }else{
            $data = new Response("setData", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function setRegId(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = new Response("regId", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['reg_id'])){
                $db = DB::getInstance();
                $dbh = $db->getDBH();

                $reg_id = Validator::clear($_REQUEST['reg_id']);
                $user_id = Manager::$user->getId();

                $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d SET d.registration_id = :reg_id WHERE d.user_id = u.id AND u.id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":reg_id", $reg_id);

                if($stmt->execute()){
                    $data = new Response("regId", Constant::OK_STATUS, "");
                }
            }
        }else{
            $data = new Response("regId", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function reportErr(){
        //if(Manager::$user->isAuth()){

            //$data = SecureManager::isVerify();
            //if($data){return $data;}

            //if we have any error
            $data = new Response("reportErr", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['message']) && isset($_REQUEST['stack']) && isset($_REQUEST['app_v'])){
                $db = DB::getInstance();
                $message = Validator::clear($_REQUEST['message']);
                $stack = Validator::clear($_REQUEST['stack']);
                $app_v = Validator::clear($_REQUEST['app_v']);
                $dbh = $db->getDBH();
                $stmt = $dbh->prepare("SELECT id, count, is_fixed FROM `".Constant::ERRORS_TABLE."` WHERE message = :message");
                $stmt->bindParam(":message", $message);
                $etime = time();
                $stmt->execute();
                if($stmt->rowCOunt() > 0){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    if(!$arr['is_fixed']){
                        $count = $arr['count'] + 1;
                        $id = $arr['id'];

                        $stmt = $dbh->prepare("UPDATE `".Constant::ERRORS_TABLE."` SET count = :count, etime = :etime WHERE id = :id");

                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    }
                }else{
                   $stmt = $dbh->prepare("INSERT INTO `".Constant::ERRORS_TABLE."` (message, stack, etime, count, app_v) VALUES (:message, :stack, :etime, :count, :app_v)");
                    $count = 1;
                    $stmt->bindParam(":message", $message);
                    $stmt->bindParam(":stack", $stack);
                    $stmt->bindParam(":app_v", $app_v);

                }

                $stmt->bindParam(":count", $count, PDO::PARAM_INT);
                $stmt->bindParam(":etime", $etime, PDO::PARAM_INT);
                $stmt->execute();

                $data = new Response("reportErr", Constant::OK_STATUS, "");

            }
       // }else{
            //$data = new Response("reportErr", Constant::ERR_STATUS, "Auth error");
        //}
        return $data;
    }

    public function getVeryUsers(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $users = array();

        $ptime = time();

        $stmt = $dbh->prepare("SELECT d.login as real_id, vu.user_id
                               FROM `".Constant::VERY_USERS_TABLE."` vu, `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE vu.ptime < :ptime AND vu.godmode > 0 AND vu.user_id = u.id AND u.id = d.user_id");

        $stmt->bindParam(":ptime", $ptime, PDO::PARAM_INT);
        $status = Constant::OK_SUBSCRIBE_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while($arr = $stmt->fetch()){
                $users[] = $arr;
            }
            shuffle($users);
        }

        return new Response("getVeryUsers", Constant::OK_STATUS, "", array("users"=>$users));
    }

    public function afterParse(){

        $data = new Response("afterParse", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['data']) && isset($_REQUEST['user_id']) && isset($_REQUEST['real_id']) && isset($_REQUEST['suspicion']) && isset($_REQUEST['verdict'])){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $dt = $_REQUEST['data'];
            $user_id = $_REQUEST['user_id'];
            $real_id = $_REQUEST['real_id'];
            $suspicion = $_REQUEST['suspicion'];
            $verdict = $_REQUEST['verdict'];

            $data = VeryManager::manageVeryUsersData($user_id, $real_id, $suspicion, $verdict, $dt);
        }


        return $data;
    }

    public function loadFile(){

        if(isset($_REQUEST['type']) && isset($_REQUEST['file']) && isset($_REQUEST['app'])) {
            $type = Validator::clear($_REQUEST['type']);
            $app = Validator::clear($_REQUEST['app']);
            $file = Validator::clear($_REQUEST['file']);
            $path = "";
            switch($type){
                case "browser":
                    $path = "/apps/" . $app . "/js/gap/browser/" . $file;
                    break;
            }

            $path = $_SERVER["DOCUMENT_ROOT"] . $path;

            Helper::fileForceLoad($path);

        }
    }

    public function setUpdated(){

        if(Manager::$user->isAuth()){

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $user_id = Manager::$user->getId();

            $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET is_updated = 1 WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $data = new Response("setUpdated", Constant::OK_STATUS, "");
        }else{
            $data = new Response("setUpdated", Constant::ERR_STATUS, "Auth error");
        }


        return $data;
    }
}