<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Paymentwall as Model_Paymentwall;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Paymentwall extends Controller
{

    function __construct()
    {
        $this->model = new Model_Paymentwall();
        $this->view = new View();
    }

    function action_get_widget(){
        $data = $this->model->getWidget();
        $this->view->generateJSON($data);
    }

    function action_get_products(){
        $data = $this->model->getProducts();
        $this->view->generateJSON($data);
    }

    function action_ping_back(){
        $data = $this->model->pingBack();
        $this->view->generateJSON($data);
    }

    function action_get_goods(){
        $data = $this->model->getGoods();
        $this->view->generateJSON($data);
    }
}