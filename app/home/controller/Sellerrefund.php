<?php

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
class  Sellerrefund extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerrefund.lang.php');
    }

    /**
     * 退款记录列表页
     *
     */
    public function index() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $condition[]=array('refund_type','=','1'); //类型:1为退款,2为退货
        $keyword_type = array('order_sn', 'refund_sn', 'buyer_name');

        $key = input('key');
        $type = input('type');
        if (trim($key) != '' && in_array($type, $keyword_type)) {
            $condition[] = array($type,'like', '%' . $key . '%');
        }
        $add_time_from = input('add_time_from');
        $add_time_to = input('add_time_to');
        if (trim($add_time_from) != '') {
            $add_time_from=strtotime($add_time_from);
            if ($add_time_from !== false) {
                $condition[] = array('refundreturn_add_time', '>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to=strtotime($add_time_to)+86399;
            if ($add_time_to !== false) {
                $condition[] = array('refundreturn_add_time', '<=', $add_time_to);
            }
        }
        $refundreturn_seller_state = intval(input('state'));
        if ($refundreturn_seller_state > 0) {
            $condition[]=array('refundreturn_seller_state','=',$refundreturn_seller_state);
        }
        $refund_list = $refundreturn_model->getRefundList($condition, 10);
        $page=$refundreturn_model->page_info->render();

        View::assign('refund_list', $refund_list);
        View::assign('show_page', $page);


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_refund');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('seller_refund');

        return View::fetch($this->template_dir.'index');
    }

    /**
     * 退款审核页
     *
     */
    public function edit() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $condition[]=array('refund_id','=',intval(input('param.refund_id')));
        $refund = $refundreturn_model->getRefundreturnInfo($condition);
        if(empty($refund)){
            $this->error(lang('param_error'));
        }


        if (!request()->isPost()) {
            View::assign('refund', $refund);
            $info['buyer'] = array();
            if (!empty($refund['pic_info'])) {
                $info = unserialize($refund['pic_info']);
            }
            View::assign('pic_list', $info['buyer']);
            $member_model = model('member');
            $member = $member_model->getMemberInfoByID($refund['buyer_id']);
            View::assign('member', $member);
            $condition = array();
            $condition[] = array('order_id','=',$refund['order_id']);
            $order = $refundreturn_model->getRightOrderList($condition, $refund['order_goods_id']);
            View::assign('order', $order);
            View::assign('store', $order['extend_store']);
            View::assign('order_common', $order['extend_order_common']);
            View::assign('goods_list', $order['goods_list']);


            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_refund');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('');
            return View::fetch($this->template_dir.'edit');
        } else {
            if ($refund['refundreturn_seller_state'] != '1') {//检查状态,防止页面刷新不及时造成数据错误
                ds_json_encode(10001,lang('param_error'));
            }
            $order_id = $refund['order_id'];
            $refund_array = array();
            $refund_array['refundreturn_seller_time'] = TIMESTAMP;
            $refund_array['refundreturn_seller_state'] = input('post.refundreturn_seller_state'); //卖家处理状态:1为待审核,2为同意,3为不同意
            $refund_array['refundreturn_seller_message'] = input('post.refundreturn_seller_message');
            if ($refund_array['refundreturn_seller_state'] == '3') {
                $refund_array['refundreturn_admin_state'] = '3'; //状态:1为处理中,2为待管理员处理,3为已完成
            } else {
                $refund_array['refundreturn_seller_state'] = '2';
                $refund_array['refundreturn_admin_state'] = '2';
            }
            $state = $refundreturn_model->editRefundreturn($condition, $refund_array);

            if ($state) {
                if ($refund_array['refundreturn_seller_state'] == '3') {
                    $refundreturn_model->editOrderUnlock($order_id); //订单解锁
                }
                $this->recordSellerlog(lang('refund_processing') . $refund['refund_sn']);

                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $refund['buyer_id'];
                $param['ali_param'] = array(
                    'refund_sn' => $refund['refund_sn']
                );
                $param['ten_param'] = array(
                    $refund['refund_sn']
                );
                $param['param'] = array_merge($param['ali_param'],array(
                    'refund_url' => HOME_SITE_URL .'/Memberrefund/view?refund_id='.$refund['refund_id'],
                ));
                //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_site_url').'/pages/member/'.($refund['refund_type'] == 1 ? 'refund/RefundView' : 'return/ReturnView').'?refund_id='.$refund['refund_id'],
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $refund['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $refund['refund_amount'],
                            "color" => "#333"
                        )
                    ),
                );
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));
               
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 退款记录查看页
     *
     */
    public function view() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $condition[]=array('refund_id','=',intval(input('param.refund_id')));
        $refund = $refundreturn_model->getRefundreturnInfo($condition);
        if(empty($refund)){
            $this->error(lang('param_error'));
        }
        
        View::assign('refund', $refund);
        $info['buyer'] = array();
        if (!empty($refund['pic_info'])) {
            $info = unserialize($refund['pic_info']);
        }

        View::assign('pic_list', $info['buyer']);
        $member_model = model('member');
        $member = $member_model->getMemberInfoByID($refund['buyer_id']);
        View::assign('member', $member);
        $condition = array();
        $condition[] = array('order_id','=',$refund['order_id']);
        $order = $refundreturn_model->getRightOrderList($condition, $refund['order_goods_id']);
        View::assign('order', $order);
        View::assign('store', $order['extend_store']);
        View::assign('order_common', $order['extend_order_common']);
        View::assign('goods_list', $order['goods_list']);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_refund');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('');
        return View::fetch($this->template_dir.'view');
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
                'name' => 'seller_refund',
                'text' => '退款',
                'url' => (string) url('Sellerrefund/index')
            ),
        );
        return $menu_array;
    }

}
