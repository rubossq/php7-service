<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-10-20
 * Time: 13:20
 */

namespace Famous\Lib\Managers;


use Famous\Lib\Common\Response;
use Famous\Lib\Utils\Constant;
use Famous\Lib\Utils\DB;
use \PDO as PDO;

class UtilsManager
{
    public static function getNetworkId(&$dbh, $package_name, $table){
        $stmt = $dbh->prepare("SELECT network_id FROM `".$table."` WHERE app_id = :package_name");
        $stmt->bindParam(":package_name", $package_name);

        $stmt->execute();

        if($stmt->rowCount() > 0){
            $network_id = $stmt->fetchColumn();
            $data = new Response("getNetworkId", Constant::OK_STATUS, "", array("network_id"=>$network_id));
        }else{
            $data = new Response("getNetworkId", Constant::ERR_STATUS, "No network for the package");
        }

        return $data;
    }

}