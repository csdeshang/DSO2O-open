<?php

/**
 * 店铺入住模型
 */

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
class  Storejoinin extends BaseModel {
    public $page_info;


    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStorejoininList($condition, $pagesize = '', $order = '', $field = '*') {
        if($pagesize){
            $result = Db::name('storejoinin')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            $result = Db::name('storejoinin')->field($field)->where($condition)->order($order)->select()->toArray();
            return $result;
        }
    }

    /**
     * 店铺入住数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStorejoininCount($condition) {
        return Db::name('storejoinin')->where($condition)->count();
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array
     */
    public function getOneStorejoinin($condition) {
        $result = Db::name('storejoinin')->where($condition)->find();
        return $result;
    }

    /**
     * 判断是否存在
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return boolean
     */
    public function isStorejoininExist($condition) {
        $result = $this->getOneStorejoinin($condition);
        if (empty($result)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addStorejoinin($data) {
        return Db::name('storejoinin')->insert($data);
    }


    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 数据
     * @param type $condition 条件
     * @return type
     */
    public function editStorejoinin($update, $condition) {
        return Db::name('storejoinin')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function delStorejoinin($condition) {
        return Db::name('storejoinin')->where($condition)->delete();
    }
	
	/**
	 * 充值卡支付
	 * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
	 */
	public function rcbPay($order_info, $input, $buyer_info) {
		$available_rcb_amount = floatval($buyer_info['available_rc_balance']);

		if ($available_rcb_amount <= 0)
			return $order_info;
		$predeposit_model = model('predeposit');

		$order_amount = round($order_info['paying_amount'] - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);
		$data_pd = array();
		$data_pd['member_id'] = $buyer_info['member_id'];
		$data_pd['member_name'] = $buyer_info['member_name'];
		$data_pd['amount'] = $order_amount;
		$data_pd['order_sn'] = $order_info['pay_sn'];

		if ($available_rcb_amount >= $order_amount) {

			// 预存款立即支付，订单支付完成
			$predeposit_model->changeRcb('storejoinin_pay', $data_pd);
			$available_rcb_amount -= $order_amount;
			//支付被冻结的充值卡
			$rcb_amount = isset($order_info['rcb_amount']) ? floatval($order_info['rcb_amount']) : 0;
			if ($rcb_amount > 0) {
				$data_pd = array();
				$data_pd['member_id'] = $buyer_info['member_id'];
				$data_pd['member_name'] = $buyer_info['member_name'];
				$data_pd['amount'] = $rcb_amount;
				$data_pd['order_sn'] = $order_info['pay_sn'];
				$predeposit_model->changeRcb('storejoinin_comb_pay', $data_pd);
			}
			$store_model=model('store');
			$store_model->setStoreOpen($order_info,array('joinin_state'=>STORE_JOIN_STATE_FINAL,'payment_code'=>'predeposit'));
			
			// 订单状态 置为已支付
			$order_info['rcb_amount'] = round($order_info['rcb_amount'] + $order_amount, 2);
		} else {

			//暂冻结预存款,后面还需要 API彻底完成支付
			$data_pd['amount'] = $available_rcb_amount;
			$predeposit_model->changeRcb('storejoinin_freeze', $data_pd);
			//预存款支付金额保存到订单
			$data_order = array();
			$order_info['rcb_amount'] = $data_order['rcb_amount'] = round($order_info['rcb_amount'] + $available_rcb_amount, 2);
			$result = $this->editStorejoinin($data_order, array('member_id' => $order_info['member_id']));
			if (!$result) {
				throw new \think\Exception('订单更新失败', 10006);
			}
		}
		return $order_info;
	}

	/**
	 * 预存款支付 主要处理
	 * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
	 */
	public function pdPay($order_info, $input, $buyer_info) {
		if ($order_info['joinin_state'] == STORE_JOIN_STATE_FINAL)
			return $order_info;

		$available_pd_amount = floatval($buyer_info['available_predeposit']);
		if ($available_pd_amount <= 0)
			return $order_info;

		$predeposit_model = model('predeposit');

		$order_amount = round($order_info['paying_amount'] - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);
		$data_pd = array();
		$data_pd['member_id'] = $buyer_info['member_id'];
		$data_pd['member_name'] = $buyer_info['member_name'];
		$data_pd['amount'] = $order_amount;
		$data_pd['order_sn'] = $order_info['pay_sn'];

		if ($available_pd_amount >= $order_amount) {

			//预存款立即支付，订单支付完成
			$predeposit_model->changePd('storejoinin_pay', $data_pd);
			$available_pd_amount -= $order_amount;

			//下单，支付被冻结的充值卡
			$pd_amount = floatval($order_info['rcb_amount']);
			if ($pd_amount > 0) {
				$data_pd = array();
				$data_pd['member_id'] = $buyer_info['member_id'];
				$data_pd['member_name'] = $buyer_info['member_name'];
				$data_pd['amount'] = $pd_amount;
				$data_pd['order_sn'] = $order_info['pay_sn'];
				$predeposit_model->changeRcb('order_comb_pay', $data_pd);
			}
			//支付被冻结的预存款
			$pd_amount = isset($order_info['pd_amount']) ? floatval($order_info['pd_amount']) : 0;
			if ($pd_amount > 0) {
				$data_pd = array();
				$data_pd['member_id'] = $buyer_info['member_id'];
				$data_pd['member_name'] = $buyer_info['member_name'];
				$data_pd['amount'] = $pd_amount;
				$data_pd['order_sn'] = $order_info['pay_sn'];
				$predeposit_model->changePd('storejoinin_comb_pay', $data_pd);
			}
			$store_model=model('store');
			$store_model->setStoreOpen($order_info,array('joinin_state'=>STORE_JOIN_STATE_FINAL,'payment_code'=>'predeposit'));
		   
		    // 订单状态 置为已支付
			$order_info['pd_amount'] = round($order_info['pd_amount'] + $order_amount, 2);
		} else {

			//暂冻结预存款,后面还需要 API彻底完成支付
			$data_pd['amount'] = $available_pd_amount;
			$predeposit_model->changePd('storejoinin_freeze', $data_pd);
			//预存款支付金额保存到订单
			$data_order = array();
			$order_info['pd_amount'] = $data_order['pd_amount'] = round($order_info['pd_amount'] + $available_pd_amount, 2);
			$result = $this->editStorejoinin($data_order, array('member_id' => $order_info['member_id']));
			if (!$result) {
				throw new \think\Exception('订单更新失败', 10006);
			}
		}
		return $order_info;
	}


}
