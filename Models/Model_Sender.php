<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Core\Route;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Managers\BalanceManager;
use Famous\Lib\Managers\CashManager;
use Famous\Lib\Managers\EmailManager;
use Famous\Lib\Managers\NewsManager;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\SessionManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Managers\DataManager as DataManager;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Utils\SendMail;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:52
 */
class Model_Sender extends Model
{

    public function sendNotif(){
        //if we have any error
        $data = new Response("sendNotif", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['not_id'])){

            $not_id = Validator::clear($_REQUEST['not_id']);

            $data = NotificationManager::sendNotification($not_id);

        }

        return $data;
    }

    public function addNotif(){
        //if we have any error
        $data = new Response("addNotif", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['name'])){

            $name = Validator::clear($_REQUEST['name']);

            $user_id = Manager::$user->getId();
            $stime = time();

            NotificationManager::addNotification($user_id, $name, $stime);

        }
        $data = new Response("addNotif", Constant::OK_STATUS);
        return $data;
    }

    public function sendEmail(){


        $data = new Response("sendNotif", Constant::ERR_STATUS, "No depth value");

        if(isset($_REQUEST['to']) && isset($_REQUEST['email_id'])){


            $mailer = new SendMail();

            $to = Validator::clear($_REQUEST['to']);
            $email_id = Validator::clear($_REQUEST['email_id']);

            $arr= array("username"=>"Petr","position"=>"Team Lead","vacantion"=>"Engineer");



            $mailer->setTo($to);
            $body = $mailer->parseByEntities('content.txt',$arr );


            $mailer->setSubject("Subject!!!");
            $mailer->setBody($body);

            $mailer->sendHtml(      "<html><body><h1>Congratulations!!</h1><p>$body</p>!!!!!!! <img src='version.png'></body></html>",
                "version.png");

            echo $mailer->getResult();
        }

        return $data;
    }

    public function oldInvite(){

        //set for exception work
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $langIn = "ru";

        $dbh = new PDO("mysql:host=localhost;dbname=inst_famous_idb2;charset=utf8;", "inst_famous_idb2", "ajHmIo6aPauycALBbuSE");
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $dbh->prepare("SELECT id, login, email FROM `".Constant::DATA_TABLE."` WHERE lang IN('$langIn') AND emailed = 0 AND user_id = 1771 LIMIT 1000");
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $data = $stmt->fetchAll();

            $dataEmail = DataManager::getEmailData(1);
            if($dataEmail->getStatus() == Constant::OK_STATUS){
                $email = $dataEmail->getObject();
                $sm = new SendMail();
                //file_put_contents("letter.html", json_encode($email));
                foreach($data as $d){
                    echo $d['email'];
                    $sm->setTo($d['email']);
                    $arr = array("username"=>$d['login'], "logo"=>Route::getUrl() . "/" . $email['images'][0]['path']);

                    $subject = $sm->parseByEntities($email['subject'], $arr, true);
                    $sm->setSubject($subject);

                    $html = $sm->parseByEntities($email['content'], $arr, true);
                    //file_put_contents("letter.html", "\xEF\xBB\xBF" .$html);
                    //print_r($email);
                    $sm->sendHtml($html);

                    $id = $d['id'];
                    $stmt = $dbh->prepare("UPDATE `".Constant::DATA_TABLE."` SET emailed = 1 WHERE id = :id");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $stmt->execute();

                    $data = new Response("oldInvite",  $sm->getResult(), "Pear status");
                }

            }else{
                $data = new Response("oldInvite", Constant::ERR_STATUS, "Can not get email data");
            }
        }else{
            $data = new Response("oldInvite", Constant::ERR_STATUS, "No users");
        }

        $dbh = null;

        return $data;
    }

}