<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-11-02
 * Time: 10:51
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Manager;
use Famous\Lib\Common\Response;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use Famous\Lib\Utils\Helper;
use Famous\Models\Model_Task;
use \PDO as PDO;

class VeryManager
{

    const GOD_LOW = 1;
    const GOD_MID = 2;
    const GOD_TOP = 3;
    const GOD_KING = 4;

    const GOD_LOW_PARSE = 900;
    const GOD_MID_PARSE = 600;
    const GOD_TOP_PARSE = 300;
    const GOD_KING_PARSE = 180;

    const GOD_LOW_LIKES_LIMIT = 1000;
    const GOD_MID_LIKES_LIMIT = 2000;
    const GOD_TOP_LIKES_LIMIT = 3000;
    const GOD_KING_LIKES_LIMIT = 5000;

    const GOD_LOW_SUBSCRIBE_LIMIT = 100;
    const GOD_MID_SUBSCRIBE_LIMIT = 200;
    const GOD_TOP_SUBSCRIBE_LIMIT = 300;
    const GOD_KING_SUBSCRIBE_LIMIT = 600;

    const EXEC_TIME = 90;
    const MIN_LIKES_ORDER = 3;
    const POSTS_LIMIT = 5;


    public static function manageVeryUsersData($user_id, $real_id, $suspicion, $verdict, $dt){

        $suspisions = explode(",", $suspicion);
        $verdicts = explode(",", $verdict);

        $private = false;
        $exists = false;



        for($i=0; $i<count($suspisions); $i++){
            switch($suspisions[$i]){
                case Constant::CHECK_FROZEN:
                    if($verdicts[$i] == Constant::OK_STATUS){
                        $exists = true;
                    }else{
                        $exists = false;
                    }
                    break;
                case Constant::CHECK_PRIVATE:
                    if($verdicts[$i] == Constant::OK_STATUS){
                        $private = true;
                    }else{
                        $private = false;
                    }
                    break;
            }
        }

        if($private || !$exists){
            //send notification
            $data = new Response("manageVeryUsersData", Constant::ERR_STATUS, "Private or not exists");
        }else{
            $dt = urldecode($dt);
            $dt = json_decode($dt);



            $user = $dt->user;
            $posts = $dt->posts;

            //var_dump($user);

            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $statData = self::getStat($user_id, $dbh);

            if($statData->getStatus() == Constant::OK_STATUS){
                $stat = $statData->getObject();
                $stat = $stat['stat'];
                $limits = self::getLimits($stat['godmode']);

                $likesLeft = $limits['likes'] - $stat['likes'];
                $subscribesLeft = $limits['subscribes'] - $stat['subscribes'];

                $tdate = date("Y-m-d");
                $midnight = strtotime($tdate.' 00:00:00') + 86400;	//midnight tomorrow
                $left = $midnight - time();

                $ptime = self::getPtime($stat['godmode']);
                $left -= $ptime;

                Manager::$user->setId($user_id);
                Manager::$user->setIsAuth(true);
                Manager::$user->setGod(true);

                $likes = 0;
                $subscribes = 0;

                print_r($posts);
                echo "</br>";
                usort($posts, array("Famous\\Lib\\Utils\\HelperHelper", "postsSort"));
                echo "</br>";
                print_r($posts);
                if($likesLeft > 1111110){

                    if($left > ($ptime + self::EXEC_TIME)){
                        $count = floor($left / $ptime);
                        $target_count = floor($likesLeft/$count);
                    }else{
                        $target_count = $likesLeft;
                    }

                    $order = self::MIN_LIKES_ORDER;

                    $postsCount = floor($target_count / $order);
                    if($postsCount > self::POSTS_LIMIT){
                        $order = floor($target_count / self::POSTS_LIMIT);
                        $postsCount = self::POSTS_LIMIT;
                    }

                    if($postsCount == 0){
                        $postsCount = 1;
                    }


                    foreach($posts as $post){
                        //need refactoring and complete
                    }


                }

                if($subscribesLeft > 0){
                    if($left > ($ptime + self::EXEC_TIME)){
                        $count = floor($left / $ptime);
                        $target_count = floor($subscribesLeft / $count);
                    }else{
                        $target_count = $subscribesLeft;
                    }

                    if($target_count == 0){
                        $target_count = 1;
                    }

                    $id = $user->id;
                    $head =  $user->head;
                    $meta =  $user->meta;
                    $meta = base64_encode( mb_substr($meta, 0, Constant::MAX_META_LENGTH, "UTF-8"));

                    $mt = new Model_Task();
                    $dataBid = $mt->makeBid(Constant::SUBSCRIBE_TYPE, $id, $head, $meta, $real_id, $target_count);

                    if($dataBid->getStatus() == Constant::OK_STATUS){
                        $subscribes = $target_count;
                        $data = new Response("manageVeryUsersData", Constant::OK_STATUS);
                    }else{
                        $data = new Response("manageVeryUsersData", Constant::ERR_STATUS, "Bid error");
                    }
                }

                if($likes > 0 || $subscribes > 0){
                    self::updateLimits($user_id, $likes, $subscribes, $dbh);
                }else{
                    $data = new Response("manageVeryUsersData", Constant::ERR_STATUS, "Limits error");
                }

            }else{
                $data = $statData;
            }
        }

        return $data;
    }

    private static function updateLimits($user_id, $likes, $subscribes, $dbh){
        $stmt = $dbh->prepare("UPDATE `".Constant::VERY_USERS_TABLE."` SET likes = likes + :likes, subscribes = subscribes + :subscribes WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":likes", $likes, PDO::PARAM_INT);
        $stmt->bindParam(":subscribes", $subscribes, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getStat($user_id, &$dbh){
        $stmt = $dbh->prepare("SELECT likes, subscribes, godmode FROM `".Constant::VERY_USERS_TABLE."` WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $data = new Response("getStat", Constant::OK_STATUS, "", array("stat"=>$arr));
        }else{
            $data = new Response("getStat", Constant::ERR_STATUS, "Can not get stat");
        }

        return $data;
    }

    public static function getPtime($godmode){
        $times = array(self::GOD_LOW => self::GOD_LOW_PARSE, self::GOD_MID => self::GOD_MID_PARSE,
                       self::GOD_TOP => self::GOD_TOP_PARSE, self::GOD_KING => self::GOD_KING_PARSE);
        return $times[$godmode];
    }

    private static function getLimits($godmode){
        $limits = array(self::GOD_LOW => array("likes"=>self::GOD_LOW_LIKES_LIMIT, "subscribes"=>self::GOD_LOW_SUBSCRIBE_LIMIT),
                        self::GOD_MID => array("likes"=>self::GOD_MID_LIKES_LIMIT, "subscribes"=>self::GOD_MID_SUBSCRIBE_LIMIT),
                        self::GOD_TOP => array("likes"=>self::GOD_TOP_LIKES_LIMIT, "subscribes"=>self::GOD_TOP_SUBSCRIBE_LIMIT),
                        self::GOD_KING => array("likes"=>self::GOD_KING_LIKES_LIMIT, "subscribes"=>self::GOD_KING_SUBSCRIBE_LIMIT));

        return $limits[$godmode];
    }

}