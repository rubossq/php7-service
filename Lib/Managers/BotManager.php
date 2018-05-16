<?php
namespace Famous\Lib\Managers;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Cash as Cash;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Managers\BalanceManager as BalanceManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Utils\Redis as Redis;
use Famous\Lib\Utils\DB as DB;
use \PDO as PDO;
use \Exception as Exception;

/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 22:02
 */
class BotManager
{
    const MIN_TOTAL_LIKES = 20;
    const MIN_POSTS = 4;
    const MIN_FOLLOWED = 20;
}