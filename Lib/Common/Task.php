<?php
namespace Famous\Lib\Common;
use Famous\Lib\Common\Quest as Quest;

class Task extends Quest{
	
	private $readyCount;
	private $meta;
	private $head;
	private $targetCount;
	private $status;

	public function __construct($type, $id, $readyCount, $meta, $head, $targetCount, $status){
		parent::__construct($type, $id);
		$this->readyCount = $readyCount;
		$this->meta = $meta;
		$this->head = $head;
		$this->targetCount = $targetCount;
		$this->status = $status;
	}

	/* get complete percent of Quest */
	public function getReadyPercent(){}

	/* get and set Task.readyCount */
	public function setReadyCount($readyCount){
		$this->readyCount = $readyCount;
	}

	public function getReadyCount(){
		return $this->readyCount;
	}

	/* get and set Task.meta */
	public function setMeta($meta){
		$this->meta = $meta;
	}

	public function getMeta(){
		return $this->meta;
	}
	
	/* get and set Task.head */
	public function setHead($head){
		$this->head = $head;
	}

	public function getHead(){
		return $this->head;
	}

	/* get and set Task.targetCount */
	public function setTargetCount($targetCount){
		$this->targetCount = $targetCount;
	}

	public function getTargetCount(){
		return $this->targetCount;
	}

	/**
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function getArr(){
		return array("id"=>$this->id, "meta"=>$this->meta, "head"=>$this->head, "type"=>$this->type, "target_count"=>$this->targetCount, "ready_count"=>$this->readyCount, "status"=>$this->status);
	}
}

?>