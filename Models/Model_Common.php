<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\CashManager;
use Famous\Lib\Managers\DonateManager;
use Famous\Lib\Managers\NewsManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Managers\SessionManager;
use Famous\Lib\Managers\UtilsManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\DataManager as DataManager;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:52
 */
class Model_Common extends Model
{
    public function auth(){
        //if we have any error
        $data = new Response("auth", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['login']) && isset($_REQUEST['lang'])){

            $login = Validator::clear($_REQUEST['login']);
            $lang = Validator::clear($_REQUEST['lang']);
            $iid = Validator::clear($_REQUEST['iid']);

            DataManager::checkOld($login);

            $data = DataManager::authUser($login, $lang, $iid);

            if($data->getAction() != SecureManager::CAPTCHA_ACTION){
                if($data->getStatus() == Constant::OK_STATUS){

                    $package_name = Validator::clear($_REQUEST['package_name']);
                    if($package_name == Constant::PACKAGE_NAME_METEOR){
                        $data = new Response("auth", Constant::ERR_STATUS, "Not available now");
                        return $data;
                    }
                    $app_v = Validator::clear($_REQUEST['app_v']);
                    $platform = Validator::clear($_REQUEST['platform']);
                    $device_name = Validator::clear($_REQUEST['device_name']);

                    DataManager::setMainData($package_name, $app_v, $platform, $iid, 0, $device_name);

                    $data = DataManager::getAuthData();

                    HelpManager::updateOnline();
                    BalanceManager::priorityBalance();

                    if($data->getStatus() == Constant::OK_STATUS){
                        $data->setObject(array_merge($data->getObject(), Manager::$user->getArr()));
                    }
                }
            }

        }
        return $data;
    }

    public function update(){
        if(Manager::$user->isAuth()){
                $package_name = Manager::$user->getPackageName();
                $app_v = Manager::$user->getAppVersion();
                $platform = Manager::$user->getPlatform();
                $vtime = Manager::$user->getVtime();
                $iid = Manager::$user->getIid();

                $data = DataManager::authUser(Manager::$user->getLogin(), Manager::$user->getLang(), Manager::$user->getIid());
                if ($data->getStatus() == Constant::OK_STATUS) {
                    DataManager::setMainData($package_name, $app_v, $platform, $iid, $vtime);
                    $data = DataManager::getAuthData();
                    if ($data->getStatus() == Constant::OK_STATUS) {
                        $data->setObject(array_merge($data->getObject(), Manager::$user->getArr()));
                    }
                }
        }else{
            $data = new Response("update", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function logout(){
        Manager::destroySession();

        $data = new Response("logout", Constant::OK_STATUS);

        return $data;
    }

    public function getNews(){
        $data = new Response("getNews", Constant::ERR_STATUS, "No depth value");
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $user_id = Manager::$user->getId();

            $db = DB::getInstance();


            /*$res = $db->select("SELECT n.id
                                FROM  `news` n
                                LEFT JOIN  `feeds` f ON f.news_id = n.id AND f.user_id = $user_id
                                WHERE f.id IS NULL
                                ORDER BY n.id  DESC
                                LIMIT 10");*/
            $time = time();
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT f.id, f.news_id, n.html_json, n.css_json, n.js_json, n.type, f.params FROM `".Constant::FEEDS_TABLE."` f, `".Constant::NEWS_TABLE."` n
									WHERE f.user_id = :user_id
									AND f.fire_time <= :time
									AND f.is_complete = 0
									AND f.news_id = n.id
									ORDER BY f.fire_time DESC
									LIMIT :limit");
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":time", $time);
            $newsLimit = Constant::NEWS_LIMIT;
            $stmt->bindParam(":limit", $newsLimit, PDO::PARAM_INT);

            $news = null;
            if($stmt->execute()){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stime = time();
                while($arr = $stmt->fetch()){
                    $params = "id=".$arr['id']."&stime=".$stime;
                    if(!empty($arr['params'])){
                        $params .= "&".$arr['params'];
                    }
                    $js = "var params = '".$params."';\n".html_entity_decode($arr['js_json']);
                    $news[] = array("news_id" => $arr['news_id'], "html_json" => html_entity_decode($arr['html_json']),
                        "css_json"=> html_entity_decode($arr['css_json']), "js_json"=> $js, "type"=>$arr["type"]);
                }
                $data = new Response("getNews", Constant::OK_STATUS, "", array("news"=>$news));
            }else{
                $data = new Response("getNews", Constant::OK_STATUS, "No news", array("news"=>null));
            }
        }else{
            $data = new Response("getNews", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function getTop(){

        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $db = DB::getInstance();
            $tdate = date("Y-m-d");
            $dbh = $db->getDBH();
            $stmt = $dbh->prepare("SELECT d.login, t.count
								 FROM `".Constant::TOPS_TABLE."` t, `".Constant::USERS_TABLE."` u, `".Constant::DATA_TABLE."` d
								 WHERE u.id = t.user_id AND u.id = d.user_id AND t.tdate = :tdate
								 ORDER BY t.count DESC
								 LIMIT :limit");
            $stmt->bindParam(":tdate", $tdate);
            $limit = Constant::TOP_USERS_LIMIT;
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);

            if($stmt->execute()){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $tops = null;
                $data = $stmt->fetchAll();
                foreach($data as $arr){
                    $tops[] = array("login"=>$arr['login'], "count"=>$arr['count']);
                }
                $midnight = strtotime($tdate.' 00:00:00') + 86400;	//midnight tomorrow
                $left = $midnight - time();

                //If user is not in top than back position 1
                $stmt = $dbh->prepare("SELECT COUNT(*) + 1 as place
								       FROM `".Constant::TOPS_TABLE."`
								       WHERE count > (SELECT count FROM `".Constant::TOPS_TABLE."` WHERE user_id = :user_id ORDER BY id DESC LIMIT 1) AND tdate = :tdate");

                $stmt->bindParam(":tdate", $tdate);
                $user_id = Manager::$user->getId();
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

                $stmt->execute();

                $place = 0;

                if($stmt->rowCount() > 0){
                    $place = $stmt->fetchColumn();
                }

                //do not set tops low than 5 place
                $data = new Response("getTop", Constant::OK_STATUS, "", array("tops"=>$tops, "left"=>$left, "place"=>$place));
            }else{
                $data = new Response("getTop", Constant::ERR_STATUS, "No top error");
            }

        }else{
            $data = new Response("getTop", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function getApps(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            //if we have any error
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $package_name =  Manager::$user->getPackageName();
            $adsNetworkData = UtilsManager::getNetworkId($dbh, $package_name, Constant::APPS_TABLE);
            $apps = array();

            if($adsNetworkData->getStatus() == Constant::OK_STATUS) {
                $obj = $adsNetworkData->getObject();
                $network_id = $obj['network_id'];

                $stmt = $dbh->prepare("SELECT name, app_name, app_id FROM `".Constant::APPS_TABLE."`
                                       WHERE network_id = :network_id AND app_id != :package_name AND outgoing = 0 ORDER BY priority DESC");
                $stmt->bindParam(":network_id", $network_id, PDO::PARAM_INT);
                $stmt->bindParam(":package_name", $package_name, PDO::PARAM_INT);
                if($stmt->execute()){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    while($arr = $stmt->fetch()){
                        $apps[] = $arr;
                    }
                }

            }

            $data = new Response("getApps", Constant::OK_STATUS, "", array("apps" => $apps));
        }else{
            $data = new Response("getApps", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function getAds(){
        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            //if we have any error
            $data = new Response("getAds", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['lang'])){
                $db = DB::getInstance();
                $lang = Validator::clear($_REQUEST['lang']);
                $user_id = Manager::$user->getId();
                $dbh = $db->getDBH();
                $package_name =  Manager::$user->getPackageName();
                $adsNetworkData = UtilsManager::getNetworkId($dbh, $package_name, Constant::ADS_TABLE);
                $ads = array();

                if($adsNetworkData->getStatus() == Constant::OK_STATUS){
                    $obj = $adsNetworkData->getObject();
                    $network_id = $obj['network_id'];

                    $stmt = $dbh->prepare("SELECT a.id, a.name, a.app_name, a.app_id, a.desc1, a.desc2
                                        FROM `".Constant::ADS_TABLE."` a
                                        LEFT JOIN `".Constant::ADS_FEED_TABLE."` af ON a.id = af.ad_id AND af.user_id = :user_id AND (af.status = 1 OR repeat_cnt > :repeat)
                                        WHERE af.id IS NULL AND a.target_lang IN ('all', :lang) AND a.network_id = :network_id AND a.app_id != :package_name AND a.outgoing = 0
                                        ORDER BY a.priority DESC
                                        LIMIT 5");
                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(":network_id", $network_id, PDO::PARAM_INT);
                    $stmt->bindParam(":package_name", $package_name);

                    $repeat = Constant::WATCH_AD_LIMIT;
                    $stmt->bindParam(":repeat", $repeat, PDO::PARAM_INT);
                    $stmt->bindParam(":lang", $lang);
                    $stmt->execute();
                    if($stmt->rowCount() > 0){
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $data = $stmt->fetchAll();
                        foreach($data as $arr){
                            $ads[] = $arr;
                        }
                    }
                }

                $data = new Response("getAds", Constant::OK_STATUS, "", array("ads" => $ads));
            }
        }else{
            $data = new Response("getAds", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function donate(){

    }

    public function setComplete(){
        $data = new Response("setComplete", Constant::ERR_STATUS, "No depth value");
        if(isset($_REQUEST['name']) && isset($_REQUEST['id'])&& isset($_REQUEST['value'])){
            if(Manager::$user->isAuth()){

                $data = SecureManager::isVerify();
                if($data){return $data;}

                $package_name = Manager::$user->getPackageName();
                $app_v = Manager::$user->getAppVersion();
                $platform =  Manager::$user->getPlatform();
                $vtime = Manager::$user->getVtime();
                $iid = Manager::$user->getIid();

                $user_id = Manager::$user->getId();
                $value = Validator::clear($_REQUEST['value']);
                $name = Validator::clear($_REQUEST['name']);
                $id = Validator::clear($_REQUEST['id']);
                $dataNews = NewsManager::completeFeed($user_id, $value, $name, $id);
                if($dataNews->getStatus() == Constant::OK_STATUS){
                    $data = DataManager::authUser(Manager::$user->getLogin(), Manager::$user->getLang(), Manager::$user->getIid());
                    if($data->getStatus() == Constant::OK_STATUS){
                        DataManager::setMainData($package_name, $app_v, $platform, $iid, $vtime);
                        $data = DataManager::getAuthData();
                        if($data->getStatus() == Constant::OK_STATUS){
                            $data->setObject(array_merge($data->getObject(), Manager::$user->getArr()));
                        }
                    }
                }
            }else{
                $data = new Response("setComplete", Constant::ERR_STATUS, "Auth error");
            }
        }
        return $data;
    }

    public function subscribe(){
        if(Manager::$user->isAuth()){
            //if we have any error
            $data = new Response("subscribe", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['purchase_token']) && isset($_REQUEST['platform'])) {
                $db = DB::getInstance();
                $platform = Validator::clear($_REQUEST['platform']);
                $purchase_token = Validator::clear($_REQUEST['purchase_token']);
                $user_id = Manager::$user->getId();
                $dbh = $db->getDBH();
                //$stmt = $dbh->prepare("SELECT id, product_id, status FROM `".Constant::SUBSCRIBES_TABLE."` WHERE purchase_token = :purchase_token AND user_id = :user_id");
                $stmt = $dbh->prepare("SELECT id, product_id, status FROM `".Constant::SUBSCRIBES_TABLE."` WHERE purchase_token = :purchase_token");
                $stmt->bindParam(":purchase_token", $purchase_token);
                //$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                if($stmt->execute()){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    $product_id = $arr['product_id'];
                    $turbo = HelpManager::getTurbo($product_id);
                    //if($arr['status'] == Constant::INIT_SUBSCRIBE_STATUS){
                    if(true){
                        $id = $arr['id'];
                        $priority =  HelpManager::getPriority($product_id);
                        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET turbo = :turbo, priority = :priority WHERE id = :user_id");
                        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                        $stmt->bindParam(":turbo", $turbo, PDO::PARAM_INT);
                        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
                        $stmt->execute();

                        $stmt = $dbh->prepare("UPDATE `".Constant::SUBSCRIBES_TABLE."` SET status = :status WHERE id = :id");
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $status = Constant::OK_SUBSCRIBE_STATUS;
                        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                        $stmt->execute();

                        Manager::$user->setTurbo($turbo);
                        Manager::$user->setPriority($priority);
                        SessionManager::updateUser(Manager::$user);
                        HelpManager::updateTablesPriority();

                        $data = new Response("purchase", Constant::OK_STATUS, "", array("turbo"=>$turbo));
                    }else if($arr['status'] == Constant::OK_SUBSCRIBE_STATUS){
                        $data = new Response("purchase", Constant::OK_STATUS, "", array("turbo"=>$turbo));
                    }else{
                        $data = new Response("subscribe", Constant::ERR_STATUS, "Get error");
                    }
                }else{
                    $data = new Response("subscribe", Constant::ERR_STATUS, "Get error");
                }
            }
        }else{
            $data = new Response("subscribe", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function rateAward(){
        if(Manager::$user->isAuth()){

            $withdrawData = CashManager::deposit(Constant::RATE_BONUS);
            if($withdrawData->getStatus() == Constant::OK_STATUS){
                $cashData = CashManager::getCash();
                if($cashData->getStatus() == Constant::OK_STATUS){
                    $data = new Response("rateAward", Constant::OK_STATUS, "", $cashData->getObject());
                }else{
                    $data = new Response("rateAward", Constant::ERR_STATUS, "Cash error");
                }
            }else{
                $data = new Response("rateAward", Constant::ERR_STATUS, "Withdraw error");
            }
        }else{
            $data = new Response("rateAward", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function purchase(){
        if(Manager::$user->isAuth()){
            //if we have any error
            $data = new Response("purchase", Constant::ERR_STATUS, "No depth value");

            if (isset($_REQUEST['purchase_token']) && isset($_REQUEST['platform'])) {
                $db = DB::getInstance();
                $user_id = Manager::$user->getId();
                $purchase_token = Validator::clear($_REQUEST['purchase_token']);
                $platform = Validator::clear($_REQUEST['platform']);
                $dbh = $db->getDBH();

               /* if($platform == Constant::PLATFORM_IOS){
                    $stmt = $dbh->prepare("SELECT purchase_token FROM `".Constant::PURCHASES_TABLE."` WHERE status = :status AND user_id = :user_id ORDER BY id DESC");
                    $status = Constant::INIT_PURCHASE_STATUS;
                    $stmt->bindParam(":status", $status);
                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    if($stmt->rowCount() > 0){
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $purchase_token = $stmt->fetchColumn();
                    }
                }*/
                //file_put_contents("purchase", $purchase_token);


                $stmt = $dbh->prepare("SELECT id, product_id FROM `".Constant::PURCHASES_TABLE."` WHERE  purchase_token = :purchase_token AND status = :status AND user_id = :user_id");
                $stmt->bindParam(":purchase_token", $purchase_token);
                $status = Constant::INIT_PURCHASE_STATUS;
                $stmt->bindParam(":status", $status);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $arr = $stmt->fetch();
                    $product_id = $arr['product_id'];
                    $id = $arr['id'];
                    if($product_id != "premium" && $product_id != "gsm_rf_premium_new" && $product_id != "fugs_rlt_premium" && $product_id != "rfp_naut_premium" ){
                        $diamonds = CashManager::getDiamonds($product_id);

                        $withdrawData = CashManager::deposit($diamonds);
                        if($withdrawData->getStatus() == Constant::OK_STATUS){
                            ReferalManager::referalDeposit($diamonds);
                            $stmt = $dbh->prepare("UPDATE `".Constant::PURCHASES_TABLE."` SET status = :status WHERE id = :id");
                            $status = Constant::OK_PURCHASE_STATUS;
                            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                            $stmt->execute();
                        }else{
                            $data = new Response("purchase", Constant::ERR_STATUS, "Withdraw error");
                        }
                    }else if($product_id == "premium" || $product_id == "gsm_rf_premium_new" || $product_id == "fugs_rlt_premium" || $product_id == "rfp_naut_premium"){
                        $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET premium = :premium WHERE id = :user_id");
                        $premium = Constant::PREMIUM_ON;
                        $stmt->bindParam(":premium", $premium, PDO::PARAM_INT);
                        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                        $stmt->execute();

                        Manager::$user->setPremium($premium);
                        SessionManager::updateUser(Manager::$user);

                        $stmt = $dbh->prepare("UPDATE `".Constant::PURCHASES_TABLE."` SET status = :status WHERE id = :id");
                        $status = Constant::OK_PURCHASE_STATUS;
                        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    $cashData = CashManager::getCash();
                    if($cashData->getStatus() == Constant::OK_STATUS){
                        $data = new Response("purchase", Constant::OK_STATUS, "", $cashData->getObject());
                    }else{
                        $data = new Response("purchase", Constant::ERR_STATUS, "Cash error");
                    }
                }else{
                    $data = new Response("purchase", Constant::ERR_STATUS, "Get error");
                }
            }
        }else{
            $data = new Response("purchase", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

}