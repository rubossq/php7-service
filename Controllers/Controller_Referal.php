<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Referal as Model_Referal;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Referal extends Controller
{

    function __construct()
    {
        $this->model = new Model_Referal();
        $this->view = new View();
    }

    function action_get_referal_link(){
        $data = $this->model->getReferalLink();
        $this->view->generateJSON($data);
    }

    function action_get_referals(){
        $data = $this->model->getReferals();
        $this->view->generateJSON($data);
    }

    function action_get_referals_diamonds(){
        $data = $this->model->getReferalsDiamonds();
        $this->view->generateJSON($data);
    }

    function action_stay_referal(){
        $data = $this->model->stayReferal();
        $this->view->generateJSON($data);
    }

    function action_get_referal_data(){
        $data = $this->model->getReferalData();
        $this->view->generateJSON($data);
    }

}