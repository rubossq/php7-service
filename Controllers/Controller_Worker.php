<?php
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Models\Model_Worker as Model_Worker;
use Famous\Core\View as View;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 18:27
 */
class Controller_Worker extends Controller
{

    function __construct(){
        $this->model = new Model_Worker();
        $this->view = new View();
    }

    function action_optimize_news(){
        $data = $this->model->optimizeNews();
        $this->view->generateJSON($data);
    }

    function action_optimize_notifications(){
        $data = $this->model->optimizeNotifications();
        $this->view->generateJSON($data);
    }

    function action_optimize_captchas(){
        $data = $this->model->optimizeCaptchas();
        $this->view->generateJSON($data);
    }

    function action_optimize_verify(){
        $data = $this->model->optimizeVerify();
        $this->view->generateJSON($data);
    }

    function action_optimize_tasks(){
        $data = $this->model->optimizeTasks();
        $this->view->generateJSON($data);
    }

    function action_optimize_old_users(){
        $data = $this->model->optimizeOldUsers();
        $this->view->generateJSON($data);
    }

    function action_optimize_errors(){
        $data = $this->model->optimizeErrors();
        $this->view->generateJSON($data);
    }

    function action_check_subscribes(){
        $data = $this->model->checkSubscribes();
        $this->view->generateJSON($data);
    }

    function action_grant_tops(){
        $data = $this->model->grantTops();
        $this->view->generateJSON($data);
    }

    function action_check_frozen(){
        $data = $this->model->checkFrozen();
        $this->view->generateJSON($data);
    }

    function action_priority_balance(){
        $data = $this->model->priorityBalance();
        $this->view->generateJSON($data);
    }

    function action_send_notifications(){
        $data = $this->model->sendNotifications();
        $this->view->generateJSON($data);
    }

    function action_send_vip_invites(){
        $data = $this->model->sendVIPInvites();
        $this->view->generateJSON($data);
    }

    function action_clear_vip_invites(){
        $data = $this->model->clearVIPInvites();
        $this->view->generateJSON($data);
    }

    function action_very_users_service(){
        $data = $this->model->veryUsersService();
        $this->view->generateJSON($data);
    }

    function action_referal_pay(){
        $data = $this->model->referalPay();
        $this->view->generateJSON($data);
    }

    function action_save_last(){
        $data = $this->model->saveLast();
        $this->view->generateJSON($data);
    }

    function action_check_pw_subscribes(){
        $data = $this->model->checkPWSubscribes();
        $this->view->generateJSON($data);
    }

    //TEMP FUNCTION FROM 03-10-2016
    function action_reset_tasks(){
        $data = $this->model->resetTasks();
        $this->view->generateJSON($data);
    }
}