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
class  Trade extends BaseModel {

    /**
     * 订单处理天数
     * @access public
     * @author csdeshang
     * @param type $day_type 天数类型
     * @return int
     */
    public function getMaxDay($day_type = 'all') {
        $max_data = array(
            'order_refund' => 1, //收货完成后可以申请退款退货
            'refund_confirm' => 7, //卖家不处理退款退货申请时按同意处理
            'return_confirm' => 7, //卖家不处理收货时按弃货处理
            'return_delay' => 5 //退货的商品发货多少天以后才可以选择没收到
        );
        if ($day_type == 'all')
            return $max_data; //返回所有
        if (intval($max_data[$day_type]) < 1)
            $max_data[$day_type] = 1; //最小的值设置为1
        return $max_data[$day_type];
    }


    /**
     * 更新退款申请
     * @access public
     * @author csdeshang
     * @param int $member_id 会员编号
     * @param int $store_id 店铺编号
     * @return type 
     */
    public function editRefundConfirm($member_id = 0, $store_id = 0) {
        $refund_confirm = $this->getMaxDay('refund_confirm'); //卖家不处理退款申请时按同意并弃货处理
        $day = TIMESTAMP - $refund_confirm * 60 * 60 * 24;
        $condition = " refundreturn_seller_state=1 and refundreturn_add_time<" . $day; //状态:1为待审核,2为同意,3为不同意
        $condition_sql = "";
        if ($member_id > 0) {
            $condition_sql = " buyer_id = '" . $member_id . "'  and ";
        }
        if ($store_id > 0) {
            $condition_sql = " store_id = '" . $store_id . "' and ";
        }
        $condition_sql = $condition_sql . $condition;
        $refund_array = array();
        $refund_array['refundreturn_admin_state'] = '2'; //状态:1为处理中,2为待管理员处理,3为已完成
        $refund_array['refundreturn_seller_state'] = '2'; //卖家处理状态:1为待审核,2为同意,3为不同意
        $refund_array['return_type'] = '1'; //退货类型:1为不用退货,2为需要退货
        $refund_array['refundreturn_seller_time'] = TIMESTAMP;
        $refund_array['refundreturn_seller_message'] = '超过' . $refund_confirm . '天未处理退款退货申请，按同意处理。';
        $refund = Db::name('refundreturn')->field('refund_id,refund_sn,store_id,order_sn,refund_amount,refund_type')->where($condition_sql)->select()->toArray();
        Db::name('refundreturn')->where($condition_sql)->update($refund_array);

        // 发送商家提醒
        foreach ((array) $refund as $val) {
            // 参数数组
            $message = array();
            $message['refund_sn'] = $val['refund_sn'];
            $ten_message = array($message['refund_sn']);
            $weixin_param = array(
                    'url' => config('ds_config.h5_store_site_url').'/pages/seller/refund/RefundView?refund_id='.$val['refund_id'].'&refund_type='.$val['refund_type'],
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $val['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $val['refund_amount'],
                            "color" => "#333"
                        )
                        )
                    );
            if (intval($val['refund_type']) == 1) {
// 退款
                $this->sendStoremsg('refund_auto_process', $val['store_id'], $message,$weixin_param, $message,$ten_message);
            } else {
// 退货
                $this->sendStoremsg('return_auto_process', $val['store_id'], $message,$weixin_param, $message,$ten_message);
            }
        }

    }

    /**
     * 发送店铺消息
     * @access public
     * @author csdeshang
     * @param string $code 编码
     * @param int $store_id 店铺ID
     * @param array $message 消息
     */
    private function sendStoremsg($code, $store_id, $message,$weixin_param=array(),$ali_param=array(),$ten_param=array()) {
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize(array('code' => $code, 'store_id' => $store_id, 'param' => $message,'weixin_param'=>$weixin_param,'ali_param'=>$ali_param,'ten_param'=>$ten_param))));
    }

}

?>
