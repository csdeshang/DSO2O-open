<?php

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
class Sellerorder extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerorder.lang.php');
    }

    /**
     * 订单列表
     *
     */
    public function index() {
        $order_model = model('order');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));

        $order_sn = input('get.order_sn');
        if ($order_sn != '') {
            $condition[] = array('order_sn', '=', $order_sn);
        }
        $buyer_name = input('get.buyer_name');
        if ($buyer_name != '') {
            $condition[] = array('buyer_name', '=', $buyer_name);
        }
        $allow_state_array = array('state_new', 'state_pay', 'state_pick', 'state_send', 'state_success', 'state_cancel');
        $state_type = input('param.state_type');
        if (in_array($state_type, $allow_state_array)) {
            $condition[] = array('order_state', '=', str_replace($allow_state_array, array(ORDER_STATE_NEW, ORDER_STATE_PAY, ORDER_STATE_DELIVER, ORDER_STATE_SEND, ORDER_STATE_SUCCESS, ORDER_STATE_CANCEL), $state_type));
        } else {
            $state_type = 'store_order';
        }
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('get.query_start_date'));
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('get.query_end_date'));
        $start_unixtime = $if_start_date ? strtotime(input('get.query_start_date')) : null;
        $end_unixtime = $if_end_date ? (strtotime(input('get.query_end_date'))+86399) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('add_time', '<=', $end_unixtime);
        }

        $skip_off = input('get.buyer_name');
        if ($skip_off == 1) {
            $condition[] = array('order_state', '<>', ORDER_STATE_CANCEL);
        }

        $order_list = $order_model->getOrderList($condition, 10, '*', 'order_id desc', '', array('order_goods', 'order_common', 'member'));
        
        $refundreturn_model = model('refundreturn');
        $order_list = $refundreturn_model->getGoodsRefundList($order_list);
        
        View::assign('show_page', $order_model->page_info->render());

        //页面中显示那些操作
        foreach ($order_list as $key => $order_info) {
            //显示取消订单
            $order_info['if_cancel'] = $order_model->getOrderOperateState('store_cancel', $order_info);
            //显示调整运费
            $order_info['if_modify_price'] = $order_model->getOrderOperateState('modify_price', $order_info);
            //显示修改价格
            $order_info['if_spay_price'] = $order_model->getOrderOperateState('spay_price', $order_info);

            //显示锁定中
            $order_info['if_order_refund_lock'] = $order_model->getOrderOperateState('order_refund_lock', $order_info);

            //显示接单
            $order_info['if_receipt'] = $order_model->getOrderOperateState('receipt', $order_info);

            //显示派单
            $order_info['if_deliver'] = $order_model->getOrderOperateState('deliver', $order_info);

            foreach ($order_info['extend_order_goods'] as $value) {
                $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
                $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
                $value['goods_url'] = url('Goods/index', ['goods_id' => $value['goods_id']]);
                if ($value['goods_type'] == 5) {
                    $order_info['zengpin_list'][] = $value;
                } else {
                    $order_info['goods_list'][] = $value;
                }
            }

            if (empty($order_info['zengpin_list'])) {
                $order_info['goods_count'] = count($order_info['goods_list']);
            } else {
                $order_info['goods_count'] = count($order_info['goods_list']) + 1;
            }
            $order_list[$key] = $order_info;
        }

        View::assign('order_list', $order_list);


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerorder');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem($state_type);
        return View::fetch($this->template_dir . 'index');
    }

    /*
     * 指派订单
     */

    public function deliver_order() {
        $order_id = intval(input('post.order_id'));
        $o2o_distributor_id = intval(input('post.o2o_distributor_id'));
        if (!$order_id || !$o2o_distributor_id) {
            ds_json_encode(10001, lang('param_error'));
        }

        $order_model = model('order');
        $o2o_distributor_notice_model = model('o2o_distributor_notice');
        $o2o_distributor_model = model('o2o_distributor');
        $o2o_distributor_info = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $o2o_distributor_id, 'o2o_distributor_state' => 1));
        if (!$o2o_distributor_info) {
            ds_json_encode(10001, lang('seller_order_distributor_not_exist'));
        }
        $order_info = $order_model->getOrderInfo(array('order_id' => $order_id, 'store_id' => session('store_id'), 'order_state' => ORDER_STATE_RECEIPT, 'refund_state' => 0, 'order_refund_lock_state' => 0,));
        if (!$order_info) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        Db::startTrans();
        try {
            $update_order = array();
            $update_order['o2o_distributor_id'] = $o2o_distributor_info['o2o_distributor_id'];
            $update_order['o2o_distributor_name'] = $o2o_distributor_info['o2o_distributor_name'];
            $update_order['o2o_distributor_realname'] = $o2o_distributor_info['o2o_distributor_realname'];
            $update_order['o2o_distributor_phone'] = $o2o_distributor_info['o2o_distributor_phone'];
            $update_order['order_state'] = ORDER_STATE_DELIVER;
            $update_order['o2o_order_deliver_time'] = TIMESTAMP;
            $update_order['o2o_order_source'] = 2;
            $update = $order_model->editOrder($update_order, array(
                'order_id' => $order_info['order_id'], 'order_state' => ORDER_STATE_RECEIPT, 'refund_state' => 0, 'order_refund_lock_state' => 0,
            ));
            if (!$update) {
                throw new \think\Exception(lang('seller_order_edit_order_fail'), 10006);
            }


            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = 'seller';
            $data['log_user'] = session('seller_name');
            $data['log_msg'] = '店铺派单给配送员' . $update_order['o2o_distributor_name'];
            $data['log_orderstate'] = ORDER_STATE_DELIVER;
            $order_model->addOrderlog($data);


            //生成订单通知
            $o2o_distributor_notice_model->addO2oDistributorNotice(array(
                'o2o_distributor_id' => $update_order['o2o_distributor_id'],
                'o2o_distributor_name' => $update_order['o2o_distributor_name'],
                'o2o_distributor_notice_type' => 1,
                'order_id' => $order_info['order_id'],
                'o2o_distributor_notice_title' => lang('ds_o2o_distributor_notice_type_text')[1],
                'o2o_distributor_notice_content' => sprintf(lang('seller_order_deliver_order_notice'), $order_info['order_sn']),
                'o2o_distributor_notice_add_time' => TIMESTAMP,
            ));
            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }


        $order_info = array_merge($order_info, $update_order);
        $order_info = $order_model->formatO2oOrder($this->store_info, $order_info);
        ds_json_encode(10000, lang('ds_common_op_succ'), array('order_info' => $order_info));
    }

    /*
     * 查看配送员
     */

    public function show_distributor() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('param_error'));
        }

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('order_state', '=', ORDER_STATE_RECEIPT);
        $condition[] = array('store_id', '=', session('store_id'));
        $temp = $order_model->getOrderInfo($condition, array('order_common'));
        if (empty($temp)) {
            $this->error(lang('store_order_none_exist'));
        }
        $order_info = array(
            'order_type' => 'real_order',
            'order_id' => $temp['order_id'],
            'order_sn' => $temp['order_sn'],
            'reciver_name' => $temp['extend_order_common']['reciver_name'],
            'reciver_address' => $temp['extend_order_common']['reciver_info']['address'].$temp['extend_order_common']['reciver_info']['house_number'],
            'reciver_phone' => $temp['extend_order_common']['reciver_info']['mob_phone'],
            'order_distance' => $temp['o2o_order_distance'],
            'order_lng' => $temp['o2o_order_lng'],
            'order_lat' => $temp['o2o_order_lat'],
        );
        if (!$this->store_info['store_longitude'] || !$this->store_info['store_latitude']) {
            $this->error('请先设置店铺地图', url('sellersetting/map'));
        }
        //View::assign('o2o_distributor_list', $o2o_distributor_list);
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        View::assign('order_info', $order_info);
        View::assign('store_longitude', $this->store_info['store_longitude']);
        View::assign('store_latitude', $this->store_info['store_latitude']);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerorder');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem();
        return View::fetch($this->template_dir . 'show_distributor');
    }

    /*
     * 配送员列表
     */

    public function get_distributor_list() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('order_state', '=', ORDER_STATE_RECEIPT);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common'));
        if (empty($order_info)) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        //配送员列表
        $o2o_distributor_model = model('o2o_distributor');
        $condition = array();
        $condition[] = array('o2o_distributor_state', '=', 1);
        $condition[] = array('o2o_distributor_lng','>',0);
        $condition[] = array('o2o_distributor_lat','>',0);
        
        //判断是否只能指定店铺下的配送员
