<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2017-05-22
 * Time: 14:46
 */

namespace Famous\Models;


use Famous\Lib\Common\Response;
use Famous\Lib\Managers\GameManager;
use Famous\Lib\Utils\Constant;

class Model_Game
{
    public function index(){
        $data = null;
        $dataGame = GameManager::lastGames();
        $data = $dataGame->getObject();
        return $data;
    }

    public function parseNext(){
        $data = new Response("parseNext", Constant::OK_STATUS);
        GameManager::parseNext();
        return $data;
    }

    public function parseResponse(){
        $data = new Response("parseResponse", Constant::OK_STATUS);
        GameManager::parseResponse();
        return $data;
    }
}