<?php
namespace Famous\Lib\Common;
class Cash{

	private $deposit;

	/**
	 * Cash constructor.
	 * @param $deposit
	 */
	public function __construct($deposit)
	{
		$this->deposit = $deposit;
	}

	/**
	 * @return mixed
	 */
	public function getDeposit()
	{
		return $this->deposit;
	}

	/**
	 * @param mixed $deposit
	 */
	public function setDeposit($deposit)
	{
		$this->deposit = $deposit;
	}



	public function getArr(){
		return array("deposit"=>$this->deposit);
	}
}

?>