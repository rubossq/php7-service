<?
namespace Famous\Controllers;
use Famous\Core\Controller as Controller;
use Famous\Core\View as View;
use Famous\Models\Model_Main;

class Controller_Main extends Controller
{
	function __construct()
	{
		$this->model = new Model_Main();
		$this->view = new View();
	}

	function action_index()
	{
	}

}
?>