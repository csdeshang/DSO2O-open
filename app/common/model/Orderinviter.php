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
class  Orderinviter extends BaseModel {

    /**
     * 支付给钱
     * @access public
     * @author csdeshang
     * @param type $order_id 订单编号
     */
    public function giveMoney($order_id, $type) {
        $orderinviter_list = Db::name('orderinviter')->where(array('orderinviter_order_id' => $order_id, 'orderinviter_valid' => 0, 'orderinviter_order_type' => $type))->lock(true)->select()->toArray();
        if ($orderinviter_list) {
            $predeposit_model = model('predeposit');
            foreach ($orderinviter_list as $val) {
                //如果是被清退的分销员，则得不到分销佣金，对冲分销员分销完商品后，退款的情况
                $inviter = Db::name('inviter')->where(array('inviter_id' => $val['orderinviter_member_id'], 'inviter_state' => 1))->lock(true)->find();
                if ($inviter) {
                    $data = array();
                    $data['member_id'] = $val['orderinviter_member_id'];
                    $data['member_name'] = $val['orderinviter_member_name'];
                    $data['amount'] = $val['orderinviter_money'];
                    $data['order_sn'] = $val['orderinviter_order_sn'];
                    $data['lg_desc'] = $val['orderinviter_remark'];
                    $predeposit_model->changePd('order_inviter', $data);
                    $goodscommon = Db::name('goodscommon')->where('goods_commonid=' . $val['orderinviter_goods_commonid'])->lock(true)->find();

                    if ($goodscommon) {
                        $goodscommon_data = array();
                        if (!Db::name('orderinviter')->where(array(array('orderinviter_order_id' ,'=', $order_id), array('orderinviter_goods_commonid' ,'=', $val['orderinviter_goods_commonid']), array('orderinviter_valid','<>', 0)))->lock(true)->find()) {
                            //更新商品的分销情况
                            $goodscommon_data['inviter_total_quantity'] = $goodscommon['inviter_total_quantity'] + $val['orderinviter_goods_quantity'];
                            $goodscommon_data['inviter_total_amount'] = bcadd($goodscommon['inviter_total_amount'], $val['orderinviter_goods_amount'], 2);
                        }
                        if ($val['orderinviter_money'] > 0) {
                            $goodscommon_data['inviter_amount'] = bcadd($goodscommon['inviter_amount'], $val['orderinviter_money'], 2);
                        }

                        if (!empty($goodscommon_data)) {
                            $mysql_flag = Db::name('goodscommon')->where('goods_commonid=' . $val['orderinviter_goods_commonid'])->update($goodscommon_data);
                            if (!$mysql_flag) {
                                throw new \think\Exception('[订单id：' . $order_id . ']商品分销信息更新失败', 10006);
                            }
                        }
                    }
                    $inviter_data = array(
                        'inviter_goods_quantity' => $inviter['inviter_goods_quantity'] + $val['orderinviter_goods_quantity'],
                        'inviter_goods_amount' => bcadd($inviter['inviter_goods_amount'], $val['orderinviter_goods_amount'], 2),
                        'inviter_total_amount' => bcadd($inviter['inviter_total_amount'], $val['orderinviter_money'], 2),
                    );
                    //更新分销员的分销情况
                    $mysql_flag = Db::name('inviter')->where(array('inviter_id' => $val['orderinviter_member_id']))->update($inviter_data);
                    if (!$mysql_flag) {
                        throw new \think\Exception('[订单id：' . $order_id . ']分销员分销信息更新失败', 10006);
                    }
                    $mysql_flag = Db::name('orderinviter')->where('orderinviter_id', $val['orderinviter_id'])->update(['orderinviter_valid' => 1]);
                    if (!$mysql_flag) {
                        throw new \think\Exception('[订单id：' . $order_id . ']分销佣金状态更新失败', 10006);
                    }
                }
            }
        }
    }

}
