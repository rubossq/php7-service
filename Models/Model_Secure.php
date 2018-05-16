<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\CashManager as CashManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Managers\TableManager as TableManager;
use Famous\Lib\Managers\VeryManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Utils\Secure;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */
class Model_Secure extends Model
{


    public function generateCaptcha(){
        if(Manager::$user->isAuth()){
            $data = SecureManager::captchaGenerate(Manager::$user->getId());
        }else{
            $data = new Response("generateCaptcha", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }

    public function checkCaptcha(){
        $data = SecureManager::checkCaptcha();
        return $data;
    }

    public function tryVerify(){
        $data = SecureManager::tryVerify();
        return $data;
    }

    public function verify(){
        $data = SecureManager::verify();
        return $data;
    }
}