<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Util as Model_Util;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:29
 */
class Controller_Util  extends Controller
{

    function __construct()
    {
        $this->model = new Model_Util();
        $this->view = new View();
    }

    function action_get_cash(){
        $data = $this->model->getCash();
        $this->view->generateJSON($data);
    }

    function action_version(){
        $data = $this->model->version();
        $this->view->generateJSON($data);
    }

    function action_get_news_count(){
        $data = $this->model->getNewsCount();
        $this->view->generateJSON($data);
    }

    function action_watch_all_news(){
        $data = $this->model->watchAllNews();
        $this->view->generateJSON($data);
    }

    function action_view_ad(){
        $data = $this->model->viewAd();
        $this->view->generateJSON($data);
    }

    function action_report_err(){
        $data = $this->model->reportErr();
        $this->view->generateJSON($data);
    }

    function action_set_data(){
        $data = $this->model->setData();
        $this->view->generateJSON($data);
    }

    function action_set_reg_id(){
        $data = $this->model->setRegId();
        $this->view->generateJSON($data);
    }

    function action_get_very_users(){
        $data = $this->model->getVeryUsers();
        $this->view->generateJSON($data);
    }

    function action_after_parse(){
        $data = $this->model->afterParse();
        $this->view->generateJSON($data);
    }

    function action_load_file(){
        $this->model->loadFile();
        $this->view->generateMute();
    }

    function action_set_updated(){
        $this->model->setUpdated();
        $this->view->generateMute();
    }

}