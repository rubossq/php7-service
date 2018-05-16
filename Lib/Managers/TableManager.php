<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/31/2016
 * Time: 11:16
 */

namespace Famous\Lib\Managers;
use Famous\Lib\Utils\Constant as Constant;

class TableManager
{
    public static function getTaskTable($type){
        $tables = array(Constant::LIKE_TYPE => Constant::LIKE_TABLE, Constant::SUBSCRIBE_TYPE => Constant::SUBSCRIBE_TABLE);
        return $tables[$type];
    }

    public static function getReadyTable($type){
        $tables = array(Constant::LIKE_TYPE => Constant::LIKE_READY, Constant::SUBSCRIBE_TYPE => Constant::SUBSCRIBE_READY);
        return $tables[$type];
    }

    public static function getFrozenTable($type){
        $tables = array(Constant::LIKE_TYPE => Constant::LIKE_FROZEN, Constant::SUBSCRIBE_TYPE => Constant::SUBSCRIBE_FROZEN);
        return $tables[$type];
    }

    public static function getInfoTable($type){
        $tables = array(Constant::LIKE_TYPE => Constant::LIKE_INFO, Constant::SUBSCRIBE_TYPE => Constant::SUBSCRIBE_INFO);
        return $tables[$type];
    }

    public static function getExpireTime($type){
        $tables = array(Constant::LIKE_TYPE => Constant::EXPIRE_LIKE_DELAY, Constant::SUBSCRIBE_TYPE => Constant::EXPIRE_SUBSCRIBE_TIME);
        return $tables[$type];
    }

}