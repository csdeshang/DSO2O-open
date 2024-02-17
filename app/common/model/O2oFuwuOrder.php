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
class  O2oFuwuOrder extends BaseModel {

    public $page_info;

    /**
     * 取得服务订单列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oFuwuOrderList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_fuwu_order_id desc', $limit = 0) {
        if ($pagesize) {
            $result = Db::name('o2o_fuwu_order')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('o2o_fuwu_order')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
    }

    /**
     * 取得服务订单单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oFuwuOrderInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_fuwu_order')->where($condition)->field($fields)->find();
    }

    /**
     * 添加服务订单
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oFuwuOrder($data, $member_info) {
        if (!isset($data['address_id'])) {
            return ds_callback(false, '未选择地址');
        }
        if (!isset($data['spec_quantity_list'])) {
            return ds_callback(false, '服务项目错误');
        }
        $data['spec_quantity_list']= htmlspecialchars_decode($data['spec_quantity_list']);
        $data['address_id'] = intval($data['address_id']);
        $data['spec_quantity_list'] = json_decode($data['spec_quantity_list'], true);
        if (!is_array($data['spec_quantity_list']) || empty($data['spec_quantity_list'])) {
            return ds_callback(false, '服务项目错误');
        }
        try {
            Db::startTrans();
            //获取服务商品
            $o2o_fuwu_goods = model('o2o_fuwu_goods');
            $o2o_fuwu_goods_info = $o2o_fuwu_goods->getO2oFuwuGoodsInfo(array('o2o_fuwu_goods_id' => $data['o2o_fuwu_goods_id'], 'o2o_fuwu_goods_state' => 1));
            if (!$o2o_fuwu_goods_info) {
                throw new \think\Exception('服务不存在', 10006);
            }
            $address_model = model('address');
            $address_info = $address_model->getAddressInfo(array('member_id' => $member_info['member_id'], 'address_id' => $data['address_id']));
            if (!$address_info) {
                throw new \think\Exception('地址不存在', 10006);
            }
            $data['o2o_fuwu_order_appointment_time'] = strtotime($data['o2o_fuwu_order_appointment_time']);
            if (!$data['o2o_fuwu_order_appointment_time']) {
                throw new \think\Exception('预约时间错误', 10006);
            }
            //生成服务订单
            $o2o_fuwu_order_data = array(
                'o2o_fuwu_order_sn' => makePaySn($member_info['member_id']),
                'o2o_fuwu_organization_id' => $o2o_fuwu_goods_info['o2o_fuwu_organization_id'],
                'o2o_fuwu_organization_name' => $o2o_fuwu_goods_info['o2o_fuwu_organization_name'],
                'member_id' => $member_info['member_id'],
                'member_name' => $member_info['member_name'],
                'o2o_fuwu_order_state' => O2O_FUWU_ORDER_STATE_NEW,
                'o2o_fuwu_order_add_time' => TIMESTAMP,
                'o2o_fuwu_order_appointment_time' => $data['o2o_fuwu_order_appointment_time'],
                'o2o_fuwu_order_employer_name' => $address_info['address_realname'],
                'o2o_fuwu_order_employer_address' => $address_info['address_detail'],
                'o2o_fuwu_order_employer_phone' => $address_info['address_mob_phone'],
                'o2o_fuwu_order_employer_lat' => $address_info['address_latitude'],
                'o2o_fuwu_order_employer_lng' => $address_info['address_longitude'],
                'o2o_fuwu_goods_id' => $o2o_fuwu_goods_info['o2o_fuwu_goods_id'],
                'o2o_fuwu_goods_name' => $o2o_fuwu_goods_info['o2o_fuwu_goods_name'],
                'o2o_fuwu_order_remark' => $data['o2o_fuwu_order_remark'],
            );
            $o2o_fuwu_order_id = Db::name('o2o_fuwu_order')->insertGetId($o2o_fuwu_order_data);
            if (!$o2o_fuwu_order_id) {
                throw new \think\Exception('服务订单生成失败', 10006);
            }

            $o2o_fuwu_order_log_data = array();
            $o2o_fuwu_order_log_data['o2o_fuwu_order_id'] = $o2o_fuwu_order_id;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_role'] = 'buyer';
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_user'] = $member_info['member_name'];
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_time'] = TIMESTAMP;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_msg'] = '新增订单';
            $insert = model('o2o_fuwu_order_log')->addO2oFuwuOrderLog($o2o_fuwu_order_log_data);
            if (!$insert) {
                throw new \think\Exception('记录订单日志出现错误', 10006);
            }

            $o2o_fuwu_order_goods_spec_data = array();
            $o2o_fuwu_order_default_quantity = 0;
            $o2o_fuwu_order_default_amount = 0;
            $o2o_fuwu_order_added_amount = 0;
            $o2o_fuwu_goods_spec_id = 0;
            $o2o_fuwu_goods_spec_name = '';
            $o2o_fuwu_goods_spec_price = 0;
            //服务定增值服务
            $o2o_fuwu_goods_spec_model = model('o2o_fuwu_goods_spec');
            for ($i = 0; $i < count($data['spec_quantity_list']); $i++) {
                $spec = $data['spec_quantity_list'][$i];
                if ($i == 0) {
                    if ($spec['quantity'] < 1) {
                        throw new \think\Exception('默认服务项目数量错误', 10006);
                    }
                    $default_spec_info = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecInfo(array('o2o_fuwu_goods_spec_type' => 0, 'o2o_fuwu_goods_id' => $o2o_fuwu_goods_info['o2o_fuwu_goods_id'], 'o2o_fuwu_goods_spec_id' => $spec['o2o_fuwu_goods_spec_id'],));
                    if (!$default_spec_info) {
                        throw new \think\Exception('默认服务项目不存在', 10006);
                    }
                    $o2o_fuwu_goods_spec_id = $spec['o2o_fuwu_goods_spec_id'];
                    $o2o_fuwu_goods_spec_name = $spec['o2o_fuwu_goods_spec_name'];
                    $o2o_fuwu_goods_spec_price = $spec['o2o_fuwu_goods_spec_price'];
                    $o2o_fuwu_order_default_quantity = $spec['quantity'];
                    $o2o_fuwu_order_default_amount = bcmul($default_spec_info['o2o_fuwu_goods_spec_price'], $spec['quantity'], 2);
                } else {
                    if ($spec['quantity'] > 0) {
                        $added_spec_info = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecInfo(array('o2o_fuwu_goods_spec_type' => 1, 'o2o_fuwu_goods_id' => $o2o_fuwu_goods_info['o2o_fuwu_goods_id'], 'o2o_fuwu_goods_spec_id' => $spec['o2o_fuwu_goods_spec_id'],));
                        if (!$added_spec_info) {
                            throw new \think\Exception('增值服务项目不存在', 10006);
                        }
                        $o2o_fuwu_order_goods_spec_data[] = array(
                            'o2o_fuwu_order_id' => $o2o_fuwu_order_id,
                            'o2o_fuwu_goods_spec_id' => $added_spec_info['o2o_fuwu_goods_spec_id'],
                            'o2o_fuwu_goods_spec_name' => $added_spec_info['o2o_fuwu_goods_spec_name'],
                            'o2o_fuwu_goods_spec_price' => $added_spec_info['o2o_fuwu_goods_spec_price'],
                            'o2o_fuwu_order_goods_spec_quantity' => $spec['quantity'],
                        );
                        $o2o_fuwu_order_added_amount = bcadd($o2o_fuwu_order_added_amount, $added_spec_info['o2o_fuwu_goods_spec_price'] * $spec['quantity'], 2);
                    }
                }
            }
            if (!empty($o2o_fuwu_order_goods_spec_data) && !Db::name('o2o_fuwu_order_goods_spec')->insertAll($o2o_fuwu_order_goods_spec_data)) {
                throw new \think\Exception('订单增值服务生成失败', 10006);
            }
            $o2o_fuwu_order_amount = bcadd($o2o_fuwu_order_default_amount, $o2o_fuwu_order_added_amount, 2);
            $o2o_fuwu_order_edit_data = array('o2o_fuwu_order_if_added' => !empty($o2o_fuwu_order_goods_spec_data) ? 1 : 0, 'o2o_fuwu_goods_spec_id' => $o2o_fuwu_goods_spec_id, 'o2o_fuwu_goods_spec_name' => $o2o_fuwu_goods_spec_name, 'o2o_fuwu_goods_spec_price' => $o2o_fuwu_goods_spec_price, 'o2o_fuwu_order_default_quantity' => $o2o_fuwu_order_default_quantity, 'o2o_fuwu_order_default_amount' => $o2o_fuwu_order_default_amount, 'o2o_fuwu_order_added_amount' => $o2o_fuwu_order_added_amount, 'o2o_fuwu_order_amount' => $o2o_fuwu_order_amount);
            if (!$this->editO2oFuwuOrder($o2o_fuwu_order_edit_data, array('o2o_fuwu_order_id' => $o2o_fuwu_order_id))) {
                throw new \think\Exception('服务订单更新失败', 10006);
            }
            $o2o_fuwu_order_data = array_merge($o2o_fuwu_order_data, $o2o_fuwu_order_edit_data, array('o2o_fuwu_order_id' => $o2o_fuwu_order_id));

            Db::commit();
            return ds_callback(true, '', array('o2o_fuwu_order_sn' => $o2o_fuwu_order_data['o2o_fuwu_order_sn']));
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
    }

    /**
     * 充值卡支付
     * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function rcbPay($order_info, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

        $available_rcb_amount = floatval($buyer_info['available_rc_balance']);
        if ($available_rcb_amount <= 0)
            return $order_info;

        $predeposit_model = model('predeposit');

        if ($order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_NEW)
            return $order_info;
        $order_amount = round($order_info['o2o_fuwu_order_amount'] - $order_info['o2o_fuwu_order_rcb_amount'] - $order_info['o2o_fuwu_order_pd_amount'], 2);
        $data_pd = array();
        $data_pd['member_id'] = $member_id;
        $data_pd['member_name'] = $member_name;
        $data_pd['amount'] = $order_amount;
        $data_pd['order_sn'] = $order_info['o2o_fuwu_order_sn'];

        if ($available_rcb_amount >= $order_amount) {
            $this->payO2oFuwuOrder($order_info['o2o_fuwu_order_sn'], 'predeposit', '充值卡', '', 'buyer', $member_name);
        } else {
            //暂冻结充值卡,后面还需要 API彻底完成支付
                $data_pd['amount'] = $available_rcb_amount;
        }
        $predeposit_model->changeRcb('order_freeze', $data_pd);
        //支付金额保存到订单
        $data_order = array();
        $data_order['o2o_fuwu_order_rcb_amount'] = $order_info['o2o_fuwu_order_rcb_amount'] = round($order_info['o2o_fuwu_order_rcb_amount'] + $data_pd['amount'], 2);
        $result = $this->editO2oFuwuOrder($data_order, array('o2o_fuwu_order_id' => $order_info['o2o_fuwu_order_id']));
        if (!$result) {
            throw new \think\Exception('订单更新失败', 10006);
        }
        return $order_info;
    }

    /**
     * 预存款支付
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_info, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0)
            return $order_info;

        $predeposit_model = model('predeposit');

        if ($order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_NEW)
            return $order_info;
        $order_amount = round($order_info['o2o_fuwu_order_amount'] - $order_info['o2o_fuwu_order_rcb_amount'] - $order_info['o2o_fuwu_order_pd_amount'], 2);
        $data_pd = array();
        $data_pd['member_id'] = $member_id;
        $data_pd['member_name'] = $member_name;
        $data_pd['amount'] = $order_amount;
        $data_pd['order_sn'] = $order_info['o2o_fuwu_order_sn'];

        if ($available_pd_amount >= $order_amount) {
            $this->payO2oFuwuOrder($order_info['o2o_fuwu_order_sn'], 'predeposit', '预存款', '', 'buyer', $member_name);
        } else {
            //暂冻结预存款,后面还需要 API彻底完成支付
            $data_pd['amount'] = $available_pd_amount;
        }
        $predeposit_model->changePd('order_freeze', $data_pd);
        //预存款支付金额保存到订单
        $data_order = array();
        $data_order['o2o_fuwu_order_pd_amount'] = $order_info['o2o_fuwu_order_pd_amount'] = round($order_info['o2o_fuwu_order_pd_amount'] + $data_pd['amount'], 2);
        $result = $this->editO2oFuwuOrder($data_order, array('o2o_fuwu_order_id' => $order_info['o2o_fuwu_order_id']));
        if (!$result) {
            throw new \think\Exception('订单更新失败', 10006);
        }
        return $order_info;
    }

    /*
     * 获取订单状态
     */

