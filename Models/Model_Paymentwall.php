<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\DataManager;
use Famous\Lib\Managers\DonateManager;
use Famous\Lib\Managers\PaymentwallManager;
use Famous\Lib\Managers\PhantomManager;
use Famous\Lib\Managers\ReferalManager;

use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB;
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
class Model_Paymentwall extends Model
{
    public function getWidget(){
        $data = new Response("getWidget", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['email']) && isset($_REQUEST['real_rtime']) && isset($_REQUEST['display_goodsid'])
                && isset($_REQUEST['lang'])  && isset($_REQUEST['country_code'])) {

                $user_id = Manager::$user->getId();
                $email = Validator::clear($_REQUEST['email']);
                $real_rtime = Validator::clear($_REQUEST['real_rtime']);
                $display_goodsid = Validator::clear($_REQUEST['display_goodsid']);

                $db = DB::getInstance();
                $dbh = $db->getDBH();

                $lang = Validator::clear($_REQUEST['lang']);
                $country_code = Validator::clear($_REQUEST['country_code']);

                $data = PaymentwallManager::getWidget($user_id, $email, $real_rtime, $display_goodsid, $dbh, $lang, $country_code);
            }
        }else{
            $data = new Response("getWidget", Constant::ERR_STATUS, "Auth error");
        }

        return $data;
    }

    public function getProducts(){
        $data = new Response("getWidget", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            if (isset($_REQUEST['locale'])) {

                $locale = Validator::clear($_REQUEST['locale']);

                $data = PaymentwallManager::getProducts($locale);
            }
        }else{
            $data = new Response("getWidget", Constant::ERR_STATUS, "Auth error");
        }

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


            $dataGoods = PaymentwallManager::getGoods($dbh, $user_id);

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

        $configs = array (
            'public_key' => PaymentwallManager::PROJECT_KEY,
            'private_key' => PaymentwallManager::SECRET_KEY,
        );

        if(PaymentwallManager::CUR_MODE == PaymentwallManager::TEST_MODE){
            $configs = array (
                'public_key' => PaymentwallManager::TEST_PROJECT_KEY,
                'private_key' => PaymentwallManager::TEST_SECRET_KEY,
            );
        }


        Paymentwall_Config::getInstance()->set($configs);

        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
        if ($pingback->validate()) {

            $productId = $pingback->getProductId();
            $type = $pingback->getType();
            $user_id = $pingback->getUserId();
            $s_length = $pingback->getProductPeriodLength();
            $s_period = $pingback->getProductPeriodType();
            $ref = $pingback->getReferenceId();

            $ref = intval(preg_replace('/[^0-9]+/', '', $ref), 10);

            $ptype = PaymentwallManager::CONSUMABLE_TYPE;
            if(!empty($s_length) && !empty($s_period)){
                $ptype = PaymentwallManager::SUBSCRIPTION_TYPE;
            }

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            if($ptype == PaymentwallManager::CONSUMABLE_TYPE){
                PaymentwallManager::managePurchase($dbh, $pingback, $ref, $type, $productId, $user_id);
            }

            if($ptype == PaymentwallManager::SUBSCRIPTION_TYPE){
                PaymentwallManager::manageSubscribe($dbh, $pingback, $ref, $type, $productId, $user_id, $s_length, $s_period);
            }

            echo "OK";
            exit;
        } else {
            echo $pingback->getErrorSummary();
        }

        exit;
    }


}