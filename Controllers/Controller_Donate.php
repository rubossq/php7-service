<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Donate as Model_Donate;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Donate extends Controller
{

    function __construct()
    {
        $this->model = new Model_Donate();
        $this->view = new View();
    }

    function action_get_free_pack(){
        $data = $this->model->getFreePack();
        $this->view->generateJSON($data);
    }

    function action_order_full(){
        $data = $this->model->orderFull();
        $this->view->generateJSON($data);
    }

    function action_has_free_packs(){
        $data = $this->model->hasFreePacks();
        $this->view->generateJSON($data);
    }

    function action_free_app(){
        $data = $this->model->freeApp();
        $this->view->generateJSON($data);
    }

    function action_get_ad(){
        $data = $this->model->getAd();
        $this->view->generateJSON($data);
    }

    function action_get_prices(){
        $data = $this->model->getPrices();
        $this->view->generateJSON($data);
    }
}