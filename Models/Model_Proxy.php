<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Common\Response as Response;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/20/2016
 * Time: 22:54
 */
class Model_Proxy extends Model
{

    public function notFound404($address){
        $data = new Response("notFound404", Constant::ERR_STATUS, "Page $address not found");
        header('HTTP/1.1 404 Not Found');
        header("Status: 404 Not Found");
        return $data;
    }

    public function redirect301($address){
        $data = new Response("redirect301", Constant::ERR_STATUS, "Page $address moved permanently");
        Header( "HTTP/1.1 301 Moved Permanently" );
        Header( "Location: $address" );
        return $data;
    }
}