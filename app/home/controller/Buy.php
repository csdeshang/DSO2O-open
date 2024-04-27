<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Lang;
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
class  Buy extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/buy.lang.php');
        //验证该会员是否禁止购买
        if(!session('is_buy')){
            $this->error(lang('cart_buy_noallow'));
        }
        if(config('ds_config.member_auth') && $this->member_info['member_auth_state']!=3){
            $this->error(lang('cart_buy_noauth'),url('MemberAuth/index'));
        }
    }

    public function buy_step1() {
        if(empty(input('post.'))){
            $this->error(lang('param_error'));
        }
        $ifcart = input('post.ifcart');

        $buy_logic = model('buy','logic');
        $result = $buy_logic->buyStep1(input('post.cart_id/a'), $ifcart, session('member_id'), session('store_id'));
        if ($result['code'] != 'SUCCESS') {
            $this->error($result['msg']);
        } else {
            $result = $result['data'];
        }
        View::assign('store_ids', implode(',', array_keys($result['store_cart_list'])));
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        View::assign('store_cart_list', $result['store_cart_list']);
        View::assign('store_goods_total', $result['store_goods_total']);
        View::assign('store_goods_original_total', $result['store_goods_original_total']);
        View::assign('store_goods_discount_total', $result['store_goods_discount_total']);
        View::assign('store_o2o_fee_list', $result['store_o2o_fee_list']);

        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        View::assign('store_premiums_list', $result['store_premiums_list']);
        View::assign('store_mansong_rule_list', $result['store_mansong_rule_list']);

        //返回店铺可用的代金券
        View::assign('store_voucher_list', $result['store_voucher_list']);


        //输出用户默认收货地址
        View::assign('address_info', $result['address_info']);


        //不提供增值税发票时抛出true(模板使用)
        View::assign('vat_deny', $result['vat_deny']);
        
        //增值税发票哈希值(php验证使用)
        View::assign('vat_hash', $result['vat_hash']);

        //输出默认使用的发票信息
        View::assign('inv_info', $result['inv_info']);

        //显示预存款、支付密码、充值卡
        View::assign('available_pd_amount', isset($result['available_predeposit'])?$result['available_predeposit']:'');
        View::assign('member_paypwd', $result['member_paypwd']);
        View::assign('available_rcb_amount', isset($result['available_rc_balance'])?$result['available_rc_balance']:'');

        //标识购买流程执行步骤
        View::assign('buy_step', 'step2');
        View::assign('ifcart', $ifcart);
        //店铺信息
        $store_list = model('store')->getStoreMemberIDList(array_keys($result['store_cart_list']));
        View::assign('store_list', $store_list);
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        //达达配送信息
        $weight_list=array();
        foreach($result['weight_list'] as $store_id => $weight){
            $weight_list[]=$store_id .'|'. $weight;
        }
        View::assign('weight_list', implode(',', $weight_list));
        return View::fetch($this->template_dir.'buy_step1');
    }

    /**
     * 生成订单
     *
     */
    public function buy_step2() {
        $buy_logic = model('buy','logic');
        $result = $buy_logic->buyStep2(input('post.'), session('member_id'), session('member_name'), session('member_email'));
        if (!$result['code']) {
            $this->error($result['msg']);
        }
        //转向到商城支付页面
        $this->redirect('Buy/pay', ['pay_sn' => $result['data']['pay_sn']]);
    }

    /**
     * 下单时支付页面
     */
    public function pay() {
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            $this->error(lang('cart_order_pay_not_exists'), 'home/Memberorder/index');
        }

        //查询支付单信息
        $order_model = model('order');
        $pay_info = $order_model->getOrderpayInfo(array('pay_sn' => $pay_sn, 'buyer_id' => session('member_id')));
        if (empty($pay_info)) {
            $this->error(lang('cart_order_pay_not_exists'), 'home/Memberorder/index');
        }
        View::assign('pay_info', $pay_info);

        //取子订单列表
        $condition = array();
        $condition[] = array('pay_sn','=',$pay_sn);
        $condition[] = array('order_state','in',array_values(array(ORDER_STATE_NEW, ORDER_STATE_PAY)));
        $order_list = $order_model->getOrderList($condition);
        if (empty($order_list)) {
            $this->error(lang('no_order_paid_was_found'), 'home/Memberorder/index');
        }

        //重新计算在线支付金额
        $pay_amount_online = 0;
        $pay_amount_offline = 0;
        //订单总支付金额(不包含货到付款)
        $pay_amount = 0;

        foreach ($order_list as $key => $order_info) {

            $payed_amount = floatval($order_info['rcb_amount']) + floatval($order_info['pd_amount']);
            //计算相关支付金额
            if ($order_info['payment_code'] != 'offline') {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount_online += ds_price_format(floatval($order_info['order_amount']) - $payed_amount);
                }
                $pay_amount += floatval($order_info['order_amount']);
            } else {
                $pay_amount_offline += floatval($order_info['order_amount']);
            }

            //显示支付方式与支付结果
            if ($order_info['payment_code'] == 'offline') {
                $order_list[$key]['payment_state'] = lang('cart_step2_arrival_pay');
            } else {
                $order_list[$key]['payment_state'] = lang('cart_step2_online_pay');
                if ($payed_amount > 0) {
                    $payed_tips = '';
                    if (floatval($order_info['rcb_amount']) > 0) {
                        $payed_tips = lang('card_has_been_paid').'：￥' . $order_info['rcb_amount'];
                    }
                    if (floatval($order_info['pd_amount']) > 0) {
                        $payed_tips .= lang('prepaid_deposits_beenpaid').'：￥' . $order_info['pd_amount'];
                    }
                    $order_list[$key]['order_amount'] .= " ( {$payed_tips} )";
                }
            }
        }
        View::assign('order_list', $order_list);

        //如果线上线下支付金额都为0，转到支付成功页
        if (empty($pay_amount_online) && empty($pay_amount_offline)) {
            $this->redirect('Buy/pay_ok', ['pay_sn' => $pay_sn, 'pay_amount' => ds_price_format($pay_amount)]);
        }

        //输出订单描述
        if (empty($pay_amount_online)) {
            $order_remind = lang('successful_ordering_delivery');
        } elseif (empty($pay_amount_offline)) {
            $order_remind = lang('timely_payment');
        } else {
            $order_remind = lang('payment_soon_possible');
        }
        View::assign('order_remind', $order_remind);
        View::assign('pay_amount_online', ds_price_format($pay_amount_online));
