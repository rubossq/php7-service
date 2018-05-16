<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2017-08-06
 * Time: 09:24
 */

namespace Famous\Models;


use DirectoryIterator;
use Famous\Core\Model;
use Famous\Lib\Common\Response;
use Famous\Lib\Managers\CashManager;
use Famous\Lib\Managers\HelpManager;
use Famous\Lib\Managers\TableManager;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use \PDO;
use ReceiptValidator\GooglePlay\Validator;

class Model_Migration extends Model
{

    const MIGRATION_PATH = "migrations";
    public function read(){

        $data = new Response("read", Constant::ERR_STATUS, "Undefined error");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $limit = 0;

        //unlink("migrate.txt");

        self::clearDir(Model_Migration::MIGRATION_PATH);



        while(true){
            $stmt = $dbh->prepare("SELECT user_id FROM `".Constant::DATA_TABLE."` WHERE atime >= :atime LIMIT $limit, 1000");
            $atime = 1501665117;
            //$ctime = 0;
            $stmt->bindParam(":atime", $atime, PDO::PARAM_INT);

            $stmt->execute();

            if($stmt->rowCount() > 0){

                $dt = array();

                $stmt->setFetchMode(PDO::FETCH_ASSOC);

                while($user_id = $stmt->fetchColumn()){

                    $arr['data'] = self::getDataByUserId(Constant::DATA_TABLE, $user_id, "user_id", $dbh);
                    $arr['users'] = self::getDataByUserId(Constant::USERS_TABLE, $user_id, "id", $dbh);
                    $arr['feeds'] = self::getDataByUserId(Constant::FEEDS_TABLE, $user_id, "user_id", $dbh);
                    $arr['subscribes_info'] = self::getDataByUserId(Constant::SUBSCRIBE_INFO, $user_id, "user_id", $dbh);
                    $arr['likes_info'] = self::getDataByUserId(Constant::LIKE_INFO, $user_id, "user_id", $dbh);

                    $dt[] = $arr;
                }

                $str = serialize($dt);
                file_put_contents(Model_Migration::MIGRATION_PATH . "/" . "migrate_$limit.txt", $str);

                $data = new Response("read", Constant::OK_STATUS);
            }else{

                //$data = new Response("read", Constant::ERR_STATUS, "No rows error");
                break;
            }
            $limit += 1000;
        }

        return $data;
    }

