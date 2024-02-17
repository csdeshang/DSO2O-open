<?php

/*
 * 支付相关处理
 */

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
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
class  Payment extends BaseMall {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/buy.lang.php');
    }

    private function use_predeposit($order_info, $post, $virtual = 0) {
        if ($virtual==1) {

            $logic_buy = model('buyvirtual', 'logic');
		}elseif($virtual==3){
            $logic_buy = model('o2o_errand_order');
        }elseif($virtual==4){
            $logic_buy = model('o2o_fuwu_order');
        } elseif($virtual==2){
			$logic_buy = model('storejoinin');
        } else {

            $logic_buy = model('buy_1', 'logic');
        }
        if (empty($post['password'])) {
            return $order_info;
        }
        $member_model = model('member');
        $buyer_info = $member_model->getMemberInfoByID(session('member_id'));
        if ($buyer_info['member_paypwd'] == '' || $buyer_info['member_paypwd'] != md5($post['password'])) {
            return $order_info;
        }
        if ($buyer_info['available_rc_balance'] == 0) {
            $post['rcb_pay'] = null;
        }
        if ($buyer_info['available_predeposit'] == 0) {
            $post['pd_pay'] = null;
        }


        try {
            Db::startTrans();


            if (!empty($post['rcb_pay'])) {
                $order_info = $logic_buy->rcbPay($order_info, $post, $buyer_info);
            }

            if (!empty($post['pd_pay'])) {
                $order_info = $logic_buy->pdPay($order_info, $post, $buyer_info);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exit($e->getMessage());
        }

        return $order_info;
    }

    private function get_order_info($result) {
        //计算本次需要在线支付的订单总金额
        $pay_amount = 0;
        $pay_order_id_list = array();
        if (!empty($result['data']['order_list'])) {
            foreach ($result['data']['order_list'] as $order_info) {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount += $order_info['order_amount'] - $order_info['pd_amount'] - $order_info['rcb_amount'];
                    $pay_order_id_list[] = $order_info['order_id'];
                }
            }
        }

        if (round($pay_amount,2) == 0) {
            $result['data']['pay_end'] = 1;
        } else {
            $result['data']['pay_end'] = 0;
        }
        $result['data']['api_pay_amount'] = ds_price_format($pay_amount);
        //临时注释
        if (!empty($pay_order_id_list)) {
            $update = model('order')->editOrder(array('payment_time'=>TIMESTAMP), array(array('order_id', 'in', $pay_order_id_list)));
//            if (!$update) {
//                exit('更新订单信息发生错误，请重新支付');//因为微信支付时会重定向获取openid所以会更新两次
//            }
        }
        //如果是开始支付尾款，则把支付单表重置了未支付状态，因为支付接口通知时需要判断这个状态
        if (isset($result['data']['if_buyer_repay'])) {
            $update = model('order')->editOrderpay(array('api_paystate' => 0), array('pay_id' => $result['data']['pay_id']));
            if (!$update) {
                exit(lang('order_pay_fail'));
            }
            $result['data']['api_paystate'] = 0;
        }
        return $result;
    }
	
	private function get_sj_order_info($result) {
		//计算本次需要在线支付的订单总金额
		$pay_amount = 0;
		if ($result['data']['joinin_state'] == STORE_JOIN_STATE_VERIFY_SUCCESS) {
			$pay_amount += $result['data']['paying_amount'] - $result['data']['pd_amount'] - $result['data']['rcb_amount'];
		}

		if ($pay_amount == 0) {
			$result['data']['pay_end'] = 1;
		} else {
			$result['data']['pay_end'] = 0;
		}

		$result['data']['api_pay_amount'] = ds_price_format($pay_amount);
	


		return $result;
	}
	/**
	 * 店铺入驻
	 */
	public function sj_order() {
		$storejoinin_model = model('storejoinin');
		$joinin_detail = $storejoinin_model->getOneStorejoinin(array('member_id' => session('member_id')));
		if(!$joinin_detail){
			$this->error('店铺入驻不存在');
		}
		$payment_code = input('post.payment_code');
		$url = (string)url('Seller/index');

		$pay_sn=$joinin_detail['pay_sn'];
		if(!$pay_sn){
			$pay_sn=makePaySn(session('member_id'));
			$storejoinin_model->editStorejoinin(array('pay_sn'=>$pay_sn), array('member_id' => session('member_id'),'pay_sn'=>''));
		}
		
		$logic_payment = model('payment', 'logic');
		$result = $logic_payment->getPaymentInfo($payment_code);
		if (!$result['code']) {
			$this->error($result['msg'], $url);
		}
		$payment_info = $result['data'];
		//计算所需支付金额等支付单信息
		$result = $logic_payment->getSjOrderInfo($pay_sn);
		if (!$result['code']) {
			$this->error($result['msg'], $url);
		}

		if ($result['data']['joinin_state'] != STORE_JOIN_STATE_VERIFY_SUCCESS || empty($result['data']['api_pay_amount'])) {
			$this->error(lang('no_payment_required_this_order'), $url);
		}
		$result['data'] = $this->use_predeposit($result['data'], input('param.'), 2);
		$result = $this->get_sj_order_info($result);
		if ($result['data']['pay_end'] == 1) {
			$this->redirect($url);return;
		}
		//转到第三方API支付
		$this->_api_pay($result['data'], $payment_info);
	}
    
    /**
     * 实物商品订单
     */
    public function real_order() {
        $pay_sn = input('post.pay_sn');
        $payment_code = input('post.payment_code');
        $url = url('Memberorder/index');

        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            $this->error(lang('param_error'), $url);
        }
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $payment_info = $result['data'];
        //计算所需支付金额等支付单信息
        $result = $logic_payment->getRealOrderInfo($pay_sn, session('member_id'));
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        if ($result['data']['api_paystate'] || empty($result['data']['api_pay_amount'])) {
            $this->error(lang('no_payment_required_this_order'), $url);
        }
        $result['data']['order_list'] = $this->use_predeposit($result['data']['order_list'], input('param.'), 0);
        $result = $this->get_order_info($result);
        if ($result['data']['pay_end'] == 1) {
            //站内支付了全款
            $this->redirect($url);return;
        }
        //转到第三方API支付
        $this->_api_pay($result['data'], $payment_info);
    }

    /**
     * 预存款充值
     */
    public function pd_order() {
        $pdr_sn = input('param.pdr_sn');
        $payment_code = input('param.payment_code');
        $url = url('Predeposit/index');

        if (!preg_match('/^\d{20}$/', $pdr_sn)) {
            $this->error(lang('param_error'), $url);
        }

        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $payment_info = $result['data'];
        $result = $logic_payment->getPdOrderInfo($pdr_sn, session('member_id'));
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        if ($result['data']['pdr_payment_state'] || empty($result['data']['api_pay_amount'])) {
            $this->error(lang('no_payment_required'), $url);
        }

        //转到第三方API支付
        $this->_api_pay($result['data'], $payment_info);
    }

    /*
     * 跑腿订单
     */
    public function o2o_errand_order(){
        //H5 相关接口的调用
        @header("Content-type: text/html; charset=UTF-8");
        $pay_sn = input('param.pay_sn');
        $payment_code = input('param.payment_code');
        $url = url('MemberO2oErrandOrder/index');
        if (!$pay_sn) {
            $this->error(lang('param_error'), $url);
        }
        
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $payment_info = $result['data'];
        //计算所需支付金额等支付单信息
        $result = $logic_payment->getO2oErrandOrderInfo($pay_sn, session('member_id'));
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $result['data'] = $this->use_predeposit($result['data'], input('param.'),3);
        $result['data']['api_pay_amount'] = $result['data']['o2o_errand_order_amount']-$result['data']['o2o_errand_order_rcb_amount']-$result['data']['o2o_errand_order_pd_amount'];
        if ($result['data']['api_pay_amount']<=0) {
            $this->error('该订单不需要支付', $url);
        }
        
        $this->_api_pay($result['data'], $payment_info);
    }
    /*
     * 服务订单
     */
    public function o2o_fuwu_order(){
        //H5 相关接口的调用
        @header("Content-type: text/html; charset=UTF-8");
        $pay_sn = input('param.pay_sn');
        $payment_code = input('param.payment_code');
        $url=url('MemberO2oFuwuOrder/index');
        if (!$pay_sn) {
            $this->error(lang('param_error'), $url);
        }
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $payment_info = $result['data'];
        //计算所需支付金额等支付单信息
        $result = $logic_payment->getO2oFuwuOrderInfo($pay_sn, session('member_id'));
        if (!$result['code']) {
            $this->error($result['msg'], $url);
        }
        $result['data'] = $this->use_predeposit($result['data'], input('param.'),4);
        $result['data']['api_pay_amount'] = $result['data']['o2o_fuwu_order_amount']-$result['data']['o2o_fuwu_order_rcb_amount']-$result['data']['o2o_fuwu_order_pd_amount'];
        if ($result['data']['api_pay_amount']<=0) {
            $this->error('该订单不需要支付', $url);
        }
        
        $this->_api_pay($result['data'], $payment_info);
    }
    
    /**
     * 第三方在线支付接口
     *
     */
    private function _api_pay($order_info, $payment_info) {
        try{
        $payment_api = new $payment_info['payment_code']($payment_info);
        }catch(\Exception $e){
            $this->error($e->getMessage());
        }
        if (in_array($payment_info['payment_code'],array('wxpay_native','allinpay'))) {
            if (!extension_loaded('curl')) {
                $this->error(lang('please_check_system_configuration'));
            }

            if (array_key_exists('order_list', $order_info)) {
                View::assign('order_list', $order_info['order_list']);
                View::assign('args', 'buyer_id=' . session('member_id') . '&pay_id=' . $order_info['pay_id']);
            } else {
                View::assign('order_list', array());
                if ($order_info['order_type'] == 'pd_order') {
                    View::assign('args', 'buyer_id=' . session('member_id') . '&pdr_sn=' . $order_info['pdr_sn']);
                } else {
                    View::assign('args', 'buyer_id=' . session('member_id') . '&order_id=' . (isset($order_info['order_id']) ? $order_info['order_id'] : ''));
                }
            }
            View::assign('api_pay_amount', $order_info['api_pay_amount']);
            try{
            $pay_url=base64_encode(ds_encrypt($payment_api->get_payform($order_info), MD5_KEY));
            }catch(\Exception $e){
                $this->error($e->getMessage());
            }
            
            View::assign('pay_url', $pay_url);
            View::assign('nav_list', rkcache('nav', true));
            if($payment_info['payment_code']=='wxpay_native'){
                $pay_method=lang('pay_method_wechat');
            }elseif($payment_info['payment_code']=='allinpay'){
                $paytype=input('param.paytype');
                switch($paytype){
                    case 'W01':
                        $pay_method=lang('pay_method_wechat');
                        break;
                    case 'A01':
                        $pay_method=lang('pay_method_alipay');
                        break;
                    case 'Q01':
                        $pay_method=lang('pay_method_tenpay');
                        break;
                    case 'U01':
                        $pay_method=lang('pay_method_unionpay');
                        break;
                    default:
                        $this->error(lang('please_check_system_configuration'));
                }

            }
            View::assign('pay_method',$pay_method);
            echo View::fetch($this->template_dir . 'wxpay');
        } else {
            try{
            $pay_url=$payment_api->get_payform($order_info);
            }catch(\Exception $e){
                $this->error($e->getMessage());
            }
            @header("Location: " . $pay_url);
        }
        exit();
    }
    
    /**
     * 二维码显示(微信扫码支付) 
     */
    public function qrcode() {
        $data = base64_decode(input('data'));
        $data = ds_decrypt($data, MD5_KEY, 30);
        include_once root_path(). 'extend/qrcode/phpqrcode.php';
        \QRcode::png($data);
    }
    /**
     * 扫码支付结果判断
     */
    public function query_state() {
        $type = input('param.type');
        $logic_payment=model('payment', 'logic');
        switch ($type) {
            case 'o2o_fuwu_order':
                $pay_sn=input('param.pay_sn');
                $result = $logic_payment->getO2oFuwuOrderInfo($pay_sn, session('member_id'));
                exit(json_encode(array('state' => isset($result['data']['code']) && $result['data']['code']==31001, 'type' => 'o2o_fuwu_order')));
                break;
            case 'o2o_errand_order':
                $pay_sn=input('param.pay_sn');
                $result = $logic_payment->getO2oErrandOrderInfo($pay_sn, session('member_id'));
                exit(json_encode(array('state' => isset($result['data']['code']) && $result['data']['code']==51001, 'type' => 'o2o_errand_order')));
                break;
            default:
                if (intval(input('param.pay_id')) > 0) {
                    $info = model('order')->getOrderpayInfo(array(
                        'pay_id' => intval(input('param.pay_id')),
                        'buyer_id' => intval(input('param.buyer_id'))
                    ));
                    exit(json_encode(array(
                        'state' => ($info['api_paystate'] == '1'), 'pay_sn' => $info['pay_sn'], 'type' => 'real_order'
                    )));
                } else {
                    $result = $logic_payment->getPdOrderInfo(input('param.pdr_sn'), input('param.buyer_id'));
                    exit(json_encode(array('state' => $result['code'] && $result['data']['pdr_payment_state'], 'pdr_sn' => $result['code'] ? $result['data']['pay_sn'] : '', 'type' => 'pd_order')));
                }
        }
    }

    /**
     * 
     * @param type $payment_code  共用回调方法
     * @param type $show_code  实际支付方式名称
     */
    public function notify($payment_code,$show_code='') {
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        $payment_info = $result['data'];
        if($show_code){
            $result = $logic_payment->getPaymentInfo($show_code);
            $payment_info['payment_config'] = array_merge($payment_info['payment_config'],$result['data']['payment_config']);
        }
        //创建支付接口对象
        $payment_api = new $payment_code($payment_info);

        //对进入的参数进行远程数据判断
        $verify = $payment_api->verify_notify();
        if ($verify['trade_status'] != 1) {
            exit;
        }
        $out_trade_no = $verify['out_trade_no']; #内部订单号
        $trade_no = $verify['trade_no']; #交易订单号
        $order_type = $verify['order_type']; #交易类型

        $update_result = $logic_payment->updateOrder($out_trade_no, $trade_no, $order_type, $show_code?$show_code:$payment_code,$payment_info['payment_name']);
        exit($update_result ? 'success' : 'fail');
    }


    /**
     * 支付接口同步返回路径
     */
    public function alipay_return() {
        $this->return_verify('alipay');
    }

    /**
     * 银联同步通知
     */
    public function unionpay_return() {
        $this->return_verify('unionpay');
    }
    
    
    public function return_verify($payment_code){

        $logic_payment = model('payment', 'logic');
        //取得支付方式
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            $this->error($result['msg'], 'Memberorder/index');
        }
        $payment_info = $result['data'];

        //创建支付接口对象
        $payment_api = new $payment_info['payment_code']($payment_info);

        //返回参数判断
        $verify = $payment_api->return_verify();
        if (!$verify || $verify['trade_status']=='0') {
            $this->error(lang('payment_data_validation_failed'), 'Memberorder/index');
        }
        $order_type=$verify['order_type'];
        $out_trade_no=$verify['out_trade_no'];
        $order_amount=$verify['total_fee'];
        //支付成功后跳转
        if ($order_type == 'real_order') {
            $pay_ok_url = HOME_SITE_URL . '/buy/pay_ok?pay_sn=' . $out_trade_no . '&pay_amount=' . ds_price_format($order_amount);
        } elseif ($order_type == 'pd_order') {
            $pay_ok_url = HOME_SITE_URL . '/predeposit/index';
        }
        header("Location:$pay_ok_url");
        exit;
    }
    /**
     * 通联异步通知
     */
    public function allinpay_notify(){
        $this->notify('allinpay');
    }
    /**
     * 银联异步通知
     */
    public function unionpay_notify(){
        $this->notify('unionpay');
    }
    /**
     * 微信扫码支付异步通知
     */
    public function wxpay_native_notify() {
        $this->notify('wxpay_native');
    }
     /**
     * 小程序支付异步通知
     */
    public function wxpay_minipro_notify() {
        $this->notify('wxpay_native','wxpay_minipro');
    }
     /**
     * 微信支付支付异步通知
     */
    public function wxpay_jsapi_notify() {
        $this->notify('wxpay_native','wxpay_jsapi');
    }
    /**
     * 微信H5支付异步通知
     */
    public function wxpay_h5_notify() {
        $this->notify('wxpay_native','wxpay_h5');
    }
    /**
     * 微信APP支付异步通知
     */
    public function wxpay_app_notify() {
        $this->notify('wxpay_native','wxpay_app');
    }
    /**
     * 通知处理(支付宝异步对账)
     */
    public function alipay_notify() {
        $this->notify('alipay');
    }
    /**
     * 支付宝APP支付异步通知
     */
    public function alipay_app_notify() {
        $this->notify('alipay','alipay_app');
    }
    /**
     * 支付宝wap支付异步通知
     */
    public function alipay_h5_notify() {
        $this->notify('alipay','alipay_h5');
    }
    

}

?>
