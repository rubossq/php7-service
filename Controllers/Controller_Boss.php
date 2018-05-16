<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Boss as Model_Boss;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:28
 */
class Controller_Boss extends Controller
{

    function __construct()
    {
        $this->model = new Model_Boss();
        $this->view = new View();
    }

    function action_index()
    {
        $data = $this->model->index();
        $data['title'] = "admin panel";

        $this->view->generate('boss_view.php', 'template_view.php', $data);
    }

    function action_check_redis()
    {
        $this->model->checkRedis();
    }

    function action_main_info()
    {
        $data = $this->model->mainInfo();
        $this->view->generateJSON($data);
    }

    function action_news_list()
    {
        $data = $this->model->newsList();
        $this->view->generateJSON($data);
    }

    function action_get_news()
    {
        $data = $this->model->getNews();
        $this->view->generateJSON($data);
    }

    function action_ads_list()
    {
        $data = $this->model->adsList();
        $this->view->generateJSON($data);
    }

    function action_get_ad()
    {
        $data = $this->model->getAd();
        $this->view->generateJSON($data);
    }

    function action_app_list()
    {
        $data = $this->model->appList();
        $this->view->generateJSON($data);
    }

    function action_get_app()
    {
        $data = $this->model->getApp();
        $this->view->generateJSON($data);
    }

    function action_users_list()
    {
        $data = $this->model->usersList();
        $this->view->generateJSON($data);
    }

    function action_get_user()
    {
        $data = $this->model->getUser();
        $this->view->generateJSON($data);
    }

    function action_send_new_meteor()
    {
        $data = $this->model->sendNewMeteor();
        $this->view->generateJSON($data);
    }
}