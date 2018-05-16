<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\DataManager;
use Famous\Lib\Managers\DonateManager;
use Famous\Lib\Managers\HelpManager;
use Famous\Lib\Managers\LiqpayManager;
use Famous\Lib\Managers\PhantomManager;
use Famous\Lib\Managers\ReferalManager;

use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB;
use Famous\Lib\Utils\LiqPay;
use Famous\Lib\Utils\Validator;

require_once($_SERVER['DOCUMENT_ROOT'] . '/paymentwall/lib/paymentwall.php');

use \Paymentwall_Config as Paymentwall_Config;
use \Paymentwall_Pingback as Paymentwall_Pingback;
use \PDO as PDO;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */
class Model_Liqpay extends Model
{
    public function getEmbed(){
        $data = new Response("getEmbed", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['product_id'])) {

                $user_id = Manager::$user->getId();
                $product_id = Validator::clear($_REQUEST['product_id']);

                $db = DB::getInstance();
                $dbh = $db->getDBH();

                $data = LiqpayManager::getEmbed($user_id, $product_id, $dbh);
            }
        }else{
            $data = new Response("getEmbed", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function getProducts(){
        $data = new Response("getWidget", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['locale'])) {

                $locale = Validator::clear($_REQUEST['locale']);

                $data = LiqpayManager::getProducts($locale);
            }
        }else{
            $data = new Response("getWidget", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function payIframe(){
        $data = new Response("payIframe", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['session_id'])) {

                $session_id = Validator::clear($_REQUEST['session_id']);
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $user_id = Manager::$user->getId();
                $data = LiqpayManager::getSessionData($session_id, $user_id, $dbh);
            }
        }else{
            $data = new Response("payIframe", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function getOrderList(){
        $data = new Response("getOrderList", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $user_id = Manager::$user->getId();
            $data = LiqpayManager::getOrderList($dbh, $user_id);
        }else{
            $data = new Response("payIframe", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function cancelSubscription(){
        $data = new Response("cancelSubscription", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['sid'])) {
                $sid = Validator::clear($_REQUEST['sid']);
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $user_id = Manager::$user->getId();
                $data = LiqpayManager::cancelSubscription($user_id, $sid, $dbh);
            }
        }else{
            $data = new Response("cancelSubscription", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }



    public function testIframe(){
        $data = new Response("testIframe", Constant::ERR_STATUS, "No depth value");

        //if(Manager::$user->isAuth()){
            if (isset($_REQUEST['session_id'])) {

                $session_id = Validator::clear($_REQUEST['session_id']);
                $db = DB::getInstance();
                $dbh = $db->getDBH();
                $user_id = 363055;//Manager::$user->getId();
                $data = LiqpayManager::getSessionData($session_id, $user_id, $dbh);
            }
       // }else{
           // $data = new Response("payIframe", Constant::ERR_STATUS, "Auth error");
        //}

        return $data;
    }

    public function getGoods(){
        if(Manager::$user->isAuth()){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            $user_id = Manager::$user->getId();

            $package_name = Manager::$user->getPackageName();
            $app_v = Manager::$user->getAppVersion();
            $platform =  Manager::$user->getPlatform();
            $vtime = Manager::$user->getVtime();
            $iid = Manager::$user->getIid();


            $dataGoods = LiqpayManager::getGoods($dbh, $user_id);

            if($dataGoods->getStatus() == Constant::OK_STATUS){
                $data = DataManager::authUser(Manager::$user->getLogin(), Manager::$user->getLang(), Manager::$user->getIid());
                if($data->getStatus() == Constant::OK_STATUS){
                    DataManager::setMainData($package_name, $app_v, $platform, $iid, $vtime);
                    $data = DataManager::getAuthData();
                    if($data->getStatus() == Constant::OK_STATUS){
                        $data->setObject(array_merge($data->getObject(), $dataGoods->getObject(), Manager::$user->getArr()));
                    }
                }
            }else{
                $data = new Response("getGoods", Constant::ERR_STATUS, "Get goods error");
            }
        }else{
            $data = new Response("getGoods", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function pingBack(){

        //file_put_contents("lq.txt", json_encode($_REQUEST));
        //$params = json_decode('{"signature":"B7+AMXjiZdRGI57NeMtNeM7wOLI=","data":"eyJhY3Rpb24iOiJwYXkiLCJwYXltZW50X2lkIjozMzIwNzE0NDQsInN0YXR1cyI6InNhbmRib3giLCJ2ZXJzaW9uIjozLCJ0eXBlIjoiYnV5IiwicGF5dHlwZSI6ImNhcmQiLCJwdWJsaWNfa2V5IjoiaTE3OTA4MTAyMjcyIiwiYWNxX2lkIjo0MTQ5NjMsIm9yZGVyX2lkIjoiaXBhY2tfMV8xOSIsImxpcXBheV9vcmRlcl9pZCI6Ik4xRTJUMUhXMTQ4NTE4NjYzNTkxNzQzNyIsImRlc2NyaXB0aW9uIjoiMTAwMCBkaWFtb25kcyIsInNlbmRlcl9jYXJkX21hc2syIjoiNTE2ODc1KjU2Iiwic2VuZGVyX2NhcmRfYmFuayI6InBiIiwic2VuZGVyX2NhcmRfdHlwZSI6Im1jIiwic2VuZGVyX2NhcmRfY291bnRyeSI6ODA0LCJpcCI6Ijc3LjEyMC4xODAuMzAiLCJhbW91bnQiOjAuOTksImN1cnJlbmN5IjoiVVNEIiwic2VuZGVyX2NvbW1pc3Npb24iOjAuMCwicmVjZWl2ZXJfY29tbWlzc2lvbiI6MC4wMywiYWdlbnRfY29tbWlzc2lvbiI6MC4wLCJhbW91bnRfZGViaXQiOjI4LjQ1LCJhbW91bnRfY3JlZGl0IjoyOC40NSwiY29tbWlzc2lvbl9kZWJpdCI6MC4wLCJjb21taXNzaW9uX2NyZWRpdCI6MC43OCwiY3VycmVuY3lfZGViaXQiOiJVQUgiLCJjdXJyZW5jeV9jcmVkaXQiOiJVQUgiLCJzZW5kZXJfYm9udXMiOjAuMCwiYW1vdW50X2JvbnVzIjowLjAsIm1waV9lY2kiOiI3IiwiaXNfM2RzIjpmYWxzZSwiY3VzdG9tZXIiOiIzNjMwNTUiLCJwcm9kdWN0X25hbWUiOiJpcGFja18xIiwicHJvZHVjdF9kZXNjcmlwdGlvbiI6IngxMDAwIiwiY3JlYXRlX2RhdGUiOjE0ODUxODY2MzU5NTQsImVuZF9kYXRlIjoxNDg1MTg2NjM1OTU0LCJ0cmFuc2FjdGlvbl9pZCI6MzMyMDcxNDQ0fQ=="}');
        //$_REQUEST['data'] = $params->data;
        //$_REQUEST['signature'] = $params->signature;

        if(isset($_REQUEST['data']) && isset($_REQUEST['signature'])){

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $data = $_REQUEST['data'];
            $signature = $_REQUEST['signature'];

            $liqpay = new LiqPay(LiqpayManager::PROJECT_KEY, LiqpayManager::SECRET_KEY);

            $signatureCome = $liqpay->data_to_sign($data);


            $data = base64_decode($data);

            $data = json_decode($data);

            $user_id = $data->customer;
            $order_id = $data->order_id;

            if ( $signature == $signatureCome && LiqpayManager::checkSession($user_id, $order_id, $dbh)) {

                $productId = $data->product_name;
                //$productId = "turbos_3";
                $type = $data->status;
                //$type = "unsubscribed";
                $ref = $data->transaction_id;
                $amount = $data->amount;

                file_put_contents(LiqpayManager::LQ_PATH . $ref.".txt", json_encode($_REQUEST));

                $ptype = HelpManager::getProductType($productId);

                if($ptype == Constant::CONSUMABLE_TYPE){
                    LiqpayManager::managePurchase($dbh, $ref, $type, $productId, $user_id, $amount, $order_id);
                }

                if($ptype == Constant::SUBSCRIPTION_TYPE){
                    LiqpayManager::manageSubscribe($dbh, $ref, $type, $productId, $user_id, $amount, $order_id);
                }

                echo "OK";
                exit;
            } else {
                echo "ERROR";
            }

            exit;
        }

        echo "ERROR";
        exit;
    }


}