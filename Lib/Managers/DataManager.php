<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\User as User;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/25/2016
 * Time: 13:36
 */
class DataManager
{
    const AUTH_DATA = 1;

    public static function getData($type){
        switch($type){
            case self::AUTH_DATA:
                $data = self::getAuthData();
                break;
            default:
                $data = new Response("getData", Constant::ERR_STATUS, "Wrong type of data");
                break;
        }
        return $data;
    }

    public static function getAuthData(){
        $cashData = CashManager::getCash();
        if($cashData->getStatus() == Constant::OK_STATUS){

            BalanceManager::calculatePrices();//order is important before AppConfig

            $config = ConfigManager::getConfig();
            if($config->getStatus() == Constant::OK_STATUS){
                $achieves = XpManager::getAchieves();
                if($achieves->getStatus() == Constant::OK_STATUS){
                    $news = HelpManager::getNewsCount();
                    if($news->getStatus() == Constant::OK_STATUS){
                        $delay = HelpManager::getEarnDelay();
                        if($delay->getStatus() == Constant::OK_STATUS){
                            $balance = BalanceManager::getBalance();
                            if($balance->getStatus() == Constant::OK_STATUS){
                                $referal = ReferalManager::getReferalsDiamonds();
                                if($referal->getStatus() == Constant::OK_STATUS){
                                    $data = new Response("getAuthData", Constant::OK_STATUS, "",
                                        array_merge($cashData->getObject(), $achieves->getObject(),
                                            $config->getObject(), $news->getObject(),
                                            $delay->getObject(), $balance->getObject(), $referal->getObject(),
                                            HelpManager::getHeaderPackage(), HelpManager::getSessionId()));
                                }else{
                                    $data = new Response("getAuthData", Constant::ERR_STATUS, "Get balance error");
                                }
                            }else{
                                $data = new Response("getAuthData", Constant::ERR_STATUS, "Get balance error");
                            }
                        }else{
                            $data = new Response("getAuthData", Constant::ERR_STATUS, "Get delay error");
                        }
                    }else{
                        $data = new Response("getAuthData", Constant::ERR_STATUS, "Get news count error");
                    }
                }else{
                    $data = new Response("getAuthData", Constant::ERR_STATUS, "Get achieves error");
                }
            }else{
                $data = new Response("getAuthData", Constant::ERR_STATUS, "Get xpInfo error");
            }

        }else{
            $data = new Response("getAuthData", Constant::ERR_STATUS, "Get cash error");
        }
        return $data;
    }

    public static function authUser($login, $lang, $iid){

        $data = new Response("authData", Constant::ERR_STATUS, "User prepare data error");

        $user = self::getUserByLogin($login, $iid);
        //if we have user just return data
        if(!$user){
            /*
            $data = HelpManager::createUser($login, $lang, $iid);
            if($data->getStatus() == Constant::OK_STATUS){
                $user = self::getUserByLogin($login, $iid);
            }*/
            if(isset($_REQUEST['captcha']) && $_REQUEST['captcha'] == Constant::OK_STATUS){
                $data = HelpManager::createUser($login, $lang, $iid);
                if($data->getStatus() == Constant::OK_STATUS){
                    $user = self::getUserByLogin($login, $iid);
                }
            }else{
                $dbh = null;
                $data = SecureManager::captchaGenerate(null, $dbh, true);
            }
        }else{
            if((isset($_REQUEST['device_name']) && ($user->getDeviceName() !=  Validator::clear($_REQUEST['device_name'])) && Helper::getIp() != $user->getIp())
                || (isset($_REQUEST['package_name']) && $user->getPackageName() != $_REQUEST['package_name']) ){
                if(isset($_REQUEST['captcha']) && $_REQUEST['captcha'] == Constant::OK_STATUS ){
                    //nothing to do
                }else{
                    $dbh = null;
                    $data = SecureManager::captchaGenerate(null, $dbh, true);
                    $user = null;
                }
            }
        }
        if($user){
            $data = SessionManager::prepare($user);
        }
        return $data;
    }

    public static function setPackage($packageName){

        if(!$packageName){
            $packageName = Constant::PACKAGE_NAME_METEOR;
        }

        Manager::$user->setPackageName($packageName);

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d SET d.package_name = :package_name WHERE u.id = :id AND d.user_id = u.id");
        $user_id = Manager::$user->getId();
        $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":package_name", $packageName);

        $stmt->execute();

