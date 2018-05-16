<?php
namespace Famous\Lib\Common;
class Quest{

	protected $type;
	protected $id;
	private $target_id;
	private $real_id;

	public function __construct($type, $id, $target_id = 0, $real_id=""){
		$this->type = $type;
		$this->id = $id;
		$this->target_id = $target_id;
		$this->real_id = $real_id;
	}

	/* get and set Quest.type */
	public function setType($type){
		$this->type = $type;
	}

	public function getType(){
		return $this->type;
	}

	/* get and set Quest.id */
	public function setId($id){
		$this->id = $id;
	}

	public function getId(){
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getRealId()
	{
		return $this->real_id;
	}

	/**
	 * @param mixed $real_id
	 */
	public function setRealId($real_id)
	{
		$this->real_id = $real_id;
	}

	public function getArr(){
		return array("id"=>$this->id, "type"=>$this->type, "target_id"=>$this->target_id, "real_id"=>$this->real_id);
	}
}

?>