<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Proxy as Model_Proxy;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/20/2016
 * Time: 22:50
 */
class Controller_Proxy extends Controller
{

    function __construct()
    {
        $this->model = new Model_Proxy();
        $this->view = new View();
    }

    function action_not_found_404()
    {
        $data = $this->model->notFound404($this->query['address']);
        $this->view->generateJSON($data);
    }

    function action_redirect_301()
    {
        $data = $this->model->redirect301($this->query['address']);
        $this->view->generateJSON($data);
    }
}