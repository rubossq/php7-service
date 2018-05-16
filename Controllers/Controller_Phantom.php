<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Phantom as Model_Phantom;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:29
 */
class Controller_Phantom  extends Controller
{

    function __construct()
    {
        $this->model = new Model_Phantom();
        $this->view = new View();
    }

    function action_get_user_info(){
        $data = $this->model->getUserInfo();
        $this->view->generateJSON($data);
    }

    function action_get_post_info(){
        $data = $this->model->getPostInfo();
        $this->view->generateJSON($data);
    }

    function action_get_response(){
        $data = $this->model->getResponse();
        $this->view->generateJSON($data);
    }

    function action_get_tasks(){
        $data = $this->model->getTasks();
        $this->view->generateJSON($data);
    }

    function action_set_response(){
        $data = $this->model->setResponse();
        $this->view->generateJSON($data);
    }

    function action_get_config(){
        $data = $this->model->getConfig();
        $this->view->generateJSON($data);
    }

}