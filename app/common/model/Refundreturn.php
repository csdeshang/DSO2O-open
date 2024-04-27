<?php

namespace app\common\model;

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
 * 数据层模型
 */
class Refundreturn extends BaseModel {

    public $page_info;

    /**
     * 增加退款退货
     * @access public
     * @author csdeshang
     * @param type $refund_array 退款数组
     * @param type $order 排序
     * @param type $goods 商品数组
     * @return type
     */
    public function addRefundreturn($refund_array, $order = array(), $goods = array()) {
        if (!empty($order) && is_array($order)) {
            $refund_array['order_id'] = $order['order_id'];
            $refund_array['order_sn'] = $order['order_sn'];
            $refund_array['store_id'] = $order['store_id'];
            $refund_array['store_name'] = $order['store_name'];
            $refund_array['buyer_id'] = $order['buyer_id'];
            $refund_array['buyer_name'] = $order['buyer_name'];
        }
        if (!empty($goods) && is_array($goods)) {
            $refund_array['goods_id'] = $goods['goods_id'];
            $refund_array['order_goods_id'] = $goods['rec_id'];
            $refund_array['goods_name'] = $goods['goods_name'];
            $refund_array['commis_rate'] = $goods['commis_rate'];
            $refund_array['goods_image'] = $goods['goods_image'];
        }
        $refund_array['refund_sn'] = $this->getRefundsn($refund_array['store_id']);
        $refund_id = Db::name('refundreturn')->insertGetId($refund_array);

        // 发送商家提醒
        $message = array();
        if (intval($refund_array['refund_type']) == 1) {    // 退款
            $message['code'] = 'refund';
        } else {    // 退货
            $message['code'] = 'return';
        }
        $message['store_id'] = $order['store_id'];
        $message['ali_param'] = array(
            'refund_sn' => $refund_array['refund_sn']
        );
        $message['ten_param'] = array(
            $refund_array['refund_sn']
        );
        $message['param'] = $message['ali_param'];
        //微信模板消息
        $message['weixin_param'] = array(
            'url' => config('ds_config.h5_store_site_url') . '/pages/seller/refund/RefundForm?refund_id=' . $refund_id . '&refund_type=' . $refund_array['refund_type'],
            'data' => array(
                "keyword1" => array(
                    "value" => $refund_array['order_sn'],
                    "color" => "#333"
                ),
                "keyword2" => array(
                    "value" => $refund_array['refund_amount'],
                    "color" => "#333"
                )
            ),
        );
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize($message)));
        return $refund_id;
    }

    /**
     * 订单锁定
     * @access public
     * @author csdeshang
     * @param type $order_id 订单编号
     * @return boolean
     */
    public function editOrderLock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition[] = array('order_id', '=', $order_id);
            $data = array();
            $data['order_refund_lock_state'] = Db::raw('order_refund_lock_state+1');
            $order_model = model('order');
            $result = $order_model->editOrder($data, $condition);
            return $result;
        }
        return false;
    }

    /**
     * 订单解锁
     * @access public
     * @author csdeshang
     * @param type $order_id 订单编号
     * @return boolean
     */
    public function editOrderUnlock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition[] = array('order_id', '=', $order_id);
            $condition[] = array('order_refund_lock_state', '>=', '1');
            $data = array();
            $data['order_refund_lock_state'] = Db::raw('order_refund_lock_state-1');
            $data['delay_time'] = TIMESTAMP;
            $order_model = model('order');
            $result = $order_model->editOrder($data, $condition);
            return $result;
        }
        return false;
    }

    /**
     * 
     * 修改记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return boolean
     */
    public function editRefundreturn($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = Db::name('refundreturn')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 退款
     * @access public
     * @author csdeshang
     * @param type $refund 退款
     * @return boolean
     */
    public function refundAmount($order, $refund_amount) {
        $order_model = model('order');
        //生成out_request_no 支付宝部分退款必传唯一的标识一次退款请求号
        $order['out_request_no'] = $order['order_sn'];

        if(!isset($order['total_order_amount'])){
            //订单总金额，单次支付包含多个店铺订单的总金额（针对于微信退款原路返回需要传订单总金额）
            $order['total_order_amount'] = 0;
            $order_array = $order_model->getOrderList(array('trade_no' => $order['trade_no']));
            foreach ($order_array as $key => $value) {
                $order['total_order_amount'] += $order_model->getOrderInfo(array('order_id' => $value['order_id']), array(), 'order_amount')['order_amount'];
            }
        }


        $order_amount = $order['order_amount']; //订单金额
        $rcb_amount = $order['rcb_amount']; //充值卡支付金额
        $pd_amount = $order['pd_amount']; //预存款支付金额

        $predeposit_amount = $order_amount - $order['refund_amount'] - $rcb_amount; //可退预存款金额（预存款+在线支付金额） 在线支付可能原路返还

        $predeposit_model = model('predeposit');

        $not_trade_refund = TRUE; //在线支付 不原路返还
        $alipay_payment_list = array('alipay', 'alipay_app', 'alipay_h5');
        $wxpay_payment_list = array('wxpay_app', 'wxpay_h5', 'wxpay_jsapi', 'wxpay_minipro', 'wxpay_native');

        //未使用预存款支付 以及  充值卡支付的订单 才支持订单原路返还。
        if ($predeposit_amount > 0 && (in_array($order['payment_code'], $alipay_payment_list) || in_array($order['payment_code'], $wxpay_payment_list)) && $rcb_amount == 0 && $pd_amount == 0) {
            if (in_array($order['payment_code'], $alipay_payment_list)) {
                $payment_code = 'alipay';
            }
            if (in_array($order['payment_code'], $wxpay_payment_list)) {
                $payment_code = 'wxpay_native';
            }
            //调用支付接口处理原路退款
            $logic_payment = model('payment', 'logic');
            $result = $logic_payment->getPaymentInfo($payment_code);
            if (!$result['code']) {
                throw new \think\Exception($payment_code . '支付方法未开启', 10006);
            }
            $main_payment_info = $payment_info = $result['data'];

            if($payment_code == 'alipay' && !isset($payment_info['payment_config']['alipay_trade_refund_state'])){
                throw new \think\Exception($payment_code . '请配置支付宝支付', 10006);
            }
            if($payment_code == 'wxpay_native' && !isset($payment_info['payment_config']['wx_trade_refund_state'])){
                throw new \think\Exception($payment_code . '请配置微信支付', 10006);
            }
            //支付宝/微信 未开启原路返回
            if (($payment_code == 'alipay' && $payment_info['payment_config']['alipay_trade_refund_state'] == 1) || ($payment_code == 'wxpay_native' && $payment_info['payment_config']['wx_trade_refund_state'] == 1)) {
                $result = $logic_payment->getPaymentInfo($order['payment_code']);
                if (!$result['code']) {
                    throw new \think\Exception($order['payment_code'] . '支付方法未开启', 10006);
                }
                $payment_info = $result['data'];
                //原路返还金额
                $trade_refund_amount = $refund_amount; //退预存款金额
                if ($refund_amount > $predeposit_amount) {
                    $trade_refund_amount = $predeposit_amount;
                }
                $payment_info['payment_config']=array_merge($main_payment_info['payment_config'],$payment_info['payment_config']);
                $payment_api = new $payment_code($payment_info);
                $result = $payment_api->trade_refund($order, $trade_refund_amount);
                if (!$result['code']) {
                    throw new \think\Exception($result['msg'], 10006);
                }
                $not_trade_refund = FALSE;
            }
        }



        if (($rcb_amount > 0) && ($refund_amount > $predeposit_amount) && $not_trade_refund) {//退充值卡
            $log_array = array();
            $log_array['member_id'] = $order['buyer_id'];
            $log_array['member_name'] = $order['buyer_name'];
            $log_array['order_sn'] = $order['order_sn'];
            $log_array['amount'] = $refund_amount;
            if ($predeposit_amount > 0) {
                $log_array['amount'] = $refund_amount - $predeposit_amount;
            }
            $state = $predeposit_model->changeRcb('refund', $log_array); //增加买家可用充值卡金额
            if (!$state) {
                throw new \think\Exception('充值卡退回失败', 10006);
            }
        }


        //全部退回预存款
        if ($predeposit_amount > 0 && $not_trade_refund) {
            $log_array = array();
            $log_array['member_id'] = $order['buyer_id'];
            $log_array['member_name'] = $order['buyer_name'];
            $log_array['order_sn'] = $order['order_sn'];
            $log_array['amount'] = $refund_amount; //退预存款金额
            if ($refund_amount > $predeposit_amount) {
                $log_array['amount'] = $predeposit_amount;
            }
            $state = $predeposit_model->changePd('refund', $log_array); //增加买家可用预存款金额
            if (!$state) {
                throw new \think\Exception('预存款退回失败', 10006);
            }
        }
    }

    /**
     * 平台确认退款处理
     * @access public
     * @author csdeshang
     * @param type $refund 退款
     * @return boolean
     */
    public function editOrderRefund($refund) {
        $refund_id = intval($refund['refund_id']);
        if ($refund_id > 0) {
            $order_id = $refund['order_id']; //订单编号
            $field = '*';
            $order_model = model('order');

            $order_model->lock = true;
            $order = $order_model->getOrderInfo(array('order_id' => $order_id), array(), $field);

            $logic_order = model('order', 'logic');
            //同意退款之后,订单状态自动设置为已完成 ,  成交的金额减去退款的金额, 交易成功后,买家次月产生的其他退款，由再下月进行结算
            if ($order['order_state'] != ORDER_STATE_SUCCESS) {
                $result = $logic_order->changeOrderStateReceive($order, 'system', '系统', '用户申请退款,商品自动收货');
            }


            try {
                Db::startTrans();

                //对店铺资金进行扣款
                $logic_order = model('order', 'logic');
                $logic_order->balanceOrderStateRefundreturn($order,$refund);
                

                $this->refundAmount($order, $refund['refund_amount']);
                $goods_list = $order_model->getOrdergoodsList(array('order_id' => $order_id));



                    $order_array = array();
                    $order_amount = $order['order_amount']; //订单金额
                    $refund_amount = $order['refund_amount'] + $refund['refund_amount']; //退款金额
                    $order_array['refund_state'] = ($order_amount - $refund_amount) > 0 ? 1 : 2;
                    $order_array['refund_amount'] = ds_price_format($refund_amount);
                    $order_array['delay_time'] = TIMESTAMP;
                    $state = $order_model->editOrder($order_array, array('order_id' => $order_id)); //更新订单退款
                    if (!$state) {
                        throw new \think\Exception('订单修改失败', 10006);
                    }
                    
                    $state = $this->editOrderUnlock($order_id); //订单解锁
                    if (!$state) {
                        throw new \think\Exception('订单解锁失败', 10006);
                    }

                //全额退款或单个商品退款  修改退款的资金处理
                $state = $this->editRefundreturn(array('refund_id'=>$refund_id),array('refundreturn_money_state'=>1));
                if (!$state) {
                    throw new \think\Exception('refundreturn_money_state状态修改失败', 10006);
                }
                    
                Db::commit();
                return ds_callback(true);
            } catch (\Exception $e) {
                Db::rollback();
                return ds_callback(false, $e->getMessage());
            }
        }
        return ds_callback(false, '参数错误');
    }

    /**
     * 增加退款退货原因
     * @access public
     * @author csdeshang
     * @param type $reason_array 原因数组
     * @return type
     */
    public function addReason($reason_array) {
        $reason_id = Db::name('refundreason')->insertGetId($reason_array);
        return $reason_id;
    }

    /**
     * 修改退款退货原因记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return boolean
     */
    public function editReason($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = Db::name('refundreason')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 删除退款退货原因记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return boolean
     */
    public function delReason($condition) {
        if (empty($condition)) {
            return false;
        } else {
            $result = Db::name('refundreason')->where($condition)->delete();
            return $result;
        }
    }

    /**
     * 退款退货原因记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $limit 限制
     * @param type $fields 字段
     * @return array
     */
    public function getReasonList($condition = array(), $pagesize = '', $limit = 0, $fields = '*') {
        if ($pagesize) {
            $result_paginate = Db::name('refundreason')->field($fields)->where($condition)->order('reason_sort asc,reason_id desc')->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result_paginate;
            $result = $result_paginate->items();
        } else {
            $result = Db::name('refundreason')->field($fields)->where($condition)->order('reason_sort asc,reason_id desc')->select()->toArray();
        }
        $result = ds_change_arraykey($result, 'reason_id');
        return $result;
    }

    /**
     * 获取退款退货记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $fields 字段
     * @param type $limit 限制
     * @return type
     */
    public function getRefundreturnList($condition = array(), $pagesize = '', $field = '*', $order = 'refund_id desc', $limit = 0) {
        if ($pagesize) {
            $result = Db::name('refundreturn')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            $result = $result->items();
        } else {
            $result = Db::name('refundreturn')->field($field)->where($condition)->order($order)->limit($limit)->select()->toArray();
        }
        
        foreach ($result as $key => $refundreturn) {
            $result[$key]['refundreturn_seller_state_desc'] = get_refundreturn_seller_state($refundreturn['refundreturn_seller_state']);
            $result[$key]['refundreturn_admin_state_desc'] = get_refundreturn_admin_state($refundreturn['refundreturn_admin_state']);
        }

        return $result;
    }

    /**
     * 取退款记录列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param type $pagesize 分页
     * @return type
     */
    public function getRefundList($condition = array(), $pagesize = '', $field = '*', $order = 'refund_id desc', $limit = 0) {
        $condition[] = array('refund_type', '=', '1'); //类型:1为退款,2为退货
        $result = $this->getRefundreturnList($condition, $pagesize, $field, $order, $limit);
        return $result;
    }

    /**
     * 取退货记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param type $pagesize 分页
     * @return type
     */
    public function getReturnList($condition = array(), $pagesize = '', $field = '*', $order = 'refund_id desc', $limit = 0) {
        $condition[] = array('refund_type', '=', '2'); //类型:1为退款,2为退货
        $result = $this->getRefundreturnList($condition, $pagesize, $field, $order, $limit);
        return $result;
    }

    /**
     * 退款退货申请编号
     * @access public
     * @author csdeshang
     * @param type $store_id 店铺id
     * @return string
     */
    public function getRefundsn($store_id) {
        $result = mt_rand(100, 999) . substr(100 + $store_id, -3) . date('ymdHis');
        return $result;
    }

    /**
     * 取一条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $fields 字段
     * @return type
     */
    public function getRefundreturnInfo($condition = array(), $fields = '*') {
        $result = Db::name('refundreturn')->where($condition)->field($fields)->find();
        if(!empty($result)){
            $result['refundreturn_seller_state_desc'] = get_refundreturn_seller_state($result['refundreturn_seller_state']);
            $result['refundreturn_admin_state_desc'] = get_refundreturn_admin_state($result['refundreturn_admin_state']);
        }
        return $result;
    }

    /**
     * 根据订单取商品的退款退货状态
     * @access public
     * @author csdeshang
     * @param type $order_list 订单列表
     * @param type $order_refund 退款订单
     * @return string
     */
    public function getGoodsRefundList($order_list = array()) {
        $order_ids = array(); //订单编号数组
        $order_ids = array_keys($order_list);
        $condition = array();
        $condition[] = array('order_id', 'in', $order_ids);
        $refund_list = Db::name('refundreturn')->where($condition)->order('refund_id desc')->select()->toArray();
        $refund_goods = array(); //已经提交的退款退货商品
        if (!empty($refund_list) && is_array($refund_list)) {
            foreach ($refund_list as $key => $value) {
                $order_id = $value['order_id']; //订单编号
                $goods_id = $value['order_goods_id']; //订单商品表编号
                if (empty($refund_goods[$order_id][$goods_id])) {
                    $refund_goods[$order_id][$goods_id] = $value;
                }
            }
        }
        if (!empty($order_list) && is_array($order_list)) {
            foreach ($order_list as $key => $value) {
                $order_id = $key;
                $goods_list = $value['extend_order_goods']; //订单商品
                $order_state = $value['order_state']; //订单状态
                $payment_code = $value['payment_code']; //支付方式
                if (!empty($refund_goods[$order_id][0])) {
                    //全额退款信息
                    $order_list[$order_id]['extend_order_refund'] = $refund_goods[$order_id][0];
                }
                if ($order_state == ORDER_STATE_PAY && $payment_code != 'offline') {
                    //已付款未发货的非货到付款订单可以申请取消
                    
                    // getOrderOperateState 判断是否可以进行订单全额退款
                    if (empty($refund_goods[$order_id])) {
                        $order_list[$order_id]['if_allow_order_refund'] = '1';
                    }else{
                        $order_list[$order_id]['if_allow_order_refund'] = '0';
                    }
                    
                } elseif ($order_state > ORDER_STATE_PAY && !empty($goods_list) && is_array($goods_list)) {
                    //只有已发货的商品,才能对单个商品进行退款退货
                    //根据订单状态判断是否可以退款退货
                    $if_allow_goods_refund = $this->getOrderAllowRefundState($value); 
                    foreach ($goods_list as $k => $v) {
                        $goods_id = $v['rec_id']; //订单商品表编号
                        if ($v['goods_pay_price'] > 0) {//实际支付额大于0的可以退款
                            $v['if_allow_goods_refund'] = $if_allow_goods_refund;
                        }
                        if (!empty($refund_goods[$order_id][$goods_id])) {
                            //已经存在处理中或同意的商品不能再进行退款
                            $v['if_allow_goods_refund'] = '0'; 
                            //单个商品退款信息
                            $v['extend_order_goods_refund'] = $refund_goods[$order_id][$goods_id];
                        } elseif (!empty($refund_goods[$order_id][0])) {
                            //如果有订单全额退款,则订单下的商品都不能申请退款
                            $v['if_allow_goods_refund'] = '0';
                        }
                        
                        $goods_list[$k] = $v;
                    }
                }
                $order_list[$order_id]['extend_order_goods'] = $goods_list;
            }
        }

        return $order_list;
    }


    /**
     * 详细页右侧订单信息
     * @access public
     * @author csdeshang
     * @param type $order_condition 条件
     * @param type $order_goods_id 订单商品id
     * @return type
     */
    public function getRightOrderList($order_condition, $order_goods_id = 0) {
        $order_model = model('order');
        $order_info = $order_model->getOrderInfo($order_condition, array('order_common', 'store'));

        $order_id = $order_info['order_id'];
        $order_common = $order_info['extend_order_common'];
        $order_info['order_common'] = $order_common;



        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        if ($order_goods_id > 0) {
            $condition[] = array('rec_id', '=', $order_goods_id); //订单商品表编号
        }
        $goods_list = $order_model->getOrdergoodsList($condition);
        $order_info['goods_list'] = $goods_list;

        return $order_info;
    }

    /**
     * 根据订单状态判断是否可以退款退货
     * @access public
     * @author csdeshang
     * @param type $order 订单
     * @return bool
     */
    public function getOrderAllowRefundState($order) {
        $refund = '0'; //默认不允许退款退货
        $order_state = $order['order_state']; //订单状态
        $trade_model = model('trade');
        switch ($order_state) {
            case ORDER_STATE_RECEIPT:
            case ORDER_STATE_DELIVER:
            case ORDER_STATE_SEND:
                $payment_code = $order['payment_code']; //支付方式
                if ($payment_code != 'offline') {//货到付款订单在没确认收货前不能退款退货
                    $refund = '1';
                } else {
                    $refund = '0';
                }
                break;
            case ORDER_STATE_SUCCESS:
                $order_refund = $trade_model->getMaxDay('order_refund'); //15:收货完成后可以申请退款退货
                $delay_time = $order['delay_time'] + 60 * 60 * 24 * $order_refund;
                if ($delay_time > TIMESTAMP) {
                    $refund = '1';
                } else {
                    $refund = '0';
                }
                break;
            default:
                $refund = '0';
                break;
        }

        return $refund;
    }

    /**
     * 退货退款数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getRefundreturnCount($condition) {
        return Db::name('refundreturn')->where($condition)->count();
    }

    /**
     * 取得退款数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return type
     */
    public function getRefundCount($condition) {
        $condition[] = array('refund_type', '=', 1);
        return Db::name('refundreturn')->where($condition)->count();
    }

    /**
     * 取得退款退货数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getReturnCount($condition) {
        $condition[] = array('refund_type', '=', 2);
        return Db::name('refundreturn')->where($condition)->count();
    }

    /**
     * 获得退货退款的店铺列表
     * @access public
     * @author csdeshang
     * @param type $list 店铺列表
     * @return array
     */
    public function getRefundStoreList($list) {
        $store_ids = array();
        if (!empty($list) && is_array($list)) {
            foreach ($list as $key => $value) {
                $store_ids[] = $value['store_id']; //店铺编号
            }
        }
        $field = 'store_id,store_name,member_id,member_name,store_qq,store_ww,store_phone';
        return model('store')->getStoreMemberIDList($store_ids, $field);
    }

}

?>
