<?php
namespace Famous\Lib\Managers;
use Famous\Core\Route;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB as DB;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Validator;
use Famous\Models\Model_Common;
use Gregwar\Captcha\CaptchaBuilder;
use \PDO as PDO;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class SecureManager
{
    const CHECK_VERIFICATION_TIME = 300;
    const SUSPICIOUS_LIMIT = 2;           //temp 2

    const CAPTCHA_TABLE = "captchas";
    const VERIFY_TABLE = "verify";


    const AUTH_TIME = 3600;
    const AUTH_LIMIT = 10;         //temp 10

    const AUTH_TIME2 = 900;
    const AUTH_LIMIT2 = 15;         //temp 10

    const READY_TIME = 1200;           //temp 1200
    const READY_LIMIT = 100;         //temp 100

    const ORDER_LIMIT = 10;         //temp 10

    const CAPTCHA_ACTION = "captchaGenerate";
    //DO NOT CHANGE NAME OF captchaGenerate - case sensetive

    const REQ_LIMIT = 100;

    const CAPTCHA_LIMIT = 10;          //temp 5
    const CAPTCHA_TIME = 300;
    const CAPTCHA_LIVE_TIME = 90;

    const VERIFY_LIMIT = 20;             //temp 10
    const VERIFY_TIME = 300;

    const VERIFY_EXPIRE = 1200;  //600
    const VERIFY_DEMON_TIME = 300;   //60
    const VERIFY_REQUIRED = 600;       //300


    const VERIFY_LIVE_TIME = 90;
    const IID_LIFE = 7200;

    const CRITICAL_NOT_TVERIFY_TIME = 600;     //temp 600

    //DO NOT CHANGE NAME OF captchaGenerate - case sensetive
    public static function captchaGenerate($user_id = null, &$dbh = null, $needPreauth = false){

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }
        $key = rand(1000, 9999);

        $ip = Helper::getIp();
        $ctime = time() - self::CAPTCHA_TIME;
        $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".self::CAPTCHA_TABLE."` WHERE ip = :ip AND ctime > :ctime");
        $stmt->bindParam(":ctime", $ctime, PDO::PARAM_INT);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $cnt = $stmt->fetchColumn();
            if($cnt >= self::CAPTCHA_LIMIT){
                $data = new Response("captchaGenerate", Constant::ERR_STATUS, "Captcha limit");
                return $data;
            }
        }

        if($needPreauth){
            $_REQUEST['captcha'] = Constant::ERR_STATUS;
            Manager::$user->setPreauth($_REQUEST);
            SessionManager::updateUser(Manager::$user);
        }

        $ctime = time();

        $stmt = $dbh->prepare("INSERT INTO `".self::CAPTCHA_TABLE."` (ctime, captcha, ip, nick) VALUES (:ctime, :captcha, :ip, :nick) ");
        $stmt->bindParam(":ctime", $ctime, PDO::PARAM_INT);
        $stmt->bindParam(":captcha", $key, PDO::PARAM_INT);
        $stmt->bindParam(":ip", $ip);
        $nick = Validator::clear($_REQUEST['login']);;
        $stmt->bindParam(":nick", $nick);
        $stmt->execute();

        $id = $dbh->lastInsertId();

        $captcha = new CaptchaBuilder();
        $captcha->setPhrase($key);
        $captcha->build()->save(Constant::CAPTCHA_PATH . "c_".$id.'.jpg');

        $data = new Response("captchaGenerate", Constant::OK_STATUS, "", array("hash"=>$id, "captcha"=> Route::getUrl() . "/" . Constant::CAPTCHA_PATH . "c_".$id.'.jpg'));

        return $data;
    }

    public static function checkCaptcha($user_id = null, &$dbh = null){


        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }
        $data = new Response("checkCaptcha", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['captcha']) && isset($_REQUEST['hash'])) {

            $captcha = Validator::clear($_REQUEST['captcha']);
            $hash =  Validator::clear($_REQUEST['hash']);

            $_REQUEST = Manager::$user->getPreauth();

            $stmt = $dbh->prepare("SELECT ip FROM `".self::CAPTCHA_TABLE."` WHERE ctime > :ctime AND id = :id AND captcha = :captcha AND nick = :nick");
            $ctime = time() - self::CAPTCHA_LIVE_TIME;
            $stmt->bindParam(":ctime", $ctime, PDO::PARAM_INT);
            $stmt->bindParam(":captcha", $captcha, PDO::PARAM_INT);
            $nick = Validator::clear($_REQUEST['login']);
            $stmt->bindParam(":nick", $nick);
            $stmt->bindParam(":id", $hash, PDO::PARAM_INT);
            $stmt->execute();

            //echo "SELECT ip FROM `".self::CAPTCHA_TABLE."` WHERE ctime > $ctime AND id = '$hash' AND captcha = '$captcha' AND nick = '$nick'";

            if($stmt->rowCount() > 0){
                if(!$user_id){
                    $_REQUEST['captcha'] = Constant::OK_STATUS;
                    Manager::$user->setPreauth($_REQUEST);
                    $mc = new Model_Common();
                    $data = $mc->auth();
                }
            }else{
                $_REQUEST['captcha'] = Constant::ERR_STATUS;
                Manager::$user->setPreauth($_REQUEST);
                $data = self::captchaGenerate();
            }

        }

        return $data;
    }

    public static function isVerify(){
        //disable verifying
        /*if(!Manager::$user->isVerify() && Constant::CURRENT_MODE != Constant::DEBUG_MODE){
            $data = new Response("Method", Constant::ERR_STATUS, "Auth error");
            return $data;
        }*/
        return null;
    }

    public static function verify(&$dbh = null, $user_id = null){

        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        if(!$dbh){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
        }

        $data = new Response("tryVerify", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['password']) && isset($_REQUEST['hash'])) {
            $password = Validator::clear($_REQUEST['password']);
            $hash = Validator::clear($_REQUEST['hash']);

            $stmt = $dbh->prepare("SELECT vtime FROM `".self::VERIFY_TABLE."` WHERE password = :password AND id = :id AND vtime > :vtime");
            $vtime = time() - self::VERIFY_LIVE_TIME;
            $stmt->bindParam(":vtime", $vtime, PDO::PARAM_INT);
            $stmt->bindParam(":id", $hash);
            $stmt->bindParam(":password", $password);
            $stmt->execute();

            if($stmt->rowCount() > 0){
                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET vtime = :vtime WHERE user_id = :user_id");
                $vtime = time();
                $stmt->bindParam(":vtime", $vtime, PDO::PARAM_INT);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();

                Manager::$user->setVtime($vtime);
                SessionManager::updateUser(Manager::$user);

                $data = new Response("verify", Constant::OK_STATUS);
            }else{
                $data = self::tryVerify();
            }
        }

        return $data;
    }

    public static function tryVerify(&$dbh = null, $user_id = null){

        if(Manager::$user->getVtime() <= time() - self::VERIFY_REQUIRED){
            if(!$dbh){
                $db = DB::getInstance();
                $dbh = $db->getDBH();
            }
            if(!$user_id){
                $user_id = Manager::$user->getId();
            }

            $data = new Response("tryVerify", Constant::ERR_STATUS, "No depth value");

            $vtime = time() - self::VERIFY_TIME;
            $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".self::VERIFY_TABLE."` WHERE user_id = :user_id AND vtime > :vtime");
            $stmt->bindParam(":vtime", $vtime, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $cnt = $stmt->fetchColumn();
                if($cnt >= self::VERIFY_LIMIT){
                    $data = new Response("tryVerify", Constant::ERR_STATUS, "Verify limit");
                    return $data;
                }
            }

            $stmt = $dbh->prepare("SELECT login, iid FROM `".Constant::DATA_TABLE."` WHERE iid != '' AND atime > :atime ORDER BY RAND() LIMIT 1");
            $atime = time() - self::IID_LIFE;
            $stmt->bindParam(":atime", $atime, PDO::PARAM_INT);
            $stmt->execute();

            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();
                $nick = $arr['login'];
                $password = $arr['iid'];
                $vtime = time();
                $stmt = $dbh->prepare("INSERT INTO `".self::VERIFY_TABLE."`(nick, password, vtime, user_id) VALUES(:nick, :password, :vtime, :user_id)");
                $stmt->bindParam(":vtime", $vtime, PDO::PARAM_INT);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":nick", $nick);
                $stmt->bindParam(":password", $password);
                if($stmt->execute()){
                    $hash = $dbh->lastInsertId();
                    $data = new Response("tryVerify", Constant::OK_STATUS, "", array("hash"=>$hash, "target"=>$nick));
                }else{
                    $data = new Response("tryVerify", Constant::ERR_STATUS, "Create quest err");
                }
            }
        }else{
            $data = new Response("tryVerify", Constant::ERR_STATUS, "Try later");
        }


        return $data;
    }
}