    public function getO2oFuwuOrderStateText($state) {
        $lang = '';
        switch ($state) {
            case 0:
                $lang = '已取消';
                break;
            case 10:
                $lang = '待付款';
                break;
            case 20:
                $lang = '待服务';
                break;
            case 30:
                $lang = '服务中';
                break;
            case 40:
                $lang = '已完成';
                break;
        }
        return $lang;
    }

    /*
     * 获取订单按钮
     * @access public
     * @author csdeshang  
     * @param array $val 订单数据
     * @param string $operator 操作人员 buyer用户 admin管理员 fuwu服务机构
     * @return arrau
     */

    public function getO2oFuwuOrderBtn($val, $operator) {

        if ($operator == 'admin') {
            $data = array(
                'if_cancel' => false,
                'if_pay' => false,
                'if_receive' => false,
            );
            if ($val['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_CANCEL && $val['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_SUCCESS) {
                $data['if_cancel'] = true;
            }
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_NEW) {
                $data['if_pay'] = true;
            }
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_SEND) {
                $data['if_receive'] = true;
            }
        } else if ($operator == 'buyer') {
            $data = array(
                'if_cancel' => false,
                'if_receive' => false,
                'if_pay' => false,
                'if_complaint' => false,
                'if_evaluate'=>false,
            );
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_NEW) {
                $data['if_cancel'] = true;
                $data['if_pay'] = true;
            }
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_PAY) {
                $data['if_cancel'] = true;
            }

            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_SEND) {
                $data['if_receive'] = true;
            }
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_SUCCESS && !$val['o2o_fuwu_order_if_evaluate']) {
                $data['if_evaluate'] = true;
            }
