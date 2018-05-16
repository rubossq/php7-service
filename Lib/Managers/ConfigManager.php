<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\AppConfig as AppConfig;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\Constant as Constant;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/25/2016
 * Time: 13:32
 */
class ConfigManager
{
    //get user cash
    public static function getConfig(){
        if(Manager::$user->isAuth()){
            $config = new AppConfig();
            $data = new Response("getConfig", Constant::OK_STATUS, "", array("config"=>$config->getArr()));
        }else{
            $data = new Response("getConfig", Constant::ERR_STATUS, "Auth error");
        }
        return $data;
    }
}