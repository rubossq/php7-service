<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Cash as Cash;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Managers\BalanceManager as BalanceManager;
use Famous\Lib\Utils\Config;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
use \Exception as Exception;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class PhantomManager
{
    const PARAMS_ERROR = 1;
    const VALIDATION_ERROR = 2;
    const ID_CREATION_ERROR = 3;
    const NOT_READY_ERROR = 4;
    const WRONG_TYPE_ERROR = 5;
    const EXPIRE_ERROR = 6;
    const UNKNOWN_ERROR = 7;

    /*USER INFO PHANTOM*/
    const PHANTOM_UI = 1;
    const PHANTOM_UI_LOOP_TIME = 500;      //msec
    const PHANTOM_UI_ETIME = 15;         //sec
    const PHANTOM_UI_RTIME = 90;         //sec
    const PHANTOM_UI_LAUNCH_DELAY = 10;        //sec

    /*POST INFO PHANTOM*/
    const PHANTOM_PI = 2;
    const PHANTOM_PI_LOOP_TIME = 500;      //msec
    const PHANTOM_PI_ETIME = 15;         //sec
    const PHANTOM_PI_RTIME = 90;         //sec
    const PHANTOM_PI_LAUNCH_DELAY = 10;        //sec

    /*GAME INFO PHANTOM*/
    const PHANTOM_GI = 3;
    const PHANTOM_GI_LOOP_TIME = 1000;      //msec
    const PHANTOM_GI_ETIME = 300;         //sec
    const PHANTOM_GI_RTIME = 600;         //sec
    const PHANTOM_GI_LAUNCH_DELAY = 10;        //sec

    const PHANTOMS_TABLE = 'phantoms';
    const PHANTOMS_TASKS_TABLE = 'phantom_tasks';

    const WORK_STATUS = 1;
    const BOX_STATUS = 2;       //in phantom
    const DONE_STATUS = 3;
    const EXPIRED_STATUS = 4;


    public static function checkPhantom($phantom_id, &$dbh){
        $stmt = $dbh->prepare("SELECT id FROM `".self::PHANTOMS_TABLE."` WHERE ltime >= :ltime AND id = :phantom_id");
        $ltime = time() - self::getLaunchDelay($phantom_id);
        $stmt->bindParam(":ltime", $ltime, PDO::PARAM_INT);
        $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
        $stmt->execute();

        //launch phantom if it not working
        if($stmt->rowCount() == 0){
            exec(Config::PHANTOM_PATH . " --web-security=no " . Constant::PHANTOM_WORKERS_PATH . $phantom_id . "/worker.js > /dev/null 2>/dev/null &");
        }
    }

    public static function getPhantomTask($phantom_id, $pkey, $params, &$dbh){

        //rtime sec if we have this task done before
        $stmt = $dbh->prepare("SELECT id, params FROM `".self::PHANTOMS_TASKS_TABLE."`
                                       WHERE rtime >= :rtime AND pkey = :pkey AND status = :status AND phantom_id = :phantom_id
                                       ORDER BY id DESC LIMIT 1");
        $rtime = time() - self::getReadyTIme($phantom_id);
        $status = self::DONE_STATUS;

        $stmt->bindParam(":rtime", $rtime, PDO::PARAM_INT);
        $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":pkey", $pkey);

        $stmt->execute();

        //we have response by this key in last time
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $data =  new Response("getPhantomTask", Constant::OK_STATUS, "", array("id"=>$arr['id']));
        }else{
            $data = self::taskManage($pkey, $params, $phantom_id, $dbh);
        }

        return $data;
    }

    public static function taskManage($pkey, $params, $phantom_id, &$dbh){
        $stmt = $dbh->prepare("SELECT id FROM `".self::PHANTOMS_TASKS_TABLE."`
                                       WHERE etime >= :etime AND pkey = :pkey AND status = :status AND phantom_id = :phantom_id");
        $etime = time();
        $status = self::WORK_STATUS;

        $stmt->bindParam(":etime", $etime, PDO::PARAM_INT);
        $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":pkey", $pkey);
        $stmt->execute();

        //if this tasks expired
        if($stmt->rowCount() == 0){
            $stmt = $dbh->prepare("INSERT INTO `".self::PHANTOMS_TASKS_TABLE."` (etime, pkey, status, params, phantom_id) VALUES(:etime, :pkey, :status, :params, :phantom_id)");
            $etime = time() + self::getExpireTIme($phantom_id);

            $stmt->bindParam(":etime", $etime, PDO::PARAM_INT);
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->bindParam(":pkey", $pkey, PDO::PARAM_INT);
            $stmt->bindParam(":params", $params);
            $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
            $stmt->execute();
            $id = $dbh->lastInsertId();
        }else{
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $id = $stmt->fetchColumn();
        }

        if($id){
            $data =  new Response("taskManage", Constant::OK_STATUS, "", array("id"=>$id));
        }else{
            $data = new Response("taskManage", Constant::ERR_STATUS, "No id for task", array("error_code"=>self::ID_CREATION_ERROR));
        }

        return $data;
    }

    public static function checkReady($id, &$dbh){

        $stmt = $dbh->prepare("SELECT response, status, etime FROM `".self::PHANTOMS_TASKS_TABLE."` WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            if($arr['status'] == self::DONE_STATUS){
                $json = html_entity_decode($arr['response']);

                $response = json_decode($json);

                $data = new Response("getUserInfo", Constant::OK_STATUS, "", $response);
            }else{
                $time = time();
                $etime = $arr['etime'];

                if($etime >= $time) {
                    $data = new Response("checkReady", Constant::ERR_STATUS, "Not ready", array("error_code"=>self::NOT_READY_ERROR));
                }else{
                    $data = new Response("checkReady", Constant::ERR_STATUS, "Expire", array("error_code"=>self::EXPIRE_ERROR));
                }
            }
        }else{
            $data = new Response("checkReady", Constant::ERR_STATUS, "Id not exists", array("error_code"=>self::UNKNOWN_ERROR));
        }

        return $data;
    }

    public static function getTasks($phantom_id, &$dbh){

        $data = new Response("getTasks", Constant::ERR_STATUS, "No choice", array("error_code"=>self::WRONG_TYPE_ERROR));

        self::updateLTime($phantom_id, $dbh);

        $stmt = $dbh->prepare("SELECT id, params FROM `".self::PHANTOMS_TASKS_TABLE."`
                                       WHERE phantom_id = :phantom_id AND status = :status AND etime >= :etime ORDER BY id");
        $status = self::WORK_STATUS;
        $etime = time();
        $stmt->bindParam(":etime", $etime, PDO::PARAM_INT);
        $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();

            $ids = array();
            foreach($dt as $arr){
                $ids[] = $arr['id'];
            }

            $ids = implode(",", $ids);

            $stmt = $dbh->prepare("UPDATE `".self::PHANTOMS_TASKS_TABLE."` SET status = :status WHERE id IN (".$ids.")");
            $status = self::BOX_STATUS;
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->execute();

            $data = new Response("getTasks", Constant::OK_STATUS, "", array("tasks"=>$dt));
        }else{
            $data = new Response("getTasks", Constant::ERR_STATUS, "No tasks");
        }

        return $data;
    }

    public static function setResponse($phantom_id, $response, $id, &$dbh){
        $data = new Response("getTasks", Constant::ERR_STATUS, "No choice", array("error_code"=>self::WRONG_TYPE_ERROR));

        self::updateLTime($phantom_id, $dbh);

        $stmt = $dbh->prepare("UPDATE `".self::PHANTOMS_TASKS_TABLE."` set response = :response, status = :status, rtime = :rtime WHERE id = :id");

        $status = self::DONE_STATUS;
        $rtime = time();
        $stmt->bindParam(":rtime", $rtime, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":response", $response);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->execute()){
            $data = new Response("getTasks", Constant::OK_STATUS);
        }else{
            $data = new Response("getTasks", Constant::ERR_STATUS, "Can not update");
        }

        return $data;
    }

    public static function getLaunchDelay($phantom_id){
        $delays = array(self::PHANTOM_UI => self::PHANTOM_UI_LAUNCH_DELAY, self::PHANTOM_PI => self::PHANTOM_PI_LAUNCH_DELAY,
                        self::PHANTOM_GI => self::PHANTOM_GI_LAUNCH_DELAY);

        return $delays[$phantom_id];
    }

    public static function getExpireTIme($phantom_id){
        $delays = array(self::PHANTOM_UI => self::PHANTOM_UI_ETIME, self::PHANTOM_PI => self::PHANTOM_PI_ETIME,
                        self::PHANTOM_GI => self::PHANTOM_GI_ETIME);

        return $delays[$phantom_id];
    }

    public static function getLoopTIme($phantom_id){
        $delays = array(self::PHANTOM_UI => self::PHANTOM_UI_LOOP_TIME, self::PHANTOM_PI => self::PHANTOM_PI_LOOP_TIME,
                        self::PHANTOM_GI => self::PHANTOM_GI_LOOP_TIME);

        return $delays[$phantom_id];
    }

    public static function getReadyTIme($phantom_id){
        $delays = array(self::PHANTOM_UI => self::PHANTOM_UI_RTIME, self::PHANTOM_PI => self::PHANTOM_PI_RTIME,
                        self::PHANTOM_GI => self::PHANTOM_GI_RTIME);

        return $delays[$phantom_id];
    }

    public static function getConfig($phantom_id, &$dbh){
        self::updateLTime($phantom_id, $dbh);

        $data = new Response("getConfig", Constant::OK_STATUS, "", array('loop_time'=>self::getLoopTIme($phantom_id)));         //msec

        return $data;

    }

    private function updateLTime($phantom_id, &$dbh){
        //update ltime for phantom
        $stmt = $dbh->prepare("UPDATE `".self::PHANTOMS_TABLE."` SET ltime = :ltime WHERE id = :phantom_id");
        $ltime = time();
        $stmt->bindParam(":ltime", $ltime, PDO::PARAM_INT);
        $stmt->bindParam(":phantom_id", $phantom_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}