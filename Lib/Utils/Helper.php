<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-10-12
 * Time: 22:02
 */

namespace Famous\Lib\Utils;


class Helper
{

    /*
     * This function add elements from subArray
     * to mainArray if they don't exist in mainArray
    */
    public static function mergeFill($mainArray, $subArray){

        foreach($subArray as $k => $v){
            if(!key_exists($k, $mainArray)){
                $mainArray[$k] = $v;
            }
        }

        return $mainArray;
    }


    public static function postsSort($a, $b){
        if($a->likes == $b->likes){
            return 0;
        }

        return $a->likes > $b->likes ? -1 : 1;
    }

    public static function productsSort($a, $b){
        if($a['purchase_time'] == $b['purchase_time']){
            return 0;
        }

        return $a['purchase_time'] < $b['purchase_time'] ? -1 : 1;
    }

    public static function getIp(){
        if(!empty($_REQUEST['ip'])){
            $ip = Validator::clear($_REQUEST['ip']);
        }elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public static function numVersion($app_v){
        return intval(str_replace(',', '', $app_v));
    }

    public static function fileForceLoad($file) {
        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }
            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // читаем файл и отправляем его пользователю
            readfile($file);
            exit;
        }
    }


    public static function getallheaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

}