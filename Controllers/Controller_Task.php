<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Task as Model_Task;
use Famous\Core\View as View;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:25
 */
class Controller_Task extends Controller
{

    function __construct()
    {
        $this->model = new Model_Task();
        $this->view = new View();
    }

    function action_bid(){
        $data = $this->model->bid();
        $this->view->generateJSON($data);
    }

    function action_bid_list(){
        $data = $this->model->bidList();
        $this->view->generateJSON($data);
    }

    function action_get_tasks(){
        $data = $this->model->getTasks();
        $this->view->generateJSON($data);
    }

    function action_get_quests(){
        $data = $this->model->getQuests();
        $this->view->generateJSON($data);
    }

    function action_delete_task(){
        $data = $this->model->deleteTask();
        $this->view->generateJSON($data);
    }

    function action_set_ready(){
        $data = $this->model->setReady();
        $this->view->generateJSON($data);
    }

    function action_report_quest(){
        $data = $this->model->reportQuest();
        $this->view->generateJSON($data);
    }

    function action_get_frozens(){
        $data = $this->model->getFrozens();
        $this->view->generateJSON($data);
    }

    function action_task_verdict(){
        $data = $this->model->taskVerdict();
        $this->view->generateJSON($data);
    }

    function action_refresh(){
        $data = $this->model->refresh();
        $this->view->generateJSON($data);
    }

    function action_fast_earn(){
        $data = $this->model->fastEarn();
        $this->view->generateJSON($data);
    }

    function action_fast_earn_delay(){
        $data = $this->model->fastEarnDelay();
        $this->view->generateJSON($data);
    }
}