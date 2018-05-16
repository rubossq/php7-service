<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-10-11
 * Time: 21:08
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Manager;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\Helper;
use \PDO as PDO;

class SettingsManager
{

    const SUBSCRIBE_SETTING = "subscribe";
    const DEFAULT_SUBSCRIBE_VALUE = 0;

    const AUTOEARN_SETTING = "autoearn";
    const DEFAULT_AUTOEARN_VALUE = 1;

    const FIRSTTIME_SETTING = "firsttime";
    const DEFAULT_FIRSTTIME_VALUE = 1;

    const NOTIFICATION_SETTING = "notifvolume";
    const DEFAULT_NOTIFICATION_VALUE = 1;

    const NOTIFICATION_ACTIVE_SETTING = "notifactivate";
    const DEFAULT_NOTIFICATION_ACTIVE_VALUE = 1;

    const AUTO_TASK_SETTING = "autotaskmode";
    const AUTO_TASK_VALUE = 0;

    const AUTO_TASK_LIMIT_SETTING = "autotasklimit";
    const AUTO_TASK_LIMIT_VALUE = 0;

    const RATE_ALERT_SETTING = "ratealert";
    const RATE_ALERT_VALUE = 0;

    public static function setSetting($name, $value, $user_id=null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        $settings = self::getDefaultSettingsValues();
        if(key_exists($name, $settings)){
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $stmt = $dbh->prepare("SELECT id FROM `".Constant::SETTINGS_TABLE."` WHERE name = :name AND user_id = :user_id");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":name", $name);
            $stmt->execute();

            if($stmt->rowCount() > 0){
                $stmt = $dbh->prepare("UPDATE `".Constant::SETTINGS_TABLE."` SET value = :value WHERE name = :name AND user_id = :user_id");
            }else{
                $stmt = $dbh->prepare("INSERT INTO `".Constant::SETTINGS_TABLE."` (value, name, user_id) VALUES (:value, :name, :user_id)");
            }

            $stmt->bindParam(":value", $value, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":name", $name);
            $stmt->execute();
        }

        $data = new Response("setSetting", Constant::OK_STATUS);

        return $data;
    }

    public static function initSettings($user_id = null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        $settings = self::getDefaultSettingsValues();

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        foreach($settings as $name => $value){
            $stmt = $dbh->prepare("INSERT INTO `".Constant::SETTINGS_TABLE."`  (value, name, user_id) VALUES (:value, :name, :user_id)");
            $stmt->bindParam(":value", $value, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":name", $name);
            $stmt->execute();
        }
    }

    public static function getDefaultSettingsValues(){
        return [self::SUBSCRIBE_SETTING => self::DEFAULT_SUBSCRIBE_VALUE,
                self::AUTOEARN_SETTING => self::DEFAULT_AUTOEARN_VALUE,
                self::FIRSTTIME_SETTING => self::DEFAULT_FIRSTTIME_VALUE,
                self::NOTIFICATION_SETTING => self::DEFAULT_NOTIFICATION_VALUE,
                self::NOTIFICATION_ACTIVE_SETTING => self::DEFAULT_NOTIFICATION_ACTIVE_VALUE,
                self::AUTO_TASK_SETTING => self::AUTO_TASK_VALUE,
                self::AUTO_TASK_LIMIT_SETTING => self::AUTO_TASK_LIMIT_VALUE,
                self::RATE_ALERT_SETTING => self::RATE_ALERT_VALUE];
    }

    public static function getSettingsValues($user_id = null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        $settings = array();

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT name, value FROM `".Constant::SETTINGS_TABLE."` WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();

            foreach($data as $arr){
                $settings[$arr['name']] = $arr['value'];
            }

            $settings = Helper::mergeFill($settings, self::getDefaultSettingsValues());
            $data = new Response("getSettingsValues", Constant::OK_STATUS, "", $settings);
        }else{
            $data = new Response("getSettingsValues", Constant::OK_STATUS, "", self::getDefaultSettingsValues());
        }

        return $data;
    }
}
