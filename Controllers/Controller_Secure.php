<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Secure as Model_Secure;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:29
 */
class Controller_Secure  extends Controller
{

    function __construct()
    {
        $this->model = new Model_Secure();
        $this->view = new View();
    }

    function action_generate_captcha(){
        $data = $this->model->generateCaptcha();
        $this->view->generateJSON($data);
    }

    function action_check_captcha(){
        $data = $this->model->checkCaptcha();
        $this->view->generateJSON($data);
    }

    function action_try_verify(){
        $data = $this->model->tryVerify();
        $this->view->generateJSON($data);
    }

    function action_verify(){
        $data = $this->model->verify();
        $this->view->generateJSON($data);
    }
}