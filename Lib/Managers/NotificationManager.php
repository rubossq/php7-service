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

use \PDO as PDO;


class NotificationManager
{

    const BONUS_NOTIFICATION = "bonus";
    const RATE_NOTIFICATION = "rate";
    const LVL_NOTIFICATION = "getlvl";
    const ACCESS_NOTIFICATION = "access";
    const NEWS_NOTIFICATION = "news";
    const SPEED_NOTIFICATION = "speed";
    const LAST_NOTIFICATION = "last";

    // (iOS) Private key's passphrase.
    private static $passphrase = 'joashp';

    // Change the above three vriables as per your app.
    public function __construct() {
        exit('Init function is not allowed');
    }


    public static function sendNotification($not_id, $user_id=null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        $dataNotif = DataManager::getNotificationData($not_id);
        $dataMain = DataManager::getMainData($user_id);
        $dataSettings = SettingsManager::getSettingsValues($user_id);

        if($dataNotif->getStatus() == Constant::OK_STATUS && $dataMain->getStatus() == Constant::OK_STATUS && $dataSettings->getStatus() == Constant::OK_STATUS){
            $data = array_merge($dataNotif->getObject(), $dataMain->getObject());
            $settings = $dataSettings->getObject();
            if(!empty($data['registration_id']) && $settings[SettingsManager::NOTIFICATION_ACTIVE_SETTING]){

                if($data['platform'] == Constant::PLATFORM_ANDROID ){
                    if($settings[SettingsManager::NOTIFICATION_SETTING]){
                        $data['sound'] = "default";
                    }
                }else if($data['platform'] == Constant::PLATFORM_IOS ){
                    if($settings[SettingsManager::NOTIFICATION_SETTING]){
                        $data['sound'] = "default";
                    }
                }

                $response = json_decode(self::sendMobile($data));
                if($response->success){
                    $data = new Response("sendNotification", Constant::OK_STATUS);
                }else{
                    $data = new Response("sendNotification", Constant::ERR_STATUS, "Send error");
                }

            }else{
                $data = new Response("sendNotification", Constant::OK_STATUS, "Quiet mode");
            }
        }else{
            $data = new Response("sendNotification", Constant::ERR_STATUS, "Prepare data error");
        }

        return $data;

    }


    //add or refresh notifications
    public static function addNotification($user_id, $name, $stime){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $dataMain = DataManager::getMainData($user_id);
        $dataSettings = SettingsManager::getSettingsValues($user_id);
        if($dataMain->getStatus() == Constant::OK_STATUS && $dataSettings->getStatus() == Constant::OK_STATUS) {
            //$data = $dataMain->getObject();
            $settings = $dataSettings->getObject();

            if($settings[SettingsManager::NOTIFICATION_ACTIVE_SETTING]){
                $notif_id = self::getNotifId($dbh, $name);
                if($notif_id){
                    $stmt = $dbh->prepare("SELECT stime FROM `".Constant::NOTIFICATIONS_FEED_TABLE."` WHERE user_id = :user_id AND notif_id = :notif_id");
                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(":notif_id", $notif_id, PDO::PARAM_INT);
                    $stmt->execute();

                    if($stmt->rowCount() > 0){
                        $stmt = $dbh->prepare("UPDATE `".Constant::NOTIFICATIONS_FEED_TABLE."` SET stime = :stime WHERE user_id = :user_id AND notif_id = :notif_id ");
                    }else{
                        $stmt = $dbh->prepare("INSERT INTO `".Constant::NOTIFICATIONS_FEED_TABLE."` (user_id, notif_id, stime) VALUES(:user_id, :notif_id, :stime)");
                    }

                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(":notif_id", $notif_id, PDO::PARAM_INT);
                    $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    }

    public static function clearNotification($user_id, $name, $notif_id = null){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        if(!$notif_id){
            $notif_id = self::getNotifId($dbh, $name);
        }

        if($notif_id) {
            $stime = time();
            $stmt = $dbh->prepare("DELETE FROM `".Constant::NOTIFICATIONS_FEED_TABLE."` WHERE user_id = :user_id AND notif_id = :notif_id AND stime < :stime");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":notif_id", $notif_id, PDO::PARAM_INT);
            $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    public static function getNotifId(&$dbh, $name){
        $stmt = $dbh->prepare("SELECT id FROM `".Constant::NOTIFICATIONS_TABLE."` WHERE name = :name");
        $stmt->bindParam(":name", $name);

        $stmt->execute();
        $id = 0;
        if($stmt->rowCount() > 0){
            $id = $stmt->fetchColumn();
        }

        return $id;
    }

    public static function linkEntity2Notif($news_name){
        switch($news_name){
            case "bonus":
                $notif = self::BONUS_NOTIFICATION;
                break;
            case "rate":
                $notif = self::RATE_NOTIFICATION;
                break;
            case "getlvl":
                $notif = self::LVL_NOTIFICATION;
                break;
            case "accesserror":
            case "privatewarn":
                $notif = self::ACCESS_NOTIFICATION;
                break;
            default:
                $notif = self::NEWS_NOTIFICATION;
                break;
        }

        return $notif;
    }

    public static function getNotifName(&$dbh, $notif_id){
        $stmt = $dbh->prepare("SELECT name FROM `".Constant::NOTIFICATIONS_TABLE."` WHERE id = :notif_id");
        $stmt->bindParam(":notif_id", $notif_id, PDO::PARAM_INT);

        $stmt->execute();
        $name = "";
        if($stmt->rowCount() > 0){
            $name = $stmt->fetchColumn();
        }

        return $name;
    }

    // Sends Push notification for Android users
    private static function sendMobile($data) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $apiKey = HelpManager::getGCMKey($data['package_name']);

        $headers = array(
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json'
        );

        $fields = array(
            'to' => $data['registration_id'],
            'notification' => $data
        );

        return self::useCurl($url, $headers, json_encode($fields));
    }

    // Curl
    private function useCurl($url, $headers, $fields = null) {
        // Open connection
        $ch = curl_init();
        if ($url) {
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            }

            // Execute post
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }

            // Close connection
            curl_close($ch);

            return $result;
        }
    }
}
