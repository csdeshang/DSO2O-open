<?php

namespace app\admin\controller;
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
class  Order extends AdminControl {

    const EXPORT_SIZE = 1000;

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/order.lang.php');
    }

    public function index() {
        $order_model = model('order');
        $condition = array();

        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[]=array('order_sn','=',$order_sn);
        }
        $store_name = input('param.store_name');
        if ($store_name) {
            $condition[]=array('store_name','=',$store_name);
        }
        $order_state = input('param.order_state');
        if (in_array($order_state, array('0', '10', '20', '30', '40'))) {
            $condition[]=array('order_state','=',$order_state);
        }
        $payment_code = input('param.payment_code');
        if ($payment_code) {
            $condition[]=array('payment_code','=',$payment_code);
        }
        $buyer_name = input('param.buyer_name');
        if ($buyer_name) {
            $condition[]=array('buyer_name','=',$buyer_name);
        }
        $query_start_time = input('param.query_start_time');
        $query_end_time = input('param.query_end_time');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_time);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_time);
        $start_unixtime = $if_start_time ? strtotime($query_start_time) : null;
        $end_unixtime = $if_end_time ? strtotime($query_end_time) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time','>=',$start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('add_time','<=',$end_unixtime);
        }
        $order_list = $order_model->getOrderList($condition, 10);
        View::assign('show_page', $order_model->page_info->render());
        
        $order_group_list=array();
        foreach ($order_list as $order_id => $order_info) {
            //显示取消订单
            $order_list[$order_id]['if_cancel'] = $order_model->getOrderOperateState('system_cancel', $order_info);
            //显示收到货款
            $order_list[$order_id]['if_system_receive_pay'] = $order_model->getOrderOperateState('system_receive_pay', $order_info);
            $order_group_list[$order_info['pay_sn']]['order_list'][]=$order_list[$order_id];
            //如果有在线支付且未付款的订单则显示合并付款链接
            if (!isset($order_group_list[$order_info['pay_sn']]['pay_amount'])) {
                $order_group_list[$order_info['pay_sn']]['pay_amount'] = 0;
            }
            if ($order_info['order_state'] == ORDER_STATE_NEW) {
                $order_group_list[$order_info['pay_sn']]['pay_amount'] += $order_info['order_amount'] - $order_info['pd_amount'] - $order_info['rcb_amount'];
            }
        }
        //显示支付接口列表(搜索)
        $payment_list = model('payment')->getPaymentOpenList();
        View::assign('payment_list', $payment_list);
        View::assign('order_group_list', $order_group_list);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        $this->setAdminCurItem('add');
        return View::fetch('index');
    }

    /**
     * 平台订单状态操作
     *
     */
    public function change_state() {
        $state_type = input('param.state_type');
        if ($state_type == 'cancel') {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('miss_order_number'));
        }
        $order_model = model('order');

        //获取订单详细
        $condition = array();
        $condition[]=array('order_id','=',$order_id);
        $order_info = $order_model->getOrderInfo($condition);

        
            $result = $this->_order_cancel($order_info);
            if (!$result['code']) {
                $this->error($result['msg']);
            } else {
                ds_json_encode(10000, $result['msg']);
            }
        } elseif ($state_type == 'receive_pay') {
            $result = $this->_order_receive_pay(input('param.'));
            if (!$result['code']) {
                $this->error($result['msg']);
            } else {
                dsLayerOpenSuccess($result['msg'],'Order/index');
            }
        }
    }

    /**
     * 系统取消订单
     */
    private function _order_cancel($order_info) {
        $order_id = $order_info['order_id'];
        $order_model = model('order');
        $logic_order = model('order','logic');
        $if_allow = $order_model->getOrderOperateState('system_cancel', $order_info);
        if (!$if_allow) {
            return ds_callback(false, '无权操作');
        }
        try{
            Db::startTrans();
            $logic_order->changeOrderStateCancel($order_info, 'system', $this->admin_info['admin_name']);
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        $this->log(lang('order_log_cancel') . ',' . lang('order_number') . ':' . $order_info['order_sn'], 1);
        return ds_callback(true, lang('ds_common_op_succ'));
    }

    /**
     * 系统收到货款
     * @throws Exception
     */
    private function _order_receive_pay($post) {
        $order_model = model('order');
        $logic_order = model('order','logic');
        $pay_sn=$post['pay_sn'];
        $pay_info = $order_model->getOrderpayInfo(array('pay_sn' => $pay_sn));
        if (empty($pay_info)) {
            return ds_callback(false, lang('no_right_operate'));
        }
        
        //取子订单列表
        $condition = array();
        $condition[]=array('pay_sn','=',$pay_sn);
        $condition[]=array('order_state','in',array_values(array(ORDER_STATE_NEW, ORDER_STATE_PAY)));
        $order_list = $order_model->getOrderList($condition);
        if (empty($order_list)) {
            return ds_callback(false, lang('no_right_operate'));
        }
        //重新计算在线支付金额
        $pay_amount_online = 0;
        //订单总支付金额(不包含货到付款)
        $pay_amount = 0;
        $order_sn_list = array();
        foreach($order_list as $order_info){
            $if_allow = $order_model->getOrderOperateState('system_receive_pay', $order_info);
            if (!$if_allow) {
                return ds_callback(false, lang('no_right_operate'));
            }
            $payed_amount = floatval($order_info['rcb_amount']) + floatval($order_info['pd_amount']);
            //计算相关支付金额
            if ($order_info['payment_code'] != 'offline') {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount_online += ds_price_format(floatval($order_info['order_amount']) - $payed_amount);
                }
                $pay_amount += floatval($order_info['order_amount']);
            }
            $order_sn_list[]=$order_info['order_sn'];
        }


        if (!request()->isPost()) {
            View::assign('order_sn_list', implode('`', $order_sn_list));
            View::assign('pay_amount_online', ds_price_format($pay_amount_online));
            View::assign('pay_amount', ds_price_format($pay_amount));
            //显示支付接口列表
            $payment_list = model('payment')->getPaymentOpenList();
            //去掉预存款和货到付款
            foreach ($payment_list as $key => $value) {
                if ($value['payment_code'] == 'predeposit' || $value['payment_code'] == 'offline') {
                    unset($payment_list[$key]);
                }
            }
            View::assign('payment_list', $payment_list);
            echo View::fetch('receive_pay');
            exit;
        } else {
            $order_list = $order_model->getOrderList(array('pay_sn' => $pay_sn, 'order_state' => ORDER_STATE_NEW));
            try{
                Db::startTrans();
                $logic_order->changeOrderReceivePay($order_list, 'system', $this->admin_info['admin_name'], $post);
            } catch (\Exception $e) {
                Db::rollback();
                return ds_callback(false, $e->getMessage());
            }
            Db::commit();    
            $this->log('将订单改为已收款状态,' . lang('ds_order_sn') . ':' . implode('`', $order_sn_list), 1);
            return ds_callback(true, lang('ds_common_op_succ'));
        }
    }

    /**
     * 查看订单
     *
     */
    public function show_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('miss_order_number'));
        }
        $order_model = model('order');
        $order_info = $order_model->getOrderInfo(array('order_id' => $order_id), array('order_goods', 'order_common', 'store','orderlog'));


        //退款退货信息
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[]=array('order_id','=',$order_info['order_id']);
        $condition[]=array('refundreturn_seller_state','=',2);
        $condition[] = array('refundreturn_admin_time','>', 0);
        $return_list = $refundreturn_model->getReturnList($condition);
        View::assign('return_list', $return_list);

        //退款信息
        $refund_list = $refundreturn_model->getRefundList($condition);
        View::assign('refund_list', $refund_list);

        //卖家发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            $daddress_info = model('daddress')->getAddressInfo(array('daddress_id' => $order_info['extend_order_common']['daddress_id']));
            View::assign('daddress_info', $daddress_info);
        }
        View::assign('order_info', $order_info);
        return View::fetch('show_order');
    }

    /**
     * 导出
     *
     */
    public function export_step1() {

        $order_model = model('order');
        $condition = array();
        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[]=array('order_sn','=',$order_sn);
        }
        $store_name = input('param.store_name');
        if ($store_name) {
            $condition[]=array('store_name','=',$store_name);
        }
        $order_state = input('param.order_state');
        if (in_array($order_state, array('0', '10', '20', '30', '40'))) {
            $condition[]=array('order_state','=',$order_state);
        }
        $payment_code = input('param.payment_code');
        if ($payment_code) {
            $condition[]=array('payment_code','=',$payment_code);
        }
        $buyer_name = input('param.buyer_name');
        if ($buyer_name) {
            $condition[]=array('buyer_name','=',$buyer_name);
        }
        $query_start_time = input('param.query_start_time');
        $query_end_time = input('param.query_end_time');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_time);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_time);
        $start_unixtime = $if_start_time ? strtotime($query_start_time) : null;
        $end_unixtime = $if_end_time ? strtotime($query_end_time) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[]=array('add_time','between',array($start_unixtime, $end_unixtime));
        }

        if (!is_numeric(input('param.page'))) {
            $count = $order_model->getOrderCount($condition);
            $export_list = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $export_list[$i] = $limit1 . ' ~ ' . $limit2;
                }
                View::assign('export_list', $export_list);
                return View::fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $order_model->getOrderList($condition, 0, '*', 'order_id desc', self::EXPORT_SIZE);
                $this->createExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.page') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $order_model->getOrderList($condition, $limit2, '*', 'order_id desc');
            $this->createExcel($data);
        }
    }

    /**
     * 生成excel
     *
     * @param array $data
     */
    private function createExcel($data = array()) {
        Lang::load(base_path() .'admin/lang/'.config('lang.default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_no'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_store'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_buyer'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_xtimd'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_count'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_yfei'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_paytype'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_state'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_storeid'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_buyerid'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_od_bemail'));
        //data
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => 'DS' . $v['order_sn']);
            $tmp[] = array('data' => $v['store_name']);
            $tmp[] = array('data' => $v['buyer_name']);
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['add_time']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['order_amount']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['shipping_fee']));
            $tmp[] = array('data' => get_order_payment_name($v['payment_code']));
            $tmp[] = array('data' => get_order_state($v));
            $tmp[] = array('data' => $v['store_id']);
            $tmp[] = array('data' => $v['buyer_id']);
            $tmp[] = array('data' => $v['buyer_email']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_od_order'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_od_order'), CHARSET) . input('param.page') . '-' . date('Y-m-d-H', TIMESTAMP));
    }

}
