<?php
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 09/01/2016
 * Time: 23:16
 */

namespace Famous\Lib\Utils;
use Predis\Client as Client;

class Redis
{
    private static $redis;
    private $client;
    /**
     * RedisManager constructor.
     */

    private function __construct()
    {
        if(Config::REDIS_MODE == Config::REDIS_LOCAL_MODE){
            $this->client = new Client(['scheme' => 'unix', 'path' => '/run/redis/redis.sock']);
        }else{
            // Parameters passed using a named array:
            $this->clien = new Client([
                'scheme' => 'tcp',
                'host'   => Config::REDIS_SERVER,
                'port'   => 6379
            ]);
        }
    }
    /*
    * get current instance
    * @return object of class DB
    * @access public
    */
    public static function getInstance(){
        if(self::$redis === null){
            self::$redis = new Redis();
        }
        return self::$redis;
    }

    public function getClient(){
        return $this->client;
    }
}