        SessionManager::updateUser(Manager::$user);
    }

    public static function setMainData($package_name, $app_v, $platform, $iid = null, $vtime=null, $device_name=null){
        DataManager::setPackage($package_name);
        DataManager::setVersion($app_v);
        DataManager::setPlatform($platform);
        DataManager::setVtime($vtime);
        DataManager::setDeviceName($device_name);
        DataManager::setIid($iid);
        DataManager::setIp(Helper::getIp());

        $data = ReferalManager::getReferalData();
        if($data->getStatus() == Constant::OK_STATUS){
            $obj = $data->getObject();
            $referal_id = $obj['is_referal'];
            DataManager::setReferal($referal_id);
        }
    }

    public static function setIid($iid){

        if($iid){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET iid = :iid WHERE user_id = :user_id");
            $user_id = Manager::$user->getId();
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":iid", $iid, PDO::PARAM_INT);
            $stmt->execute();

            Manager::$user->setIid($iid);
            SessionManager::updateUser(Manager::$user);
        }

    }

    public static function setPlatform($platform){

        if(!$platform){
            $platform = Constant::PLATFORM_ANDROID;
        }

        Manager::$user->setPlatform($platform);

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET platform = :platform WHERE user_id = :user_id");
        $user_id = Manager::$user->getId();
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":platform", $platform, PDO::PARAM_INT);

        $stmt->execute();

        SessionManager::updateUser(Manager::$user);
    }

    public static function setVtime($vtime){

        if($vtime){
            Manager::$user->setVtime($vtime);
            SessionManager::updateUser(Manager::$user);
        }

    }

    public static function setIp($ip){

        if($ip){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET ip = :ip WHERE user_id = :user_id");
            $user_id = Manager::$user->getId();
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":ip", $ip, PDO::PARAM_INT);
            $stmt->execute();
        }

    }

    public static function setDeviceName($device_name){

        if($device_name){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET device_name = :device_name WHERE user_id = :user_id");
            $user_id = Manager::$user->getId();
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":device_name", $device_name);
            $stmt->execute();
        }

    }

    public static function setReferal($is_referal){
        Manager::$user->setReferal($is_referal);
        SessionManager::updateUser(Manager::$user);
    }

    public static function setVersion($app_v){
        Manager::$user->setAppVersion($app_v);

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET app_v = :app_v WHERE user_id = :user_id");
        $user_id = Manager::$user->getId();
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":app_v", $app_v, PDO::PARAM_INT);

        $stmt->execute();

        SessionManager::updateUser(Manager::$user);
    }

    public static function getUserByLogin($login, $iid){
        $user = null;
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT u.id, d.login, u.deposit, u.priority, u.premium, u.turbo, u.xp, d.rtime, d.lang, d.email, d.etime, d.vtime, d.package_name, d.device_name, d.ip, d.iid, d.ip, d.is_updated
                               FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.user_id = u.id AND (d.login = :login OR d.iid = :iid)");
        $stmt->bindParam(":login", $login);
        $stmt->bindParam(":iid", $iid);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $user = new User($arr);


            //need for verify
            try{
                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET atime = :atime WHERE user_id = :user_id");
                $atime = time();
                $user_id = $arr['id'];
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":atime", $atime, PDO::PARAM_INT);
                $stmt->execute();
            }catch(\PDOException $e){

            }

        }

        return $user;
    }

    public static function checkOld($login){
        $data = new Response("checkOld", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['need_update']) && isset($_REQUEST['old_login'])) {

            $need_update = Validator::clear($_REQUEST['need_update']);
            $old_login = Validator::clear($_REQUEST['old_login']);
            if($need_update){
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET login = :login WHERE login = :old_login");
                $stmt->bindParam(":login", $login);
                $stmt->bindParam(":old_login", $old_login);
                if($stmt->execute()){
                    Manager::$user->setLogin($login);
                    SessionManager::updateUser(Manager::$user);
                    $data = new Response("checkOld", Constant::OK_STATUS);
                }else{
                    $data = new Response("checkOld", Constant::ERR_STATUS, "Update error");
                }
            }

        }
        return $data;
    }


    public static function getMainData($user_id){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT registration_id, package_name, platform FROM `".Constant::DATA_TABLE."` WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $data = new Response("getMainData", Constant::OK_STATUS, "", $arr);
        }else{
            $data = new Response("getMainData", Constant::ERR_STATUS, "Get data by id err");
        }

        return $data;
    }

    public static function getNotificationData($not_id, $lang = Constant::DEFAULT_LANG){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $title = "title_".$lang;
        $message = "message_".$lang;

        $stmt = $dbh->prepare("SELECT ".$title." as title, ".$message." as body, name FROM `".Constant::NOTIFICATIONS_TABLE."` WHERE id = :not_id");
        $stmt->bindParam(":not_id", $not_id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();

            $arr['icon'] = Constant::NOTIFICATION_ICON;
            //$arr['image'] = Constant::NOTIFICATION_IMAGES_PATH . $arr['name'] . ".png";

            $data = new Response("getNotificationData", Constant::OK_STATUS, "", $arr);
        }else{
            $data = new Response("getNotificationData", Constant::ERR_STATUS, "Get data by id err");
        }

        return $data;
    }

    public static function getEmailData($email_id, $lang = Constant::DEFAULT_LANG){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $subject = "subject_".$lang;
        $content = "content_".$lang;

        $stmt = $dbh->prepare("SELECT $subject as subject, $content as content, name FROM `".EmailManager::EMAILS_TABLE."` WHERE id = :email_id");
        $stmt->bindParam(":email_id", $email_id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $images = array();

            if(file_exists(EmailManager::EMAILS_PATH . $arr['name'] . "/images")){
                $tmp = scandir(EmailManager::EMAILS_PATH . $arr['name'] . "/images");
                foreach($tmp as $t){
                    if($t != "." && $t != ".."){
                        $images[] = array("path"=>EmailManager::EMAILS_PATH . $arr['name'] . "/images/" . $t, "name"=>$t);
                    }
                }
            }

            $arr['images'] = $images;
            //print_r($arr);
            $data = new Response("getEmailData", Constant::OK_STATUS, "", $arr);
        }else{
            $data = new Response("getEmailData", Constant::ERR_STATUS, "Get data by id err");
        }

        return $data;
    }

}