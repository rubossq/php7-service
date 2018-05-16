<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\DonateManager;
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
class Model_Donate extends Model
{


    public function getFreePack(){
        $data = new Response("getFreePack", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = DonateManager::getFreePack();
        }

        return $data;
    }

    public function orderFull(){
        $data = new Response("orderFull", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = DonateManager::orderFull();
        }

        return $data;
    }

    public function hasFreePacks(){
        $data = new Response("hasFreePacks", Constant::ERR_STATUS, "No depth value");

        if(Manager::$user->isAuth()){

            $data = SecureManager::isVerify();
            if($data){return $data;}

            $data = DonateManager::hasFreePacks();
        }

        return $data;
    }

    public function freeApp(){
        //$data = new Response("freeApp", Constant::ERR_STATUS, "No depth value");

        //$data = SecureManager::isVerify();
        //if($data){return $data;}

        $data = DonateManager::freeApp();

        return $data;
    }

    public function getPrices(){
        //$data = new Response("freeApp", Constant::ERR_STATUS, "No depth value");

        //$data = SecureManager::isVerify();
        //if($data){return $data;}

        $data = DonateManager::getPrices();

        return $data;
    }

    public function getAd(){
        //$data = new Response("getAdd", Constant::ERR_STATUS, "No depth value");

        //$data = SecureManager::isVerify();
        //if($data){return $data;}

        $data = DonateManager::getAd();

        return $data;
    }
}