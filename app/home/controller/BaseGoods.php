<?php

/*
 * 商品的类
 */

namespace app\home\controller;
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
class  BaseGoods extends BaseStore {

    protected $store_info;

    public function initialize() {
        parent::initialize();
        //输出会员信息
        $this->getMemberAndGradeInfo(false);
    }
    
    protected function getStoreInfo($store_id, $goods_info = null) {
        $store_model = model('store');
        $store_info = $store_model->getStoreOnlineInfoByID($store_id);
        if (empty($store_info)) {
            $this->error(lang('ds_store_close'));
        }
            $storejoinin_model=model('storejoinin');
                $storejoinin_info=$storejoinin_model->getOneStorejoinin(array('member_id'=>$store_info['member_id']));
                //营业执照
                if($storejoinin_info){
                    $store_info['business_licence_number_electronic']=$storejoinin_info['business_licence_number_electronic']?get_store_joinin_imageurl($storejoinin_info['business_licence_number_electronic']):'';
                }  
        $this->outputStoreInfo($store_info, $goods_info);
    }
}

?>
