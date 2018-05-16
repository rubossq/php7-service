<?
namespace Famous\Core;
use Famous\Lib\Utils\DB as DB;

class View
{
	function generate($content_view, $template_view, $data = null)
	{
		include 'famous/Views/'.$template_view;
	}

	function generateSolo($content_view, $data)
	{
		include 'famous/Views/'.$content_view;
	}

	function generateJSON($response){
		$this->closeDB();
		echo json_encode($response->getArr());
		die;
	}

	function generateMute(){
		$this->closeDB();
		die;
	}

	function closeDB(){
		DB::closeIfExist();
	}
}
?>