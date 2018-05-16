<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Sender as Model_Sender;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Sender extends Controller
{

    function __construct()
    {
        $this->model = new Model_Sender();
        $this->view = new View();
    }

    function action_send_notif(){
        $data = $this->model->sendNotif();
        $this->view->generateJSON($data);
    }

    function action_add_notif(){
        $data = $this->model->addNotif();
        $this->view->generateJSON($data);
    }

    function action_old_invite(){
        $data = $this->model->oldInvite();
        $this->view->generateJSON($data);
    }
}