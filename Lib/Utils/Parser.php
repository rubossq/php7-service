<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 2016-10-12
 * Time: 22:02
 */

namespace Famous\Lib\Utils;


class Parser
{

    public static function parsePostLink($link){
        preg_match("/www\\.instagram\\.com\\/p\\/([a-zA-Z0-9]+)/i", $link, $matches);
        return array("real_id"=>$matches[1]);
    }

}