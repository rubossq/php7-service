<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Liqpay as Model_Liqpay;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Liqpay extends Controller
{

    function __construct()
    {
        $this->model = new Model_Liqpay();
        $this->view = new View();
    }

    function action_get_embed(){
        $data = $this->model->getEmbed();
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

    function action_get_order_list(){
        $data = $this->model->getOrderList();
        $this->view->generateJSON($data);
    }

    function action_cancel_subscription(){
        $data = $this->model->cancelSubscription();
        $this->view->generateJSON($data);
    }

    function action_pay_iframe(){
        $data = $this->model->payIframe();
        $this->view->generateSolo('lq_view.php', $data);
        //$this->view->generateJSON($data);
    }

    function action_test_iframe(){
        $data = $this->model->testIframe();
        $this->view->generateSolo('lq_view.php', $data);
        //$this->view->generateJSON($data);
    }
}