<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2017-05-22
 * Time: 12:03
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Response;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use Famous\Models\Model_Phantom;
use \PDO;

class GameManager
{
    const COMPANIES_TABLE = "companies";
    const GAMES_TABLE = "games";
    const LOCAL_TASKS = "local_tasks";

    const MARKET_TYPE = 1;
    const ITUNES_TYPE = 2;

    const WAIT_STATUS = 1;
    const READY_STATUS = 2;

    const NEW_COMPANY_TIME = 86400;

    public static function parseNext(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, alias, market, itunes FROM `".self::COMPANIES_TABLE."` ORDER BY stime LIMIT 1");
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();

            $company_id = $arr["id"];
            $alias = $arr["alias"];
            $market = $arr["market"];
            $itunes = $arr["itunes"];

            self::updateSearchTime($arr["id"], $dbh);

            $model = new Model_Phantom();
            $dataGameInfo = $model->getGameInfo($alias, $market, $itunes, $company_id);

            if($dataGameInfo->getStatus() == Constant::OK_STATUS){
                $obj = $dataGameInfo->getObject();
                $id = $obj["id"];
                self::addLocalTask($id, $dbh);
            }
        }
    }

    private static function addLocalTask($id, &$dbh){
        $stmt = $dbh->prepare("INSERT INTO `".self::LOCAL_TASKS."` (task_id, status) VALUES (:task_id, :status)");

        $status = self::WAIT_STATUS;

        $stmt->bindParam(":task_id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);

        $stmt->execute();
    }

    public static function parseResponse(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, task_id FROM `".self::LOCAL_TASKS."` WHERE status = :status AND cnt < 5");
        $status = self::WAIT_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();

            foreach($dt as $d){
                $tid = $d['task_id'];
                $data = PhantomManager::checkReady($tid, $dbh);

                if($data->getStatus() == Constant::OK_STATUS){
                    $obj = $data->getObject();
                    self::computeResponse($d['id'], $obj, $dbh);
                }else{
                    self::updateCnt($d['id'], $dbh);
                }
            }
        }
    }

    private static function updateCnt($id, &$dbh){
        $stmt = $dbh->prepare("UPDATE `".self::LOCAL_TASKS."` SET cnt = cnt + 1 WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function computeResponse($id, $response, &$dbh){


        $company_id = $response->company_id;
        $games = $response->games;

        foreach($games as $g){

            $package_id = $g->package;
            $type = $g->type;
            $name = $g->name;

            self::addGame($company_id, $package_id, $type, $name, $dbh);
        }

        $status = self::READY_STATUS;
        $stmt = $dbh->prepare("UPDATE `".self::LOCAL_TASKS."` SET status = :status WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function addGame($company_id, $package_id, $type, $name, &$dbh){
        try{
            $ftime = time();
            $stmt = $dbh->prepare("INSERT INTO `".self::GAMES_TABLE."` (name, ftime, company_id, package_id, type)
                               VALUES (:name, :ftime, :company_id, :package_id, :type)");

            $stmt->bindParam(":package_id", $package_id);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":ftime", $ftime, PDO::PARAM_INT);
            $stmt->bindParam(":type", $type, PDO::PARAM_INT);
            $stmt->bindParam(":company_id", $company_id, PDO::PARAM_INT);

            $stmt->execute();
        }catch(\Exception $e){

        }

    }

    private static function updateSearchTime($id, &$dbh){
        $stmt = $dbh->prepare("UPDATE `".self::COMPANIES_TABLE."` SET stime = :stime WHERE id = :id");
        $stime = time();
        $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function lastGames(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT g.name as gname, g.type, g.package_id, c.name as cname, g.ftime FROM `".self::GAMES_TABLE."` g, `".self::COMPANIES_TABLE."` c
                               WHERE g.company_id = c.id AND c.ctime < :ctime ORDER BY g.ftime DESC, g.name  LIMIT 200");
        $ctime = time() - self::NEW_COMPANY_TIME;
        $stmt->bindParam(":ctime", $ctime, PDO::PARAM_INT);
        $stmt->execute();
        $games = array();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dt = $stmt->fetchAll();

            foreach($dt as $d){
                $d['ago'] = intval((time() - $d['ftime']) / 3600);
                if($d['type'] == self::MARKET_TYPE){
                    $d["link"] = "https://play.google.com/store/apps/details?id=".$d["package_id"];
                }else{
                    $d["link"] = "https://itunes.apple.com/us/app/".$d["package_id"];
                }

                $games[] = $d;
            }
        }

        $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".self::LOCAL_TASKS."` WHERE status = :status");
        $status = self::WAIT_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        $cnt_wait = $stmt->fetchColumn();

        $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".self::GAMES_TABLE."` ");
        $stmt->execute();

        $cnt_games = $stmt->fetchColumn();

        $data =  new Response("taskManage", Constant::OK_STATUS, "", array("games"=>$games, "info"=>array("cnt_wait"=>$cnt_wait, "cnt_games"=>$cnt_games)));
        return $data;
    }
}