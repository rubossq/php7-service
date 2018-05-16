<?php

namespace Famous\Lib\Utils;

require_once Config::PHP_PEAR_PATH . "/Mail.php";
require_once Config::PHP_PEAR_PATH . "/Mail/mime.php";

class SendMail {

	private $connection;
	private $mime;
	
	private $to;
	private $from;
	private $headers;
	private $subject;
	public $body;
	private $attachment;
	
	private $result;

	const SENDMAIL_PATH = "/usr/lib/sendmail";
	const EMAIL_FROM = "Meteor Boost <meteor@ruboss.top>";

	public function __construct ()
	{
		$this->from = self::EMAIL_FROM;
		$this->connection  =  \Mail::factory('sendmail', array("sendmail_path"=>self::SENDMAIL_PATH));

		$this->mime = new \Mail_mime(array('eol' => PHP_EOL));

    }

	public function setFrom($email) {
		$this->from = $email;
	}
	
	public function setTo($email) {
		$this->to = $email;
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	public function setBody($body) {
		$this->body = $body;
	}
	
	public function addAttachment($file){

		$this->mime->addAttachment($file);
	}
	
	
	//parse1
	public function parseByEntities ($entity, $data, $forceParse = false){
		if($forceParse){
			$content = $entity;
		}else{
			$content = file_get_contents($entity);
		}

		$patterns = array();
		$replacements = array();

		foreach($data as $key => $value){
			$patterns[]="/{{".$key."}}/";
			$replacements[]=$value;

		}
		$result= preg_replace($patterns, $replacements, $content);
		return $result;
	}
	
	//parse2
	public function parseByOrder ($file, $const){
		$content = file_get_contents($file);
		$pattern[]="/\{\{(.*?)\}\}/";
		$result= preg_replace($pattern, $const, $content);
		return $result;
	}
	
	public function sendText(){
		
		$this->headers = array("From"=> $this->from, "To"=>$this->to, "Subject"=>$this->subject);
		if($this->mime)
		{
			$this->headers = $this->mime->headers($this->headers);
			$this->mime->setTXTBody($this->body);
			$this->body = $this->mime->get();
		}
		$this->result= $this->connection->send($this->to, $this->headers, $this->body);
	}
	 
	public function sendHtml( $html, $images = null) {
			$this->mime->setHTMLBody($html);
			if($images){
				foreach($images as $i){
					$this->mime->addHTMLImage ($i['path'], $i['name']);
				}
			}

			$this->headers = array("From"=> $this->from, "To"=>$this->to, "Subject"=>$this->subject);
		
			if($this->mime){
				$this->headers = $this->mime->headers($this->headers);
				$this->body = $this->mime->get();
			}
			$this->result= $this->connection->send($this->to, $this->headers, $this->body);
	}
	

	
	public function getResult(){
		if (\PEAR::isError($this->result)){
			//return "error: {$this->result->getMessage()}";
			return Constant::ERR_STATUS;
		} else {
			return Constant::OK_STATUS;
		}
	}
}
?>