//        if ($order_info['o2o_order_distributor_type'] == 1) {
//            $condition[] = array('store_id', '=', session('store_id'));
//        } else {
//            $condition[] = array('store_id', '=', 0);
//        }
        
        $order = 'o2o_distributor_addtime asc';
        $field = '*';
        //如果传了位置经纬度，则按照经纬度的距离排序
        $lat = floatval(input('param.lat'));
        $lng = floatval(input('param.lng'));
        if($lat>0 && $lng>0){
            $field.= ',(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-o2o_distributor_lat)/360),2)+COS(PI()*' . $lat . '/180)* COS(o2o_distributor_lat * PI()/180)*POW(SIN(PI()*(' . $lng . '-o2o_distributor_lng)/360),2)))) as poi_distance';
            $order = 'poi_distance asc';
        }
        
        $o2o_distributor_list = $o2o_distributor_model->getO2oDistributorList($condition, $field, 10,$order);
        foreach ($o2o_distributor_list as $key => $val) {
            
            $o2o_distributor_list[$key]['o2o_distributor_avatar'] = get_o2o_distributor_file($val['o2o_distributor_avatar'], 'avatar');
            $o2o_distributor_list[$key]['count_wait'] = $order_model->getOrderCount(array(array('o2o_order_deliver_time', '>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id', '=', $val['o2o_distributor_id']), array('order_state', 'in', [ORDER_STATE_DELIVER, ORDER_STATE_SEND])));
            $o2o_distributor_list[$key]['count_complete'] = $order_model->getOrderCount(array(array('o2o_order_deliver_time', '>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id', '=', $val['o2o_distributor_id']), array('order_state', 'in', [ORDER_STATE_SUCCESS])));
        }
        ds_json_encode(10000, '', $o2o_distributor_list);
    }

    /*
     * 订单取货
     */

    public function check_o2o_order_pickup_code() {
        $o2o_order_pickup_code = input('post.o2o_order_pickup_code');
        if (!$o2o_order_pickup_code) {
            ds_json_encode(10001, lang('store_order_o2o_order_pickup_code_require'));
        }
        if (strlen($o2o_order_pickup_code) != 6) {
            ds_json_encode(10001, lang('store_order_o2o_order_pickup_code_length_error'));
        }
        $order_model = model('order');
        $order_info = $order_model->getOrderInfo(array('order_state' => ORDER_STATE_DELIVER, 'o2o_order_pickup_code' => $o2o_order_pickup_code, 'store_id' => session('store_id'), 'refund_state' => 0, 'order_refund_lock_state' => 0,));
        if (empty($order_info)) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        ds_json_encode(10000, '', array('order_id' => $order_info['order_id'], 'o2o_order_pickup_code' => $o2o_order_pickup_code));
    }

    /*
     * 接收订单
     */

    public function receipt_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('order_state', '=', ORDER_STATE_PAY);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition);
        if (empty($order_info)) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        Db::startTrans();
        try {
            $update_order = array();
            $o2o_order_pickup_code = makeO2oOrderPickupCode();
            if ($o2o_order_pickup_code === false) {
                throw new \think\Exception(lang('seller_order_make_pickup_code_fail'), 10006);
            }
            $update_order['o2o_order_pickup_code'] = $o2o_order_pickup_code;
            $update_order['order_state'] = ORDER_STATE_RECEIPT;
            $update_order['o2o_order_receipt_time'] = TIMESTAMP;

            $update = $order_model->editOrder($update_order, array(
                'order_id' => $order_info['order_id'], 'order_state' => ORDER_STATE_PAY, 'refund_state' => 0, 'order_refund_lock_state' => 0,
            ));
            //派单给达达
            if ($order_info['o2o_third'] == 'dada') {
                $order_common = $order_model->getOrdercommonInfo(array('order_id' => $order_info['order_id']));
                $reciver_info = unserialize($order_common['reciver_info']);
                $temp = explode(',', $reciver_info['dada_lng_lat']);
                include_once root_path() . 'extend/dada/index.php';
                $body = array(
                    'shop_no' => $this->store_info['dada_shop_no'],
                    'origin_id' => 'real_order-'.$order_info['order_sn'],
                    'city_code' => $this->store_info['dada_city_code'],
                    'cargo_price' => $order_info['goods_amount'],
                    'is_prepay' => 0,
                    'receiver_name' => $order_common['reciver_name'],
                    'receiver_address' => $reciver_info['address'],
                    'receiver_phone' => $reciver_info['mob_phone'],
                    'receiver_tel' => $reciver_info['tel_phone'],
                    'callback' => API_SITE_URL . '/index/dada_callback',
                    'receiver_lat' => $temp[1],
                    'receiver_lng' => $temp[0],
                    'cargo_weight' => $order_info['goods_weight'],
                );
                $result = query_dada('/api/order/addOrder', json_encode($body));
                if ($result->status != 'success') {
                    throw new \think\Exception('达达订单推送失败', 10006);
                }
            }
            if (config('ds_config.yly_client_id') && config('ds_config.yly_client_secret') && $this->store_info['yly_machine_code']) {
                //小票打印
                $order_info['extend_order_goods'] = $order_model->getOrdergoodsList(array('order_id' => $order_info['order_id']));
                $order_model->printO2oOrder($this->store_info, $order_info);
            }
            if (!$update) {
                throw new \think\Exception(lang('seller_order_edit_order_fail'), 10006);
            }
            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = 'seller';
            $data['log_user'] = session('seller_name');
            $data['log_msg'] = '店铺手动接单';
            $data['log_orderstate'] = ORDER_STATE_RECEIPT;
            $order_model->addOrderlog($data);

            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /*
     * 拒绝订单
     */

    public function reject_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('order_state', '=', ORDER_STATE_PAY);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition);
        if (empty($order_info)) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        Db::startTrans();
        try {
            model('order', 'logic')->changeOrderStateCancel($order_info, 'seller', session('seller_name'), '店铺拒绝订单',true);
            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /**
     * 卖家订单详情
     *
     */
    public function show_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods', 'member', 'orderlog'));
        if (empty($order_info)) {
            $this->error(lang('store_order_none_exist'));
        }

        $refundreturn_model = model('refundreturn');
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $refundreturn_model->getGoodsRefundList($order_list); //订单商品的退款退货显示
        $order_info = $order_list[$order_id];

        //显示锁定中
        $order_info['if_order_refund_lock'] = $order_model->getOrderOperateState('order_refund_lock', $order_info);

        //显示调整运费
        $order_info['if_modify_price'] = $order_model->getOrderOperateState('modify_price', $order_info);

        //显示调整价格
        $order_info['if_spay_price'] = $order_model->getOrderOperateState('spay_price', $order_info);

        //显示取消订单
        $order_info['if_cancel'] = $order_model->getOrderOperateState('store_cancel', $order_info);

        //显示接单
        $order_info['if_receipt'] = $order_model->getOrderOperateState('receipt', $order_info);

        //显示派单
        $order_info['if_deliver'] = $order_model->getOrderOperateState('deliver', $order_info);

        //显示取货
        $order_info['if_pickup'] = (input('param.o2o_order_pickup_code') == $order_info['o2o_order_pickup_code']) ? $order_model->getOrderOperateState('pickup', $order_info) : false;

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + config('ds_config.order_auto_cancel_day') * 24 * 3600;
        }



        //显示系统自动收获时间
        if ($order_info['order_state'] == ORDER_STATE_SEND) {
            $order_info['order_confirm_day'] = $order_info['delay_time'] + config('ds_config.order_auto_receive_day') * 24 * 3600;
        }


        foreach ($order_info['extend_order_goods'] as $value) {
            $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
            $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
            $value['goods_url'] = url('Goods/index', ['goods_id' => $value['goods_id']]);
            if ($value['goods_type'] == 5) {
                $order_info['zengpin_list'][] = $value;
            } else {
                $order_info['goods_list'][] = $value;
            }
        }

        if (empty($order_info['zengpin_list'])) {
            $order_info['goods_count'] = count($order_info['goods_list']);
        } else {
            $order_info['goods_count'] = count($order_info['goods_list']) + 1;
        }

        View::assign('order_info', $order_info);

        //发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            $daddress_info = model('daddress')->getAddressInfo(array('daddress_id' => $order_info['extend_order_common']['daddress_id']));
            View::assign('daddress_info', $daddress_info);
        }
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerorder');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem();
        return View::fetch($this->template_dir . 'show_order');
    }

    /**
     * 卖家订单状态操作
     *
     */
    public function change_state() {
        $state_type = input('param.state_type');
        $order_id = intval(input('param.order_id'));

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition);
        if ($state_type == 'order_cancel') {
            $result = $this->_order_cancel($order_info, input('post.'));
        } elseif ($state_type == 'modify_price') {
            $result = $this->_order_ship_price($order_info, input('post.'));
        } elseif ($state_type == 'spay_price') {
            $result = $this->_order_spay_price($order_info, input('post.'));
        }
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        } else {
            ds_json_encode(10000, $result['msg']);
        }
    }

    /*
     * 订单取货
     */

    public function pickup_order() {
        $order_model = model('order');
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }


        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('order_state', '=', ORDER_STATE_DELIVER);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition);
        if (empty($order_info)) {
            ds_json_encode(10001, lang('store_order_none_exist'));
        }
        Db::startTrans();
        try {
            $update_order = array();
            $o2o_order_receive_code = makeO2oOrderReceiveCode();
            if ($o2o_order_receive_code === false) {
                throw new \think\Exception(lang('seller_order_make_receive_code_fail'), 10006);
            }
            $update_order['o2o_order_receive_code'] = $o2o_order_receive_code;

            $update_order['order_state'] = ORDER_STATE_SEND;
            $update_order['o2o_order_pickup_time'] = TIMESTAMP;
            $update = $order_model->editOrder($update_order, array(
                'order_id' => $order_info['order_id'], 'order_state' => ORDER_STATE_DELIVER, 'refund_state' => 0, 'order_refund_lock_state' => 0,
            ));
            if (!$update) {
                throw new \think\Exception(lang('seller_order_edit_order_fail'), 10006);
            }
            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = 'seller';
            $data['log_user'] = session('seller_name');
            $data['log_msg'] = '店铺确认配送员取货';
            $data['log_orderstate'] = ORDER_STATE_SEND;
            $order_model->addOrderlog($data);
            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        $order_info = array_merge($order_info, $update_order);
        $order_info = $order_model->formatO2oOrder($this->store_info, $order_info);
        ds_json_encode(10000, lang('ds_common_op_succ'), array('order_info' => $order_info));
    }
    
    /**
     * 查看订单
     */
    public function print_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods'));
        if (empty($order_info)) {
            $this->error(lang('member_printorder_ordererror'));
        }
        View::assign('order_info', $order_info);

        //卖家信息
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID($order_info['store_id']);
        if (!empty($store_info['store_logo'])) {
            $store_info['store_avatar'] = ds_get_pic( ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id'],$store_info['store_avatar']);
        }
        if (!empty($store_info['store_seal'])) {
            $store_info['store_seal'] = ds_get_pic( ATTACH_STORE , $store_info['store_seal']);
        }
        View::assign('store_info', $store_info);

        //订单商品
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $goods_new_list = array();
        $goods_all_num = 0;
        $goods_total_price = 0;
        if (isset($order_info['extend_order_goods']) && !empty($order_info['extend_order_goods'])) {
            $i = 1;
            foreach ($order_info['extend_order_goods'] as $k => $v) {
                $v['goods_name'] = str_cut($v['goods_name'], 100);
                $goods_all_num += $v['goods_num'];
                $v['goods_all_price'] = ds_price_format($v['goods_num'] * $v['goods_price']);
                $goods_total_price += $v['goods_all_price'];
                $goods_new_list[ceil($i / 15)][$i] = $v;
                $i++;
            }
        }
        //优惠金额
        $promotion_amount = $goods_total_price - $order_info['goods_amount'];
        //运费
        $order_info['shipping_fee'] = $order_info['shipping_fee'];
        View::assign('promotion_amount', $promotion_amount);
        View::assign('goods_all_num', $goods_all_num);
        View::assign('goods_total_price', ds_price_format($goods_total_price));
        View::assign('goods_list', $goods_new_list);
        
        return View::fetch($this->template_dir.'print_order');
    }

    /**
     * 取消订单
     * @param unknown $order_info
     */
    private function _order_cancel($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');

        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'cancel');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('store_cancel', $order_info);
            if (!$if_allow) {
                return ds_callback(false, '无权操作');
            }
            $msg = $post['state_info1'] != '' ? $post['state_info1'] : $post['state_info'];
            try{
                Db::startTrans();
                $logic_order->changeOrderStateCancel($order_info, 'seller', session('member_name'), $msg);
            } catch (\Exception $e) {
                Db::rollback();
                return ds_callback(false, $e->getMessage());
            }
            Db::commit();    
            return ds_callback(true, lang('ds_common_op_succ'));
        }
    }

    /**
     * 修改运费
     * @param unknown $order_info
     */
    private function _order_ship_price($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');
        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'edit_price');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('modify_price', $order_info);
            if (!$if_allow) {
                return ds_callback(false, '无权操作');
            }
            return $logic_order->changeOrderShipPrice($order_info, 'seller', session('member_name'), $post['shipping_fee']);
        }
    }

    /**
     * 修改商品价格
     * @param unknown $order_info
     */
    private function _order_spay_price($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');
        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'edit_spay_price');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('spay_price', $order_info);
            if (!$if_allow) {
                return ds_callback(false, '无权操作');
            }
            return $logic_order->changeOrderSpayPrice($order_info, 'seller', session('member_name'), $post['goods_amount']);
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'store_order',
                'text' => lang('ds_member_path_all_order'),
                'url' => url('Sellerorder/index')
            ),
            array(
                'name' => 'state_new',
                'text' => lang('ds_member_path_wait_pay'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_new'])
            ),
            array(
                'name' => 'state_pay',
                'text' => lang('ds_member_path_wait_send'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_pay'])
            ),
            array(
                'name' => 'state_pick',
                'text' => lang('ds_member_path_pick'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_pick'])
            ),
            array(
                'name' => 'state_send',
                'text' => lang('ds_member_path_sent'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_send'])
            ),
            array(
                'name' => 'state_success',
                'text' => lang('ds_member_path_finished'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_success'])
            ),
            array(
                'name' => 'state_cancel',
                'text' => lang('ds_member_path_canceled'),
                'url' => url('Sellerorder/index', ['state_type' => 'state_cancel'])
            ),
        );
        return $menu_array;
    }

}

?>
