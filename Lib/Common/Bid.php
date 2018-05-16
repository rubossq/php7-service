<?php

class Bid extends Quest{
	
	private $meta;
	private $head;
	private $targetCount;

	public function __construct($type, $id, $meta, $head, $targetCount){
		parent::__construct($type, $id);
		$this->meta = $meta;
		$this->targetCount = $targetCount;
	}

	/* get and set Bid.meta */
	public function setMeta($meta){
		$this->meta = $meta;
	}

	public function getMeta(){
		return $this->meta;
	}
	
	/* get and set Bid.meta */
	public function setHead($head){
		$this->meta = $head;
	}

	public function getHead(){
		return $this->head;
	}

	/* get and set Bid.targetCount */
	public function setTargetCount($targetCount){
		$this->targetCount = $targetCount;
	}

	public function getTargetCount(){
		return $this->targetCount;
	}
}

?>