<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Settings as Model_Settings;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:23
 */
class Controller_Settings extends Controller
{

    function __construct()
    {
        $this->model = new Model_Settings();
        $this->view = new View();
    }

    function action_set_setting(){
        $data = $this->model->setSetting();
        $this->view->generateJSON($data);
    }

}