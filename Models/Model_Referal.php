<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\ReferalManager;

use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Validator;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */
class Model_Referal extends Model
{


    public function getReferalLink(){
        $data = new Response("getReferalLink", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){
            $data = ReferalManager::getReferalLink();
        }

        return $data;
    }

    public function stayReferal(){
        $data = new Response("stayReferal", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            if(isset($_REQUEST['referal_id'])){
                $referal_id = Validator::clear($_REQUEST['referal_id']);
                $data = ReferalManager::stayReferal($referal_id);
            }

        }

        return $data;
    }

    public function getReferalData(){
        $data = new Response("getReferalData", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $data = ReferalManager::getReferalData();

        }

        return $data;
    }


    public function getReferals(){
        $data = new Response("stayReferal", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $user_id = Manager::$user->getId();
            $data = ReferalManager::getReferals($user_id);
        }

        return $data;
    }

    public function getReferalsDiamonds(){
        $data = new Response("getReferalsDiamonds", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $user_id = Manager::$user->getId();
            $data = ReferalManager::getReferalsDiamonds($user_id);
        }

        return $data;
    }
}