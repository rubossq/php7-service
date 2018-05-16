<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\TableManager;
use Famous\Lib\Utils\DB as DB;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Redis as Redis;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:51
 */
class Model_Boss extends Model
{
    public function index(){


        $db = DB::getInstance();
        $dbh = $db->getDBH();
        $this->newsForm($dbh);
        $this->pushNewsForm($dbh);
        $this->pushAdsForm($dbh);
        $this->form($dbh);
        $data = null;
        $day = 86400;
        $dayBefore = time() - $day;

        $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".Constant::USERS_TABLE."`");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['totalUsersCount'] = $cnt;
        }

        $time = time();

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_FLWRS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountFlwrs'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_LKS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountLks'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_LKS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRealLks'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_FLWRS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRoyalFlwrs'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRoyalLikes'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_FLWRS_BOOST."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountFlwrsBoost'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_PHANTOM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountPhantom'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRealFollowers'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRoyalFollowers'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_LIKES_TOP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountRealLikes'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_METEOR_GP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['onlineUsersCountMeteor'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_FLWRS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountFlwrs'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_LKS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountLks'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_FLWRS_BOOST."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountFlwrsBoost'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_PHANTOM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountPhantom'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_LKS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRoyalFlwrs'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_FLWRS."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRealLks'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_LIKES_PREMIUM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRoyalLikes'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRealFollowers'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_METEOR_GP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountMeteor'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_ROYAL_FOLLOWERS_TOP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRoyalFollowers'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_REAL_LIKES_TOP."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $data['newUsersCountRealLikes'] = $cnt;
        }


        if(isset($_REQUEST['nid'])){
            $id = $this->clear($_REQUEST['nid']);
            $stmt = $dbh->prepare("SELECT * FROM `".Constant::NEWS_TABLE."` WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data['news'] = $stmt->fetch();
        }

        return $data;
    }

    public function checkRedis(){
        $redis = Redis::getInstance()->getClient();
        $time = time();
        $key = "test_".$time;
        if (!$redis->exists($key)){
            echo "test 1 pass";
            echo "<br>";
        }

        $redis->set($key, 1);
        $redis->expire($key , 100);

        if ($redis->exists($key)){
            echo "test 2 pass";
            echo "<br>";
        }
    }

    private function newsForm(&$dbh){

        if(isset($_REQUEST['news_css']) && isset($_REQUEST['news_html']) && isset($_REQUEST['news_js'])
            && isset($_REQUEST['name']) && isset($_REQUEST['type']) && isset($_REQUEST['can_complete']) && isset($_REQUEST['rtime'])){
            $news_css = $_REQUEST['news_css'];
            $news_html = $_REQUEST['news_html'];
            $news_js = $_REQUEST['news_js'];
            $name = $this->clear($_REQUEST['name']);
            $rtime = $this->clear($_REQUEST['rtime']);
            $type = Validator::clear( $_REQUEST['type']);
            $can_complete = Validator::clear( $_REQUEST['can_complete']);

            if(isset($_REQUEST['news_id'])){
                $news_id = Validator::clear( $_REQUEST['news_id']);
                $stmt = $dbh->prepare("UPDATE `".Constant::NEWS_TABLE."`
										SET html_json = :news_html, css_json = :news_css, js_json = :news_js, type = :type, rtime = :rtime, name = :name, can_complete = :can_complete
										WHERE id = :news_id");

                $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
            }else{
                $stmt = $dbh->prepare("INSERT INTO `".Constant::NEWS_TABLE."` (html_json, css_json, js_json, type, rtime, name, can_complete)
										VALUES(:news_html, :news_css, :news_js, :type, :rtime, :name, :can_complete)");
            }
            $stmt->bindValue(":news_html", $news_html, PDO::PARAM_STR);
            $stmt->bindValue(":news_css", $news_css, PDO::PARAM_STR);
            $stmt->bindValue(":news_js", $news_js, PDO::PARAM_STR);
            $stmt->bindParam(":type", $type, PDO::PARAM_INT);
            $stmt->bindParam(":rtime", $rtime, PDO::PARAM_INT);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":can_complete", $can_complete);
            $res = $stmt->execute();
            if($res){
                echo "Added ok<br>";
            }else{
                echo "Added err<br>";
            }
        }
    }

    private function pushAdsForm(&$db){
        if(isset($_REQUEST['ad_name']) && isset($_REQUEST['ad_priority']) && isset($_REQUEST['ad_name_app'])
            && isset($_REQUEST['ad_desc1']) && isset($_REQUEST['ad_desc2']) && isset($_REQUEST['target_lang']) && isset($_REQUEST['ad_app_id'])){
            $ad_name = $this->clear($_REQUEST['ad_name']);
            $ad_priority = $this->clear($_REQUEST['ad_priority']);
            $ad_name_app = $this->clear($_REQUEST['ad_name_app']);
            $ad_desc1 = $this->clear($_REQUEST['ad_desc1']);
            $ad_desc2 = $this->clear($_REQUEST['ad_desc2']);
            $ad_app_id = $this->clear($_REQUEST['ad_app_id']);
            $target_lang = $this->clear($_REQUEST['target_lang']);

            $res = $db->insert("INSERT INTO `ads` (name, priority, target_lang, app_name, app_id, desc1, desc2)
									VALUES ('$ad_name', $ad_priority, '$target_lang', '$ad_name_app', '$ad_app_id', '$ad_desc1', '$ad_desc2')");
            if($res){
                echo "Added ok<br>";
            }else{
                echo "Adding err<br>";
            }
        }
    }

    private function pushNewsForm(&$dbh){

        if(isset($_REQUEST['news_name']) && isset($_REQUEST['ftime'])){
            $news_name = $this->clear($_REQUEST['news_name']);
            $ftime = time() + $this->clear($_REQUEST['ftime']);
            $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".Constant::USERS_TABLE."`");
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $cnt = $stmt->fetchColumn();
                $stmt = $dbh->prepare("SELECT id FROM `".Constant::NEWS_TABLE."` WHERE name = '$news_name' ");
                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $id = $stmt->fetchColumn();
                    for($i=1; $i <= $cnt; $i++){
                        $stmt = $dbh->prepare("INSERT INTO `".Constant::FEEDS_TABLE."` (user_id, news_id, fire_time) VALUES ($i, $id, $ftime)");
                        $stmt->execute();
                    }
                    echo "Added ok<br>";
                }else{
                    echo "No news with the name err<br>";
                }
            }else{
                echo "Get Users count err<br>";
            }


        }
    }

    function clear($var){
        return addslashes(htmlentities(trim($var)));
    }

    private function form(&$dbh){

        if(isset($_REQUEST['query'])){
            $query = json_decode($_REQUEST['query']);
            $user_id = 0;
            $target_count = 10;
            $meta = "meta for it";
            $head = "head for it";
            $utime = time();
            foreach($query->arr as $id){

                $target_id = $id;
                $info = TableManager::getInfoTable($query->type);
                $table = TableManager::getTaskTable($query->type);
                $stmt = $dbh->prepare("INSERT INTO `$info` (target_id, meta, head, utime, target_count, user_id)
									VALUES('$target_id', '$meta', '$head', $utime, $target_count, $user_id)");
                $stmt->execute();

                $id = $dbh->lastInsertId();
                $stmt = $dbh->prepare("INSERT INTO `$table` (task_id) VALUES($id)");
                $stmt->execute();

                //echo $target_id."<br>";
            }
            echo "<p>Added ok</p>";
        }
    }

    public function mainInfo(){
        $data = new Response("mainInfo", Constant::ERR_STATUS, "No depth value");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $day = 86400;
        $dayBefore = time() - $day;

        $stmt = $dbh->prepare("SELECT COUNT(id) as cnt FROM `".Constant::USERS_TABLE."`");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $arr['total_users'] = $cnt;
        }

        $time = time();
        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND (d.package_name = '".Constant::PACKAGE_NAME_METEOR."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $arr['online_users_meteor'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE $time - d.last_visit < 600 AND d.user_id = u.id  AND d.package_name = '".Constant::PACKAGE_NAME_TORPEDA."'");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $arr['online_users_torpeda'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND (d.package_name = '".Constant::PACKAGE_NAME_METEOR."' OR d.package_name = '')");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $arr['new_users_meteor'] = $cnt;
        }

        $stmt = $dbh->prepare("SELECT COUNT(u.id) as cnt FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE d.rtime > $dayBefore AND d.user_id = u.id AND d.package_name = '".Constant::PACKAGE_NAME_TORPEDA."'");
        if($stmt->execute()){
            $cnt = $stmt->fetchColumn();
            $arr['new_users_torpeda'] = $cnt;
        }

        if($arr){
            $data = new Response("mainInfo", Constant::OK_STATUS, "", array("info"=>$arr));
        }

        return $data;
    }

    public function newsList(){
        $data = new Response("newsList", Constant::ERR_STATUS, "No depth value");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, name, rtime, can_complete, type FROM `".Constant::NEWS_TABLE."`");
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetchAll();
            $data = new Response("newsList", Constant::OK_STATUS, "", array("list"=>$arr));
        }

        return $data;
    }

    public function getNews(){
        $data = new Response("getNews", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['nid'])){
            $nid = Validator::clear($_REQUEST['nid']);
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $stmt = $dbh->prepare("SELECT id, name, rtime, can_complete, type, html_json, css_json, js_json FROM `".Constant::NEWS_TABLE."` WHERE id = :nid");
            $stmt->bindParam(":nid", $nid, PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();
                $data = new Response("getNews", Constant::OK_STATUS, "", array("news"=>$arr));
            }

        }

        return $data;
    }

    public function adsList(){
        $data = new Response("adsList", Constant::ERR_STATUS, "No depth value");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, name, target_lang, app_name, app_id, priority FROM `".Constant::ADS_TABLE."`");
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetchAll();
            $data = new Response("adsList", Constant::OK_STATUS, "", array("list"=>$arr));
        }

        return $data;
    }

    public function getAd(){
        $data = new Response("getAd", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['aid'])) {
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $aid = Validator::clear($_REQUEST['aid']);
            $stmt = $dbh->prepare("SELECT id, name, target_lang, app_name, app_id, desc1, desc2, priority FROM `" . Constant::ADS_TABLE . "` WHERE id = :aid");
            $stmt->bindParam(":aid", $aid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();
                $data = new Response("getAd", Constant::OK_STATUS, "", array("ad" => $arr));
            }
        }
        return $data;
    }

    public function appList(){
        $data = new Response("appList", Constant::ERR_STATUS, "No depth value");

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        $stmt = $dbh->prepare("SELECT id, name, app_name, app_id, priority FROM `".Constant::APPS_TABLE."`");
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetchAll();
            $data = new Response("appList", Constant::OK_STATUS, "", array("list"=>$arr));
        }

        return $data;
    }

    public function getApp(){
        $data = new Response("getApp", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['aid'])) {
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $aid = Validator::clear($_REQUEST['aid']);
            $stmt = $dbh->prepare("SELECT id, name, app_name, app_id, priority FROM `" . Constant::APPS_TABLE . "` WHERE id = :aid");
            $stmt->bindParam(":aid", $aid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();
                $data = new Response("getApp", Constant::OK_STATUS, "", array("app" => $arr));
            }
        }
        return $data;
    }

    public function usersList(){
        $data = new Response("usersList", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['offset'])) {
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $offset = Validator::clear($_REQUEST['offset']);
            $limit = 100;
            $order = "u.deposit DESC";
            $stmt = $dbh->prepare("SELECT u.id, d.login, u.priority, u.deposit, u.premium, u.turbo, u.xp
                                   FROM `" . Constant::USERS_TABLE . "` u, `".Constant::DATA_TABLE."` d
                                   WHERE d.user_id = u.id
                                   ORDER BY ".$order."
                                   LIMIT $offset, $limit");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetchAll();
                $data = new Response("usersList", Constant::OK_STATUS, "", array("list" => $arr));
            }
        }
        return $data;
    }

    public function getUser(){
        $data = new Response("getUser", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['uid'])) {
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $uid = Validator::clear($_REQUEST['uid']);

            $stmt = $dbh->prepare("SELECT u.id, d.login, u.priority, u.deposit, u.premium, u.turbo, u.xp
                                   FROM `" . Constant::USERS_TABLE . "` u, `".Constant::DATA_TABLE."` d
                                   WHERE u.id = :uid");
            $stmt->bindParam(":uid", $uid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $arr = $stmt->fetch();
                $data = new Response("getUser", Constant::OK_STATUS, "", array("user" => $arr));
            }
        }
        return $data;
    }

    public function sendNewMeteor(){
        $db = DB::getInstance();
        $dbh = $db->getDBH();


        $news_id = 29;
        $stmt = $dbh->prepare("SELECT u.id FROM `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
                               WHERE u.id = d.user_id AND d.last_visit > :last_visit
                               AND u.id NOT IN (SELECT f.user_id FROM `".Constant::FEEDS_TABLE."` f WHERE news_id = :news_id)
                               LIMIT 100");

        //$package_name = Constant::PACKAGE_NAME_REAL;
        //$stmt->bindParam(":package_name", $package_name);
        $last_visit = time() - 100000;
        //$rtime = time() - 3600;
        $stmt->bindParam(":last_visit", $last_visit, PDO::PARAM_INT);
        //$stmt->bindParam(":rtime", $rtime, PDO::PARAM_INT);
        $stmt->bindParam(":news_id", $news_id, PDO::PARAM_INT);
        $stmt->execute();

         if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            while($arr = $stmt->fetch()){
                $user_id = $arr['id'];
                $time = time();
                $stmt2 = $dbh->prepare("INSERT INTO `feeds` (user_id, news_id, fire_time, params) VALUES ($user_id, $news_id, $time, 'diamonds=100')");
                if($stmt2->execute()){
                    //$stmt2 = $dbh->prepare("UPDATE");
                    $notif_id = NotificationManager::getNotifId($dbh, NotificationManager::NEWS_NOTIFICATION);
                    NotificationManager::sendNotification($notif_id, $user_id);
                }
            }
        }

        return  new Response("sendNewMeteor", Constant::OK_STATUS);
    }
}