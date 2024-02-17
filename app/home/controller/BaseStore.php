<?php

/*
 * 店铺的类
 */

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
class  BaseStore extends BaseHome {
    protected $store_info;
    
    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/baseseller.lang.php');
        //店铺模板路径
        $this->template_dir = 'default/store/default/' . strtolower(request()->controller()) . '/';
        View::assign('store_theme', 'default');
        //当方法为 store 进行执行
        if (request()->controller() == 'Store') {

            //输出会员信息
            $this->getMemberAndGradeInfo(false);

            $store_id = intval(input('param.store_id'));
            if ($store_id <= 0) {
                $this->error(lang('ds_store_close'));
            }

            $store_model = model('store');
            $store_info = $store_model->getStoreOnlineInfoByID($store_id);
            if (empty($store_info)) {
                $this->error(lang('ds_store_close'));
            } else {
                $this->store_info = $store_info;
            }
            $storejoinin_model=model('storejoinin');
            if(!$store_info['is_platform_store']){
                $storejoinin_info=$storejoinin_model->getOneStorejoinin(array('member_id'=>$store_info['member_id']));
                //营业执照
                if($storejoinin_info){
                    $this->store_info['business_licence_number_electronic']=$storejoinin_info['business_licence_number_electronic']?get_store_joinin_imageurl($storejoinin_info['business_licence_number_electronic']):'';
                }  
            }

            $this->outputStoreInfo($this->store_info);
            $this->getStorenavigation($store_id);
            $this->outputSeoInfo($this->store_info);
        }
    }
    
    

    /**
     * 检查店铺开启状态
     *
     * @param int $store_id 店铺编号
     * @param string $msg 警告信息
     */
    protected function outputStoreInfo($store_info) {
            $store_model = model('store');

            //店铺分类
            $goodsclass_model = model('storegoodsclass');
            $goods_class_list = $goodsclass_model->getShowTreeList($store_info['store_id']);
            View::assign('goods_class_list', $goods_class_list);

            //热销排行
            $hot_sales = $store_model->getHotSalesList($store_info['store_id'], 5);
            View::assign('hot_sales', $hot_sales);

            //收藏排行
            $hot_collect = $store_model->getHotCollectList($store_info['store_id'], 5);
            View::assign('hot_collect', $hot_collect);

            // 如果已登录 判断该店铺是否已被收藏
            if (session('member_id')) {
                $member_id=intval(session('member_id'));
                $c = (int) model('favorites')->getStoreFavoritesCountByStoreId($store_info['store_id'], $member_id);
                $store_info['is_favorate'] = $c > 0;
            } else {
                $store_info['is_favorate'] = false;
            }
        
        View::assign('store_info', $store_info);
        View::assign('page_title', $store_info['store_name']);
    }

    protected function getStorenavigation($store_id) {
        $storenavigation_model = model('storenavigation');
        $store_navigation_list = $storenavigation_model->getStorenavigationList(array('storenav_store_id' => $store_id));
        View::assign('store_navigation_list', $store_navigation_list);
    }

    protected function outputSeoInfo($store_info) {
        $seo_param = array();
        $seo_param['shopname'] = $store_info['store_name'];
        $seo_param['key'] = $store_info['store_keywords'];
        $seo_param['description'] = $store_info['store_description'];
        //SEO 设置
        $this->_assign_seo(model('seo')->type('shop')->param($seo_param)->show());
    }
    
    

}

?>
