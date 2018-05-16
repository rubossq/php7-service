<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Managers\CashManager;
use Famous\Lib\Managers\LiqpayManager;
use Famous\Lib\Managers\NewsManager;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\PaymentwallManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Config as Config;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
use \PDOException as PDOException;
use ReceiptValidator\GooglePlay\Validator as Validator;
use ReceiptValidator\iTunes\Validator as iTunesValidator;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Managers\TableManager as TableManager;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:58
 */
class Model_Worker extends Model
{
    public function optimizeTasks(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $this->clearTasks(Constant::LIKE_TYPE, $dbh);
        $this->clearTasks(Constant::SUBSCRIBE_TYPE, $dbh);

        $data = new Response("clearTasks", Constant::OK_STATUS);
        return $data;
    }

    private function clearTasks($type, &$dbh){

        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $time = TableManager::getExpireTime($type);

        $del_time = time() - $time;
        $ids = null;
        $price = CashManager::getTaskPriceBid($type);

        $stmt = $dbh->prepare("SELECT id, user_id, target_count, ready_count FROM `$info` WHERE utime < :del_time LIMIT 1000");

        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while ($arr = $stmt->fetch()) {
                $user_id = $arr['user_id'];
                $orderLeft = ($arr['target_count'] - $arr['ready_count']) * $price;
                $return = Constant::CREDIT_PERCENT * $orderLeft;
                if($return){
                    CashManager::deposit($return, $user_id);
                }
                $ids[] = $arr['id'];
            }
        }

