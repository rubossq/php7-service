<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Common as Model_Common;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Common extends Controller
{

    function __construct()
    {
        $this->model = new Model_Common();
        $this->view = new View();
    }

    function action_auth(){
        $data = $this->model->auth();
        $this->view->generateJSON($data);
    }

    function action_get_ads(){
        $data = $this->model->getAds();
        $this->view->generateJSON($data);
    }

    function action_logout(){
        $data = $this->model->logout();
        $this->view->generateJSON($data);
    }

    function action_update(){
        $data = $this->model->update();
        $this->view->generateJSON($data);
    }

    function action_rate_award(){
        $data = $this->model->rateAward();
        $this->view->generateJSON($data);
    }

    function action_get_news(){
        $data = $this->model->getNews();
        $this->view->generateJSON($data);
    }

    function action_get_top(){
        $data = $this->model->getTop();
        $this->view->generateJSON($data);
    }

    function action_get_apps(){
        $data = $this->model->getApps();
        $this->view->generateJSON($data);
    }

    function action_donate(){
        $data = $this->model->donate();
        $this->view->generateJSON($data);
    }

    function action_set_complete(){
        $data = $this->model->setComplete();
        $this->view->generateJSON($data);
    }

    function action_purchase(){
        $data = $this->model->purchase();
        $this->view->generateJSON($data);
    }

    function action_subscribe(){
        $data = $this->model->subscribe();
        $this->view->generateJSON($data);
    }
}