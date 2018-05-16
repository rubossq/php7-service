<?php
namespace Famous\Lib\Common;
use Famous\Lib\Utils\Secure as Secure;


class Response{
	
	private $action;
	private $status;
	private $message;
	private $object;

	public function __construct($action, $status, $message = "", $object = null){
		$this->action = $action;
		$this->status = $status;
		$this->message = $message;
		$this->object = $object;
	}

	/* get and set Response.action */
	public function setAction($action){
		$this->action = $action;
	}

	public function getAction(){
		return $this->action;
	}

	/* get and set Response.status */
	public function setStatus($status){
		$this->status = $status;
	}

	public function getStatus(){
		return $this->status;
	}

	/* get and set Response.message */
	public function setMessage($message){
		$this->message = $message;
	}

	public function getMessage(){
		return $this->message;
	}

	/* get and set Response.object */
	public function setObject($object){
		$this->object = $object;
	}

	public function getObject(){
		return $this->object;
	}
	
	public function getArr(){
		$paramsStr = array("action"=>$this->action, "status"=>$this->status, "message"=>$this->message, "object"=>$this->object, "time"=>Manager::getExecuteTime());
		if(!isset($_REQUEST['cancel_secure'])){
			return array("response" => urlencode(Secure::encrypt(json_encode($paramsStr))));
		}
		else{
			return array("response" => $paramsStr);	//DEV TIME
		}

	}
}