        if($ids){
            $in = implode(",", $ids);
            $stmt = $dbh->prepare("DELETE FROM `$table` WHERE task_id IN ($in)");
            $stmt->execute();
            $stmt = $dbh->prepare("DELETE FROM `$info` WHERE id IN ($in)");
            $stmt->execute();

        }

    }

    public function optimizeOldUsers(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $last_visit = time() - 86400 * 30;
        $stmt = $dbh->prepare("SELECT user_id FROM `".Constant::DATA_TABLE."` WHERE last_visit < :last_visit AND last_visit > 0 LIMIT 1000");
        $stmt->bindParam(":last_visit", $last_visit, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();
           foreach($data as $arr){
                $user_id = $arr['user_id'];
                $stmt = $dbh->prepare("DELETE FROM `".Constant::SETTINGS_TABLE."` WHERE user_id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $dbh->prepare("DELETE FROM `".PaymentwallManager::PAY_SESSIONS_TABLE."` WHERE user_id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();

                $this->setOldTasks($user_id, Constant::LIKE_TYPE, $dbh);
                $this->setOldTasks($user_id, Constant::SUBSCRIBE_TYPE, $dbh);

                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` set last_visit = 0 WHERE user_id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $data = new Response("optimizeOldUsers", Constant::OK_STATUS);
        return $data;
    }

    private function setOldTasks($user_id, $type, &$dbh){
        $info = TableManager::getInfoTable($type);
        $time = TableManager::getExpireTime($type);
        $utime = time() - $time;
        $stmt = $dbh->prepare("UPDATE `".$info."` SET utime = :utime WHERE user_id = :user_id");
        $stmt->bindParam(":utime", $utime, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function optimizeErrors(){
        $db = DB::getInstance();
        $del_time = time() - 86400 * 14;
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("DELETE FROM `".Constant::ERRORS_TABLE."` WHERE etime < :del_time");
        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        $data = new Response("optimizeErrors", Constant::OK_STATUS);
        return $data;
    }

    public function optimizeNews(){
        $db = DB::getInstance();
        $del_time = time() - 86400 * 14;
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("DELETE FROM `".Constant::FEEDS_TABLE."` WHERE fire_time < :del_time");
        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        $data = new Response("optimizeNews", Constant::OK_STATUS);
        return $data;
    }

    public function optimizeNotifications(){
        $db = DB::getInstance();
        $del_time = time() - 86400 * 7;
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("DELETE FROM `".Constant::NOTIFICATIONS_FEED_TABLE."` WHERE stime < :del_time");
        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        $data = new Response("optimizeNotifications", Constant::OK_STATUS);
        return $data;
    }

    public function optimizeCaptchas(){
        $db = DB::getInstance();
        $del_time = time() - 86400 * 7;
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("DELETE FROM `".SecureManager::CAPTCHA_TABLE."` WHERE ctime < :del_time");
        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        $data = new Response("optimizeCaptchas", Constant::OK_STATUS);
        return $data;
    }

    public function optimizeVerify(){
        $db = DB::getInstance();
        $del_time = time() - 86400 * 7;
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("DELETE FROM `".SecureManager::VERIFY_TABLE."` WHERE vtime < :del_time");
        $stmt->bindParam(":del_time", $del_time, PDO::PARAM_INT);
        $stmt->execute();
        $data = new Response("optimizeVerify", Constant::OK_STATUS);
        return $data;
    }

    private function checkPlaySubscribe($productId, $purchaseToken, $packageName){

        $validator = HelpManager::getValidatorByPackage($packageName);

        $validator->setPurchaseType(Validator::TYPE_SUBSCRIPTION);

        try {
            $response = $validator->setPackageName($packageName)
                ->setProductId($productId)
                ->setPurchaseToken($purchaseToken)
                ->validate();
        } catch (Exception $e){
            $data = new Response("verify", Constant::ERR_STATUS, "Can not take data");
        }

        if($response){
            $startTime = (int)($response->startTimeMillis / 1000);
            $expiryTime = (int)($response->expiryTimeMillis / 1000);
            $data = new Response("verify", Constant::OK_STATUS, "", array("start_time"=>$startTime, "expiry_time"=>$expiryTime));
        }

        return $data;
    }

    private function checkStoreSubscribe($productId, $purchaseToken, $packageName){

        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);        //ENDPOINT_PRODUCTION ENDPOINT_SANDBOX

        $data = new Response("verify", Constant::ERR_STATUS, "Something went wrong");
        try {
            echo $purchaseToken;
            $validator->setReceiptData($purchaseToken);
            $validator->setSharedSecret(Constant::IOS_ITUNES_SECRET);
            $response = $validator->validate();
            if($response){
                if ($response->isValid()) {
                    $receipt = $response->getReceipt();
                    $startTime = (int)($receipt['purchase_date_ms'] / 1000);
                    $expiryTime = (int)($receipt['expires_date'] / 1000);
                    $data = new Response("verify", Constant::OK_STATUS, "", array("start_time"=>$startTime, "expiry_time"=>$expiryTime));
                }
            }
        } catch (Exception $e) {
            $data = new Response("verify", Constant::ERR_STATUS, "Can not take data");
        }

        return $data;
    }



    public function checkSubscribes(){
        $db = DB::getInstance();
        $time = time();
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT id, product_id, purchase_token, status, package_name, platform, order_id FROM `".Constant::SUBSCRIBES_TABLE."` WHERE expiry_time <= :time AND (status = :status_ok OR status = :status_free)");
        $stmt->bindParam(":time", $time, PDO::PARAM_INT);

        $status_free = Constant::OK_FREE_SUBSCRIBE_STATUS;
        $status_ok = Constant::OK_SUBSCRIBE_STATUS;
        $stmt->bindParam(":status_ok", $status_ok, PDO::PARAM_INT);
        $stmt->bindParam(":status_free", $status_free, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = new Response("verify", Constant::ERR_STATUS, "Start checker");
            $res = $stmt->fetchAll();
            foreach($res as $arr){
                $id = $arr['id'];
                if($arr['status'] == Constant::OK_SUBSCRIBE_STATUS){
                    if($arr['platform'] == Constant::PLATFORM_ANDROID){
                        $data = $this->checkPlaySubscribe($arr['product_id'], $arr['purchase_token'], $arr['package_name']);
                    }
                    if($arr['platform'] == Constant::PLATFORM_IOS){
                        $data = $this->checkStoreSubscribe($arr['product_id'], $arr['order_id'], $arr['package_name']);
                    }
                    if($data->getStatus() == Constant::OK_STATUS){
                        $expiry_time = $data->getObject()['expiry_time'];
                        $start_time = $data->getObject()['start_time'];
                        $diff = $expiry_time - $time;
                        if($diff > 0){
                            $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET expiry_time = $expiry_time, start_time = $start_time, expiry_cnt = 0 WHERE id = $id");
                            $stmt->execute();
                        }else{
                            $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET expiry_cnt = expiry_cnt + 1 WHERE id = $id");
                            $stmt->execute();
                        }
                        $data = new Response("verify", Constant::OK_STATUS, "Ok time subscribe");
                    }else{
                        $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET expiry_cnt = expiry_cnt + 1 WHERE id = $id");
                        $stmt->execute();
                        $data = new Response("verify", Constant::ERR_STATUS, "Play market data Err");
                    }
                }else if($arr['status'] == Constant::OK_FREE_SUBSCRIBE_STATUS){
                    $expiry_cnt = Constant::EXPIRY_LIMIT + 1;
                    $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET expiry_cnt = $expiry_cnt WHERE id = $id");
                    $stmt->execute();
                    $data = new Response("verify", Constant::OK_STATUS, "Ok time free subscribe");
                }
            }
        }else{
            $data = new Response("checkSubscribes", Constant::OK_STATUS, "Subscribes not found");
        }

        $this->refreshSubscribes($dbh);

        return $data;
    }

    private function refreshSubscribes(&$dbh){
        $stmt = $dbh->prepare("SELECT id, product_id, user_id FROM `".Constant::SUBSCRIBES_TABLE."` WHERE (status = :status_ok OR status = :status_free) AND expiry_cnt > " . Constant::EXPIRY_LIMIT);

        $status_free = Constant::OK_FREE_SUBSCRIBE_STATUS;
        $status_ok = Constant::OK_SUBSCRIBE_STATUS;
        $stmt->bindParam(":status_ok", $status_ok, PDO::PARAM_INT);
        $stmt->bindParam(":status_free", $status_free, PDO::PARAM_INT);

        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $res = $stmt->fetchAll();
            foreach($res as $arr){
                $turbo = HelpManager::getTurbo($arr['product_id']);
                $user_id = $arr['user_id'];
                $id = $arr['id'];

                $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET turbo = 0, priority = 0 WHERE id = $user_id AND turbo = $turbo");
                $stmt->execute();

                $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET status = :status_finish WHERE $id = id");
                $status_finish = Constant::FINISHED_SUBSCRIBE_STATUS;
                $stmt->bindParam(":status_finish", $status_finish, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    // no need for autorenewal subscribes
    public function checkPWSubscribes(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, product_id, user_id FROM `".PaymentwallManager::SUBSCRIBES_TABLE."` WHERE status = :status AND expiry_time <= :expiry_time");

        $status = PaymentwallManager::CANCEL_STATUS;
        $expiry_time = time();

        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":expiry_time", $expiry_time, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $res = $stmt->fetchAll();
            foreach($res as $arr){
                $turbo = HelpManager::getTurbo($arr['product_id']);
                $user_id = $arr['user_id'];
                $id = $arr['id'];


                $stmt = $dbh->prepare("UPDATE `".PaymentwallManager::SUBSCRIBES_TABLE."` SET status = :status WHERE $id = id");
                $status = PaymentwallManager::BAD_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $stmt->execute();

                PaymentwallManager::checkTurbos($dbh, $user_id, $expiry_time, $turbo);
            }
        }

        $data = new Response("checkPWSubscribes", Constant::OK_STATUS);
        return $data;
    }

    public function grantTops(){
        $db = DB::getInstance();
        $tdate = date("Y-m-d", strtotime("yesterday"));
        $dbh = $db->getDBH();
        $stmt = $dbh->prepare("SELECT user_id FROM `".Constant::TOPS_TABLE."` WHERE tdate = '$tdate' ORDER BY count DESC LIMIT :limit");
        $limit = Constant::TOP_LIMIT;
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $data = $stmt->fetchAll();
            $place = 1;
            $prizes = $this->getTopPrizes();
            foreach($data as $arr){
                $user_id = $arr['user_id'];
                $params = "place=".$place."&prize=".$prizes[$place-1];
                $time = time();
                $stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time, params) VALUES (:user_id, 3, :time, :params)");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":time", $time, PDO::PARAM_INT);
                $stmt->bindParam(":params", $params);
                $stmt->execute();
                $place++;
            }
        }

        $tdate = date("Y-m-d");
        $stmt = $dbh->prepare("DELETE FROM `".Constant::TOPS_TABLE."` WHERE tdate < '$tdate'");
        $stmt->execute();

        $data = new Response("grantTops", Constant::OK_STATUS);
        return $data;
    }

    public function checkFrozen(){
        $res = exec(Config::PHANTOM_PATH . " --web-security=no " . Constant::PHANTOM_PATH . "checker.js > /dev/null 2>/dev/null &");
        $data = new Response("checkFrozen", Constant::OK_STATUS);
        return $data;
    }

    private function getTopPrizes(){
        $prizes = array(500, 400, 300, 200, 100);
        return $prizes;
    }

    public function priorityBalance(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT u.id FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.user_id = u.id
                               AND u.priority = :priority AND (u.turbo = 0 OR u.turbo = 4)
                               AND d.last_visit < :last_visit");

        $last_visit = time() - (Constant::NEWS_DEMON_TIME + Constant::HANG_TIME);

        $priority = HelpManager::getPriority("turbo_balance");
        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
        $stmt->bindParam(":last_visit", $last_visit, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();
            foreach($data as $arr){
                $user_id = $arr['id'];
                $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET priority = 0
                               WHERE id = :user_id AND priority = :priority AND (turbo = 0 OR turbo = 4)");

                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $p = 0;
                    HelpManager::updateTablesPriority($user_id, $p, $dbh);
                }
            }
        }

        $data = new Response("checkPremiumBalance", Constant::OK_STATUS);
        return $data;
    }

    public function sendNotifications(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT notif_id, user_id FROM `".Constant::NOTIFICATIONS_FEED_TABLE."` WHERE stime < :stime GROUP BY user_id");
        $stime = time();
        $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while($arr = $stmt->fetch()){
                NotificationManager::sendNotification($arr['notif_id'], $arr['user_id']);
                NotificationManager::clearNotification($arr['user_id'], null, $arr['notif_id']);
            }
        }

        $data = new Response("sendNotifications", Constant::OK_STATUS);
        return $data;
    }

    public function saveLast(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT user_id FROM `".Constant::DATA_TABLE."` WHERE last_visit < :last_visit AND last_visit > 0 LIMIT 1000");
        $last_visit = time() - Constant::LAST_VISIT_AGRESSION_TIME;
        $stmt->bindParam(":last_visit", $last_visit, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();
            foreach($dt as $arr){
                $user_id = $arr['user_id'];
                $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` set last_visit = 0 WHERE user_id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::LAST_NOTIFICATION);
                NotificationManager::sendNotification($notif_id, $user_id);
            }
        }

        $data = new Response("referalPay", Constant::OK_STATUS);
        return $data;

    }

    public function sendVIPInvites(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        self::sendVIPByTable($dbh, Constant::PURCHASES_TABLE);
        self::sendVIPByTable($dbh, Constant::SUBSCRIBES_TABLE);

        self::sendVIPByTable($dbh, PaymentwallManager::PURCHASES_TABLE);
        self::sendVIPByTable($dbh, PaymentwallManager::SUBSCRIBES_TABLE);

        self::sendVIPByTable($dbh, LiqpayManager::PURCHASES_TABLE);
        self::sendVIPByTable($dbh, LiqpayManager::SUBSCRIBES_TABLE);

        $data = new Response("sendVIPInvite", Constant::OK_STATUS);
        return $data;
    }

    private function sendVIPByTable(&$dbh, $table){
        $stmt = $dbh->prepare("SELECT user_id FROM `".$table."` WHERE purchase_time >= :ptime GROUP BY user_id");
        $ptime = time() - 3600; //last hour + 100 sec
        $stmt->bindParam(":ptime", $ptime, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();
            foreach($data as $arr){
                $user_id = $arr['user_id'];
                $news_id = 13;
                $params = "etime=".(time()+86400);

                NewsManager::refreshOrAddNews($news_id, $user_id, $dbh, true, $params);
                $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::NEWS_NOTIFICATION);
                NotificationManager::sendNotification($notif_id, $user_id);
            }
        }
    }


    public function clearVIPInvites(){

        $db = DB::getInstance();
        $dbh = $db->getDBH();


        $stmt = $dbh->prepare("SELECT id FROM `".Constant::FEEDS_TABLE."` WHERE news_id = :news_id AND fire_time <= :ftime AND is_complete = 0");
        $ftime = time() - 86400; //last day
        $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);
        $news_id = 13;
        $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
        $stmt->execute();


        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();
            foreach($data as $arr){
                $id = $arr['id'];
                $stmt = $dbh->prepare("UPDATE `".Constant::FEEDS_TABLE."` SET is_complete = 1 WHERE id = :id");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
             }
        }

        $data = new Response("clearVIPInvites", Constant::OK_STATUS);
        return $data;
    }

    public function veryUsersService(){
        $res = exec(Config::PHANTOM_PATH . " --web-security=no " . Constant::PHANTOM_PATH . "autotask/userParser.js");
        $data = new Response("veryUsersService", Constant::OK_STATUS);
        return $data;
    }

    public function referalPay(){
        return ReferalManager::referalPay();
    }

    //TEMP FUNCTION FROM 03-10-2016
    public function resetTasks(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $this->resetTasksByType($dbh, Constant::LIKE_TYPE);
        $this->resetTasksByType($dbh, Constant::SUBSCRIBE_TYPE);

        $data = new Response("resetTasks", Constant::OK_STATUS);
        return $data;
    }

    //TEMP FUNCTION FROM 03-10-2016
    public function resetTasksByType(&$dbh, $type){
        $info = TableManager::getInfoTable($type);
        $table = TableManager::getTaskTable($type);
        $stmt = $dbh->prepare("SELECT u.priority, i.id FROM `".$info."` i, `".Constant::USERS_TABLE."` u
                               WHERE i.user_id = u.id AND i.status = :status AND i.target_count > i.ready_count AND i.target_count > 0");
        $status = Constant::ACTIVE_TASK_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();
            foreach($data as $arr){
                $priority = $arr['priority'];
                $id = $arr['id'];

                try{
                    $stmt = $dbh->prepare("INSERT INTO `$table`  (task_id, priority) VALUES(:task_id, :priority)");
                    $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                    $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
                    $stmt->execute();
                }catch(PDOException $e){

                }
            }
        }
    }
}