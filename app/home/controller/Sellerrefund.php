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
        $this->getRefundStateArray();
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
                $condition[] = array('add_time', '>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to=strtotime($add_time_to)+86399;
            if ($add_time_to !== false) {
                $condition[] = array('add_time', '<=', $add_time_to);
            }
        }
        $seller_state = intval(input('state'));
        if ($seller_state > 0) {
            $condition[]=array('seller_state','=',$seller_state);
        }
        $order_lock = intval(input('lock'));
        if ($order_lock != 1) {
            $order_lock = 2;
        }
        $condition[]=array('order_lock','=',$order_lock);
        $refund_list = $refundreturn_model->getRefundList($condition, 10);
        $page=$refundreturn_model->page_info->render();

        View::assign('refund_list', $refund_list);
        View::assign('show_page', $page);


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_refund');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem($order_lock);

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
        $refund_list = $refundreturn_model->getRefundList($condition);

        $refund = $refund_list[0];


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
            if ($refund['seller_state'] != '1') {//检查状态,防止页面刷新不及时造成数据错误
                ds_json_encode(10001,lang('param_error'));
            }
            $order_id = $refund['order_id'];
            $refund_array = array();
            $refund_array['seller_time'] = TIMESTAMP;
            $refund_array['seller_state'] = input('post.seller_state'); //卖家处理状态:1为待审核,2为同意,3为不同意
            $refund_array['seller_message'] = input('post.seller_message');
            if ($refund_array['seller_state'] == '3') {
                $refund_array['refund_state'] = '3'; //状态:1为处理中,2为待管理员处理,3为已完成
            } else {
                $refund_array['seller_state'] = '2';
                $refund_array['refund_state'] = '2';
            }
            $state = $refundreturn_model->editRefundreturn($condition, $refund_array);

            if ($state) {
                if ($refund_array['seller_state'] == '3' && $refund['order_lock'] == '2') {
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
        $refund_list = $refundreturn_model->getRefundList($condition);

        $refund = $refund_list[0];
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

    function getRefundStateArray($type = 'all') {
        $state_array = array(
            '1' => lang('refund_state_confirm'),
            '2' => lang('refund_state_yes'),
            '3' => lang('refund_state_no')
        ); //卖家处理状态:1为待审核,2为同意,3为不同意
        View::assign('state_array', $state_array);

        $admin_array = array(
            '1' => lang('in_processing'),
            '2' => lang('to_processed'),
            '3' => lang('has_been_completed'),
            '4' => lang('refund_state_no')
        ); //确认状态:1为买家或卖家处理中,2为待平台管理员处理,3为退款退货已完成
        View::assign('admin_array', $admin_array);

        $state_data = array(
            'seller' => $state_array,
            'admin' => $admin_array
        );
        if ($type == 'all') {
            return $state_data; //返回所有
        }
        return $state_data[$type];
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
                'name' => '2',
                'text' => lang('before_refund'),
                'url' => url('Sellerrefund/index',['lock'=>2])
            ),
            array(
                'name' => '1',
                'text' => lang('after_refund'),
                'url' => url('Sellerrefund/index',['lock'=>1])
            ),
        );
        return $menu_array;
    }

}
