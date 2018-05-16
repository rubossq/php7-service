<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class NewsManager
{
    public static function addNewsAuth(&$db, $user_id){
        $time_autotasks = time() + 1;
        $time_bonus = time() + 1;
        $time_referal = time() + 1;
        $time_rate = time() + 3600;
        $time_private = time() + 10800;
        $time_atnight = time() + 21600;
        $time_noadv = time() + 43200;
        $time_getturbo = time() + 86400;
        $time_prioritybal = time() + 160000;

        $dbh = $db->getDBH();
        $params = "diamonds=".Constant::DAILY_BONUS;
        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time, params) VALUES ($user_id, 1, $time_bonus, '$params')");
        $stmt->execute();
        NotificationManager::addNotification($user_id, NotificationManager::BONUS_NOTIFICATION, $time_bonus);


        $params = "diamonds=".Constant::RATE_BONUS;
        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time, params) VALUES ($user_id, 2, $time_rate, '$params')");
        $stmt->execute();
        NotificationManager::addNotification($user_id, NotificationManager::RATE_NOTIFICATION, $time_rate);

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 12, $time_autotasks)");
        $stmt->execute();

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 15, $time_referal)");
        $stmt->execute();


        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 5, $time_noadv)");
        $stmt->execute();

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 6, $time_atnight)");
        $stmt->execute();

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 7, $time_getturbo)");
        $stmt->execute();

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 8, $time_private)");
        $stmt->execute();

        $stmt = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time) VALUES ($user_id, 11, $time_prioritybal)");
        $stmt->execute();

        NotificationManager::addNotification($user_id, NotificationManager::NEWS_NOTIFICATION, min(array($time_noadv, $time_atnight, $time_getturbo, $time_private, $time_prioritybal)));
    }

    public static function completeFeed($user_id, $value, $name, $id){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT id, type, rtime, name, can_complete FROM `".Constant::NEWS_TABLE."` WHERE name = :name");
        $stmt->bindParam(":name", $name);
        if($stmt->execute()){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();

            $isComplete = $arr['type'] == Constant::MULTIPLE_TYPE && $arr['can_complete'] == 0 ? 0 : 1;
            $time = time() + $arr['rtime'];

            $notification = NotificationManager::linkEntity2Notif($name);

            if(!$isComplete){
                $stmt = $dbh->prepare("UPDATE `".Constant::FEEDS_TABLE."` SET fire_time = :time, is_watched = 0 WHERE id = :id AND user_id = :user_id");
                NotificationManager::addNotification($user_id, $notification, $time);
            }
            else {
                if(!$value){
                    NotificationManager::addNotification($user_id, $notification, $time);
                }
                $stmt = $dbh->prepare("UPDATE `" . Constant::FEEDS_TABLE . "` SET fire_time = :time, is_complete = :value, is_watched = 0 WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(":value", $value, PDO::PARAM_INT);
            }
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":time", $time);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if($stmt->rowCount() > 0){

                if($notification == NotificationManager::NEWS_NOTIFICATION && $isComplete && $value){
                    self::checkNewsNotifications($user_id, $notification, $dbh);
                }

                $stmt = $dbh->prepare("SELECT params FROM `".Constant::FEEDS_TABLE."` WHERE id = :id");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                if($stmt->rowCount() > 0){
                    $params = $stmt->fetchColumn();
                    if(self::makeParams($params, $dbh)){
                        $data = new Response("completeFeed", Constant::OK_STATUS);
                    }else{
                        $data = new Response("completeFeed", Constant::ERR_STATUS, "Params error");
                    }
                }
            }else{
                $data = new Response("completeFeed", Constant::ERR_STATUS, "Set error");
            }

        }else{
            $data = new Response("completeFeed", Constant::ERR_STATUS, "Params error");
        }

        return $data;
    }

    public static function makeParams($params, &$dbh){
        $params = trim($params);
        parse_str($params, $arr);
        foreach($arr as $param => $val){
            if($val){
                switch($param) {
                    case "box":
                    case "prize":
                    case "diamonds":
                        CashManager::deposit(intval($val));
                        BalanceManager::depositSum(rand(0, 1) ? Constant::LIKE_TYPE : Constant::SUBSCRIBE_TYPE, intval($val), Manager::$user->getId());
                        break;
                    case "unbalanced_diamonds":
                        CashManager::deposit(intval($val));
                        break;
                    case "premium":
                        HelpManager::setPremium($dbh, Manager::$user->getId(), intval($val));
                        Manager::$user->setPremium(intval($val));
                        SessionManager::updateUser(Manager::$user);
                        break;
                    case "turbo":
                        $turbo = HelpManager::getTurbo($val);
                        $priority = HelpManager::getTurbo($val);
                        if (Manager::$user->getTurbo() == 0) {

                            HelpManager::setTurbo($dbh, Manager::$user->getId(), $val);

                            Manager::$user->setTurbo($turbo);
                            Manager::$user->setPriority($priority);
                            SessionManager::updateUser(Manager::$user);
                            HelpManager::updateTablesPriority();
                        }
                        break;
                    case "achieve":
                        HelpManager::setAchieve(Manager::$user->getId(), intval($val));
                        break;
                }
            }
        }

        return true;
        //"diamonds=$diamonds;box=$box;premium=$premium;turbo_lite=$turbo_lite;lvl=$lvl;achieve=$achieve"
    }

    public static function refreshNews($news_id, $user_id, &$dbh, $params = null, $isComplete){
        $stmt = $dbh->prepare("SELECT id FROM `".Constant::FEEDS_TABLE."` WHERE user_id = :user_id AND news_id = :news_id AND is_complete = :is_complete");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
        $stmt->bindParam(":is_complete", $isComplete, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $id = $stmt->fetchColumn();
            $ftime = time() - 1;
            if($params){
                $stmt = $dbh->prepare("UPDATE `".Constant::FEEDS_TABLE."` SET is_complete = 0, is_watched = 0, fire_time = :ftime, params = :params WHERE id = :id");
                $stmt->bindParam(":params", $params);
            }else{
                $stmt = $dbh->prepare("UPDATE `".Constant::FEEDS_TABLE."` SET is_complete = 0, is_watched = 0, fire_time = :ftime WHERE id = :id");
            }
            $stmt->bindParam(":id" , $id, PDO::PARAM_INT);
            $stmt->bindParam(":ftime" , $ftime, PDO::PARAM_INT);

            $stmt->execute();
        }
    }

    public static function refreshOrAddNews($news_id, $user_id, &$dbh, $cancelNotif = false, $params = null, $isComplete=1){
        $stmt = $dbh->prepare("SELECT id FROM `".Constant::FEEDS_TABLE."` WHERE news_id = :news_id AND user_id = :user_id");
        $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();
        $ftime = time() - 1;
        if($stmt->rowCount() > 0){
            self::refreshNews($news_id, $user_id, $dbh, $params, $isComplete);
        }else{
            if($params){
                $stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time, params) VALUES(:user_id, :news_id, :ftime, :params)");
                $stmt->bindParam(":params", $params);
            }else{
                $stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time) VALUES(:user_id, :news_id, :ftime)");
            }

            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
            $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);

            $stmt->execute();
        }

        if(!$cancelNotif){
            $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::ACCESS_NOTIFICATION);
            NotificationManager::sendNotification($notif_id, $user_id);
        }
    }

    public static function checkNewsNotifications($user_id, $name, &$dbh){
        $stmt = $dbh->prepare("SELECT f.fire_time FROM `".Constant::FEEDS_TABLE."` f, `".Constant::NEWS_TABLE."` n
                               WHERE f.news_id = n.id AND f.is_watched = 0 AND f.is_complete = 0 AND f.fire_time >= :ftime
                               AND n.name != :bonus AND n.name != :rate AND n.name != :access AND n.name != :private AND n.name != :lvl AND f.user_id = :user_id
                               ORDER BY f.fire_time LIMIT 1");
        $ftime = time();
        $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $bonus = "bonus";
        $rate = "rate";
        $accesserror = "accesserror";
        $privatewarn = "privatewarn";
        $getlvl = "getlvl";

        $stmt->bindParam(":bonus", $bonus);
        $stmt->bindParam(":rate", $rate);
        $stmt->bindParam(":access", $accesserror);
        $stmt->bindParam(":private", $privatewarn);
        $stmt->bindParam(":lvl", $getlvl);

        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stime = $stmt->fetchColumn();
            NotificationManager::addNotification($user_id, NotificationManager::NEWS_NOTIFICATION, $stime);
        }else{
            NotificationManager::clearNotification($user_id, $name);
        }
    }


}