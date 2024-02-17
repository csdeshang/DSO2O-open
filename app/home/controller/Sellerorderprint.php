<?php
/**
 * 订单打印
 */
namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class  Sellerorderprint extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerorderprint.lang.php');
    }

    /**
     * 查看订单
     */
    public function index() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods'));
        if (empty($order_info)) {
            $this->error(lang('member_printorder_ordererror'));
        }
        View::assign('order_info', $order_info);

        //卖家信息
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID($order_info['store_id']);
        if (!empty($store_info['store_logo'])) {
           if (file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id']. DIRECTORY_SEPARATOR .$store_info['store_logo'])) {
                $store_info['store_logo'] = UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id']. DIRECTORY_SEPARATOR .$store_info['store_logo'];
            } else {
                $store_info['store_logo'] = '';
            }
        }
        if (!empty($store_info['store_seal'])) {
            if (file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_seal'])) {
                $store_info['store_seal'] = UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_seal'];
            } else {
                $store_info['store_seal'] = '';
            }
        }
        View::assign('store_info', $store_info);

        //订单商品
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $goods_new_list = array();
        $goods_all_num = 0;
        $goods_total_price = 0;
        if (isset($order_info['extend_order_goods']) && !empty($order_info['extend_order_goods'])) {
            $i = 1;
            foreach ($order_info['extend_order_goods'] as $k => $v) {
                $v['goods_name'] = str_cut($v['goods_name'], 100);
                $goods_all_num += $v['goods_num'];
                $v['goods_all_price'] = ds_price_format($v['goods_num'] * $v['goods_price']);
                $goods_total_price += $v['goods_all_price'];
                $goods_new_list[ceil($i / 15)][$i] = $v;
                $i++;
            }
        }
        //优惠金额
        $promotion_amount = $goods_total_price - $order_info['goods_amount'];
        //运费
        $order_info['shipping_fee'] = $order_info['shipping_fee'];
        View::assign('promotion_amount', $promotion_amount);
        View::assign('goods_all_num', $goods_all_num);
        View::assign('goods_total_price', ds_price_format($goods_total_price));
        View::assign('goods_list', $goods_new_list);
        
        return View::fetch($this->template_dir.'index');
    }

}

?>
