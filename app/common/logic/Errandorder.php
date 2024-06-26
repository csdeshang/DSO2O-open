<?php

namespace app\common\logic;

use think\facade\Db;
use app\common\model\Storemoneylog;
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
class Errandorder {
    
    
    /*
     * 获取订单按钮
     * @access public
     * @author csdeshang  
     * @param array $val 订单数据
     * @param string $operator 操作人员 member用户 admin管理员 distributor配送员
     * @return arrau
     */

    public function getO2oErrandOrderBtn($val, $operator) {

        if ($operator == 'admin') {
            $data = array(
                'if_cancel' => false,
                'if_pay' => false,
                'if_deliver' => false,
            );
            if ($val['o2o_errand_order_state'] == ORDER_STATE_PAY) {
                $data['if_deliver'] = true;
            }
            if ($val['o2o_errand_order_state'] != ORDER_STATE_CANCEL && $val['o2o_errand_order_state'] != ORDER_STATE_SUCCESS) {
                $data['if_cancel'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_NEW) {
                $data['if_pay'] = true;
            }
        } else if ($operator == 'member') {
            $data = array(
                'if_cancel' => false,
                'if_receive' => false,
                'if_pay' => false,
                'if_code' => false,
                'if_complaint' => false,
                'if_evaluate' => false,
            );
            if ($val['o2o_errand_order_state'] == ORDER_STATE_NEW) {
                $data['if_cancel'] = true;
                $data['if_pay'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_PAY) {
                $data['if_cancel'] = true;
            }

            if ($val['o2o_errand_order_state'] == ORDER_STATE_SEND) {
                $data['if_receive'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_SEND && $val['o2o_errand_order_check_receive'] == 1) {
                $data['if_code'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_SUCCESS && !model('o2o_complaint')->getO2oComplaintInfo(array('o2o_complaint_order_type' => 1, 'order_id' => $val['o2o_errand_order_id']))) {
                $data['if_complaint'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_SUCCESS && !$val['o2o_errand_order_if_evaluate']) {
                $data['if_evaluate'] = true;
            }
        } else if ($operator == 'distributor') {
            $data = array(
                'if_pickup' => false,
                'if_deliver' => false,
                'if_receive' => false,
            );
            if ($val['o2o_errand_order_state'] == ORDER_STATE_PAY) {
                $data['if_pickup'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_DELIVER) {
                $data['if_deliver'] = true;
            }
            if ($val['o2o_errand_order_state'] == ORDER_STATE_SEND && $val['o2o_errand_order_check_receive'] == 1) {
                $data['if_receive'] = true;
            }
        }

        return $data;
    }
    
    
    /*
     * 取消跑腿订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param string $operator 操作人员 member用户 admin管理员 system系统
     * @return type
     */

    public function cancelO2oErrandOrder($condition, $operator) {
        Db::startTrans();
        try {

            $o2o_errand_order_info = Db::name('o2o_errand_order')->where($condition)->lock(true)->find();
            if (!$o2o_errand_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($operator == 'member') {
                if ($o2o_errand_order_info['o2o_errand_order_state'] != ORDER_STATE_NEW && $o2o_errand_order_info['o2o_errand_order_state'] != ORDER_STATE_PAY) {
                    throw new \think\Exception('该订单不可取消', 10006);
                }
            } else {
                if ($o2o_errand_order_info['o2o_errand_order_state'] == ORDER_STATE_CANCEL || $o2o_errand_order_info['o2o_errand_order_state'] == ORDER_STATE_SUCCESS) {
                    throw new \think\Exception('该订单不可取消', 10006);
                }
            }
            if ($o2o_errand_order_info['o2o_errand_order_state'] == ORDER_STATE_NEW) {
                $predeposit_model = model('predeposit');
                //解冻充值卡
                $rcb_amount = floatval($o2o_errand_order_info['o2o_errand_order_rcb_amount']);
                if ($rcb_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $o2o_errand_order_info['member_id'];
                    $data_pd['member_name'] = $o2o_errand_order_info['member_name'];
                    $data_pd['amount'] = $rcb_amount;
                    $data_pd['order_sn'] = $o2o_errand_order_info['o2o_errand_order_sn'];
                    $predeposit_model->changeRcb('order_cancel', $data_pd);
                }
                //当是已下单,未支付(可能包含部分款项使用预存款,预存款在冻结资金),则退还预存款,取消订单
                $pd_amount = floatval($o2o_errand_order_info['o2o_errand_order_pd_amount']);
                if ($pd_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $o2o_errand_order_info['member_id'];
                    $data_pd['member_name'] = $o2o_errand_order_info['member_name'];
                    $data_pd['amount'] = $pd_amount;
                    $data_pd['order_sn'] = $o2o_errand_order_info['o2o_errand_order_sn'];
                    $predeposit_model->changePd('order_cancel', $data_pd);
                }
            }
            if ($o2o_errand_order_info['o2o_errand_order_payment_time']) {//已付款则退还费用
                $order = array(
                    'order_sn' => $o2o_errand_order_info['o2o_errand_order_sn'],
                    'trade_no' => $o2o_errand_order_info['o2o_errand_order_payment_sn'],
                    'order_amount' => $o2o_errand_order_info['o2o_errand_order_amount'],
                    'total_order_amount' => $o2o_errand_order_info['o2o_errand_order_amount'],
                    'payment_code'=>$o2o_errand_order_info['o2o_errand_order_payment_code'],
                    'buyer_id'=>$o2o_errand_order_info['member_id'],
                    'buyer_name'=>$o2o_errand_order_info['member_name'],
                    'refund_amount' => 0,
                    'rcb_amount' => 0,
                    'pd_amount' => 0,
                );
                $refundreturn_model = model('refundreturn');
                $refundreturn_model->refundAmount($order, $o2o_errand_order_info['o2o_errand_order_amount']);
            }
            
            $o2o_errand_order_model = model('o2o_errand_order');
            $result = $o2o_errand_order_model->editO2oErrandOrder(array('o2o_errand_order_state' => ORDER_STATE_CANCEL), array('o2o_errand_order_id' => $o2o_errand_order_info['o2o_errand_order_id']));
            if (!$result) {
                throw new \think\Exception('订单更新失败', 10006);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }
    
    
    //跑腿订单 确认收货
    public function receiveO2oErrandOrder($errand_order_info,$role, $user = ''){
        
        Db::startTrans();
        try {
            $o2o_errand_order_id = $errand_order_info['o2o_errand_order_id'];
            $o2o_errand_order_model = model('o2o_errand_order');
            $update_errand_order = array(
                'o2o_errand_order_state' => ORDER_STATE_SUCCESS,
                'o2o_errand_order_finish_time' => TIMESTAMP,
            );

            //修改订单状态
            $update = $o2o_errand_order_model->editO2oErrandOrder($update_errand_order, array('o2o_errand_order_id' => $o2o_errand_order_id));

            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }

            //给配送员结算
            //总费用  基础运费 o2o_errand_order_distance_price  重量附加费  o2o_errand_order_weight_price  特殊时段费  o2o_errand_order_time_price  小费 o2o_errand_order_gratuity
            $o2o_errand_order_amount = $errand_order_info['o2o_errand_order_amount'];
            
            //配送费为0 则 不对配送员的预存款进行操作及记录
            if ($o2o_errand_order_amount > 0) {
                $distributor_money = array(
                    'o2o_distributor_id' => $errand_order_info['o2o_distributor_id'],
                    'order_sn' => $errand_order_info['o2o_errand_order_sn'],
                    'amount' => $o2o_errand_order_amount,
                    'desc' => '[' . $role . ']',
                );
                $o2o_distributor_moneylog_model = model('o2o_distributor_moneylog');
                $o2o_distributor_moneylog_model->changeO2oDistributorMoney('order_errand', $distributor_money);
            }

            Db::commit();
            return ds_callback(true, '操作成功');
        } catch (Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        
    }
    
    
    /*
     * 评价跑腿订单
     * @access public
     * @author csdeshang  
     * @param int $member_id 用户id
     * @param int $o2o_errand_order_id 跑题订单id
     * @param string $score 评分
     * @param string $comment 评价
     * @return type
     */

    public function evaluateO2oErrandOrder($member_id, $o2o_errand_order_id, $score, $comment) {
        if ($score < 1 || $score > 5) {
            return ds_callback(false, '评分错误');
        }
        $order_model = model('o2o_errand_order');
        $condition = array();
        $condition[] = array('o2o_errand_order_id', '=', $o2o_errand_order_id);
        $condition[] = array('member_id', '=', $member_id);
        $o2o_errand_order_info = $order_model->getO2oErrandOrderInfo($condition);
        if (!$o2o_errand_order_info) {
            return ds_callback(false, '订单不存在');
        }
        if ($o2o_errand_order_info['o2o_errand_order_state'] != ORDER_STATE_SUCCESS || $o2o_errand_order_info['o2o_errand_order_if_evaluate']) {
            return ds_callback(false, '订单不可评');
        }
        Db::startTrans();
        try {
            $o2o_errand_order_model = model('o2o_errand_order');
            $result = $o2o_errand_order_model->editO2oErrandOrder(array('o2o_errand_order_if_evaluate' => 1, 'o2o_errand_order_evaluate_time' => TIMESTAMP, 'o2o_errand_order_evaluate_content' => $comment, 'o2o_errand_order_evaluate_score' => $score), $condition);
            if (!$result) {
                throw new \think\Exception('评论保存失败', 10006);
            }
            //更新服务机构评分
            $o2o_distributor_model = model('o2o_distributor');
            $o2o_distributor_info = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $o2o_errand_order_info['o2o_distributor_id']), 'o2o_distributor_id,o2o_distributor_score,o2o_distributor_comment_count');
            if ($o2o_distributor_info) {
                $count = $o2o_distributor_info['o2o_distributor_comment_count'] + 1;
                $score = round(($score + ($o2o_distributor_info['o2o_distributor_score'] * $o2o_distributor_info['o2o_distributor_comment_count'])) / $count, 2);
                if (!$o2o_distributor_model->editO2oDistributor(array('o2o_distributor_comment_count' => $count, 'o2o_distributor_score' => $score), array('o2o_distributor_id' => $o2o_distributor_info['o2o_distributor_id']))) {
                    throw new \think\Exception('评论更新失败', 10006);
                }
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }
    
    
    /*
     * 付款跑腿订单
     * @access public
     * @author csdeshang  
     * @param string $out_trade_no 订单号
     * @param string $payment_code 支付方式
     * @param string $trade_no 第三方支付单号
     * @param string $operator 操作人员 member用户 admin管理员 system系统
     * @param string $payment_time 支付时间
     * @return type
     */

    public function payO2oErrandOrder($out_trade_no, $payment_code, $trade_no, $operator, $payment_time = '') {
   

            $o2o_errand_order_info = Db::name('o2o_errand_order')->where(array('o2o_errand_order_sn' => $out_trade_no))->lock(true)->find();
            if (!$o2o_errand_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($o2o_errand_order_info['o2o_errand_order_state'] != ORDER_STATE_NEW) {
                throw new \think\Exception('该订单不可支付', 10006);
            }
            //支付被冻结的充值卡
            $predeposit_model = model('predeposit');
            if ($o2o_errand_order_info['o2o_errand_order_rcb_amount'] > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_errand_order_info['member_id'];
                $data_pd['member_name'] = $o2o_errand_order_info['member_name'];
                $data_pd['amount'] = $o2o_errand_order_info['o2o_errand_order_rcb_amount'];
                $data_pd['order_sn'] = $o2o_errand_order_info['o2o_errand_order_sn'];
                $predeposit_model->changeRcb('order_comb_pay', $data_pd);
            }
            //支付被冻结的预存款
            if ($o2o_errand_order_info['o2o_errand_order_pd_amount'] > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_errand_order_info['member_id'];
                $data_pd['member_name'] = $o2o_errand_order_info['member_name'];
                $data_pd['amount'] = $o2o_errand_order_info['o2o_errand_order_pd_amount'];
                $data_pd['order_sn'] = $o2o_errand_order_info['o2o_errand_order_sn'];
                $predeposit_model->changePd('order_comb_pay', $data_pd);
            }
            if ($payment_time) {
                $payment_time = strtotime($payment_time);
            } else {
                $payment_time = TIMESTAMP;
            }
            
            $o2o_errand_order_model = model('o2o_errand_order');
            $result = $o2o_errand_order_model->editO2oErrandOrder(array('o2o_errand_order_payment_code' => $payment_code, 'o2o_errand_order_payment_sn' => $trade_no, 'o2o_errand_order_state' => ORDER_STATE_PAY, 'o2o_errand_order_payment_time' => $payment_time), array('o2o_errand_order_id' => $o2o_errand_order_info['o2o_errand_order_id']));
            
            if (!$result) {
                throw new \think\Exception('订单更新失败', 10006);
            }

    }
    
    
    /**
     * 充值卡支付
     * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function rcbPay($order_info, $input, $buyer_info) {
        if ($order_info['o2o_errand_order_state'] == ORDER_STATE_PAY)
            return $order_info;
        $available_rcb_amount = floatval($buyer_info['available_rc_balance']);

        if ($available_rcb_amount <= 0)
            return $order_info;
        $predeposit_model = model('predeposit');

        $order_amount = round($order_info['o2o_errand_order_amount'] - $order_info['o2o_errand_order_rcb_amount'] - $order_info['o2o_errand_order_pd_amount'], 2);
        $data_pd = array();
        $data_pd['member_id'] = $buyer_info['member_id'];
        $data_pd['member_name'] = $buyer_info['member_name'];
        $data_pd['amount'] = $order_amount;
        $data_pd['order_sn'] = $order_info['o2o_errand_order_sn'];


        $o2o_errand_order_model = model('o2o_errand_order');
        if ($available_rcb_amount >= $order_amount) {
            $this->payO2oErrandOrder($order_info['o2o_errand_order_sn'], 'predeposit', '', 'member');
        } else {
            $data_pd['amount'] = $available_rcb_amount;
        }
            //暂冻结预存款,后面还需要 API彻底完成支付
            $predeposit_model->changeRcb('order_freeze', $data_pd);
            //预存款支付金额保存到订单
            $data_order = array();
            $order_info['o2o_errand_order_rcb_amount'] = $data_order['o2o_errand_order_rcb_amount'] = round($order_info['o2o_errand_order_rcb_amount'] + $data_pd['amount'], 2);
            $result = $o2o_errand_order_model->editO2oErrandOrder($data_order, array('o2o_errand_order_id' => $order_info['o2o_errand_order_id']));
            if (!$result) {
                throw new \think\Exception('订单更新失败', 10006);
            }
        return $order_info;
    }

    /**
     * 预存款支付 主要处理
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_info, $input, $buyer_info) {
        if ($order_info['o2o_errand_order_state'] == ORDER_STATE_PAY)
            return $order_info;

        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0)
            return $order_info;

        $predeposit_model = model('predeposit');

        $order_amount = round($order_info['o2o_errand_order_amount'] - $order_info['o2o_errand_order_rcb_amount'] - $order_info['o2o_errand_order_pd_amount'], 2);
        $data_pd = array();
        $data_pd['member_id'] = $buyer_info['member_id'];
        $data_pd['member_name'] = $buyer_info['member_name'];
        $data_pd['amount'] = $order_amount;
        $data_pd['order_sn'] = $order_info['o2o_errand_order_sn'];

        $o2o_errand_order_model = model('o2o_errand_order');
        if ($available_pd_amount >= $order_amount) {
            $this->payO2oErrandOrder($order_info['o2o_errand_order_sn'], 'predeposit', '', 'member');
        } else {
            //暂冻结预存款,后面还需要 API彻底完成支付
            $data_pd['amount'] = $available_pd_amount;
        }
        $predeposit_model->changePd('order_freeze', $data_pd);
        //预存款支付金额保存到订单
        $data_order = array();
        $order_info['o2o_errand_order_pd_amount'] = $data_order['o2o_errand_order_pd_amount'] = round($order_info['o2o_errand_order_pd_amount'] + $data_pd['amount'], 2);
        $result = $o2o_errand_order_model->editO2oErrandOrder($data_order, array('o2o_errand_order_id' => $order_info['o2o_errand_order_id']));
            if (!$result) {
                throw new \think\Exception('订单更新失败', 10006);
            }
        return $order_info;
    }
    
    
    
}