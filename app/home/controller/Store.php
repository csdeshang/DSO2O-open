<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
 * 控制器
 */
class  Store extends BaseStore {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/store.lang.php');
    }


    public function index() {



        //幻灯片图片
        if ($this->store_info['store_slide'] != '' && $this->store_info['store_slide'] != ',,,,') {
            View::assign('store_slide', explode(',', $this->store_info['store_slide']));
            View::assign('store_slide_url', explode(',', $this->store_info['store_slide_url']));
        }

        $storeclass_goods = array();
        $store_id = $this->store_info['store_id'];

        //判断用户是否登录
        $member_id = session('member_id');
        $cart_list = array();
        //如果登录则获取当前店铺购物车内的商品
//        if (!empty($member_id)) {
//            $cart_model = model('cart');
//            $cart_list = $cart_model->getCartList('db', array('buyer_id' => $member_id, 'store_id' => $store_id));
//            if (!empty($cart_list)) {
//                $cart_list = ds_change_arraykey($cart_list, 'goods_id');
//            }
//        }
//        $cart_amount=0;
//        $cart_count=0;
//        foreach($cart_list as $cart){
//            $cart_amount+=$cart['goods_num']*$cart['goods_price'];
//            $cart_count+=$cart['goods_num'];
//        }
//        View::assign('cart_list',$cart_list);
//        View::assign('cart_amount',$cart_amount);
//        View::assign('cart_count',$cart_count);
        //获取当前店铺推荐的商品
        $condition = array();
        $condition[] = array('goods_commend','=',1);
        $condition[] = array('store_id','=',$store_id);
        $goods_list = $this->_getGoodsList($condition, $cart_list);
        if (!empty($goods_list)) {
            $storeclass_goods[] = array(
                'name' => '店铺推荐',
                'foods' => $goods_list,
            );
        }
        
        //获取当前店铺必点的商品
        $condition = array();
        $condition[] = array('goods_if_required','=',1);
        $condition[] = array('store_id','=',$store_id);
        $goods_list = $this->_getGoodsList($condition, $cart_list);
        if (!empty($goods_list)) {
            $storeclass_goods[] = array(
                'name' => '必点商品',
                'foods' => $goods_list,
            );
        }

        //获取店铺分类
        $condition = array();
        $condition[] = array('store_id','=',$store_id);
        $condition[] = array('storegc_parent_id','=',0);
        $storegoodsclass_model = model('storegoodsclass');
        $storegoodsclass_list = $storegoodsclass_model->getStoregoodsclassList($condition);

        if (!empty($storegoodsclass_list)) {
            foreach ($storegoodsclass_list as $key => $storegoodsclass) {
                $condition = array();
                $condition[] = array('store_id','=',$store_id);
                $condition[] = array('goods_stcids','like', '%,' . $storegoodsclass['storegc_id'] . ',%');
                $goods_list = $this->_getGoodsList($condition, $cart_list);
                if (!empty($goods_list)) {
                    $storeclass_goods[] = array(
                        'name' => $storegoodsclass['storegc_name'],
                        'foods' => $goods_list,
                    );
                }
            }
        }
        View::assign('storeclass_goods', $storeclass_goods);
        
        View::assign('page', 'index');
        return View::fetch($this->template_dir . 'index');
    }
    
    //获取店铺购物车
    public function getCartList(){
        $member_id=session('member_id');
        if (!empty($member_id)) {
            $cart_model = model('cart');
            $cart_list = $cart_model->getCartList('db', array('buyer_id' => $member_id, 'store_id' => $this->store_info['store_id']));
  
        }
        $cart_amount=0;
        $cart_count=0;
        foreach($cart_list as $cart){
            $cart_amount+=$cart['goods_num']*$cart['goods_price'];
            $cart_count+=$cart['goods_num'];
        }
        ds_json_encode(10000,'',array('cart_list'=>$cart_list,'cart_amount'=>$cart_amount,'cart_count'=>$cart_count));
    }
    
    private function _getGoodsList($condition, $cart_list) {
        $goods_model = model('goods');
        $fieldstr = "goods_id,goods_commonid,goods_name,goods_storage,goods_spec,goods_advword,evaluation_good_star,goods_salenum,goods_collect,goods_price,goods_promotion_price,goods_marketprice,goods_image,store_id,COUNT(goods_id) AS spec_count";
        $goods_list = $goods_model->getGoodsListByColorDistinct($condition, $fieldstr, 'goods_salenum desc');
        foreach ($goods_list as $key => $goods) {
            if(!$goods['goods_storage']){
                    $goods_info=$goods_model->getGoodsStorageByCommonId($goods['goods_commonid']);
                    if($goods_info){
                        $goods_list[$key]['goods_id']=$goods['goods_id']=$goods_info['goods_id'];
                        $goods_list[$key]['goods_spec']=$goods['goods_spec']=$goods_info['goods_spec'];
                        $goods_list[$key]['goods_promotion_price']=$goods['goods_promotion_price']=$goods_info['goods_promotion_price'];
                        $goods_list[$key]['goods_name']=$goods['goods_name']=$goods_info['goods_name'];
                        $goods_list[$key]['goods_image']=$goods['goods_image']=$goods_info['goods_image'];
                        $goods_list[$key]['evaluation_good_star']=$goods['evaluation_good_star']=$goods_info['evaluation_good_star'];
                        $goods_list[$key]['goods_salenum']=$goods['goods_salenum']=$goods_info['goods_salenum'];
                    }
                }
            $goods_list[$key]['goods_spec']= json_encode(unserialize($goods['goods_spec']));
            $goods_list[$key]['goods_image'] = goods_cthumb($goods['goods_image'], 240);
            //通过购物车计算出当前商品的数量
            if (isset($cart_list[$goods['goods_id']])) {
                $goods_list[$key]['count'] = $cart_list[$goods['goods_id']]['goods_num'];
                $goods_list[$key]['cart_id'] = $cart_list[$goods['goods_id']]['cart_id'];
            }
        }
        return $goods_list;
    }
    
    private function getGoodsMore($goods_list1, $goods_list2 = array()) {
        if (!empty($goods_list2)) {
            $goods_list = array_merge($goods_list1, $goods_list2);
        } else {
            $goods_list = $goods_list1;
        }
        // 商品多图
        if (!empty($goods_list)) {
            $goodsid_array = array();       // 商品id数组
            $commonid_array = array(); // 商品公共id数组
            $storeid_array = array();       // 店铺id数组
            foreach ($goods_list as $value) {
                $goodsid_array[] = $value['goods_id'];
                $commonid_array[] = $value['goods_commonid'];
                $storeid_array[] = $value['store_id'];
            }
            $goodsid_array = array_unique($goodsid_array);
            $commonid_array = array_unique($commonid_array);

            // 商品多图
            $goodsimage_more = model('goods')->getGoodsImageList(array(array('goods_commonid','in', $commonid_array)));

            foreach ($goods_list1 as $key => $value) {
                // 商品多图
                foreach ($goodsimage_more as $v) {
                    if ($value['goods_commonid'] == $v['goods_commonid'] && $value['store_id'] == $v['store_id'] && $value['color_id'] == $v['color_id']) {
                        $goods_list1[$key]['image'][] = $v;
                    }
                }
            }

            if (!empty($goods_list2)) {
                foreach ($goods_list2 as $key => $value) {
                    // 商品多图
                    foreach ($goodsimage_more as $v) {
                        if ($value['goods_commonid'] == $v['goods_commonid'] && $value['store_id'] == $v['store_id'] && $value['color_id'] == $v['color_id']) {
                            $goods_list2[$key]['image'][] = $v;
                        }
                    }
                }
            }
        }
        return array(1 => $goods_list1, 2 => $goods_list2);
    }

    public function article() {
        //判断是否为导航页面
        $storenavigation_model = model('storenavigation');
        $store_navigation_info = $storenavigation_model->getStorenavigationInfo(array('storenav_id' => intval(input('param.storenav_id'))));
        if (!empty($store_navigation_info) && is_array($store_navigation_info)) {
            View::assign('store_navigation_info', $store_navigation_info);
            return View::fetch($this->template_dir . 'article');
        }
    }

    /**
     * 全部商品
     */
    public function goods_all() {

        $condition = array();
        $condition[] = array('store_id','=',$this->store_info['store_id']);
        $inkeyword = trim(input('inkeyword'));
        if ($inkeyword != '') {
            $condition[] = array('goods_name','like', '%' . $inkeyword . '%');
        }

        // 排序
        $order = input('order');
        $order = $order == 1 ? 'asc' : 'desc';
        $key = trim(input('key'));
        switch ($key) {
            case '1':
                $order = 'goods_id ' . $order;
                break;
            case '2':
                $order = 'goods_promotion_price ' . $order;
                break;
            case '3':
                $order = 'goods_salenum ' . $order;
                break;
            case '4':
                $order = 'goods_collect ' . $order;
                break;
            case '5':
                $order = 'goods_click ' . $order;
                break;
            default:
                $order = 'goods_id desc';
                break;
        }

        //查询分类下的子分类
        $storegc_id = intval(input('storegc_id'));
        if ($storegc_id > 0) {
            $condition[] = array('goods_stcids','like', '%,' . $storegc_id . ',%');
        }

        $goods_model = model('goods');
        $fieldstr = "goods_id,goods_commonid,goods_name,goods_advword,store_id,store_name,goods_price,goods_promotion_price,goods_marketprice,goods_storage,goods_image,goods_salenum,color_id,evaluation_good_star,evaluation_count,goods_promotion_type";

        $recommended_goods_list = $goods_model->getGoodsListByColorDistinct($condition, $fieldstr, $order, 24);
        $recommended_goods_list = $this->getGoodsMore($recommended_goods_list);
        View::assign('recommended_goods_list', $recommended_goods_list[1]);
        
        /* 引用搜索相关函数 */
        require_once(base_path() . '/home/common_search.php');

        //输出分页
        View::assign('show_page', empty($recommended_goods_list[1])?'':$goods_model->page_info->render());
        $stc_class = model('storegoodsclass');
        $stc_info = $stc_class->getStoregoodsclassInfo(array('storegc_id' => $storegc_id));
        View::assign('storegc_name', $stc_info['storegc_name']);
        View::assign('page', 'index');

        return View::fetch($this->template_dir .'goods_list');
    }


    /**
     * ajax 店铺流量统计入库
     */
    public function ajax_flowstat_record() {
        $store_id = intval(input('param.store_id'));
        $goods_id = intval(input('param.goods_id'));
        $controller_param = input('param.controller_param');
        $action_param = input('param.action_param');
        $store_info = model('store')->getStoreOnlineInfoByID(session('store_id'));
        model('store')->flowstat_record($store_id,$goods_id,$controller_param,$action_param,$store_info);
    }

}
