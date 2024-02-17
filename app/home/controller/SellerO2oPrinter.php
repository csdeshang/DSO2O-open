<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
use Yly\Config\YlyConfig;
use Yly\Oauth\YlyOauthClient;
use Yly\Api\PrinterService;
/**
 * ============================================================================
 * DSO2O多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class  SellerO2oPrinter extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/seller_o2o_printer.lang.php');
    }


    public function index() {
        View::assign('store_info', $this->store_info);
        View::assign('redirect_uri',urlencode(HOME_SITE_URL.'/seller_o2o_printer/bind'));
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_o2o_printer');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem();
        return View::fetch($this->template_dir.'index');
        
    }
    
    public function bind(){
        include_once root_path(). 'extend/yly/Autoloader.php';
        try{
            $config = new YlyConfig(config('ds_config.yly_client_id'), config('ds_config.yly_client_secret'));
            $client = new YlyOauthClient($config);
            $token = $client->getToken(input('param.code'));   //若是开放型应用请传授权码code
            //保存
            $data = array(
                'yly_access_token' => $token->access_token,
                'yly_refresh_token' => $token->refresh_token,
                'yly_expires_in' => TIMESTAMP+$token->expires_in,
                'yly_machine_code' => $token->machine_code,
            );
            $store_model = model('store');
            $store_model->editStore($data, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('bind_o2o_printer'));
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(lang('ds_common_op_succ'),'SellerO2oPrinter/index');
    }
    
    public function unbind(){
        include_once root_path(). 'extend/yly/Autoloader.php';
        try{
            $access_token=$this->store_info['yly_access_token'];
            $config = new YlyConfig(config('ds_config.yly_client_id'), config('ds_config.yly_client_secret'));
            if($this->store_info['yly_expires_in']<TIMESTAMP){
                $client = new YlyOauthClient($config);
                $token = $client->refreshToken($this->store_info['yly_refresh_token']);
                $access_token=$token->access_token;
            }
            $printer = new PrinterService($access_token, $config);
            $printer->deletePrinter($this->store_info['yly_machine_code']);
            //保存
            $data = array(
                'yly_access_token' => '',
                'yly_refresh_token' => '',
                'yly_expires_in' => '',
                'yly_machine_code' => '',
            );
            $store_model = model('store');
            $store_model->editStore($data, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('unbind_o2o_printer'));
        }catch (\Exception $e) {
             ds_json_encode(10001,$e->getMessage());
        }
         ds_json_encode(10000,lang('ds_common_op_succ'));
    }
    
    protected function getSellerItemList() {
        $menu_array = array(
            1 => array(
                'name' => 'index', 'text' => lang('baseseller_o2o_printer'),
                'url' => url('seller_o2o_printer/index')
            ),
            
        );
        return $menu_array;
    }

}

?>
