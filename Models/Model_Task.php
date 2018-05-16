<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Task as Task;
use Famous\Lib\Common\Quest as Quest;
use Famous\Lib\Common\XpInfo as XpInfo;
use Famous\Lib\Managers\CashManager as CashManager;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Managers\SessionManager as SessionManager;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Managers\TableManager as TableManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:52
 */
class Model_Task extends Model
{
    public function bid(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}


            //if we have any error
            $data = new Response("bid", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['type']) && isset($_REQUEST['id']) && isset($_REQUEST['meta']) && isset($_REQUEST['head']) && isset($_REQUEST['target_count']) && isset($_REQUEST['real_id'])) {

                $type = Validator::clear($_REQUEST['type']);
                $id =  Validator::clear($_REQUEST['id']);
                $head =  Validator::clear($_REQUEST['head']);
                $meta =  Validator::clear($_REQUEST['meta']);
                $real_id = Validator::clear($_REQUEST['real_id']);


                //SecureManager::checkTVerify();

                $meta = base64_encode( mb_substr($meta, 0, Constant::MAX_META_LENGTH, "UTF-8"));			//need only for likes need to add function for each type?
                $target_count =  Validator::clear($_REQUEST['target_count']);

                $data = $this->makeBid($type, $id, $head, $meta, $real_id, $target_count);

            }
        }else{
            $data = new Response("bid", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function bidList(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            //if we have any error
            $data = new Response("bidList", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['bid_info'])) {

                $bid_infol = trim($_REQUEST['bid_info']);
                $bid_info = json_decode($bid_infol);

                $totalPrice = 0;

                for($i=0; $i<count($bid_info); $i++){
                    $price = CashManager::getTaskPriceBid($bid_info[$i]->type);
                    $order = $price * $bid_info[$i]->target_count;
                    $totalPrice += $order;
                    $bid_info[$i]->meta = base64_encode( mb_substr($bid_info[$i]->meta, 0, Constant::MAX_META_LENGTH, "UTF-8"));
                }

                $cashData = CashManager::getCash();
                if($cashData->getStatus() == Constant::OK_STATUS){
                    $obj = $cashData->getObject();
                    $freeSum = $obj['cash']['deposit'];
                    if($freeSum >= $totalPrice){

                        $goodFull = true;
                        for($i=0; $i<count($bid_info); $i++){
                            $d = $this->makeBid($bid_info[$i]->type, $bid_info[$i]->id, $bid_info[$i]->head, $bid_info[$i]->meta, $bid_info[$i]->real_id, $bid_info[$i]->target_count);
                            if($d->getStatus() != Constant::OK_STATUS){
                                $data = new Response("bidList", Constant::ERR_STATUS, "Err makeBid full bidList");
                                $goodFull = false;
                                break;
                            }
                        }
                        if($goodFull){
                            $cashData = CashManager::getCash();
                            if($cashData->getStatus() == Constant::OK_STATUS){
                                $data = new Response("bidList", Constant::OK_STATUS, "", $cashData->getObject());
                            }else{
                                $data = new Response("bidList", Constant::ERR_STATUS, "Get cash error");
                            }
                        }
                    }else{
                        $data = new Response("bidList", Constant::ERR_STATUS, "No funds for full list");
                    }
                }else{
                    $data = new Response("bidList", Constant::ERR_STATUS, "Get cash error");
                }

            }
        }else{
            $data = new Response("bidList", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function makeBid($type, $id, $head, $meta, $real_id, $target_count){
        $price = CashManager::getTaskPriceBid($type);
        $order = $price * $target_count;

        $cashData = CashManager::getCash();

        if($cashData->getStatus() == Constant::OK_STATUS){
            $obj = $cashData->getObject();
            $freeSum = $obj['cash']['deposit'];
            if($freeSum >= $order){												//if user have cash for order
                $withdrawData = CashManager::withdraw($order);
                if($withdrawData->getStatus() == Constant::OK_STATUS){			//user payed for order
                    $orderData = CashManager::order($type, $id, $real_id, $meta, $head, $target_count);
                    if($orderData->getStatus() == Constant::OK_STATUS){
                        $cashData = CashManager::getCash();
                        if($cashData->getStatus() == Constant::OK_STATUS){
                            $data = new Response("bid", Constant::OK_STATUS, "", $cashData->getObject());
                        }else{
                            $data = new Response("bid", Constant::ERR_STATUS, "Get cash error");
                        }
                    }else{
                        $data = new Response("bid", Constant::ERR_STATUS, "Order error");
                    }
                }else{
                    $data = new Response("bid", Constant::ERR_STATUS, "Withdraw error");
                }
            }else{
                $data = new Response("bid", Constant::ERR_STATUS, "No funds");
            }
        }else{
            $data = new Response("bid", Constant::ERR_STATUS, "Get cash error");
        }
        return $data;
    }

    public function getTasks(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $user_id = Manager::$user->getId();
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $tasks = array();
            $this->getTasksByType(Constant::LIKE_TYPE, $user_id, $dbh, $tasks);
            $this->getTasksByType(Constant::SUBSCRIBE_TYPE, $user_id, $dbh, $tasks);

            $data = new Response("getTasks", Constant::OK_STATUS, "", array("tasks"=>$tasks));

        }else{
            $data = new Response("getTasks", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    private function getTasksByType($type, $user_id, &$dbh, &$tasks){
        $info = TableManager::getInfoTable($type);

        $stmt = $dbh->prepare("SELECT id, target_count, ready_count, meta, head, status FROM `".$info."`
								WHERE user_id = :user_id AND target_count > ready_count");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while($arr = $stmt->fetch()){
                $arr['type'] = $type;
                $meta = base64_decode($arr['meta']);
                $head = $arr['head'];
                $task = new Task($arr['type'], $arr['id'], $arr['ready_count'], $meta, $head, $arr['target_count'], $arr['status']);
                $tasks[] = $task->getArr();
            }
        }
    }

    private function getQuestsByType($type, &$dbh, $user_id, &$quests, $limit){

        //10% where priority > 0 order by  rand()
        //40% order by rand
        //40% order by task_id desc
        //10% order by task_id

        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $where = "1";
        $order = "RAND()";

        $by_priority_balance = Constant::BY_PRIORITY_BALANCE_CHANCE;
        $by_priority = $by_priority_balance + Constant::BY_PRIORITY_CHANCE;
        $by_priority_admin = $by_priority + Constant::BY_PRIORITY_CHANCE_ADMIN;
        $by_rand = $by_priority_admin + Constant::BY_RAND_CHANCE;

        $redis = Redis::getInstance()->getClient();

        $ids = array();
        for($i=0; $i<Constant::TRY_GET_QUEST_TYPE_COUNT; $i++) {

            $rand = rand(1, 100);
            if($rand <= $by_priority_balance){
                $where = "priority > 0";
            } else if ($rand <= $by_priority) {
                $where = "priority > 1";
            } else if ($rand <= $by_priority_admin) {
                $where = "priority >= 10";
            } else if ($rand <= $by_rand) {
                $where = "1";
            }

            for ($j = 0; $j < Constant::TRY_GET_QUEST_COUNT; $j++) {

                $stmt = $dbh->prepare("SELECT task_id FROM `$table` WHERE $where ORDER BY $order LIMIT $limit");
                //echo "SELECT task_id FROM `$table` WHERE $where ORDER BY $order LIMIT $limit";
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    while ($arr = $stmt->fetch()) {

                        $key = Constant::PREFIX . $user_id . ":" . $arr["task_id"];

                        if (!$redis->exists($key)) {      //if not in list
                            $ids[] = $arr['task_id'];
                        }
                    }
                    if (count($ids) >= $limit) {
                        break;
                    }
                }
            }

            if (count($ids) >= $limit) {
                break;
            }
        }

        if(count($ids) > 0){
            $ids = implode(",", $ids);
            $stmt = $dbh->prepare("SELECT id, target_id, real_id FROM `$info` WHERE id IN($ids)");
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $data = $stmt->fetchAll();
                foreach($data as $arr){
                    $arr['type'] = $type;
                    $quest = new Quest($arr['type'], $arr['id'], $arr['target_id'], $arr['real_id']);
                    $quests[] = $quest->getArr();
                }
            }
        }
    }


    private function getQuestsByTypeDev($type, &$dbh, $user_id, &$quests, $limit){

        //10% where priority > 0 order by  rand()
        //40% order by rand
        //40% order by task_id desc
        //10% order by task_id

        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);
        $where = "1";
        $order = "RAND()";

        $by_priority_balance = Constant::BY_PRIORITY_BALANCE_CHANCE;
        $by_priority = $by_priority_balance + Constant::BY_PRIORITY_CHANCE;
        $by_priority_admin = $by_priority + Constant::BY_PRIORITY_CHANCE_ADMIN;
        $by_rand = $by_priority_admin + Constant::BY_RAND_CHANCE;

        $redis = Redis::getInstance()->getClient();




        $ids = array();
        for($i=0; $i<Constant::TRY_GET_QUEST_TYPE_COUNT; $i++) {

            $rand = rand(1, 100);
            if($rand <= $by_priority_balance){
                $way = "p0_tasks_count";
                $where = "priority > 0";
            } else if ($rand <= $by_priority) {
                $way = "p1_tasks_count";
                $where = "priority > 1";
            } else if ($rand <= $by_priority_admin) {
                $way = "p10_tasks_count";
                $where = "priority >= 10";
            } else if ($rand <= $by_rand) {
                $way = "np_tasks_count";
                $where = "1";
            }

            $way .= "_$type";

            if (!$redis->exists($way)) {
                $stmt = $dbh->prepare("SELECT COUNT(task_id) as cnt FROM `$table` WHERE $where");
                $stmt->execute();
                $cnt = 1000;
                if($stmt->rowCount() > 0){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $cnt =$stmt->fetchColumn();
                }
                $redis->set($way, $cnt);
                $redis->expire($way , 10);
            }
            $count = $redis->get($way);

            file_put_contents("$way.txt", $count);

            for ($j = 0; $j < Constant::TRY_GET_QUEST_COUNT; $j++) {

                $rnd = rand(1,$count);
                $stmt = $dbh->prepare("SELECT task_id FROM `$table` WHERE $where LIMIT $rnd, $limit");
                //echo "SELECT task_id FROM `$table` WHERE $where ORDER BY $order LIMIT $limit";
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    while ($arr = $stmt->fetch()) {

                        $key = Constant::PREFIX . $user_id . ":" . $arr["task_id"];

                        if (!$redis->exists($key)) {      //if not in list
                            $ids[] = $arr['task_id'];
                        }
                    }
                    if (count($ids) >= $limit) {
                        break;
                    }
                }
            }

            if (count($ids) >= $limit) {
                break;
            }
        }

        if(count($ids) > 0){
            $ids = implode(",", $ids);
            $stmt = $dbh->prepare("SELECT id, target_id, real_id FROM `$info` WHERE id IN($ids)");
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $data = $stmt->fetchAll();
                foreach($data as $arr){
                    $arr['type'] = $type;
                    $quest = new Quest($arr['type'], $arr['id'], $arr['target_id'], $arr['real_id']);
                    $quests[] = $quest->getArr();
                }
            }
        }
    }

    public function getQuests(){
        $data = new Response("getQuests", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['types']) && isset($_REQUEST['limits'])){
            if(Manager::$user->isAuth()){

                $data = SecureManager::isVerify();
                if($data){return $data;}

                $types = explode(",", Validator::clear($_REQUEST['types']));
                $limits = explode(",", Validator::clear($_REQUEST['limits']));
                $user_id = Manager::$user->getId();
                if(count($limits) != count($types)){
                    $data = new Response("getQuests", Constant::ERR_STATUS, "Params validation error");
                }else{
                    $db = DB::getInstance();
                    $dbh = $db->getDBH();
                    $quests = array();
                    for($i=0; $i<count($types); $i++){

                        if($types[$i] == Constant::LIKE_TYPE){
                            if($user_id == 549615){
                                $this->getQuestsByTypeDev(Constant::LIKE_TYPE, $dbh, $user_id, $quests, intval($limits[$i]));
                            }else{
                                $this->getQuestsByType(Constant::LIKE_TYPE, $dbh, $user_id, $quests, intval($limits[$i]));
                            }


                        }else if($types[$i] == Constant::SUBSCRIBE_TYPE){
                            if($user_id == 549615){
                                $this->getQuestsByTypeDev(Constant::SUBSCRIBE_TYPE, $dbh, $user_id, $quests, intval($limits[$i]));
                            }else{
                                $this->getQuestsByType(Constant::SUBSCRIBE_TYPE, $dbh, $user_id, $quests, intval($limits[$i]));
                            }

                        }

                    }
                    shuffle($quests);
                    $data = new Response("getQuests", Constant::OK_STATUS, "", array("quests"=>$quests));
                }
            }else{
                $data = new Response("getQuests", Constant::ERR_STATUS, "Auth error");
            }
        }
        return $data;
    }

    public function deleteTask(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            //if we have any error
            $data = new Response("deleteTask", Constant::ERR_STATUS, "No depth value");
            $user_id = Manager::$user->getId();

            if (isset($_REQUEST['id']) && isset($_REQUEST['type'])){
                $type = Validator::clear($_REQUEST['type']);
                $id = Validator::clear( $_REQUEST['id']);
                $data = HelpManager::taskDelete($user_id, $type, $id);
            }
        }else{
            $data = new Response("deleteTask", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function setReady(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}


            //if we have any error
            $data = new Response("setReady", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['id']) && isset($_REQUEST['type']) && isset($_REQUEST['needPay'])) {
                $user_id = Manager::$user->getId();
                $type = Validator::clear($_REQUEST['type']);
                $id =  Validator::clear($_REQUEST['id']);
                $needPay =  Validator::clear($_REQUEST['needPay']);
                $info = TableManager::getInfoTable($type);
                $table = TableManager::getTaskTable($type);
                $price = CashManager::getTaskPrice($type);


                $price += CashManager::getBonusPay(Manager::$user->getTurbo());

                if(!$needPay){
                    $price = 0;
                }
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $stmt = $dbh->prepare("SELECT target_count, ready_count FROM $info WHERE id = :id");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                $xp = Manager::$user->getXpInfo()->getXp();
                $lvl = Manager::$user->getXpInfo()->getLvl();

                if($stmt->rowCount() > 0){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    $orderLeft = ($arr['target_count'] - $arr['ready_count']) * $price;
                    if($orderLeft >= $price){
                        $payData = CashManager::pay($dbh, $user_id, $id, $type, $price, $needPay);
                        if($payData->getStatus() == Constant::OK_STATUS){
                            HelpManager::topCompetition($dbh, $user_id);
                            $up = Manager::$user->getXpInfo()->upXp($user_id);
                            ReferalManager::referalDeposit($price);
                            SessionManager::updateUser(Manager::$user);
                            $data = new Response("setReady", Constant::OK_STATUS, "", array("price"=>$price, "up"=>$up, "lvl"=>$lvl, "xp"=>$xp));
                        }else{
                            $data = new Response("setReady", Constant::OK_STATUS, "Pay error, reason - " . $payData->getMessage(), array("price"=>0, "up"=>0, "lvl"=>$lvl, "xp"=>$xp));
                        }
                    }else{
                        HelpManager::checkTask($dbh, $id, $type);
                        $data = new Response("setReady", Constant::OK_STATUS, "No funds", array("price"=>0, "up"=>0, "lvl"=>$lvl, "xp"=>$xp));
                    }
                }else{
                    $data = new Response("setReady", Constant::OK_STATUS, "Task does not exist", array("price"=>0, "up"=>0, "lvl"=>$lvl, "xp"=>$xp));
                }
            }
        }else{
            $data = new Response("setReady", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public static function reportQuest(){
        $data = new Response("reportQuest", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['id']) && isset($_REQUEST['type'])){
            if(Manager::$user->isAuth()){

                $data = SecureManager::isVerify();
                if($data){return $data;}

                $id = Validator::clear($_REQUEST['id']);
                $type = Validator::clear($_REQUEST['type']);
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $info = TableManager::getInfoTable($type);
                $stmt = $dbh->prepare("UPDATE $info SET reports = reports + 1 WHERE id = :id");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                if($stmt->rowCount() > 0){

                    //TEMP HIDE
                    self::checkQuest($type, $id, $db);
                    $data = new Response("reportQuest", Constant::OK_STATUS);
                }else{
                    $data = new Response("reportQuest", Constant::ERR_STATUS, "Return time error");
                }
            }else{
                $data = new Response("reportQuest", Constant::ERR_STATUS, "Auth error");
            }
        }
        return $data;
    }

    public static function checkQuest($type, $id, &$db){
        $info = TableManager::getInfoTable($type);
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id FROM $info WHERE id = :id AND reports > :reports");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $reports = Constant::REPORTS_LIMIT;
        $stmt->bindParam(":reports", $reports, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt = $dbh->prepare("UPDATE $info SET status = :status WHERE id = :id");
            $status = Constant::FROZEN_TASK_STATUS;
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() > 0){

                HelpManager::taskDeleteHard($type, $id, Constant::CHECK_DELETE_REASON);

                $data = new Response("checkQuest", Constant::OK_STATUS);
            }else{
                $data = new Response("checkQuest", Constant::ERR_STATUS, "Withdraw error");
            }
        }else{
            $data = new Response("checkQuest", Constant::ERR_STATUS, "Task does not exist");
        }

        return $data;
    }

    public function refresh(){
        if(Manager::$user->isAuth()){
            //if we have any error
            $data = new Response("refresh", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['id']) && isset($_REQUEST['type'])) {
                $user_id = Manager::$user->getId();
                $type = Validator::clear($_REQUEST['type']);
                $id =  Validator::clear($_REQUEST['id']);

                $info = TableManager::getInfoTable($type);

                $db = DB::getInstance();
                $dbh = $db->getDBH();

                $stmt = $dbh->prepare("UPDATE `".$info."` SET status = :status WHERE user_id = :user_id AND id = :id AND status = :status_before");
                $status = Constant::FROZEN_TASK_STATUS;
                $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                $status_before = Constant::HANG_TASK_STATUS;
                $stmt->bindParam(":status_before", $status_before, PDO::PARAM_INT);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);

                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $data = new Response("refresh", Constant::OK_STATUS);
                }else{
                    $data = new Response("refresh", Constant::ERR_STATUS, "Update error");
                }
            }
        }else{
            $data = new Response("refresh", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function taskVerdict(){

        $data = new Response("taskVerdict", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['id']) && isset($_REQUEST['type']) && isset($_REQUEST['verdict']) && isset($_REQUEST['suspicion'])){

            $id = Validator::clear($_REQUEST['id']);
            $type = Validator::clear($_REQUEST['type']);
            $verdict = Validator::clear($_REQUEST['verdict']);                  // ok or err
            $suspicion = Validator::clear($_REQUEST['suspicion']);      // type of task for check

            $data = HelpManager::doVerdict($id, $type, $verdict, $suspicion);

        }

        return $data;
    }

    public function getFrozens(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $frozens = array();
        $this->getFrozenByType($dbh, Constant::LIKE_TYPE, $frozens);
        $this->getFrozenByType($dbh, Constant::SUBSCRIBE_TYPE, $frozens);
        shuffle($frozens);
        return new Response("getFrozens", Constant::OK_STATUS, "", array("frozens"=>$frozens));
    }

    public static function fastEarn(){
        if(Manager::$user->isAuth()){
            //if we have any error
            $data = new Response("fastEarn", Constant::ERR_STATUS, "No depth value");
            if(isset($_REQUEST['hours'])) {

                $hours = intval(Validator::clear($_REQUEST['hours']));
                $hours = $hours > 0 ? $hours : 1;
                $user_id = Manager::$user->getId();

                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET ftime = :ftime WHERE id = :user_id");
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

                $ftime = time() + ($hours * 3600);

                NotificationManager::addNotification($user_id, NotificationManager::SPEED_NOTIFICATION, $ftime);

                $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);
                $stmt->execute();

                $data = new Response("fastEarn", Constant::OK_STATUS);
            }

        }else{
            $data = new Response("fastEarn", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public static function fastEarnDelay(){
        if(Manager::$user->isAuth()){
            $data = HelpManager::getEarnDelay();
        }else{
            $data = new Response("fastEarnDelay", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    private function getFrozenByType(&$dbh, $type, &$frozens){

        $info = TableManager::getInfoTable($type);

        $stmt = $dbh->prepare("SELECT id, real_id, target_id FROM `".$info."` WHERE status = :status LIMIT :limit");
        $status = Constant::FROZEN_TASK_STATUS;
        $limit = Constant::FROZEN_LIMIT;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while($arr = $stmt->fetch()){
                $arr['type'] = $type;
                $frozens[] = $arr;
            }
        }
    }
}