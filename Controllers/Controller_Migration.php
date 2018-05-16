<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2017-08-06
 * Time: 09:25
 */

namespace Famous\Controllers;


use Famous\Core\Controller;
use Famous\Core\View;
use Famous\Models\Model_Migration;
use Famous\Models\Model_Task;

class Controller_Migration extends Controller
{
    function __construct()
    {
        $this->model = new Model_Migration();
        $this->view = new View();
    }

    function action_read(){
        $data = $this->model->read();
        $this->view->generateJSON($data);
    }

    function action_write(){
        $data = $this->model->write();
        $this->view->generateJSON($data);
    }

    function action_clear(){
        $data = $this->model->clear();
        $this->view->generateJSON($data);
    }
}