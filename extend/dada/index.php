<?php
//参考文档 http://newopen.imdada.cn/#/development/file/add?_k=ff7mls

define("BASE_DIR", dirname(__FILE__) . "/");
require_once BASE_DIR . 'api/baseApi.php';
require_once BASE_DIR . 'client/dadaRequestClient.php';
require_once BASE_DIR . 'client/dadaResponse.php';
require_once BASE_DIR . 'config/config.php';

function convert_coordinate($lng,$lat){
    return http_request('https://restapi.amap.com/v3/assistant/coordinate/convert?key='.config('ds_config.gaode_key').'&locations='.$lng.','.$lat.'&coordsys=baidu');
}
function query_dada($path,$body){
    $config = new Config(config('ds_config.dada_source_id'), true);
    $config->app_key=config('ds_config.dada_app_key');
    $config->app_secret=config('ds_config.dada_app_secret');

    $api = new BaseApi($path,$body);

    $dada_client = new DadaRequestClient($config, $api);
    $resp = $dada_client->makeRequest();
    return $resp;
}
