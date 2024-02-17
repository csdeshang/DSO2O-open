<?php

namespace app\common\logic;

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
 * 逻辑层模型
 */
class Order {

    /**
     * 取消订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param string $msg 操作备注
     * @param boolean $if_update_account 是否变更账户金额
     * @param boolean $if_queue 是否使用队列
     * @param boolean $if_pay 是否已经支付,已经支付则全部退回支付金额
     * @return array
     */
    public function changeOrderStateCancel($order_info, $role, $user = '', $msg = '', $if_update_account = true, $if_quque = true, $if_pay = false) {
        $order_model = model('order');
        if ($order_info['order_state'] != ORDER_STATE_CANCEL) {
            $order_id = $order_info['order_id'];

            //库存销量变更
            $goods_list = $order_model->getOrdergoodsList(array('order_id' => $order_id));
            $data = array();
            foreach ($goods_list as $goods) {
                $data[$goods['goods_id']] = $goods['goods_num'];
            }
            model('goods')->cancelOrderUpdateStorage($data);

            if ($if_update_account) {
                $predeposit_model = model('predeposit');


                //注意：当用户全额使用预存款进行支付,并不会冻结, 当用户使用部分预存款进行支付,支付的预存款则会冻结.也就是支付成功之后不会有冻结资金,当未支付成功,使用的预付款变为冻结资金。

                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    //解冻充值卡
                    $rcb_amount = floatval($order_info['rcb_amount']);
                    if ($rcb_amount > 0) {
                        $data_pd = array();
                        $data_pd['member_id'] = $order_info['buyer_id'];
                        $data_pd['member_name'] = $order_info['buyer_name'];
                        $data_pd['amount'] = $rcb_amount;
                        $data_pd['order_sn'] = $order_info['order_sn'];
                        $predeposit_model->changeRcb('order_cancel', $data_pd);
                    }
                    //当是已下单,未支付(可能包含部分款项使用预存款,预存款在冻结资金),则退还预存款,取消订单
                    $pd_amount = floatval($order_info['pd_amount']);
                    if ($pd_amount > 0) {
                        $data_pd = array();
                        $data_pd['member_id'] = $order_info['buyer_id'];
                        $data_pd['member_name'] = $order_info['buyer_name'];
                        $data_pd['amount'] = $pd_amount;
                        $data_pd['order_sn'] = $order_info['order_sn'];
                        $predeposit_model->changePd('order_cancel', $data_pd);
                    }
                }

                if ($order_info['order_state'] == ORDER_STATE_PAY && $order_info['payment_code'] != 'offline') {//offline为货到付款的订单，取消时不需要返回预存款
                    $refundreturn_model=model('refundreturn');
                    $refundreturn_model->refundAmount($order_info, $order_info['order_amount']);
                }
            }

            //更新订单信息
            $update_order = array('order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0);
            $update = $order_model->editOrder($update_order, array('order_id' => $order_id));
            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }
            //分销佣金取消
            $condition=array();
            $condition[]=array('orderinviter_order_id','=',$order_id);
            $condition[]=array('orderinviter_valid','=',0);
            $condition[]=array('orderinviter_order_type','=',0);
            Db::name('orderinviter')->where($condition)->update(['orderinviter_valid' => 2]);

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_msg'] = '取消了订单';
            $data['log_user'] = $user;
            if ($msg) {
                $data['log_msg'] .= ' ( ' . $msg . ' )';
            }
            $data['log_orderstate'] = ORDER_STATE_CANCEL;
            $order_model->addOrderlog($data);
        }
    }

    /**
     * 收货
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param string $msg 操作备注
     * @return array
     */
    public function changeOrderStateReceive($order_info, $role, $user = '', $msg = '') {
        try {
            $member_id = $order_info['buyer_id'];
            $order_id = $order_info['order_id'];
            $order_model = model('order');

            //更新订单状态
            $update_order = array();
            $update_order['finnshed_time'] = TIMESTAMP;
            $update_order['delay_time'] = TIMESTAMP;
            $update_order['order_state'] = ORDER_STATE_SUCCESS;
            $update = $order_model->editOrder($update_order, array('order_id' => $order_id));
            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }
            if ($order_info['o2o_distributor_id']) {
                //更新配送员数据
                $o2o_distributor_model = model('o2o_distributor');
                $o2o_distributor_info = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $order_info['o2o_distributor_id']));
                if ($o2o_distributor_info) {
                    $o2o_distributor_total_count = $o2o_distributor_info['o2o_distributor_total_count'] + 1;
                    if ($order_info['o2o_order_is_transfer']) {
                        $o2o_distributor_total_time = $o2o_distributor_info['o2o_distributor_total_time'] + round(($update_order['finnshed_time'] - $order_info['o2o_order_transfer_time']) / 60);
                    } else {
                        $o2o_distributor_total_time = $o2o_distributor_info['o2o_distributor_total_time'] + round(($update_order['finnshed_time'] - $order_info['o2o_order_deliver_time']) / 60);
                    }
                    $o2o_distributor_average_time = round($o2o_distributor_total_time / $o2o_distributor_total_count);
                    $o2o_distributor_model->editO2oDistributor(array('o2o_distributor_total_time' => $o2o_distributor_total_time, 'o2o_distributor_total_count' => $o2o_distributor_total_count, 'o2o_distributor_average_time' => $o2o_distributor_average_time), array('o2o_distributor_id' => $o2o_distributor_info['o2o_distributor_id']));
                }
                //更新店铺配送数据
                $store_model = model('store');
                $store_info = $store_model->getOneStore(array('store_id' => $order_info['store_id']), 'store_id,store_o2o_total_time,store_o2o_total_count,store_o2o_average_time');
                if ($store_info) {
                    $store_o2o_total_count = $store_info['store_o2o_total_count'] + 1;
                    $store_o2o_total_time = $store_info['store_o2o_total_time'] + round(($update_order['finnshed_time'] - $order_info['o2o_order_receipt_time']) / 60);
                    $store_o2o_average_time = round($store_o2o_total_time / $store_o2o_total_count);
                    $store_model->editStore(array('store_o2o_total_count' => $store_o2o_total_count, 'store_o2o_total_time' => $store_o2o_total_time, 'store_o2o_average_time' => $store_o2o_average_time), array('store_id' => $store_info['store_id']));
                }
            }
            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_msg'] = '签收了货物';
            $data['log_user'] = $user;
            if ($msg) {
                $data['log_msg'] .= ' ( ' . $msg . ' )';
            }
            $data['log_orderstate'] = ORDER_STATE_SUCCESS;
            $order_model->addOrderlog($data);

            //添加会员积分
            if (config('ds_config.points_isuse') == 1) {
                model('points')->savePointslog('order', array(
                    'pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'],
                    'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
                    'order_id' => $order_info['order_id']
                        ), true);
            }
            //添加会员经验值
            model('exppoints')->saveExppointslog('order', array(
                'explog_memberid' => $order_info['buyer_id'], 'explog_membername' => $order_info['buyer_name'],
                'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
                'order_id' => $order_info['order_id']
                    ), true);
            //邀请人获得返利积分
            $inviter_id = ds_getvalue_byname('member', 'member_id', $member_id, 'inviter_id');
            if (!empty($inviter_id)) {
                $inviter_name = ds_getvalue_byname('member', 'member_id', $inviter_id, 'member_name');
                $rebate_amount = ceil(0.01 * $order_info['order_amount'] * config('ds_config.points_rebate'));
                model('points')->savePointslog('rebate', array(
                    'pl_memberid' => $inviter_id,
                    'pl_membername' => $inviter_name,
                    'pl_points' => $rebate_amount
                        ), true);
            }

            return ds_callback(true, '操作成功');
        } catch (Exception $e) {
            return ds_callback(false, $e->getMessage());
        }
    }

    /**
     * 更改运费
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param float $price 运费
     * @return array
     */
    public function changeOrderShipPrice($order_info, $role, $user = '', $price) {
        try {

            $order_id = $order_info['order_id'];
            $order_model = model('order');

            $data = array();
            $data['shipping_fee'] = abs(floatval($price));
            $data['order_amount'] = Db::raw('goods_amount+' . $data['shipping_fee']);
            if (($order_info['rcb_amount'] + $order_info['pd_amount']) > ($data['shipping_fee'] + $order_info['goods_amount'])) {
                throw new \think\Exception('订单金额必须大于用户已支付金额', 10006);
            }
            $update = $order_model->editOrder($data, array('order_id' => $order_id));
            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }
            //记录订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '修改了运费' . '( ' . $price . ' )';
            ;
            $data['log_orderstate'] = $order_info['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $order_model->addOrderlog($data);
            return ds_callback(true, '操作成功');
        } catch (Exception $e) {
            return ds_callback(false, $e->getMessage());
        }
    }

    /**
     * 更改商品费用
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param float $price 运费
     * @return array
     */
    public function changeOrderSpayPrice($order_info, $role, $user = '', $price) {
        $order_model = model('order');
        Db::startTrans();
        try {

            $order_id = $order_info['order_id'];

            $data = array();
            $data['goods_amount'] = abs(floatval($price));
            $data['order_amount'] = Db::raw('shipping_fee+' . $data['goods_amount']);
            if (($order_info['rcb_amount'] + $order_info['pd_amount']) > ($order_info['shipping_fee'] + $data['goods_amount'])) {
                throw new \think\Exception('订单金额必须大于用户已支付金额', 10006);
            }
            $update = $order_model->editOrder($data, array('order_id' => $order_id));
            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }
            //修改商品费用
            if ($data['goods_amount'] > 0) {
                $ordergoods_list = $order_model->getOrdergoodsList(array('order_id' => $order_id));
                $diff_amount = $data['goods_amount'] - $order_info['goods_amount'];
                $i = 0;
                foreach ($ordergoods_list as $ordergoods) {
                    if ($i != (count($ordergoods_list) - 1)) {

                        if ($order_info['goods_amount'] > 0) {
                            $temp = $ordergoods['goods_pay_price'] / $order_info['goods_amount'] * $diff_amount;
                            $price = round($ordergoods['goods_pay_price'] + $temp, 2);
                        } else {
                            $price = round(1 / count($ordergoods_list) * $diff_amount, 2);
                            $temp = $price;
                        }

                        $diff_amount -= $temp;
                    } else {

                        $price = $ordergoods['goods_pay_price'] + $diff_amount;
                    }

                    $order_model->editOrdergoods(array('goods_pay_price' => $price), array('rec_id' => $ordergoods['rec_id']));
                    //修改分销佣金
                    $condition=array();
                    $condition[]=array('orderinviter_order_id','=',$order_id);
                    $condition[]=array('orderinviter_goods_id','=',$ordergoods['goods_id']);
                    $condition[]=array('orderinviter_valid','=',0);
                    $condition[]=array('orderinviter_order_type','=',0);
                    $orderinviter_list=Db::name('orderinviter')->where($condition)->select()->toArray();
                    foreach($orderinviter_list as $orderinviter_info){
                        $orderinviter_goods_amount=$price;
                        $orderinviter_money=round($orderinviter_info['orderinviter_ratio']/100*$orderinviter_goods_amount,2);
                        Db::name('orderinviter')->where(array(array('orderinviter_id','=',$orderinviter_info['orderinviter_id'])))->update(['orderinviter_goods_amount' => $orderinviter_goods_amount,'orderinviter_money'=>$orderinviter_money]);
                    }
                    $i++;
                }
            } else {
                $order_model->editOrdergoods(array('goods_pay_price' => 0), array('order_id' => $order_id));
                //修改分销佣金
                $condition=array();
                $condition[]=array('orderinviter_order_id','=',$order_id);
                $condition[]=array('orderinviter_valid','=',0);
                $condition[]=array('orderinviter_order_type','=',0);
                Db::name('orderinviter')->where($condition)->update(['orderinviter_goods_amount' => 0,'orderinviter_money'=>0]);
            }

            //记录订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '修改了商品费用' . '( ' . $price . ' )';
            ;
            $data['log_orderstate'] = $order_info['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $order_model->addOrderlog($data);
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }

    /**
     * 回收站操作（放入回收站、还原、永久删除）
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $state_type 操作类型
     * @return array
     */
    public function changeOrderStateRecycle($order_info, $role, $state_type) {
        $order_id = $order_info['order_id'];
        $order_model = model('order');
        //更新订单删除状态
        $state = str_replace(array('delete', 'drop', 'restore'), array(
            ORDER_DEL_STATE_DELETE, ORDER_DEL_STATE_DROP, ORDER_DEL_STATE_DEFAULT
                ), $state_type);
        $update = $order_model->editOrder(array('delete_state' => $state), array('order_id' => $order_id));
        if (!$update) {
            return ds_callback(false, '操作失败');
        } else {
            return ds_callback(true, '操作成功');
        }
    }

    /**
     * 收到货款
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @return array
     */
    public function changeOrderReceivePay($order_list, $role, $user = '', $post = array()) {
        $order_model = model('order');


        $data = array();
        $data['api_paystate'] = 1;

        $update = $order_model->editOrderpay($data, array('pay_sn' => $order_list[0]['pay_sn']));
        if (!$update) {
            throw new \think\Exception('更新支付单状态失败', 10006);
        }

        $predeposit_model = model('predeposit');
        foreach ($order_list as $key => $order_info) {
            $order_id = $order_info['order_id'];
            if ($order_info['order_state'] != ORDER_STATE_NEW)
                continue;
            //下单，支付被冻结的充值卡
            $rcb_amount = floatval($order_info['rcb_amount']);
            if ($rcb_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $rcb_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $predeposit_model->changeRcb('order_comb_pay', $data_pd);
            }

            //下单，支付被冻结的预存款
            $pd_amount = floatval($order_info['pd_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $predeposit_model->changePd('order_comb_pay', $data_pd);
            }
        }

        //更新订单状态
        $update_order = array();
        $update_order['order_state'] = ORDER_STATE_PAY;
        $update_order['payment_time'] = isset($post['payment_time']) ? strtotime($post['payment_time']) : TIMESTAMP;
        $update_order['payment_code'] = isset($post['payment_code'])?$post['payment_code']:'';
        $update_order['trade_no'] = isset($post['trade_no'])?$post['trade_no']:'';
        $update = $order_model->editOrder($update_order, array(
            'pay_sn' => $order_info['pay_sn'], 'order_state' => ORDER_STATE_NEW
        ));
        if (!$update) {
            throw new \think\Exception('操作失败', 10006);
        }


        foreach ($order_list as $order_info) {
            //防止重复发送消息
            if ($order_info['order_state'] != ORDER_STATE_NEW)
                continue;
            $order_id = $order_info['order_id'];
            $order_goods = $order_model->getOrdergoodsList(array('order_id' => $order_info['order_id']));
            // 支付成功发送买家消息
            $param = array();
            $param['code'] = 'order_payment_success';
            $param['member_id'] = $order_info['buyer_id'];
            //阿里短信参数
            $param['ali_param'] = array(
                'order_sn' => $order_info['order_sn'],
            );
            $param['ten_param'] = array(
                $order_info['order_sn'],
            );
            $param['param'] = array_merge($param['ali_param'], array(
                'order_url' => HOME_SITE_URL .'/Memberorder/show_order?order_id='.$order_info['order_id']
            ));
            //微信模板消息
            $param['weixin_param'] = array(
                'url' => config('ds_config.h5_site_url') . '/pages/member/order/OrderDetail?order_id=' . $order_info['order_id'],
                'data' => array(
                    "keyword1" => array(
                        "value" => $order_info['order_sn'],
                        "color" => "#333"
                    ),
                    "keyword2" => array(
                        "value" => $order_goods[0]['goods_name'] . (count($order_goods) > 1 ? sprintf(lang('order_goods_more_than_one'), count($order_goods)) : ''),
                        "color" => "#333"
                    ),
                    "keyword3" => array(
                        "value" => $order_info['order_amount'],
                        "color" => "#333"
                    ),
                    "keyword4" => array(
                        "value" => date('Y-m-d H:i', $order_info['add_time']),
                        "color" => "#333"
                    )
                ),
            );
            model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));

            // 支付成功发送店铺消息
            $param = array();
            $param['code'] = 'new_order';
            $param['store_id'] = $order_info['store_id'];
            $param['ali_param'] = array(
                'order_sn' => $order_info['order_sn']
            );
            $param['ten_param'] = array(
                $order_info['order_sn'],
            );
            $param['param'] = $param['ali_param'];

            $param['weixin_param'] = array(
                'url' => config('ds_config.h5_store_site_url') . '/pages/seller/order/OrderDetail?order_id=' . $order_info['order_id'],
                'data' => array(
                    "keyword1" => array(
                        "value" => $order_info['order_sn'],
                        "color" => "#333"
                    ),
                    "keyword2" => array(
                        "value" => $order_goods[0]['goods_name'] . (count($order_goods) > 1 ? sprintf(lang('order_goods_more_than_one'), count($order_goods)) : ''),
                        "color" => "#333"
                    ),
                    "keyword3" => array(
                        "value" => $order_info['order_amount'],
                        "color" => "#333"
                    ),
                    "keyword4" => array(
                        "value" => date('Y-m-d H:i', $order_info['add_time']),
                        "color" => "#333"
                    )
                ),
            );
            model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize($param)));

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '收到了货款 '.(isset($post['trade_no'])?('( 支付平台交易号 : ' . $post['trade_no'] . ' )'):'');
            $data['log_orderstate'] = ORDER_STATE_PAY;
            $order_model->addOrderlog($data);
        }
    }

}
