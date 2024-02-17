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
class  Seller extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/seller.lang.php');
    }

    /**
     * 商户中心首页
     *
     */
    public function index() {
        // 店铺信息
        $store_info = $this->store_info;
        $store_info['reopen_tip'] = FALSE;
        if (intval($store_info['store_endtime']) > 0) {
            $store_info['store_endtime_text'] = date('Y-m-d', $store_info['store_endtime']);
            $reopen_time = $store_info['store_endtime'] - 3600 * 24 + 1 - TIMESTAMP;
            if (!session('is_platform_store') && $store_info['store_endtime'] - TIMESTAMP >= 0 && $reopen_time < 2592000) {
                //到期续签提醒(<30天)
                $store_info['reopen_tip'] = true;
            }
        } else {
            $store_info['store_endtime_text'] = lang('store_no_limit');
        }
        // 店铺等级信息
        $store_info['grade_name'] = $this->store_grade['storegrade_name'];
        $store_info['grade_goodslimit'] = $this->store_grade['storegrade_goods_limit'];
        $store_info['grade_albumlimit'] = $this->store_grade['storegrade_album_limit'];

        View::assign('store_info', $store_info);
        // 商家帮助
        $help_model = model('help');
        $condition = array();
        $condition[]=array('helptype_show','=','1'); //是否显示,0为否,1为是
        $help_list = $help_model->getStoreHelptypeList($condition, '', 6);
        View::assign('help_list', $help_list);

        // 销售情况统计
        $field = ' COUNT(*) as ordernum,SUM(order_amount) as orderamount ';
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_isvalid','=',1);
        // 昨日销量
        $condition[] = array('order_add_time','between',array(strtotime(date('Y-m-d', (TIMESTAMP - 3600 * 24))), strtotime(date('Y-m-d', TIMESTAMP)) - 1));
        $daily_sales = model('stat')->getoneByStatorder($condition, $field);
        View::assign('daily_sales', $daily_sales);
        // 月销量
        $condition[] = array('order_add_time','>',strtotime(date('Y-m', TIMESTAMP)));
        $monthly_sales = model('stat')->getoneByStatorder($condition, $field);
        View::assign('monthly_sales', $monthly_sales);
        unset($field, $condition);

        //单品销售排行
        //最近30天
        $stime = strtotime(date('Y-m-d', (TIMESTAMP - 3600 * 24))) - (86400 * 29); //30天前
        $etime = strtotime(date('Y-m-d', TIMESTAMP)) - 1; //昨天23:59
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_isvalid','=',1);
        $condition[] = array('order_add_time','between',array($stime, $etime));
        $field = ' goods_id,goods_name,SUM(goods_num) as goodsnum,goods_image ';
        $orderby = 'goodsnum desc,goods_id';
        $goods_list = model('stat')->statByStatordergoods($condition, $field, 0, 8, $orderby, 'goods_id');
        unset($stime, $etime, $condition, $field, $orderby);
        View::assign('goods_list', $goods_list);
        
        if (!session('is_platform_store')) {
            if (intval(config('ds_config.promotion_allow')) == 1) {
                // 秒杀套餐
                $xianshiquota_info = model('pxianshiquota')->getXianshiquotaCurrent(session('store_id'));
                View::assign('xianshiquota_info', $xianshiquota_info);
                // 满即送套餐
                $mansongquota_info = model('pmansongquota')->getMansongquotaCurrent(session('store_id'));
                View::assign('mansongquota_info', $mansongquota_info);
            }
            if (config('ds_config.voucher_allow') == 1) {
                $voucherquota_info = model('voucher')->getVoucherquotaCurrent(session('store_id'));
                View::assign('voucherquota_info', $voucherquota_info);
            }
        } else {
            View::assign('isPlatformStore', true);
        }
        $phone_array = explode(',', config('ds_config.site_phone'));
        View::assign('phone_array', $phone_array);

        View::assign('menu_sign', 'index');


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_index');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem();
        return View::fetch($this->template_dir.'index');
    }

    /**
     * 异步取得卖家统计类信息
     *
     */
    public function statistics() {
//        $add_time_to = strtotime(date("Y-m-d") + 60 * 60 * 24);   //当前日期 ,从零点来时
//        $add_time_from = strtotime(date("Y-m-d", (strtotime(date("Y-m-d")) - 60 * 60 * 24 * 30)));   //30天前
        $goods_online = 0;      // 出售中商品
        $goods_waitverify = 0;  // 等待审核
        $goods_verifyfail = 0;  // 审核失败
        $goods_offline = 0;     // 仓库待上架商品
        $goods_lockup = 0;      // 违规下架商品
        $consult = 0;           // 待回复商品咨询
        $no_payment = 0;        // 待付款
        $no_delivery = 0;       // 待发货
        $no_receipt = 0;        // 待收货
        $refund_lock = 0;      // 售前退款
        $refund = 0;            // 售后退款
        $return_lock = 0;      // 售前退货
        $return = 0;            // 售后退货
        $complain = 0;          //进行中投诉

        $goods_model = model('goods');
        // 全部商品数
        $goodscount = $goods_model->getGoodsCommonCount(array('store_id' => session('store_id')));
        // 出售中的商品
        $goods_online = $goods_model->getGoodsCommonOnlineCount(array(array('store_id' ,'=', session('store_id'))));
        if (config('ds_config.goods_verify')) {
            // 等待审核的商品
            $goods_waitverify = $goods_model->getGoodsCommonWaitVerifyCount(array(array('store_id' ,'=', session('store_id'))));
            // 审核失败的商品
            $goods_verifyfail = $goods_model->getGoodsCommonVerifyFailCount(array(array('store_id' ,'=', session('store_id'))));
        }
        // 仓库待上架的商品
        $goods_offline = $goods_model->getGoodsCommonOfflineCount(array(array('store_id' ,'=', session('store_id'))));
        // 违规下架的商品
        $goods_lockup = $goods_model->getGoodsCommonLockUpCount(array(array('store_id' ,'=', session('store_id'))));
        // 等待回复商品咨询
        $consult = model('consult')->getConsultCount(array('store_id' => session('store_id'), 'consult_reply' => ''));

        // 商品图片数量
        $imagecount = model('album')->getAlbumpicCount(array('store_id' => session('store_id')));

        $order_model = model('order');
        // 交易中的订单
        $progressing = $order_model->getOrderCountByID('store', session('store_id'), 'TradeCount');
        // 待付款
        $no_payment = $order_model->getOrderCountByID('store', session('store_id'), 'NewCount');
        // 待发货
        $no_delivery = $order_model->getOrderCountByID('store', session('store_id'), 'PayCount');

        $refundreturn_model = model('refundreturn');
        // 售前退款
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_type','=',1);
        $condition[] = array('order_lock','=',2);
        $condition[] = array('refund_state','<',3);
        $refund_lock = $refundreturn_model->getRefundreturnCount($condition);
        // 售后退款
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_type','=',1);
        $condition[] = array('order_lock','=',1);
        $condition[] = array('refund_state','<',3);
        $refund = $refundreturn_model->getRefundreturnCount($condition);
        // 售前退货
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_type','=',2);
        $condition[] = array('order_lock','=',2);
        $condition[] = array('refund_state','<',3);
        $return_lock = $refundreturn_model->getRefundreturnCount($condition);
        // 售后退货
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_type','=',2);
        $condition[] = array('order_lock','=',1);
        $condition[] = array('refund_state','<',3);
        $return = $refundreturn_model->getRefundreturnCount($condition);

        $condition = array();
        $condition[] = array('accused_id','=',session('store_id'));
        $condition[] = array('complain_state','between', array(10, 90));
        $complain_mod=model('complain');
        $complain = $complain_mod->getComplainCount($condition);

        //待确认的结算账单
        $bill_model = model('bill');
        $condition = array();
        $condition[] = array('ob_store_id','=',session('store_id'));
        $condition[] = array('ob_state','=',BILL_STATE_CREATE);
        $bill_confirm_count = $bill_model->getOrderbillCount($condition);

        //统计数组
        $statistics = array(
            'goodscount' => $goodscount,
            'online' => $goods_online,
            'waitverify' => $goods_waitverify,
            'verifyfail' => $goods_verifyfail,
            'offline' => $goods_offline,
            'lockup' => $goods_lockup,
            'imagecount' => $imagecount,
            'consult' => $consult,
            'progressing' => $progressing,
            'payment' => $no_payment,
            'delivery' => $no_delivery,
            'refund_lock' => $refund_lock,
            'refund' => $refund,
            'return_lock' => $return_lock,
            'return' => $return,
            'complain' => $complain,
            'bill_confirm' => $bill_confirm_count
        );
        exit(json_encode($statistics));
    }
}

?>
