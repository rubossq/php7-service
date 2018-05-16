<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\DonateManager;
use Famous\Lib\Managers\HelpManager;
use Famous\Lib\Managers\LaunchManager;
use Famous\Lib\Managers\PaymentwallManager;
use Famous\Lib\Managers\PhantomManager;
use Famous\Lib\Managers\ReferalManager;

use Famous\Lib\Managers\SecureManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\DB;
use Famous\Lib\Utils\Validator;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */
class Model_Launch extends Model
{

    public function checkSumm(){
        $data = new Response("checkSumm", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['package_name']) && isset($_REQUEST['platform'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            $platform = Validator::clear($_REQUEST['platform']);
            $platform = HelpManager::getPlatformVerbal($platform);
            $data = LaunchManager::checkSumm($package_name, $platform);
        }

        return $data;
    }

    public function rewriteSumms(){
        //set for exception work
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $data = new Response("rewriteSumms", Constant::ERR_STATUS, "No depth value");

        $packages = HelpManager::getPackages();
        $platforms = HelpManager::getPlatforms();
        try{
            for($i=0; $i<count($packages); $i++){
                if($packages[$i] == Constant::PACKAGE_NAME_ROYAL){
                    $packages[$i] = Constant::PACKAGE_NAME_ROYAL_LAUCHER;
                }
                if($packages[$i] == Constant::PACKAGE_NAME_REAL){
                    $packages[$i] = Constant::PACKAGE_NAME_REAL_LAUNCHER;
                }
                if($packages[$i] == Constant::PACKAGE_NAME_REAL_VIP ){
                    $packages[$i] = Constant::PACKAGE_NAME_REAL_VIP_LAUCHER;
                }
                for($j=0; $j<count($platforms); $j++){
                    $platform = HelpManager::getPlatformVerbal($platforms[$j]);
                    $path = Constant::APPS_PATH . $packages[$i] . "/" . LaunchManager::PLATFORMS_PATH . $platform . "/";
                    $data = LaunchManager::checkSumm($packages[$i], $platform);
                    if($data->getStatus() == Constant::OK_STATUS){
                        $obj = $data->getObject();
                        $summ = json_encode($obj['summ']);
                        file_put_contents($path . LaunchManager::CHECKSUMM_FILE, $summ);
                    }else{
                        throw new \ErrorException();
                    }

                    $data = LaunchManager::checkSumm($packages[$i], $platform, true);
                    if($data->getStatus() == Constant::OK_STATUS){
                        $obj = $data->getObject();
                        $summ = json_encode($obj['summ']);
                        file_put_contents($path . LaunchManager::FILES_FILE, $summ);
                    }else{
                        throw new \ErrorException();
                    }
                }
            }

            if(count($packages) > 0){
                $data = new Response("rewriteSumms", Constant::OK_STATUS);
            }
        }catch(\ErrorException $e){
            $data = new Response("checkSumm", Constant::ERR_STATUS, "ErrorException write");
        }


        return $data;
    }

    public function getCheckSumm(){
        $data = new Response("getCheckSumm", Constant::ERR_STATUS, "No depth value");
        if (isset($_REQUEST['package_name']) && isset($_REQUEST['platform'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            $platform = Validator::clear($_REQUEST['platform']);
            $platform = HelpManager::getPlatformVerbal($platform);
            $data = LaunchManager::getSumm($package_name, $platform);
        }

        return $data;
    }

    public function getDataSumm(){
        $data = new Response("getDataSumm", Constant::ERR_STATUS, "No depth value");
        if (isset($_REQUEST['package_name']) && isset($_REQUEST['to_load']) && isset($_REQUEST['platform'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            $platform = Validator::clear($_REQUEST['platform']);
            $platform = HelpManager::getPlatformVerbal($platform);
            //empty string if need load all files
            $to_load = $_REQUEST['to_load'];
            $data = LaunchManager::getSummData($package_name, $platform, $to_load);
        }

        return $data;
    }

    public function getAllSumm(){
        $data = new Response("getAllSumm", Constant::ERR_STATUS, "No depth value");
        if (isset($_REQUEST['package_name']) && isset($_REQUEST['platform'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            $platform = Validator::clear($_REQUEST['platform']);
            $platform = HelpManager::getPlatformVerbal($platform);
            $data = LaunchManager::getAllSumm($package_name, $platform);
        }

        return $data;
    }

    public function getServiceStatus(){
        $data = new Response("getServiceStatus", Constant::ERR_STATUS, "No depth value");
        if (isset($_REQUEST['package_name']) && isset($_REQUEST['platform'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            //$platform = Validator::clear($_REQUEST['platform']);
            $data = LaunchManager::getServiceStatus($package_name);
        }

        return $data;
    }

    public function setServiceStatus(){
        $data = new Response("setServiceStatus", Constant::ERR_STATUS, "No depth value");
        if (isset($_REQUEST['package_name']) && isset($_REQUEST['status'])) {
            $package_name = Validator::clear($_REQUEST['package_name']);
            $status = Validator::clear($_REQUEST['status']);
            if(in_array($status, array(Constant::OK_STATUS, Constant::ERR_STATUS))){
                $data = LaunchManager::setServiceStatus($package_name, $status);
            }else{
                $data = new Response("setServiceStatus", Constant::ERR_STATUS, "Wrong status value");
            }
        }

        return $data;
    }

    public function getService(){
        $data = new Response("getService", Constant::ERR_STATUS, "No depth value");

        if (isset($_REQUEST['package_name']) && isset($_REQUEST['platform'])) {
            //$platform = Validator::clear($_REQUEST['platform']);
            $package_name = Validator::clear($_REQUEST['package_name']);
            $data = LaunchManager::getService($package_name);
        }

        return $data;
    }
}