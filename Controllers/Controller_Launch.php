<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Launch as Model_Launch;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Launch extends Controller
{

    function __construct()
    {
        $this->model = new Model_Launch();
        $this->view = new View();
    }

    function action_check_summ(){
        $data = $this->model->checkSumm();
        $this->view->generateJSON($data);
    }

    function action_rewrite_summs(){
        $data = $this->model->rewriteSumms();
        $this->view->generateJSON($data);
    }

    function action_get_check_summ(){
        $data = $this->model->getCheckSumm();
        $this->view->generateJSON($data);
    }

    function action_get_data_summ(){
        $data = $this->model->getDataSumm();
        $this->view->generateJSON($data);
    }

    function action_get_all_summ(){
        $data = $this->model->getAllSumm();
        $this->view->generateJSON($data);
    }

    function action_get_service_status(){
        $data = $this->model->getServiceStatus();
        $this->view->generateJSON($data);
    }

    function action_set_service_status(){
        $data = $this->model->setServiceStatus();
        $this->view->generateJSON($data);
    }

    function action_get_service(){
        $data = $this->model->getService();
        $this->view->generateJSON($data);
    }
}