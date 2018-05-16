<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Common\Task as Task;
use Famous\Lib\Common\Quest as Quest;
use Famous\Lib\Common\XpInfo as XpInfo;
use Famous\Lib\Managers\CashManager as CashManager;
use Famous\Lib\Managers\HelpManager as HelpManager;
use Famous\Lib\Managers\NotificationManager;
use Famous\Lib\Managers\PhantomManager;
use Famous\Lib\Managers\ReferalManager;
use Famous\Lib\Managers\SessionManager as SessionManager;
use Famous\Lib\Utils\Config;
use Famous\Lib\Utils\Parser;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Managers\TableManager as TableManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Validator as Validator;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:52
 */
class Model_Phantom extends Model
{

    public function getUserInfo(){

        $data = new Response("getUserInfo", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['nick'])) {
            $nick = Validator::clear($_REQUEST['nick']);

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            if(Validator::isValidNick($nick)){
                PhantomManager::checkPhantom(PhantomManager::PHANTOM_UI, $dbh);
                $pkey = $nick;
                $params = "real_id=".$nick;
                $data = PhantomManager::getPhantomTask(PhantomManager::PHANTOM_UI, $pkey, $params, $dbh);
            }else{
                $data = new Response("getUserInfo", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::VALIDATION_ERROR));
            }
        }

        return $data;
    }

    public function getPostInfo(){

        $data = new Response("getPostInfo", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['link'])) {
            $link = Validator::clear($_REQUEST['link']);
            $db = DB::getInstance();
            $dbh = $db->getDBH();

            if(Validator::isValidPostLink($link)){
                PhantomManager::checkPhantom(PhantomManager::PHANTOM_PI, $dbh);

                $parseArr= Parser::parsePostLink($link);
                $pkey = $parseArr['real_id'];
                $params = "real_id=".$pkey;

                $data = PhantomManager::getPhantomTask(PhantomManager::PHANTOM_PI, $pkey, $params, $dbh);
            }else{
                $data = new Response("getPostInfo", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::VALIDATION_ERROR));
            }
        }

        return $data;
    }

    public function getGameInfo($alias, $market, $itunes, $company_id){

        $db = DB::getInstance();
        $dbh = $db->getDBH();

        PhantomManager::checkPhantom(PhantomManager::PHANTOM_GI, $dbh);
        $pkey = $alias;
        $params = "company_id=".$company_id."&market=".$market."&itunes=".$itunes;
        $data = PhantomManager::getPhantomTask(PhantomManager::PHANTOM_GI, $pkey, $params, $dbh);

        return $data;
    }

    public function getResponse(){
        $data = new Response("getResponse", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['tid']) && is_numeric($_REQUEST['tid'])) {
            $tid = Validator::clear($_REQUEST['tid']);

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $data = PhantomManager::checkReady($tid, $dbh);
        }

        return $data;
    }

    public function getTasks(){
        $data = new Response("getTasks", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['phantom_id'])) {
            $phantom_id = Validator::clear($_REQUEST['phantom_id']);

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $data = PhantomManager::getTasks($phantom_id, $dbh);

        }

        return $data;
    }

    public function getConfig(){
        $data = new Response("getConfig", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['phantom_id'])) {
            $phantom_id = Validator::clear($_REQUEST['phantom_id']);

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $data = PhantomManager::getConfig($phantom_id, $dbh);

        }

        return $data;
    }

    public function setResponse(){
        $data = new Response("getTasks", Constant::ERR_STATUS, "No depth value", array("error_code"=>PhantomManager::PARAMS_ERROR));

        if (isset($_REQUEST['phantom_id']) && isset($_REQUEST['response']) && isset($_REQUEST['tid'])) {
            $phantom_id = Validator::clear($_REQUEST['phantom_id']);
            $response = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', urldecode($_REQUEST['response']));
            $tid = Validator::clear($_REQUEST['tid']);

            $db = DB::getInstance();
            $dbh = $db->getDBH();

            $data = PhantomManager::setResponse($phantom_id, $response, $tid, $dbh);
        }

        return $data;
    }

}