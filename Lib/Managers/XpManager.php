<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\XpInfo;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:03
 */
class XpManager
{
    public static function getXpInfo(){
        if(Manager::$user->isAuth()){
            $uid = Manager::$user->getId();
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT xp FROM `".Constant::USERS_TABLE."` WHERE id = :id");
            $stmt->bindParam(":id", $uid, PDO::PARAM_INT);
            if($stmt->execute()){
                $xp = $stmt->fetchColumn();
                $xpInfo = new XpInfo($xp);

                $data = new Response("getXpInfo", Constant::OK_STATUS, "", array("xp_info"=>$xpInfo->getArr()));
            }else{
                $data = new Response("getXpInfo", Constant::ERR_STATUS, "DB error");
            }
        }else{
            $data = new Response("getXpInfo", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public static function getAchieves(){
        if(Manager::$user->isAuth()){
            $user_id = Manager::$user->getId();
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT a.name FROM `achieves` a, `honors` h WHERE a.id = h.achieve_id AND h.user_id = :user_id");
            $achieves = null;
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            if($stmt->execute()){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                while ($arr = $stmt->fetch()){
                    $achieves[] = $arr['name'];
                }

                $data = new Response("getAchieves", Constant::OK_STATUS, "", array("achieves"=>$achieves));
            }else{
                $data = new Response("getAchieves", Constant::OK_STATUS, "", array("achieves"=>$achieves));
            }
        }else{
            $data = new Response("getAchieves", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public static function getAchieve($user_id){
        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT a.name, a.id
						 FROM `".Constant::ACHIEVES_TABLE."` a
						 LEFT JOIN `".Constant::HONORS_TABLE."` h ON a.id = h.achieve_id AND h.user_id = :user_id
						 WHERE h.id IS NULL
						 LIMIT :limit");

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $limit = 1;
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        if($stmt->execute()){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $achieve = $arr['id'] . $arr['name'];
        }
        return $achieve;
    }
}