    private static function clearDir($path){
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if(!$fileInfo->isDot()) {
                unlink($fileInfo->getPathname());
            }
        }
    }

    private static function getDataByUserId($table, $id, $key, &$dbh){
        $stmt2 = $dbh->prepare("SELECT * FROM `".$table."` WHERE $key = :".$key);

        $stmt2->bindParam(":".$key, $id, PDO::PARAM_INT);
        $stmt2->execute();

        if($stmt2->rowCount() > 0){
            $stmt2->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt2->fetchAll();
        }


        return array();
    }

    private static function getList($path){
        $arr = array();
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if(!$fileInfo->isDot()) {
                $arr[] = $fileInfo->getPathname();
            }
        }

        return $arr;
    }

    public function write(){
        $dataReturn = new Response("read", Constant::OK_STATUS);

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $list = self::getList(Model_Migration::MIGRATION_PATH);

        for($k=0; $k<count($list); $k++) {


            $str = file_get_contents($list[$k]);
            $dt = unserialize($str);


            for ($i = 0; $i < count($dt); $i++) {
                $arr = $dt[$i];

                $user = $arr['users'];
                $data = $arr['data'];

                $feeds = $arr['feeds'];
                $subscribes_info = $arr['subscribes_info'];
                $likes_info = $arr['likes_info'];

                if(!self::userExists($data[0]['login'], $dbh)){
                    $user_id = self::setData($user[0], Constant::USERS_TABLE, $dbh);
                    $data[0]['user_id'] = $user_id;

                    for ($j = 0; $j < count($feeds); $j++) {
                        $feeds[$j]['user_id'] = $user_id;
                    }
                    for ($j = 0; $j < count($subscribes_info); $j++) {
                        $subscribes_info[$j]['user_id'] = $user_id;
                    }
                    for ($j = 0; $j < count($likes_info); $j++) {
                        $likes_info[$j]['user_id'] = $user_id;
                    }

                    $priority = $user[0]['priority'];

                    self::setDataArr($data, Constant::DATA_TABLE, $dbh);
                    self::setDataArr($feeds, Constant::FEEDS_TABLE, $dbh);
                    self::setDataArr($subscribes_info, Constant::SUBSCRIBE_INFO, $dbh, $priority);
                    self::setDataArr($likes_info, Constant::LIKE_INFO, $dbh, $priority);
                }

            }
        }

        return $dataReturn;
    }

    private static function userExists($login, &$dbh){
        $stmt = $dbh->prepare("SELECT id FROM `".Constant::DATA_TABLE."` WHERE login = :login");
        $stmt->bindParam(":login", $login);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private static function setDataArr($arr, $table, &$dbh, $priority = 0){
        for($i=0; $i<count($arr); $i++){
            $id = self::setData($arr[$i], $table, $dbh);
            if($table == Constant::LIKE_INFO){
                if($arr[$i]['target_count'] > 0 && $arr[$i]['target_count'] >= $arr[$i]['ready_count']){
                    self::tasksTables(TableManager::getTaskTable(Constant::LIKE_TYPE), 1, $id, $dbh);
                }
            }else if($table == Constant::SUBSCRIBE_INFO){
                if($arr[$i]['target_count'] > 0 && $arr[$i]['target_count'] >= $arr[$i]['ready_count']) {
                    self::tasksTables(TableManager::getTaskTable(Constant::SUBSCRIBE_TYPE), 1, $id, $dbh);
                }
            }
        }
    }

    private static function tasksTables($task_table, $priority, $id, &$dbh){
        try{
            $stmt = $dbh->prepare("INSERT INTO `$task_table`  (task_id, priority) VALUES(:task_id, :priority)");
            $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
            $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
            $stmt->execute();
        }catch(\Exception $e){
            $stmt = $dbh->prepare("UPDATE `$task_table` SET priority = :priority WHERE task_id = :task_id");
            $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
            $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    private static function setData($arr, $table, &$dbh){

        $keys = array();
        $values = array();

        if(empty($arr)){
            return  0;
        }

        foreach($arr as $k => $v){
            if($k != "id"){
                $keys[] = $k;
                $values[] = "'".$v."'";
            }
        }

        $keysStr = implode(",", $keys);
        $valuesStr = implode(",", $values);

        $stmt = $dbh->prepare("INSERT INTO `".$table."` ($keysStr) VALUES ($valuesStr)");
        $stmt->execute();

        return $dbh->lastInsertId();
    }

    public function clear(){
        $data = new Response("clear", Constant::ERR_STATUS, "Undefined error");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        if(isset($_REQUEST['package_name'])){
            $package_name = \Famous\Lib\Utils\Validator::clear($_REQUEST['package_name']);

            if(in_array($package_name, HelpManager::getPackages())){
                $limit = 0;
                while(true){

                    $stmt = $dbh->prepare("SELECT user_id FROM `".Constant::DATA_TABLE."` WHERE package_name = :package_name LIMIT $limit, 1000");
                    $stmt->bindParam(":package_name", $package_name);
                    $stmt->execute();

                    if($stmt->rowCount() > 0){
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        while($user_id = $stmt->fetchColumn()){
                            self::removeTasks($user_id, $dbh);
                        }
                    }else{
                        break;
                    }


                    $limit += 1000;
                }
                $data = new Response("clear", Constant::OK_STATUS);
            }else{
                $data = new Response("clear", Constant::ERR_STATUS, "Wrong package error");
            }
        }

        return $data;
    }

    private static function removeTasks($user_id, &$dbh){
        self::clearTasks($user_id, Constant::LIKE_TYPE, $dbh);
        self::clearTasks($user_id, Constant::SUBSCRIBE_TYPE, $dbh);
    }

    private static function clearTasks($user_id, $type, &$dbh){

        $table = TableManager::getTaskTable($type);
        $info = TableManager::getInfoTable($type);

        $ids = null;
        $price = CashManager::getTaskPriceBid($type);

        $stmt = $dbh->prepare("SELECT id, target_count, ready_count FROM `$info` WHERE user_id = :user_id LIMIT 1000");

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while ($arr = $stmt->fetch()) {

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

}