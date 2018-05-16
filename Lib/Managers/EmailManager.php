<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-10-11
 * Time: 21:08
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Manager;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use Famous\Lib\Common\Response as Response;

use \PDO as PDO;


class EmailManager
{
    const EMAILS_TABLE = "emails";
    const EMAILS_FEED_TABLE = "emails_feed";

    const EMAILS_PATH = "emails/";

    const INVITE_EMAIL = "invite";

    public static function sendEmail($email_id, $user_id=null){
        if(!$user_id){
            $user_id = Manager::$user->getId();
        }

        $dataEmail = DataManager::getEmailData($email_id);
        if($dataEmail->getObject() == Constant::OK_STATUS){
            $email = $dataEmail->getObject();
            $dataUser = self::getUserData($email['name']);
        }

        return $dataEmail;
    }

    public static function getUserData($user_id, $name){
        $data = new Response("getUserData", Constant::ERR_STATUS, "Get user data");

        switch($name){
            case self::INVITE_EMAIL:
                $dbh = new PDO("mysql:host=localhost;dbname=inst_famous_idb2;charset=utf8;", "inst_famous_idb2", "ajHmIo6aPauycALBbuSE");
                break;
        }

        return $data;
    }

}
