<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2017-05-22
 * Time: 14:49
 */

namespace Famous\Controllers;


use Famous\Core\View;
use Famous\Models\Model_Game;

class Controller_Game
{
    function __construct()
    {
        $this->model = new Model_Game();
        $this->view = new View();
    }

    function action_index()
    {
        $data = $this->model->index();
        $data['title'] = "game panel";

        $this->view->generate('game_view.php', 'template_view.php', $data);
    }

    function action_parse_next(){
        $data = $this->model->parseNext();
        $this->view->generateJSON($data);
    }

    function action_parse_response(){
        $data = $this->model->parseResponse();
        $this->view->generateJSON($data);
    }
}