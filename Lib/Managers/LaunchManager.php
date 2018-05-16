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

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class LaunchManager{

    const IGNORE_FILE = ".launchignore";
    const CHECKSUMM_FILE = "checksumm";
    const FILES_FILE = "files";
    const CHECKSTATUS_FILE = "checkstatus.txt";
    const PLATFORMS_PATH = "platforms/";

    const CRITICAL_UPDATE = 1;
    const LITE_UPDATE = 2;

    const METEOR_LAST_UPDATE = 2;

    public static function checkSumm($package_name, $platform, $withData = false){

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });


        $data = new Response("checkSumm", Constant::ERR_STATUS, "Something went wrong");

        $path = Constant::APPS_PATH . $package_name;
        $excludes = self::getExcludes($path . "/", $platform);
        $arr = self::dirToArray($path, $excludes, null, $withData, $platform);
        if(count($arr) > 0){
            $data = new Response("checkSumm", Constant::OK_STATUS, "", array("summ"=>$arr));
        }


        return $data;
    }



    public static function getSumm($package_name, $platform){

        $path = Constant::APPS_PATH . $package_name . "/" . self::PLATFORMS_PATH . $platform . "/" . self::CHECKSUMM_FILE;

        if(file_exists($path)){
            $summ = json_decode(file_get_contents($path));
            $data = new Response("getSumm", Constant::OK_STATUS, "", array("summ"=>$summ));
        }else{
            $data = new Response("getSumm", Constant::ERR_STATUS, "File doesn't exists");
        }

        return $data;
    }

    public static function getSummData($package_name, $platform, $to_load){
        $stairs = json_decode($to_load);
        if(empty($to_load) || !is_array($stairs)){
            $path = Constant::APPS_PATH . $package_name . "/" . self::PLATFORMS_PATH . $platform . "/" . self::FILES_FILE;
            if(file_exists($path)){
                $files = json_decode(file_get_contents($path));
                $data = new Response("getSummData", Constant::OK_STATUS, "", array("files"=>$files));
            }else{
                $data = new Response("getSummData", Constant::ERR_STATUS, "File doesn't exists");
            }
        }else{


            $path = Constant::APPS_PATH . $package_name;
            $excludes = self::getExcludes($path . "/", $platform);
            $includes = self::getIncludes($stairs, $package_name, $excludes, $platform);
            //print_r($excludes['p']);
            //print_r($includes);exit;
            $files = self::dirToArray($path, $excludes, $includes, true, $platform);

            $data = new Response("getSummData", Constant::OK_STATUS, "", array("files"=>$files));
        }

        return $data;
    }

    private static function getIncludes($stairs, $package_name, $excludes, $platform){
        $includes = array("d"=>array(), "f"=>array());
        for($i=0; $i<count($stairs); $i++){
            $path = self::getPlatformPath($platform, $stairs[$i]->path, $package_name, $excludes);
            $name = $stairs[$i]->name;
            $type = $stairs[$i]->type;

            $p = Constant::APPS_PATH . $package_name . "/" . $path . $name;

            if(in_array($p, $excludes['p'])){
                $p = Constant::APPS_PATH . $package_name . "/" . $path . self::getPlatformEntity($platform, $name);
            }
            if(!in_array($p, $includes[$type])) {
                $includes[$type][] = $p;
            }

            if($type == 'f'){
                $arr = self::separatePath($path);
                for($k=0; $k<count($arr); $k++){
                    $p = Constant::APPS_PATH . $package_name . "/" . $arr[$k];

                    if(!in_array($p, $includes['d'])){
                        $includes['d'][] = $p;
                    }
                }
            }
        }

        return $includes;
    }

    private static function getPlatformPath($platform, $path, $package_name, $excludes){
        $arr = self::separatePath($path);
        for($i=0; $i<count($arr); $i++) {
            $p = Constant::APPS_PATH . $package_name . "/" . $arr[$i];

            if(in_array($p, $excludes['p'])){
                $before = $arr[$i];
                $after = self::getPlatformEntity($platform, $arr[$i]);
                $path = str_replace($before, $after, $path);
            }
        }

        return $path;
    }

    private static function getPlatformEntity($platform, $fileName){
        $arr = explode(".", $fileName);
        if($arr[0] && count($arr) > 1){
            $fileName = $arr[0] . "-" . $platform . '.' . $arr[1];
        }else if(!$arr[0] && count($arr) > 1){
            $fileName = $arr[0] . '.' . $arr[1] . "-" . $platform;
        }else{
            $fileName = $arr[0] . "-" . $platform;
        }

        return $fileName;
    }

    private static function  separatePath($path){
        $arr = explode("/", $path);
        $parts = array();
        for($k=0; $k<count($arr); $k++){
            if($arr[$k] != "/"){
                $tp = array();
                for($j=0; $j<=$k; $j++){
                    if($arr[$j] != "/"){
                        $tp[] = $arr[$j];
                    }
                }
                $parts[] = implode("/", $tp);
            }
        }

        return $parts;
    }

    public static function getAllSumm($package_name, $platform){

        $path = Constant::APPS_PATH . $package_name . "/" . self::PLATFORMS_PATH . $platform . "/" . self::CHECKSUMM_FILE;

        if(file_exists($path)){
            $allSumm = md5_file($path);
            $data = new Response("getAllSumm", Constant::OK_STATUS, "", array("all_summ"=>$allSumm));
        }else{
            $data = new Response("getAllSumm", Constant::ERR_STATUS, "File doesn't exists");
        }

        return $data;
    }

    public static function getServiceStatus($package_name){

        $path = Constant::APPS_PATH . $package_name . "/" . self::CHECKSTATUS_FILE;

        if(file_exists($path)){
            $status = file_get_contents($path);
            $data = new Response("getServiceStatus", Constant::OK_STATUS, "", array("status"=>$status));
        }else{
            $data = new Response("getServiceStatus", Constant::ERR_STATUS, "File doesn't exists");
        }

        return $data;
    }

    public static function setServiceStatus($package_name, $status){

        $path = Constant::APPS_PATH . $package_name . "/" . self::CHECKSTATUS_FILE;

        if(file_put_contents($path, $status)){
            $data = new Response("setServiceStatus", Constant::OK_STATUS);
        }else{
            $data = new Response("setServiceStatus", Constant::ERR_STATUS, "Can not write the file");
        }

        return $data;
    }

    public static function getService($package_name){
        $data = new Response("getService", Constant::OK_STATUS, "", array("service"=>self::serviceByPackage($package_name),
            "app_v"=>self::appVersionByPackage($package_name), "service_v"=>self::serviceVersionByPackage($package_name),
            "last_update"=>self::serviceUpdateByPackage($package_name)));
        return $data;
    }

    private static function serviceByPackage($package_name){
        $services = array(Constant::PACKAGE_NAME_METEOR => Constant::INST_SERVICE);
        return $services[$package_name];
    }

    private static function appVersionByPackage($package_name){
        $services = array(Constant::PACKAGE_NAME_METEOR => Constant::APP_VERSION_METEOR);
        return $services[$package_name];
    }

    private static function serviceVersionByPackage($package_name){
        $services = array(Constant::PACKAGE_NAME_METEOR => Constant::SERVICE_VERSION_INSTAGRAM);
        return $services[$package_name];
    }

    private static function serviceUpdateByPackage($package_name){
        $services = array(Constant::PACKAGE_NAME_METEOR => self::METEOR_LAST_UPDATE);
        return $services[$package_name];
    }

    private static function getExcludes($path, $platform){
        $excludes = array("d"=>array(), "f"=>array(), "a"=>array(), "c"=>array(), "p"=>array());
        $ignore = $path . self::PLATFORMS_PATH . $platform . "/" . self::IGNORE_FILE;
        if(file_exists($ignore)){
            $file = file_get_contents($ignore);

            if($file){
                $lines = explode("\n", $file);
                for($i=0; $i<count($lines); $i++){
                    $entity = trim($lines[$i]);
                    $last = substr($entity, strlen($entity)-1, 1);
                    $first = substr($entity, 0, 1);
                    if($first != "#"){
                        if($last == "/"){
                            $excludes['d'][] = $path . substr($entity, 0, strlen($entity)-1);
                        }else if($last == "!"){
                            $excludes['a'][] = substr($entity, 0, strlen($entity)-1);
                        }else if($last == "~"){

                            $key = substr($entity, strlen($entity)-2, 1);

                            if($key == '/') {
                                $entity = substr($entity, 0, strlen($entity) - 2);
                                $type = "d";
                            }else{
                                $entity = substr($entity, 0, strlen($entity) - 1);
                                $type = "f";
                            }

                            $excludes['c'][] = $path . $entity;

                            $platforms = HelpManager::getPlatforms();
                            $tmpEntity = $entity;
                            for($j = 0; $j<count($platforms); $j++){
                                $tmpEntity = str_replace("-".HelpManager::getPlatformVerbal($platforms[$j]), '', $tmpEntity);
                            }

                            $excludes['p'][] = $path . $tmpEntity;

                            $platforms = HelpManager::getPlatformsExclude(HelpManager::getPlatform($platform));

                            for($j = 0; $j<count($platforms); $j++){
                                $entityTemp = str_replace($platform, HelpManager::getPlatformVerbal($platforms[$j]), $entity);
                                $excludes[$type][] = $path . $entityTemp;
                            }


                        }else{
                            $excludes['f'][] = $path . $entity;
                        }
                    }
                }
            }
        }

        return $excludes;
    }

    private static function dirToArray($dir, $excludes, $includes, $withData, $platform) {

        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
        {
            if (!in_array($value,array(".","..")))
            {
                if(!in_array($value, $excludes['a'])){
                    $entity = $dir . "/" . $value;
                    if (is_dir($entity) && !in_array($entity, $excludes['d']))
                    {
                        if($includes){
                            if(in_array($entity, $includes['d'])){
                                if(in_array($entity, $excludes['c'])){
                                    $value = str_replace('-'.$platform, '', $value);
                                }
                                $result[$value] = self::dirToArray($entity, $excludes, $includes, $withData, $platform);
                            }
                        }else{
                            if(in_array($entity, $excludes['c'])){
                                $value = str_replace('-'.$platform, '', $value);
                            }
                            $result[$value] = self::dirToArray($entity, $excludes, $includes, $withData, $platform);
                        }
                    }
                    else if(!in_array($entity, $excludes['f']) && !is_dir($entity))
                    {
                        if($includes){
                            if(in_array($entity, $includes['f'])){
                                if(in_array($entity, $excludes['c'])){
                                    $value = str_replace('-'.$platform, '', $value);
                                    //echo $value . "<br>";
                                }
                                if($withData){
                                    $type = pathinfo($entity, PATHINFO_EXTENSION);
                                    $result[] = array("info"=>($value . ":" . md5_file($entity) . ":" . filesize($entity) . ":" . $type), "content"=>json_encode(self::getFileContent($type, $entity)));
                                }else{
                                    $result[] = $value . ":" . md5_file($entity) . ":" . filesize($entity);
                                }
                            }
                        }else{
                            if(in_array($entity, $excludes['c'])){
                                $value = str_replace('-'.$platform, '', $value);
                            }
                            if($withData){
                                $type = pathinfo($entity, PATHINFO_EXTENSION);
                                $result[] = array("info"=>($value . ":" . md5_file($entity) . ":" . filesize($entity) . ":" . $type), "content"=>json_encode(self::getFileContent($type, $entity)));
                            }else{
                                $result[] = $value . ":" . md5_file($entity) . ":" . filesize($entity);
                            }
                        }

                    }
                }
            }
        }

        return $result;
    }

    private static function getFileContent($type, $file){
        if(self::isImg($type) || self::isFont($type)){
            $content = base64_encode(file_get_contents($file));
        }else{
            $content = file_get_contents($file);
        }

        return $content;
    }

    private static function isImg($type){
        if(in_array($type, array("png", "jpg", "jpeg", "svg", "gif"))){
            return true;
        }

        return false;
    }

    private static function isFont($type){
        if(in_array($type, array("ttf"))){
            return true;
        }

        return false;
    }


}