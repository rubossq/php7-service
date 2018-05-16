<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\CashManager;
use Famous\Lib\Managers\NewsManager;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Managers\SessionManager;
use Famous\Lib\Managers\SettingsManager;
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
class Model_Settings extends Model
{

    public function setSetting(){

        $data = new Response("setSetting", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['name']) && isset($_REQUEST['value'])){
            if(Manager::$user->isAuth()){

                $data = SecureManager::isVerify();
                if($data){return $data;}

                $name = Validator::clear($_REQUEST['name']);
                $value = Validator::clear($_REQUEST['value']);
                $data = SettingsManager::setSetting($name, $value);
            }else{
                $data = new Response("update", Constant::ERR_STATUS, "Auth error");
            }
        }
        return $data;
    }

}