//        View::assign('pd_amount', ds_price_format($pd_amount));
        //显示支付接口列表
        if ($pay_amount_online > 0) {
            $payment_model = model('payment');
            $condition = array();
            $condition[]=array('payment_platform','=','pc');
            $payment_list = $payment_model->getPaymentOpenList($condition);
            if(empty($payment_list)){
                $this->error(lang('appropriate_payment_method'),'home/Memberorder/index');
            }
            foreach ($payment_list as $key => $payment) {
                if(in_array($payment['payment_code'], array('predeposit','offline'))){
                    unset($payment_list[$key]);
                }
            }
            View::assign('payment_list', $payment_list);
        }

        //显示预存款、支付密码、充值卡
        $member_model=model('member');
        $buyer_info=$member_model->getMemberInfoByID($this->member_info['member_id']);
        View::assign('available_pd_amount', ($buyer_info['available_predeposit']>0)?$buyer_info['available_predeposit']:'');
        View::assign('member_paypwd', $buyer_info['member_paypwd']);
        View::assign('available_rcb_amount', ($buyer_info['available_rc_balance']>0)?$buyer_info['available_rc_balance']:'');
        //标识 购买流程执行第几步
        View::assign('buy_step', 'step3');
        return View::fetch($this->template_dir.'buy_step2');
    }

	/**
	 * 店铺入驻支付页面
	 */
	public function sj_pay(){
		$storejoinin_model = model('storejoinin');
		$joinin_detail = $storejoinin_model->getOneStorejoinin(array('member_id' => session('member_id')));
		if(!$joinin_detail){
			$this->error('店铺入驻不存在');
		}
		if($joinin_detail['joinin_state']!=STORE_JOIN_STATE_VERIFY_SUCCESS){
			$this->error('店铺入驻状态不是待支付状态');
		}
		
		$pay_amount_online=round($joinin_detail['paying_amount']-$joinin_detail['rcb_amount']-$joinin_detail['pd_amount'],2);
		View::assign('pay_amount_online', $pay_amount_online);
		View::assign('store_name', $joinin_detail['store_name']);
		//显示支付接口列表
		if ($pay_amount_online > 0) {
		//显示支付接口列表
		$payment_model = model('payment');
		$condition = array();
		$condition[] = array('payment_code','not in',array('offline', 'predeposit'));
		$condition[] = array('payment_state','=',1);
		$condition[] = array('payment_platform','=','pc');
		$payment_list = $payment_model->getPaymentList($condition);
		if (empty($payment_list)) {
			$this->error(lang('appropriate_payment_method'));
		}
		View::assign('payment_list', $payment_list);
		}
		//显示预存款、支付密码、充值卡
		$member_model=model('member');
		$buyer_info=$member_model->getMemberInfoByID($this->member_info['member_id']);
		View::assign('available_pd_amount', ($buyer_info['available_predeposit']>0)?$buyer_info['available_predeposit']:'');
		View::assign('member_paypwd', $buyer_info['member_paypwd']);
		View::assign('available_rcb_amount', ($buyer_info['available_rc_balance']>0)?$buyer_info['available_rc_balance']:'');
		//标识 购买流程执行第几步
		View::assign('buy_step', 'step3');
		return View::fetch($this->template_dir.'storejoinin_pay');
		
	}

    /**
     * 预存款充值下单时支付页面
     */
    public function pd_pay() {
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            $this->error(lang('param_error'), url('Predeposit/index'));
        }

        //查询支付单信息
        $predeposit_model = model('predeposit');
        $pd_info = $predeposit_model->getPdRechargeInfo(array('pdr_sn' => $pay_sn, 'pdr_member_id' => session('member_id')));
        if (empty($pd_info)) {
            $this->error(lang('param_error'));
        }
        if (intval($pd_info['pdr_payment_state'])) {
            $this->error(lang('not_repeat_payment'), url('Predeposit/index'));
        }
        View::assign('pdr_info', $pd_info);

        //显示支付接口列表
        $payment_model = model('payment');
        $condition = array();
        $condition[] = array('payment_code','not in',array('offline', 'predeposit'));
        $condition[] = array('payment_state','=',1);
        $condition[] = array('payment_platform','=','pc');
        $payment_list = $payment_model->getPaymentList($condition);
        if (empty($payment_list)) {
            $this->error(lang('appropriate_payment_method'), url('Predeposit/index'));
        }
        View::assign('payment_list', $payment_list);

        //标识 购买流程执行第几步
        View::assign('buy_step', 'step3');
        return View::fetch($this->template_dir.'predeposit_pay');
    }
    
    /**
     * 跑题订单支付页面
     */
    public function o2o_errand_pay(){
        $pay_sn = input('param.pay_sn');
        if (!$pay_sn) {
            $this->error(lang('param_error'), url('MemberO2oErrandOrder/index'));
        }
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getO2oErrandOrderInfo($pay_sn, $this->member_info['member_id']);
        if (!$result['code']) {
            $this->error($result['msg'], url('MemberO2oErrandOrder/index'));
        }
        $payment_model = model('payment');

        $condition = array();
        $condition[]=array('payment_platform','=','pc');
        $payment_list = $payment_model->getPaymentOpenList($condition);
        $payment_array = array();
        if (!empty($payment_list)) {
            foreach ($payment_list as $value) {
                $payment_array[] = array('payment_code' => $value['payment_code'], 'payment_name' => $value['payment_name']);
            }
        } else {
            $this->error(lang('appropriate_payment_method'), url('MemberO2oErrandOrder/index'));
        }
        View::assign('pay_info', $result['data']);
        View::assign('payment_list', $payment_list);

        return View::fetch($this->template_dir.'pay');
    }
    /**
     * 服务订单支付页面
     */
    public function o2o_fuwu_pay() {
        $pay_sn = input('param.pay_sn');
        if (!$pay_sn) {
            $this->error('支付单号错误',url('MemberO2oFuwuOrder/index'));
        }
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getO2oFuwuOrderInfo($pay_sn, $this->member_info['member_id']);
        if (!$result['code']) {
            if(isset($result['data']) && isset($result['data']['code'])){
                if($result['data']['code']==31001){
                    $this->redirect(url('MemberO2oFuwuOrder/index'));
                }
            }
            $this->error($result['msg'],url('MemberO2oFuwuOrder/index'));
        }
        $payment_model = model('payment');

        $condition = array();
        $condition[]=array('payment_platform','=','pc');
        $payment_list = $payment_model->getPaymentOpenList($condition);
        $payment_array = array();
        if (!empty($payment_list)) {
            foreach ($payment_list as $value) {
                $payment_array[] = array('payment_code' => $value['payment_code'], 'payment_name' => $value['payment_name']);
            }
        } else {
            $this->error('暂未找到合适的支付方式!',url('MemberO2oFuwuOrder/index'));
        }
        View::assign('pay_info', $result['data']);
        View::assign('payment_list', $payment_list);

        return View::fetch($this->template_dir.'pay');
    }

    /**
     * 支付成功页面
     */
    public function pay_ok() {
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            $this->error(lang('cart_order_pay_not_exists'), 'memberorder/index');
        }

        //查询支付单信息
        $order_model = model('order');
        $pay_info = $order_model->getOrderpayInfo(array('pay_sn' => $pay_sn, 'buyer_id' => session('member_id')));
        if (empty($pay_info)) {
            $this->error(lang('cart_order_pay_not_exists'), 'home/Memberorder/index');
        }
        View::assign('pay_info', $pay_info);

        View::assign('buy_step', 'step4');
        return View::fetch($this->template_dir.'buy_step3');
    }

    function load_addr() {
        $id = intval(input('param.id'));
        $address_model=model('address');
        //如果传入ID 则删除再查询
        if ($id > 0) {
            $address_model->delAddress(array('address_id'=>$id,'member_id'=> session('member_id')));
        }
        $address_list = $address_model->getAddressList(array(array('member_id','=',session('member_id')),array('address_o2o_errand_type' ,'=', 0)));
        View::assign('address_list', $address_list);
        echo View::fetch($this->template_dir.'buy_address_load');
    }
    /*
     * 判断配送范围
     */
    function calc_distance(){
        $buy_1_logic = model('buy_1','logic');
        $weight_list=input('param.weight_list');
        if(!$weight_list){
            exit(json_encode(array('state'=>false,'msg'=>lang('param_error'))));
        }
        $weight_list= explode(',', $weight_list);
        $_weight_list=array();
        foreach($weight_list as $weight){
            $temp=explode('|',$weight);
            $_weight_list[intval($temp[0])]=floatval($temp[1]);
        }
        $res=$buy_1_logic->calcDistance(input('param.addr_id'), $_weight_list);
        exit(json_encode($res));
    }
    /*
     * 新增收货地址
     */

    function add_addr() {
        if (!request()->isPost()) {
            //设置类型关联的分类
            $area_mod=model('area');
            $region_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
            View::assign('region_list', $region_list);

            echo View::fetch($this->template_dir.'buy_address_add');
        } else {
            $data = array(
                'member_id' => session('member_id'),
                'address_realname' => input('post.true_name'),
                'area_id' => intval(input('post.area_id')),
                'city_id' => intval(input('post.city_id')),
                'area_info'=>input('post.area_info'),
                'address_detail' => input('post.address'),
				'house_number' => input('post.house_number'),
                'address_tel_phone' => input('post.tel_phone'),
                'address_mob_phone' => input('post.mob_phone'),
                'address_longitude' => input('post.longitude'),
                'address_latitude' => input('post.latitude'),
                'address_is_default' => 0,
            );
            $buy_validate = ds_validate('buy');
            if (!$buy_validate->scene('add_addr')->check($data)) {
                exit(json_encode(array('state' => false, 'msg' => $buy_validate->getError())));
            }

            $insert_id = model('address')->addAddress($data);
            if ($insert_id) {
                exit(json_encode(array('state' => true, 'addr_id' => $insert_id)));
            } else {
                exit(json_encode(array('state' => true, 'msg' => lang('add_cart_failed'))));
            }
        }
    }



    function load_inv() {
        $id = intval(input('param.id'));
        $invoice_model = model('invoice');
        //如果传入ID 则删除再查询
        if ($id > 0) {
            $condition = array();
            $condition[] = array('invoice_id','=',$id);
            $condition[] = array('member_id','=',session('member_id'));
            $invoice_model->delInvoice($condition);
        }
        $inv_list = $invoice_model->getInvoiceList(array('member_id'=> session('member_id')));
        if (!empty($inv_list)) {
            foreach ($inv_list as $key => $value) {
                if ($value['invoice_state'] == 1) {
                    $inv_list[$key]['content'] = lang('commercial_invoice') . ' ' . $value['invoice_title'] . ' ' . $value['invoice_code']. ' ' . $value['invoice_content'];
                } else {
                    $inv_list[$key]['content'] = lang('vat_invoice') . ' ' . $value['invoice_company'] . ' ' . $value['invoice_company_code'] . ' ' . $value['invoice_reg_addr'];
                }
            }
        }
        View::assign('inv_list', $inv_list);
        echo View::fetch($this->template_dir.'buy_invoice_load');
    }

    function add_inv() {
        if (!request()->isPost()) {
            echo View::fetch($this->template_dir.'buy_address_add');
        } else {
            $invoice_type = input('post.invoice_type');
            //如果是增值税发票验证表单信息
            if ($invoice_type == 2) {
                if (empty(input('post.invoice_company')) || empty(input('post.invoice_company_code')) || empty(input('post.invoice_reg_addr'))) {
                    exit(json_encode(array('state' => false, 'msg' => lang('save_information_failed'))));
                }
            }
            $data = array();
            if ($invoice_type == 1) {
                $data['invoice_state'] = 1;
                $data['invoice_title'] = input('post.invoice_title_select') == 'person' ? lang('individual') : input('post.invoice_title');
                $data['invoice_content'] = input('post.invoice_content');
                $data['invoice_code'] = input('post.invoice_code');
            } else {
                $data['invoice_state'] = 2;
                $data['invoice_company'] = input('post.invoice_company');
                $data['invoice_company_code'] = input('post.invoice_company_code');
                $data['invoice_reg_addr'] = input('post.invoice_reg_addr');
                $data['invoice_reg_phone'] = input('post.invoice_reg_phone');
                $data['invoice_reg_bname'] = input('post.invoice_reg_bname');
                $data['invoice_reg_baccount'] = input('post.invoice_reg_baccount');
                $data['invoice_rec_name'] = input('post.invoice_rec_name');
                $data['invoice_rec_mobphone'] = input('post.invoice_rec_mobphone');
                $data['invoice_rec_province'] = input('post.area_info');
                $data['invoice_goto_addr'] = input('post.invoice_goto_addr');
            }
            $data['member_id'] = session('member_id');
            $insert_id = model('invoice')->addInvoice($data);
            if ($insert_id) {
                exit(json_encode(array('state' => 'success', 'id' => $insert_id)));
            } else {
                exit(json_encode(array('state' => 'fail', 'msg' => lang('save_information_failed'))));
            }
        }
    }

    /**
     * AJAX验证支付密码
     */
    public function check_pd_pwd() {
        $password = input('param.password');
        if (empty($password))
            exit('0');
        $buyer_info = model('member')->getMemberInfoByID(session('member_id'));
        echo ($buyer_info['member_paypwd'] != '' && $buyer_info['member_paypwd'] === md5($password)) ? '1' : '0';
    }
    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    $buy_items[$match[1][0]] = $match[2][0];
                }
            }
        }
        return $buy_items;
    }

}