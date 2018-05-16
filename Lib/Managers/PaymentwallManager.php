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
use \NumberFormatter as NumberFormatter;
use \Paymentwall_Pingback as Paymentwall_Pingback;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class PaymentwallManager
{

    const LIVE_MODE = 1;
    const TEST_MODE = 2;

    const CUR_MODE = 1;

    const CONSUMABLE_TYPE = "consumable";
    const SUBSCRIPTION_TYPE = "subscription";

    const PURCHASES_TABLE = "pwpurchases";
    const SUBSCRIBES_TABLE = "pwsubscribes";

    const GOOD_STATUS = 1;
    const BAD_STATUS = 2;
    const CANCEL_STATUS = 3;

    const BONUS_PERCENT = 15;
    const MONTH_EXPIRY_TIME = 2592000;

    const PAY_SESSIONS_TABLE = "pay_sessions";

    public static function getWidget($user_id, $email, $real_rtime, $display_goodsid, &$dbh, $lang = null, $country_code = null){

        $params['key'] = self::PROJECT_KEY;
        $params['widget'] = self::WIDGET_CODE;

        if(self::CUR_MODE == self::TEST_MODE){
            $params['key'] = self::TEST_PROJECT_KEY;
            $params['widget'] = self::TEST_WIDGET_CODE;
            $params['evaluation'] = 1;
        }


        $params['uid'] = $user_id;
        $params['email'] = $email;
        $params['display_goodsid'] = $display_goodsid;

        if($lang){
            $params['lang'] = $lang;
        } if($country_code){
            $params['country_code'] = $country_code;
        }

        $params['history[registration_date]'] = $real_rtime;


        $session_id = self::openPaySession($dbh, $user_id);

        $data = new Response("getWidget", Constant::OK_STATUS, "", array("url"=>self::API_URL . "?" . http_build_query($params), "session_id"=>$session_id));

        return $data;
    }

    private static function openPaySession(&$dbh, $user_id){
        $stmt = $dbh->prepare("INSERT INTO `".self::PAY_SESSIONS_TABLE."` (user_id, stime) VALUES(:user_id, :stime)");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stime = time();
        $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
        $stmt->execute();

        return $dbh->lastInsertId();
    }

    private static function closePaySessions(&$dbh, $user_id){
        $stmt = $dbh->prepare("UPDATE `".self::PAY_SESSIONS_TABLE."` SET is_watched = 1 WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getGoods(&$dbh, $user_id){
        $stmt = $dbh->prepare("SELECT stime FROM `".self::PAY_SESSIONS_TABLE."` WHERE user_id = :user_id AND is_watched = 0 ORDER BY id LIMIT 1");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stime = $stmt->fetchColumn();

            $stmt = $dbh->prepare("SELECT product_id FROM `".self::PURCHASES_TABLE."` WHERE user_id = :user_id AND purchase_time >= :stime");
            $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $products = array();
            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $products = array_merge($products, $stmt->fetchAll());
            }

            $stmt = $dbh->prepare("SELECT product_id FROM `".self::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND purchase_time >= :stime");
            $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $products = array_merge($products, $stmt->fetchAll());
            }

            $goods = self::getGoodsNames($products);

            $data = new Response("getGoods", Constant::OK_STATUS, "", array("goods"=>$goods));
        }else{
            $data = new Response("getGoods", Constant::ERR_STATUS, "No goods taken");
        }

        self::closePaySessions($dbh, $user_id);

        return $data;
    }

    public static function getProducts($locale){

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $currency =  $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        $products = self::getAvailableProducts();
        $rate = self::getRate(self::DEFAULT_CURRENCY, $currency);

        $data = new Response("getProducts", Constant::OK_STATUS, "", array("currency"=>$currency, "products"=>$products, "default_currency"=>self::DEFAULT_CURRENCY, "rate"=>$rate));

        return $data;
    }

    public static function getRate($default, $current){
        $api = str_replace("CURRENCYCURRENCY", $default.$current, self::FINANCE_API);
        $json = file_get_contents($api);
        $obj = json_decode($json);

        return $obj->query->results->rate->Rate;
    }

    public static function getAvailableProducts(){
        $products = array();

        $products[] = array("id" => "ipack_1", "price" => 0.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x1000", "name"=>"1000 diamonds");
        $products[] = array("id" => "ipack_2", "price" => 1.49, "type" => self::CONSUMABLE_TYPE, "alias"=>"x2000", "name"=>"2000 diamonds");
        $products[] = array("id" => "pack_3", "price" => 3.49, "type" => self::CONSUMABLE_TYPE, "alias"=>"x5000", "name"=>"5000 diamonds");
        $products[] = array("id" => "pack_4", "price" => 5.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x10 000", "name"=>"10000 diamonds");
        $products[] = array("id" => "pack_5", "price" => 9.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x20 000", "name"=>"20000 diamonds");

        $products[] = array("id" => "pack_1_vip", "price" => 29.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x100 000", "name"=>"100000 diamonds");
        $products[] = array("id" => "pack_2_vip", "price" => 99.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x500 000", "name"=>"500000 diamonds");
        $products[] = array("id" => "pack_3_vip", "price" => 149.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"x1 000 000", "name"=>"1000000 diamonds");

        $products[] = array("id" => "premium", "price" => 0.99, "type" => self::CONSUMABLE_TYPE, "alias"=>"", "name"=>"Premium account");

        $products[] = array("id" => "turbos_1", "price" => 0.99, "type" => self::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo green");
        $products[] = array("id" => "iturbos_2", "price" => 1.99, "type" => self::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo blue");
        $products[] = array("id" => "turbos_3", "price" => 3.99, "type" => self::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo red");
        $products[] = array("id" => "turbos_5", "price" => 9.99, "type" => self::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo dark");

        return $products;
    }

    private static function getGoodsNames($packages){
        $products = self::getAvailableProducts();
        $goods = array();
        for($i = 0; $i<count($packages); $i++){
            for($j = 0; $j<count($products); $j++){
                if($products[$j]['id'] == $packages[$i]['product_id']){
                    $bonus = self::getBonus($packages[$i]['product_id']);
                    $goods[] = array("id"=>$packages[$i]['product_id'], "name"=>$products[$j]['name'], "bonus" => $bonus);
                }
            }
        }
        return $goods;
    }

    public static function managePurchase(&$dbh, &$pingback, $ref, $type, $productId, $user_id ){
        $stmt = $dbh->prepare("SELECT id, status FROM `".self::PURCHASES_TABLE."` WHERE purchase_token = :purchase_token");
        $stmt->bindParam(":purchase_token", $ref);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            if($arr['status'] != self::convertStatus($type)) {
                if ($pingback->isDeliverable()) {
                    self::deliverPurchase($dbh, $productId, $user_id, $ref, $arr['id']);
                } else if ($pingback->isCancelable()) {
                    self::cancelPurchase($dbh, $productId, $user_id, $arr['id']);
                }
            }
        }else{
            if($pingback->isDeliverable()){
                self::deliverPurchase($dbh, $productId, $user_id, $ref);
            }
        }
    }

    private static function cancelPurchase(&$dbh, $product_id, $user_id, $id){

        $bonus = self::getBonus($product_id);

        if($product_id != "premium"){
            $diamonds = CashManager::getDiamonds($product_id) + $bonus;
            CashManager::withdraw($diamonds, $user_id);
        }else if($product_id == "premium") {
            $stmt = $dbh->prepare("UPDATE `" . Constant::USERS_TABLE . "` SET premium = :premium WHERE id = :user_id");
            $premium = Constant::PREMIUM_OFF;
            $stmt->bindParam(":premium", $premium, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            CashManager::withdraw($bonus, $user_id);
        }

        $stmt = $dbh->prepare("UPDATE `".self::PURCHASES_TABLE."` SET status = :status WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $status = self::BAD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function deliverPurchase(&$dbh, $product_id, $user_id, $ref, $id = null){

        $bonus = self::getBonus($product_id);

        if($product_id != "premium"){
            $diamonds = CashManager::getDiamonds($product_id) + $bonus;
            $withdrawData = CashManager::deposit($diamonds, $user_id);
            if($withdrawData->getStatus() == Constant::OK_STATUS){
                ReferalManager::referalDeposit($diamonds, $user_id);
            }
        }else if($product_id == "premium") {
            $stmt = $dbh->prepare("UPDATE `" . Constant::USERS_TABLE . "` SET premium = :premium WHERE id = :user_id");
            $premium = Constant::PREMIUM_ON;
            $stmt->bindParam(":premium", $premium, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $diamonds = $bonus;
            $withdrawData = CashManager::deposit($diamonds, $user_id);
            if($withdrawData->getStatus() == Constant::OK_STATUS){
                ReferalManager::referalDeposit($diamonds, $user_id);
            }

        }

        $purchase_time = time();
        $type = self::CONSUMABLE_TYPE;

        if($id){
            $stmt = $dbh->prepare("UPDATE `".self::PURCHASES_TABLE."` SET status = :status WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        }else{
            $stmt = $dbh->prepare("INSERT INTO `".self::PURCHASES_TABLE."` (product_id, purchase_time, purchase_token, type, user_id, status)
                                   VALUES(:product_id, :purchase_time, :purchase_token, :type, :user_id, :status)");
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":purchase_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":purchase_token", $ref);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":user_id", $user_id);
        }

        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function convertStatus($status){
        if(in_array($status, array(Paymentwall_Pingback::PINGBACK_TYPE_REGULAR, Paymentwall_Pingback::PINGBACK_TYPE_GOODWILL))){
            $status = self::GOOD_STATUS;
        }
        else{
            $status = self::BAD_STATUS;
        }

        return $status;
    }

    public static function manageSubscribe(&$dbh, &$pingback, $ref, $type, $productId, $user_id, $s_length, $s_period ){
        $stmt = $dbh->prepare("SELECT id, status FROM `".self::SUBSCRIBES_TABLE."` WHERE purchase_token = :purchase_token");
        $stmt->bindParam(":purchase_token", $ref);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $status = self::convertStatus($type);
            if($arr['status'] != $status) {
                if ($pingback->isDeliverable()) {
                    self::deliverSubscribe($dbh, $productId, $user_id, $ref, $arr['id'], $s_length, $s_period );
                } else if ($pingback->isCancelable()) {
                    self::cancelSubscribe($dbh, $productId, $user_id, $arr['id']);
                }else{
                    self::cancelSubscribe($dbh, $productId, $user_id, $arr['id'], true);
                }
            }
        }else{
            if($pingback->isDeliverable()){
                self::deliverSubscribe($dbh, $productId, $user_id, $ref, null, $s_length, $s_period);
            }
        }
    }

    private static function deliverSubscribe(&$dbh, $product_id, $user_id, $ref, $id = null, $s_length, $s_period){

        $bonus = self::getBonus($product_id);
        $diamonds = $bonus;
        $withdrawData = CashManager::deposit($diamonds, $user_id);
        if($withdrawData->getStatus() == Constant::OK_STATUS){
            ReferalManager::referalDeposit($diamonds, $user_id);
        }

        $turbo = HelpManager::getTurbo($product_id);

        $priority = HelpManager::getPriority($product_id);

        $stmt = $dbh->prepare("UPDATE `" . Constant::USERS_TABLE . "` SET turbo = :turbo, priority = :priority WHERE id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":turbo", $turbo, PDO::PARAM_INT);
        $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
        $stmt->execute();

        //update or create
        $purchase_time = time();
        $type = self::SUBSCRIPTION_TYPE;
        //$expiry_time = time();
        //if($s_period == "month"){
            $expiry_time = time() + self::MONTH_EXPIRY_TIME;
        //}

        if($id){
            $stmt = $dbh->prepare("UPDATE `" . self::SUBSCRIBES_TABLE . "` SET status = :status, expiry_time = :expiry_time WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":expiry_time", $expiry_time, PDO::PARAM_INT);
        }else{
            $stmt = $dbh->prepare("INSERT INTO `" . self::SUBSCRIBES_TABLE . "`
                                   (product_id, user_id, purchase_token, purchase_time, type, status, start_time, expiry_time)
                                    VALUES (:product_id, :user_id, :purchase_token, :purchase_time, :type, :status, :start_time, :expiry_time)");
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":purchase_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":start_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":expiry_time", $expiry_time, PDO::PARAM_INT);
            $stmt->bindParam(":purchase_token", $ref);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":user_id", $user_id);
        }

        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        HelpManager::updateTablesPriority($user_id, $priority, $dbh);

    }

    public static function getBonus($product_id){
        $diamonds = CashManager::getDiamonds($product_id);
        if(!$diamonds){
            $diamonds = self::getProductBonus($product_id);
        }else{
            $diamonds = round($diamonds * (self::BONUS_PERCENT/100));
        }

        return $diamonds;
    }

    private static function getProductBonus($product_id){
        $products = array("turbos_1" => 250, "iturbos_2" => 500, "turbos_3" => 750, "turbos_4" => 1000, 'premium' => 250);
        return $products[$product_id];
    }

    private static function cancelSubscribe(&$dbh, $product_id, $user_id, $id, $mode = false){
        $turbo = HelpManager::getTurbo($product_id);

        //check for another subscription
        $status = self::BAD_STATUS;

        if(!$mode){
            $bonus = self::getBonus($product_id);
            CashManager::withdraw($bonus, $user_id);
        }else{
            $status = self::CANCEL_STATUS;
        }

        $stmt = $dbh->prepare("UPDATE `".self::SUBSCRIBES_TABLE."` SET status = :status WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();


        if(!$mode){
            self::checkTurbos($dbh,$user_id, time(), HelpManager::getTurbo($product_id));
        }

    }

    public static function checkTurbos(&$dbh, $user_id, $expiry_time, $turbo){
        $stmt = $dbh->prepare("SELECT id, product_id FROM `".PaymentwallManager::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND status = :status AND expiry_time > :expiry_time");
        $status = self::GOOD_STATUS;

        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":expiry_time", $expiry_time, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() == 0){
            $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET turbo = 0, priority = 0 WHERE id = $user_id AND turbo = $turbo");
            $stmt->execute();
            HelpManager::updateTablesPriority($user_id, 0, $dbh);
        }else{
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $turbo = HelpManager::getTurbo($arr['product_id']);
            $priority = HelpManager::getPriority($arr['product_id']);

            $stmt = $dbh->prepare("UPDATE `".Constant::USERS_TABLE."` SET turbo = :turbo, priority = :priority WHERE id = :user_id");
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":turbo", $turbo, PDO::PARAM_INT);
            $stmt->bindParam(":priority", $priority, PDO::PARAM_INT);
            $stmt->execute();
            HelpManager::updateTablesPriority($user_id, $priority, $dbh);
        }
    }

}