//            if($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_SUCCESS && !model('o2o_complaint')->getO2oComplaintInfo(array('o2o_complaint_order_type'=>1,'order_id'=>$val['o2o_fuwu_order_id']))){
//                $data['if_complaint'] = true;
//            }
        } else if ($operator == 'fuwu') {
            $data = array(
                'if_arrange' => false,
                'if_cancel' => false,
            );
            if ($val['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_CANCEL && $val['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_SUCCESS) {
                $data['if_cancel'] = true;
            }
            if ($val['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_PAY) {
                $data['if_arrange'] = true;
            }
        }

        return $data;
    }

    /*
     * 确认服务订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param string $operator 操作人员 buyer用户 admin管理员 system系统
     * @return type
     */

    public function receiveO2oFuwuOrder($condition, $operator, $user_name = '') {
        Db::startTrans();
        try {
            $o2o_fuwu_order_info = Db::name('o2o_fuwu_order')->where($condition)->lock(true)->find();
            if (!$o2o_fuwu_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_SEND) {
                throw new \think\Exception('该订单不可完成', 10006);
            }
            if (!$this->editO2oFuwuOrder(array('o2o_fuwu_order_state' => O2O_FUWU_ORDER_STATE_SUCCESS, 'o2o_fuwu_order_finish_time' => TIMESTAMP), array('o2o_fuwu_order_id' => $o2o_fuwu_order_info['o2o_fuwu_order_id']))) {
                throw new \think\Exception('订单更新失败', 10006);
            }
            //资金变更
            $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
            $data = array(
                'o2o_fuwu_organization_id' => $o2o_fuwu_order_info['o2o_fuwu_organization_id'],
                'o2o_fuwu_money_log_type' => O2oFuwuMoneyLog::TYPE_BILL,
                'o2o_fuwu_money_log_state' => O2oFuwuMoneyLog::STATE_VALID,
                'o2o_fuwu_money_log_add_time' => TIMESTAMP,
                'o2o_fuwu_organization_avaliable_money' => $o2o_fuwu_order_info['o2o_fuwu_order_amount'], 
                'o2o_fuwu_money_log_desc' => '订单'.$o2o_fuwu_order_info['o2o_fuwu_order_sn'].'完成',
            );

            $o2o_fuwu_money_log_model->changeO2oFuwuMoney($data);

            $o2o_fuwu_order_log_data = array();
            $o2o_fuwu_order_log_data['o2o_fuwu_order_id'] = $o2o_fuwu_order_info['o2o_fuwu_order_id'];
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_role'] = $operator;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_user'] = $user_name;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_time'] = TIMESTAMP;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_msg'] = '完成订单';
            $insert = model('o2o_fuwu_order_log')->addO2oFuwuOrderLog($o2o_fuwu_order_log_data);
            if (!$insert) {
                throw new \think\Exception('记录订单日志出现错误', 10006);
            }
            //更新服务次数
            Db::name('o2o_fuwu_organization')->where(array('o2o_fuwu_organization_id'=>$o2o_fuwu_order_info['o2o_fuwu_organization_id']))->inc('o2o_fuwu_organization_serve_count')->update();
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }

    /*
     * 安排服务订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param string $operator 操作人员 buyer用户 admin管理员 system系统
     * @return type
     */

    public function arrangeO2oFuwuOrder($condition, $operator, $user_name = '') {
        Db::startTrans();
        try {
            $o2o_fuwu_order_info = Db::name('o2o_fuwu_order')->where($condition)->lock(true)->find();
            if (!$o2o_fuwu_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_PAY) {
                throw new \think\Exception('该订单不可安排', 10006);
            }
            if (!$this->editO2oFuwuOrder(array('o2o_fuwu_order_state' => O2O_FUWU_ORDER_STATE_SEND, 'o2o_fuwu_order_arrange_time' => TIMESTAMP), array('o2o_fuwu_order_id' => $o2o_fuwu_order_info['o2o_fuwu_order_id']))) {
                throw new \think\Exception('订单更新失败', 10006);
            }
            $o2o_fuwu_order_log_data = array();
            $o2o_fuwu_order_log_data['o2o_fuwu_order_id'] = $o2o_fuwu_order_info['o2o_fuwu_order_id'];
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_role'] = $operator;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_user'] = $user_name;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_time'] = TIMESTAMP;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_msg'] = '安排服务';
            $insert = model('o2o_fuwu_order_log')->addO2oFuwuOrderLog($o2o_fuwu_order_log_data);
            if (!$insert) {
                throw new \think\Exception('记录订单日志出现错误', 10006);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }

    /*
     * 取消服务订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param string $operator 操作人员 buyer用户 admin管理员 system系统
     * @return type
     */

    public function cancelO2oFuwuOrder($condition, $operator, $user_name = '', $reason = '') {
        Db::startTrans();
        try {

            $o2o_fuwu_order_info = Db::name('o2o_fuwu_order')->where($condition)->lock(true)->find();
            if (!$o2o_fuwu_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($operator == 'buyer') {
                if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_NEW && $o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_PAY) {
                    throw new \think\Exception('该订单不可取消', 10006);
                }
            } else {
                if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_CANCEL || $o2o_fuwu_order_info['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_SUCCESS) {
                    throw new \think\Exception('该订单不可取消', 10006);
                }
            }

            $predeposit_model = model('predeposit');
            $rcb_amount = floatval($o2o_fuwu_order_info['o2o_fuwu_order_rcb_amount']);
            $pd_amount = floatval($o2o_fuwu_order_info['o2o_fuwu_order_pd_amount']);
            if(in_array($o2o_fuwu_order_info['o2o_fuwu_order_state'],array(O2O_FUWU_ORDER_STATE_PAY,O2O_FUWU_ORDER_STATE_SEND))){
              $predeposit_amount = floatval($o2o_fuwu_order_info['o2o_fuwu_order_amount'])-$rcb_amount;
            }else{
              $predeposit_amount = $pd_amount;
            }
            

            $not_trade_refund = TRUE; //在线支付 不原路返还
            $alipay_payment_list = array('alipay', 'alipay_app', 'alipay_h5');
            $wxpay_payment_list = array('wxpay_app', 'wxpay_h5', 'wxpay_jsapi', 'wxpay_minipro', 'wxpay_native');
            
            //未使用预存款支付 以及  充值卡支付的订单 才支持订单原路返还。
            if (in_array($o2o_fuwu_order_info['o2o_fuwu_order_state'],array(O2O_FUWU_ORDER_STATE_PAY,O2O_FUWU_ORDER_STATE_SEND)) && $predeposit_amount > 0 && (in_array($o2o_fuwu_order_info['o2o_fuwu_order_payment_code'], $alipay_payment_list) || in_array($o2o_fuwu_order_info['o2o_fuwu_order_payment_code'], $wxpay_payment_list)) && $rcb_amount == 0 && $pd_amount == 0) {
                if (in_array($o2o_fuwu_order_info['o2o_fuwu_order_payment_code'], $alipay_payment_list)) {
                    $payment_code = 'alipay';
                }
                if (in_array($o2o_fuwu_order_info['o2o_fuwu_order_payment_code'], $wxpay_payment_list)) {
                    $payment_code = 'wxpay_native';
                }
                //调用支付接口处理原路退款
                $logic_payment = model('payment', 'logic');
                $result = $logic_payment->getPaymentInfo($payment_code);
                if (!$result['code']) {
                    throw new \think\Exception($result['msg'], 10006);
                }
                $payment_info = $result['data'];
                
                //支付宝/微信 未开启原路返回
                if (($payment_code == 'alipay' && $payment_info['payment_config']['alipay_trade_refund_state'] == 1) || ($payment_code == 'wxpay_native' && $payment_info['payment_config']['wx_trade_refund_state'] == 1)) {
                    //原路返还金额
                    $trade_refund_amount = $predeposit_amount;

                    $payment_api = new $payment_code($payment_info);
                    $order=array(
                    'out_request_no'=>$o2o_fuwu_order_info['o2o_fuwu_order_sn'],
                    'trade_no'=>$o2o_fuwu_order_info['o2o_fuwu_order_payment_sn'],
                    'total_order_amount'=>$o2o_fuwu_order_info['o2o_fuwu_order_amount'],
                    'rcb_amount'=>$o2o_fuwu_order_info['o2o_fuwu_order_rcb_amount'],
                    'pd_amount'=>$o2o_fuwu_order_info['o2o_fuwu_order_pd_amount'],
                    );
                    $result = $payment_api->trade_refund($order, $trade_refund_amount);
                    if (!$result['code']) {
                        throw new \think\Exception($result['msg'], 10006);
                    }
                    $not_trade_refund = FALSE;
                }
            }
            if($o2o_fuwu_order_info['o2o_fuwu_order_state'] == O2O_FUWU_ORDER_STATE_NEW){
            //解冻充值卡
            
            if ($rcb_amount > 0 && $not_trade_refund) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_fuwu_order_info['member_id'];
                $data_pd['member_name'] = $o2o_fuwu_order_info['member_name'];
                $data_pd['amount'] = $rcb_amount;
                $data_pd['order_sn'] = $o2o_fuwu_order_info['o2o_fuwu_order_sn'];
                if (!$predeposit_model->changeRcb('order_cancel', $data_pd)) {
                    throw new \think\Exception('充值卡退款失败', 10006);
                }
            }
            //解冻预存款
            
            if ($predeposit_amount > 0 && $not_trade_refund) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_fuwu_order_info['member_id'];
                $data_pd['member_name'] = $o2o_fuwu_order_info['member_name'];
                $data_pd['amount'] = $predeposit_amount;
                $data_pd['order_sn'] = $o2o_fuwu_order_info['o2o_fuwu_order_sn'];
                if (!$predeposit_model->changePd('order_cancel', $data_pd)) {
                    throw new \think\Exception('预存款退款失败', 10006);
                }
            }
            }else{
                $order = array(
                    'order_sn' => $o2o_fuwu_order_info['o2o_fuwu_order_sn'],
                    'trade_no' => $o2o_fuwu_order_info['o2o_fuwu_order_payment_sn'],
                    'order_amount' => $o2o_fuwu_order_info['o2o_fuwu_order_amount'],
                    'total_order_amount' => $o2o_fuwu_order_info['o2o_fuwu_order_amount'],
                    'payment_code'=>$o2o_fuwu_order_info['o2o_fuwu_order_payment_code'],
                    'buyer_id'=>$o2o_fuwu_order_info['member_id'],
                    'buyer_name'=>$o2o_fuwu_order_info['member_name'],
                    'refund_amount' => 0,
                    'rcb_amount' => 0,
                    'pd_amount' => 0,
                );
                $refundreturn_model = model('refundreturn');
                $refundreturn_model->refundAmount($order, $o2o_fuwu_order_info['o2o_fuwu_order_amount']);
            }

            if (!$this->editO2oFuwuOrder(array('o2o_fuwu_order_state' => O2O_FUWU_ORDER_STATE_CANCEL), array('o2o_fuwu_order_id' => $o2o_fuwu_order_info['o2o_fuwu_order_id']))) {
                throw new \think\Exception('订单更新失败', 10006);
            }
            $o2o_fuwu_order_log_data = array();
            $o2o_fuwu_order_log_data['o2o_fuwu_order_id'] = $o2o_fuwu_order_info['o2o_fuwu_order_id'];
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_role'] = $operator;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_user'] = $user_name;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_time'] = TIMESTAMP;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_msg'] = '取消订单' . ($reason ? (':' . $reason) : '');
            $insert = model('o2o_fuwu_order_log')->addO2oFuwuOrderLog($o2o_fuwu_order_log_data);
            if (!$insert) {
                throw new \think\Exception('记录订单日志出现错误', 10006);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
        Db::commit();
        return ds_callback(true, '操作成功');
    }

    /*
     * 付款服务订单
     * @access public
     * @author csdeshang  
     * @param string $out_trade_no 订单号
     * @param string $payment_code 支付方式
     * @param string $payment_name 支付名称
     * @param string $trade_no 第三方支付单号
     * @param string $operator 操作人员 buyer用户 admin管理员 system系统
     * @param string $user_name 用户名
     * @param string $payment_time 支付时间
     * @return type
     */

    public function payO2oFuwuOrder($out_trade_no, $payment_code, $payment_name, $trade_no, $operator, $user_name = '', $payment_time = '') {
   
            $o2o_fuwu_order_info = Db::name('o2o_fuwu_order')->where(array('o2o_fuwu_order_sn' => $out_trade_no))->lock(true)->find();
            if (!$o2o_fuwu_order_info) {
                throw new \think\Exception('订单不存在', 10006);
            }
            if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_NEW) {
                throw new \think\Exception('该订单不可支付', 10006);
            }

            if ($payment_time) {
                $payment_time = strtotime($payment_time);
            } else {
                $payment_time = TIMESTAMP;
            }
            if (!$this->editO2oFuwuOrder(array('o2o_fuwu_order_payment_code' => $payment_code, 'o2o_fuwu_order_payment_name' => $payment_name, 'o2o_fuwu_order_payment_sn' => $trade_no, 'o2o_fuwu_order_state' => O2O_FUWU_ORDER_STATE_PAY, 'o2o_fuwu_order_payment_time' => $payment_time), array('o2o_fuwu_order_id' => $o2o_fuwu_order_info['o2o_fuwu_order_id']))) {
                throw new \think\Exception('订单更新失败', 10006);
            }
            $predeposit_model = model('predeposit');
            //下单，支付被冻结的充值卡
            $rcb_amount = floatval($o2o_fuwu_order_info['o2o_fuwu_order_rcb_amount']);
            if ($rcb_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_fuwu_order_info['member_id'];
                $data_pd['member_name'] = $o2o_fuwu_order_info['member_name'];
                $data_pd['amount'] = $rcb_amount;
                $data_pd['order_sn'] = $o2o_fuwu_order_info['o2o_fuwu_order_sn'];
                $predeposit_model->changeRcb('order_comb_pay', $data_pd);
            }

            //下单，支付被冻结的预存款
            $pd_amount = floatval($o2o_fuwu_order_info['o2o_fuwu_order_pd_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $o2o_fuwu_order_info['member_id'];
                $data_pd['member_name'] = $o2o_fuwu_order_info['member_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $o2o_fuwu_order_info['o2o_fuwu_order_sn'];
                $predeposit_model->changePd('order_comb_pay', $data_pd);
            }

            $o2o_fuwu_order_log_data = array();
            $o2o_fuwu_order_log_data['o2o_fuwu_order_id'] = $o2o_fuwu_order_info['o2o_fuwu_order_id'];
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_role'] = $operator;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_user'] = $user_name;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_time'] = TIMESTAMP;
            $o2o_fuwu_order_log_data['o2o_fuwu_order_log_msg'] = '订单付款';
            $insert = model('o2o_fuwu_order_log')->addO2oFuwuOrderLog($o2o_fuwu_order_log_data);
            if (!$insert) {
                throw new \think\Exception('记录订单日志出现错误', 10006);
            }

    }

    /**
     * 编辑服务订单
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuOrder($data, $condition = array()) {
        return Db::name('o2o_fuwu_order')->where($condition)->update($data);
    }

    /**
     * 删除服务订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @return type
     */
    public function delO2oFuwuOrder($condition) {
        return Db::name('o2o_fuwu_order')->where($condition)->delete();
    }
    /**
     * 取得订单数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getO2oFuwuOrderCount($condition) {
        return Db::name('o2o_fuwu_order')->where($condition)->count();
    }
}

?>
