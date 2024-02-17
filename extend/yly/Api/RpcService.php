<?php

namespace Yly\Api;

use Yly\Config\YlyConfig;
use Yly\Protocol\YlyRpcClient;

class RpcService{

    protected $client;

    public function __construct($token, YlyConfig $config)
    {
        $this->client = new YlyRpcClient($token, $config);
    }

}