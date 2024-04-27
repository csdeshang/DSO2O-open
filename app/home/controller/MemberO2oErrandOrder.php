<?php

/*
 * 交易投诉
 */

namespace app\home\controller;
use think\facade\View;
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
class MemberO2oErrandOrder extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/member_o2o_errand_order.lang.php');
    }

    public function index() {
        /*
         * 得到当前用户的跑腿订单列表
         */
        $o2o_errand_order_model = model('o2o_errand_order');
        $logic_errand_order = model('errandorder','logic');
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $o2o_errand_order_state = input('param.o2o_errand_order_state');
        if ($o2o_errand_order_state !== null && $o2o_errand_order_state !== '') {
            $condition[] = array('o2o_errand_order_state','=',intval($o2o_errand_order_state));
        }
        $query_start_time = input('param.query_start_date');
        $query_end_time = input('param.query_end_date');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_time);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_time);
        $start_unixtime = $if_start_time ? strtotime($query_start_time) : null;
        $end_unixtime = $if_end_time ? strtotime($query_end_time) : null;
        if($start_unixtime || $end_unixtime){
            $condition[] = array('o2o_errand_order_add_time','between',array($start_unixtime?$start_unixtime:0, $end_unixtime?($end_unixtime+86399):TIMESTAMP));
        }
        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[] = array('o2o_errand_order_sn','=',$order_sn);
        }
        $o2o_errand_order_list = $o2o_errand_order_model->getO2oErrandOrderList($condition, '*', 20);
        foreach($o2o_errand_order_list as $key => $val){
            $o2o_errand_order_list[$key]['o2o_errand_order_state_text']=$o2o_errand_order_model->getO2oErrandOrderStateText($val['o2o_errand_order_state']);
            $btn_list=$logic_errand_order->getO2oErrandOrderBtn($val,'member');
            $o2o_errand_order_list[$key]=array_merge($o2o_errand_order_list[$key],$btn_list);
        }
        
        View::assign('order_list', $o2o_errand_order_list);
        View::assign('show_page', $o2o_errand_order_model->page_info->render());
        $this->setMemberCurMenu('member_o2o_errand_order');
        $this->setMemberCurItem('index');
        return View::fetch($this->template_dir.'index');
    }
    
    public function view() {

        $o2o_errand_order_model = model('o2o_errand_order');
        
        $o2o_errand_order_id = input('param.o2o_errand_order_id');
        if (!$o2o_errand_order_id) {
            $this->error(lang('param_error'));
        }
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $condition[] = array('o2o_errand_order_id','=',$o2o_errand_order_id);
        $o2o_errand_order_info = $o2o_errand_order_model->getO2oErrandOrderInfo($condition);
        if(!$o2o_errand_order_info){
            $this->error('订单不存在');
        }
        $o2o_errand_order_info['o2o_errand_order_state_text']=$o2o_errand_order_model->getO2oErrandOrderStateText($o2o_errand_order_info['o2o_errand_order_state']);
        View::assign('o2o_errand_order_info', $o2o_errand_order_info);
        $this->setMemberCurMenu('member_o2o_errand_order');
        $this->setMemberCurItem('view');
        return View::fetch($this->template_dir.'view');
    }
    
    /*
     * 新增我要送
     */

    public function send() {
        $address_model = model('address');
        //获取默认发件信息
        $address_send_info = $address_model->getAddressInfo(array('member_id' => $this->member_info['member_id'], 'address_o2o_errand_type' => 1), 'address_is_default desc');
        View::assign('address_send_info', $address_send_info);
        //获取默认收件信息
        $address_receive_info = $address_model->getAddressInfo(array('member_id' => $this->member_info['member_id'], 'address_o2o_errand_type' => 0), 'address_is_default desc');
        View::assign('address_receive_info', $address_receive_info);
        return View::fetch($this->template_dir . 'send');
    }
    
    /*
     * 新增我要买
     */

    public function buy() {
        $address_model = model('address');
        //获取默认收件信息
        $address_receive_info = $address_model->getAddressInfo(array('member_id' => $this->member_info['member_id'], 'address_o2o_errand_type' => 0), 'address_is_default desc');
        View::assign('address_receive_info', $address_receive_info);
        return View::fetch($this->template_dir . 'buy');
    }

    public function add() {
        $lng1 = floatval(input('param.o2o_errand_order_pickup_lng'));
        $lat1 = floatval(input('param.o2o_errand_order_pickup_lat'));
        $lng2 = floatval(input('param.o2o_errand_order_deliver_lng'));
        $lat2 = floatval(input('param.o2o_errand_order_deliver_lat'));
        //计算距离
        $distance = getDistance($lat1, $lng1, $lat2, $lng2, 2);
        //计算距离费用
        $distance_price_list = unserialize(config('ds_config.o2o_errand_distance_price'));
        if (!is_array($distance_price_list)) {
            ds_json_encode(10001, '基础运费配置错误');
        }
        $distance_price = false;
        $start_distance_price = 0;
        foreach ($distance_price_list as $item) {
            if ($item['if_fixed']) {
                $start_distance_price = $item['price'];
            }
            if ($item['start_distance'] <= $distance && $item['end_distance'] >= $distance) {
                if ($item['if_fixed']) {
                    $distance_price = $item['price'];
                } else {
                    $interval_distance = floatval($item['interval_distance']);
                    if ($interval_distance == 0) {
                        ds_json_encode(10001, '基础运费配置错误');
                    }
                    $distance_price = floatval($start_distance_price) + intval(($distance - $item['start_distance']) / $interval_distance) * $item['price'];
                }
            }
        }
        if ($distance_price === false) {
            ds_json_encode(10001, '超过最大可配送距离' . $item['end_distance'] . '公里');
        }
        $weight = intval(input('param.o2o_errand_order_weight'));
        //计算重量费用
        $weight_price_list = unserialize(config('ds_config.o2o_errand_weight_price'));
        if (!is_array($weight_price_list)) {
            ds_json_encode(10001, '重量附加费配置错误');
        }
        $weight_price = false;
        $start_weight_price = 0;
        foreach ($weight_price_list as $item) {
            if ($item['if_fixed']) {
                $start_weight_price = $item['price'];
            }
            if ($item['start_weight'] <= $weight && $item['end_weight'] >= $weight) {
                if ($item['if_fixed']) {
                    $weight_price = $item['price'];
                } else {
                    $interval_weight = floatval($item['interval_weight']);
                    if ($interval_weight == 0) {
                        ds_json_encode(10001, '基础运费配置错误');
                    }
                    $weight_price = floatval($start_weight_price) + intval(($weight - $item['start_weight']) / $interval_weight) * $item['price'];
                }
            }
        }
        if ($weight_price === false) {
            ds_json_encode(10001, '超过最大可配送重量' . $item['end_weight'] . '公斤');
        }
        //计算时间费用
        $o2o_errand_order_appointment_time = input('param.o2o_errand_order_appointment_time');
        if ($o2o_errand_order_appointment_time) {
            $time = $o2o_errand_order_appointment_time - strtotime(date('Y-m-d 0:0:0', $o2o_errand_order_appointment_time));
        } else {
            $time = TIMESTAMP - strtotime(date('Y-m-d 0:0:0'));
        }

        $time = $time / 60;
        $time_price_list = unserialize(config('ds_config.o2o_errand_time_price'));
        if (!is_array($time_price_list)) {
            ds_json_encode(10001, '特殊时段费配置错误');
        }
        $time_price = 0;

        foreach ($time_price_list as $item) {
            if ($item['start_time'] <= $time && $item['end_time'] >= $time) {
                $time_price = $item['price'];
            }
        }
        $gratuity = floatval(input('param.o2o_errand_order_gratuity'));
        //计算订单费用
        $order_amount = round($distance_price + $weight_price + $time_price + $gratuity, 2);

        $o2o_errand_order_model = model('o2o_errand_order');


        $data = array(
            'o2o_errand_order_type' => input('param.o2o_errand_order_type'),
            'o2o_errand_order_sn' => makePaySn($this->member_info['member_id']),
            'member_id' => $this->member_info['member_id'],
            'member_name' => $this->member_info['member_name'],
            'o2o_errand_order_add_time' => TIMESTAMP,
            'o2o_errand_order_appointment_time' => $o2o_errand_order_appointment_time,
            'o2o_errand_order_distance' => $distance,
            'o2o_errand_order_pickup_region_id' => input('param.o2o_errand_order_pickup_region_id'),
            'o2o_errand_order_pickup_name' => input('param.o2o_errand_order_pickup_name'),
            'o2o_errand_order_pickup_address' => input('param.o2o_errand_order_pickup_address'),
            'o2o_errand_order_pickup_phone' => input('param.o2o_errand_order_pickup_phone'),
            'o2o_errand_order_pickup_lng' => $lng1,
            'o2o_errand_order_pickup_lat' => $lat1,
            'o2o_errand_order_deliver_region_id' => input('param.o2o_errand_order_deliver_region_id'),
            'o2o_errand_order_deliver_name' => input('param.o2o_errand_order_deliver_name'),
            'o2o_errand_order_deliver_address' => input('param.o2o_errand_order_deliver_address'),
            'o2o_errand_order_deliver_phone' => input('param.o2o_errand_order_deliver_phone'),
            'o2o_errand_order_deliver_lng' => $lng2,
            'o2o_errand_order_deliver_lat' => $lat2,
            'o2o_errand_order_detail' => input('param.o2o_errand_order_detail'),
            'o2o_errand_order_state' => ORDER_STATE_NEW,
            'o2o_errand_order_amount' => $order_amount,
            'o2o_errand_order_distance_price' => $distance_price,
            'o2o_errand_order_weight_price' => $weight_price,
            'o2o_errand_order_time_price' => $time_price,
            'o2o_errand_order_goods_price' => input('param.o2o_errand_order_goods_price'),
            'o2o_errand_order_gratuity' => $gratuity,
            'o2o_errand_order_weight' => $weight,
            'o2o_errand_order_remark' => input('param.o2o_errand_order_remark'),
            'o2o_errand_order_check_receive' => input('param.o2o_errand_order_check_receive') ? 1 : 0,
        );

        $o2o_errand_order_validate = ds_validate('o2o_errand_order');
        if (!$o2o_errand_order_validate->scene('o2o_errand_order_add')->check($data)) {
            ds_json_encode(10001, $o2o_errand_order_validate->getError());
        }


        $order_id = $o2o_errand_order_model->addO2oErrandOrder($data);
        if (!$order_id) {
            ds_json_encode(10001, '生成订单失败');
        }
        ds_json_encode(10000, lang('ds_common_op_succ'), array('pay_sn' => $data['o2o_errand_order_sn']));
    }

    public function cancel(){
        
        $o2o_errand_order_id = input('param.o2o_errand_order_id');
        if (!$o2o_errand_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        
        $logic_errand_order = model('errandorder','logic');

        $result=$logic_errand_order->cancelO2oErrandOrder(array('o2o_errand_order_id'=>$o2o_errand_order_id,'member_id'=>$this->member_info['member_id']),'member');
        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
    
    public function receive() {
        $order_id = intval(input('param.o2o_errand_order_id'));
        if (!$order_id) {
            ds_json_encode(10001, 'Hacking Attempt');
        }

        $order_model = model('o2o_errand_order');
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $condition[] = array('o2o_errand_order_state','=',ORDER_STATE_SEND);
        $condition[] = array('o2o_errand_order_id','=',$order_id);
        $o2o_errand_order_info = $order_model->getO2oErrandOrderInfo($condition);
        if(!$o2o_errand_order_info){
            ds_json_encode(10001, '订单不存在');
        }
        
        $logic_errand_order = model('errandorder','logic');
        $result=$logic_errand_order->receiveO2oErrandOrder($o2o_errand_order_info,'member');

        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }else{
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }
    }
    public function evaluate() {
        $o2o_errand_order_id = input('param.o2o_errand_order_id');
        if (!$o2o_errand_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $order_model = model('o2o_errand_order');
        $condition = array();
        $condition[] = array('o2o_errand_order_id','=',$o2o_errand_order_id);
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $o2o_errand_order_info = $order_model->getO2oErrandOrderInfo($condition);
        if (!$o2o_errand_order_info) {
            ds_json_encode(10001, '订单不存在');
        }
        if ($o2o_errand_order_info['o2o_errand_order_state'] != ORDER_STATE_SUCCESS || $o2o_errand_order_info['o2o_errand_order_if_evaluate']) {
            ds_json_encode(10001, '订单不可评');
        }
        if (request()->isPost()) {
        $comment = input('param.comment');
        $score = intval(input('param.score'));
        
        $logic_errand_order = model('errandorder','logic');
        $result=$logic_errand_order->evaluateO2oErrandOrder($this->member_info['member_id'],$o2o_errand_order_id,$score,$comment);
        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            View::assign('o2o_errand_order_info',$o2o_errand_order_info);
            return View::fetch($this->template_dir . 'evaluate');
        }
    }
    /**
     *    栏目菜单
     */
    function getMemberItemList() {
        $item_list = array(
            array(
                'name' => 'index',
                'text' => lang('ds_member_path_order_list'),
                'url' => url('MemberO2oErrandOrder/index'),
            ),
        );
        if(request()->action()=='view'){
            $item_list[]=array(
                'name' => 'view',
                'text' => lang('ds_member_path_show_order'),
                'url' => 'javascript:void(0)',
            );
        }
        return $item_list;
        
    }
}

?>
