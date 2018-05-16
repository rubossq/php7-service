<?php
namespace Famous\Lib\Managers;
use Famous\Core\Route;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Cash as Cash;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Managers\BalanceManager as BalanceManager;
use Famous\Lib\Utils\Config;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Helper;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Utils\LiqPay as LiqPay;
use \PDO as PDO;
use \Exception as Exception;
use \NumberFormatter as NumberFormatter;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class LiqpayManager
{

    const LIVE_MODE = 1;
    const TEST_MODE = 2;

    const CUR_MODE = 1;

    const GOOD_STATUS = 1;
    const BAD_STATUS = 2;
    const CANCEL_STATUS = 3;
    const TEMP_STATUS = 4;


    const BONUS_PERCENT = 15;
    const MONTH_EXPIRY_TIME = 2592000;

    const API_VERSION = 3;

    const PAY_SESSIONS_TABLE = "lq_pay_sessions";

    public static function getEmbed($user_id, $productId, &$dbh){

        $data = new Response("getEmbed", Constant::ERR_STATUS, "No depth err");


        $product = self::getProduct($productId);
        if($product){

            $session_id = self::openPaySession($dbh, $user_id);

            $params['version'] = self::API_VERSION;
            $params['public_key'] = self::PROJECT_KEY;
            $type = HelpManager::getProductType($productId);
            $params['action'] = $type == Constant::CONSUMABLE_TYPE ? "pay" : "subscribe" ;
            if($type == Constant::SUBSCRIPTION_TYPE){
                $params['subscribe'] = 1;
                $params['subscribe_periodicity'] = "month";

                $date = new \DateTime();
                $date->setTimezone(new \DateTimeZone('UTC'));
                $params['subscribe_date_start'] = $date->format("Y-m-d H:i:s");
            }
            $params['amount'] = $product['price'];
            $params['currency'] = self::DEFAULT_CURRENCY;
            $params['description'] = $product['name'];
            $params['order_id'] = $productId."_".time()."_".$session_id;
            $params['language'] = self::DEFAULT_LANGUAGE;
            $params['customer'] = $user_id;

            $params['product_name'] = $productId;
            $params['product_description'] = $product['alias'];


            if(self::CUR_MODE == self::TEST_MODE){
                $params['sandbox'] = 1;
            }

            $params['server_url'] = Route::getUrl()."/liqpay/ping_back/";

            //print_r($params);

            $liqpay = new LiqPay(self::PROJECT_KEY, self::SECRET_KEY);

            $params    = $liqpay->cnb_params($params);
            //echo json_encode($params);
            $data      = base64_encode( json_encode($params) );
            $signature = $liqpay->cnb_signature($params);




            self::prepareSession($data, $signature, $session_id, $dbh);
            $data = new Response("getEmbed", Constant::OK_STATUS, "", array("data"=>$data, "signature"=>$signature, "session_id"=>$session_id));
        }


        return $data;
    }

    public static function prepareSession($data, $signature, $session_id, &$dbh){
        $stmt = $dbh->prepare("UPDATE `".self::PAY_SESSIONS_TABLE."` SET data = :data, signature = :signature WHERE id = :session_id");
        $stmt->bindParam(":session_id", $session_id, PDO::PARAM_INT);
        $stmt->bindParam(":data", $data);
        $stmt->bindParam(":signature", $signature);
        $stmt->execute();
    }

    public static function cancelSubscription($user_id, $id, &$dbh){

        $stmt = $dbh->prepare("SELECT product_id, order_id FROM `".self::SUBSCRIBES_TABLE."` WHERE id = :id AND status = :status");
        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $product_id = $arr['product_id'];
            $order_id = $arr['order_id'];
            //file_put_contents("lqo.txt", $order_id);
            $liqpay = new LiqPay(self::PROJECT_KEY, self::SECRET_KEY);
            $res = $liqpay->api("request", array(
                'action'        => 'unsubscribe',
                'version'       => self::API_VERSION,
                'order_id'      => $order_id
            ));
            file_put_contents("lqu.txt", json_encode($res));
            if($res->status == "unsubscribed" || $res->status == "sandbox"){
                self::cancelSubscribe($dbh, $product_id, $user_id, $id, true);
                self::checkTurbos($dbh, $user_id, time(), HelpManager::getTurbo($product_id));
                $data = new Response("cancelSubscription", Constant::OK_STATUS);
            }else{
                $data = new Response("cancelSubscription", Constant::ERR_STATUS, "Some api error");
            }


        }else{
            $data = new Response("cancelSubscription", Constant::ERR_STATUS, "Subscription expire or doesn't exists");
        }


        return $data;
    }

    public static function getSessionData($session_id, $user_id, &$dbh){
        $data = new Response("getSessionData", Constant::ERR_STATUS, "No depth err");

        $stmt = $dbh->prepare("SELECT data, signature FROM `".self::PAY_SESSIONS_TABLE."` WHERE id = :session_id AND user_id = :user_id");
        $stmt->bindParam(":session_id", $session_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        //echo "SELECT data, signature FROM `".self::PAY_SESSIONS_TABLE."` WHERE id = $session_id AND user_id = $user_id";

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $arr['params'] = base64_decode($arr['data']);
            $data = new Response("getEmbed", Constant::OK_STATUS, "", $arr);
        }

        return $data;
    }

    public static function checkSession($user_id, $order_id, &$dbh){
        $arr = explode("_", $order_id);
        $order_id = $arr[count($arr)-1];
        $stmt = $dbh->prepare("SELECT stime FROM `".self::PAY_SESSIONS_TABLE."` WHERE user_id = :user_id AND id = :order_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":order_id", $order_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
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

            $stmt = $dbh->prepare("SELECT product_id FROM `".self::PURCHASES_TABLE."` WHERE user_id = :user_id AND purchase_time >= :stime AND status = :status");
            $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
            $status = self::GOOD_STATUS;
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $products = array();
            if($stmt->rowCount() > 0){
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $products = array_merge($products, $stmt->fetchAll());
            }

            $stmt = $dbh->prepare("SELECT product_id FROM `".self::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND purchase_time >= :stime AND status = :status");
            $stmt->bindParam(":stime", $stime, PDO::PARAM_INT);
            $status = self::GOOD_STATUS;
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
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

    public static function getOrderList(&$dbh, $user_id){

        $stmt = $dbh->prepare("SELECT id, product_id, type, purchase_time FROM `".self::PURCHASES_TABLE."` WHERE user_id = :user_id AND status = :status ORDER BY id DESC");
        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $subscribes = array();
        $products = array();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();

            foreach($data as $d){
                $d['purchase_time'] = time() - $d['purchase_time'];
                $products[] = $d;
            }
        }

        $stmt = $dbh->prepare("SELECT id, product_id, expiry_time, type, purchase_time FROM `".self::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND status = :status ORDER BY id DESC");
        $status = self::CANCEL_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();

            foreach($data as $d){
                $d['expiry_time'] = $d['expiry_time'] - time();
                $d['purchase_time'] = time() - $d['purchase_time'];
                $products[] = $d;
            }
        }

        $stmt = $dbh->prepare("SELECT id, product_id, expiry_time, type, purchase_time FROM `".self::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND status = :status ORDER BY expiry_time");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();

            foreach($data as $d){
                $d['expiry_time'] = $d['expiry_time'] - time();
                $d['purchase_time'] = time() - $d['purchase_time'];
                $subscribes[] = $d;
            }
        }

        $products = self::getGoodsInfo($products);
        usort($products, array("Famous\\Lib\\Utils\\Helper", "productsSort"));
        $subscribes = self::getGoodsInfo($subscribes);

        $data = new Response("getOrderList", Constant::OK_STATUS, "", array("subscribes"=>$subscribes, "purchases"=>$products));

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

        $products[] = array("id" => "ipack_1", "price" => 0.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x1000", "name"=>"1000 diamonds");
        $products[] = array("id" => "ipack_2", "price" => 1.49, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x2000", "name"=>"2000 diamonds");
        $products[] = array("id" => "pack_3", "price" => 3.49, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x5000", "name"=>"5000 diamonds");
        $products[] = array("id" => "pack_4", "price" => 5.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x10 000", "name"=>"10000 diamonds");
        $products[] = array("id" => "pack_5", "price" => 9.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x20 000", "name"=>"20000 diamonds");

        $products[] = array("id" => "pack_1_vip", "price" => 29.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x100 000", "name"=>"100000 diamonds");
        $products[] = array("id" => "pack_2_vip", "price" => 99.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x500 000", "name"=>"500000 diamonds");
        $products[] = array("id" => "pack_3_vip", "price" => 149.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"x1 000 000", "name"=>"1000000 diamonds");

        $products[] = array("id" => "premium", "price" => 0.99, "type" => Constant::CONSUMABLE_TYPE, "alias"=>"", "name"=>"Premium account");

        $products[] = array("id" => "turbos_1", "price" => 0.99, "type" => Constant::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo green");
        $products[] = array("id" => "iturbos_2", "price" => 1.99, "type" => Constant::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo blue");
        $products[] = array("id" => "turbos_3", "price" => 3.99, "type" => Constant::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo red");
        $products[] = array("id" => "turbos_5", "price" => 9.99, "type" => Constant::SUBSCRIPTION_TYPE, "alias"=>"", "name"=>"Turbo dark");

        return $products;
    }

    private static function getProduct($product_id){
        $products = self::getAvailableProducts();
        foreach($products as $p){
            if($p['id'] == $product_id){
                return $p;
            }
        }

        return null;
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

    private static function getGoodsInfo($packages){
        $products = self::getAvailableProducts();
        $goods = array();
        for($i = 0; $i<count($packages); $i++){
            for($j = 0; $j<count($products); $j++){
                if($products[$j]['id'] == $packages[$i]['product_id']){
                     $arr = array("id"=>$packages[$i]['id'], "pack_id"=>$packages[$i]['product_id'], "purchase_time"=>$packages[$i]['purchase_time'], "type"=> $packages[$i]['type'], "name"=>$products[$j]['name']);
                     if($products[$j]['type'] == Constant::SUBSCRIPTION_TYPE){
                        $arr['expiry_time'] = $packages[$i]['expiry_time'];
                     }
                     $goods[] = $arr;
                }
            }
        }
        return $goods;
    }

    public static function managePurchase(&$dbh, $ref, $type, $productId, $user_id, $amount, $order_id){
        $stmt = $dbh->prepare("SELECT id, status FROM `".self::PURCHASES_TABLE."` WHERE purchase_token = :purchase_token");
        $stmt->bindParam(":purchase_token", $ref);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            if($arr['status'] != self::convertStatus($type)) {
                if (self::isDeliverable(self::convertStatus($type))) {
                    self::deliverPurchase($dbh, $productId, $user_id, $ref, $amount, $order_id, $arr['id']);
                } else if (self::isCancelable(self::convertStatus($type))) {
                    self::cancelPurchase($dbh, $productId, $user_id, $arr['id']);
                }
            }
        }else{
            if(self::isDeliverable(self::convertStatus($type))){
                self::deliverPurchase($dbh, $productId, $user_id, $ref, $amount, $order_id);
            }
        }
    }

    private static function isCancelable($type){
        if($type == self::BAD_STATUS){
            return true;
        }
        return false;
    }

    private static function isDeliverable($type){
        if($type == self::GOOD_STATUS){
            return true;
        }
        return false;
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

    private static function deliverPurchase(&$dbh, $product_id, $user_id, $ref, $amount, $order_id, $id = null){

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
        $type = Constant::CONSUMABLE_TYPE;

        if($id){
            $stmt = $dbh->prepare("UPDATE `".self::PURCHASES_TABLE."` SET status = :status WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        }else{
            $stmt = $dbh->prepare("INSERT INTO `".self::PURCHASES_TABLE."` (product_id, purchase_time, purchase_token, type, user_id, status, amount, order_id)
                                   VALUES(:product_id, :purchase_time, :purchase_token, :type, :user_id, :status, :amount, :order_id)");
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":purchase_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":purchase_token", $ref);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":order_id", $order_id);
            $stmt->bindParam(":amount", $amount, PDO::PARAM_STR);
            $stmt->bindParam(":user_id", $user_id);
        }

        $status = self::GOOD_STATUS;
        $stmt->bindParam(":status", $status, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function convertStatus($status){
        if(in_array($status, array("success", "subscribed", "sandbox", "wait_accept"))){
            $status = self::GOOD_STATUS;
        }
        else  if(in_array($status, array("unsubscribed", "reversed", "error", "failure"))){
            $status = self::BAD_STATUS;
        }else{
            $status = self::TEMP_STATUS;
        }

        return $status;
    }

    public static function manageSubscribe(&$dbh, $ref, $type, $productId, $user_id, $amount, $order_id){
        $stmt = $dbh->prepare("SELECT id, status FROM `".self::SUBSCRIBES_TABLE."` WHERE purchase_token = :purchase_token");
        $stmt->bindParam(":purchase_token", $ref);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $arr = $stmt->fetch();
            $status = self::convertStatus($type);
            if($arr['status'] != $status) {
                if (self::isDeliverable(self::convertStatus($type))) {
                    self::deliverSubscribe($dbh, $productId, $user_id, $ref, $amount, $order_id, $arr['id']);
                } else if (self::isCancelable(self::convertStatus($type))) {
                    self::cancelSubscribe($dbh, $productId, $user_id, $arr['id']);
                }
            }
        }else{
            if(self::isDeliverable(self::convertStatus($type))){
                self::deliverSubscribe($dbh, $productId, $user_id, $ref, $amount, $order_id, null);
            }
        }
    }

    private static function deliverSubscribe(&$dbh, $product_id, $user_id, $ref, $amount, $order_id, $id = null){

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
        $type = Constant::SUBSCRIPTION_TYPE;
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
                                   (product_id, user_id, purchase_token, purchase_time, type, status, start_time, expiry_time, amount, order_id)
                                    VALUES (:product_id, :user_id, :purchase_token, :purchase_time, :type, :status, :start_time, :expiry_time, :amount, :order_id)");
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":purchase_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":start_time", $purchase_time, PDO::PARAM_INT);
            $stmt->bindParam(":expiry_time", $expiry_time, PDO::PARAM_INT);
            $stmt->bindParam(":purchase_token", $ref);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":order_id", $order_id);
            $stmt->bindParam(":amount", $amount, PDO::PARAM_STR);
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
        $stmt = $dbh->prepare("SELECT id, product_id FROM `".self::SUBSCRIBES_TABLE."` WHERE user_id = :user_id AND status = :status AND expiry_time > :expiry_time");
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