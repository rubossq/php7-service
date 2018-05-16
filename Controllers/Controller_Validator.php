<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Validator as Model_Validator;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:27
 */
class Controller_Validator  extends Controller
{

    function __construct()
    {
        $this->model = new Model_Validator();
        $this->view = new View();
    }

    function action_verify_play(){
        $data = $this->model->verifyPlay();
        $this->view->generateJSON($data);
    }

    function action_verify_itunes(){
        $data = $this->model->verifyItunes();
        $this->view->generateJSON($data);
    }
}