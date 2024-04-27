<?php

/*
 * 卖家相关控制中心
 */

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
class  BaseSeller extends BaseMall {

    //店铺信息
    protected $store_info = array();

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/basemember.lang.php');
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/baseseller.lang.php');
        //卖家中心模板路径
        $this->template_dir = 'default/seller/' . strtolower(request()->controller()) . '/';
        if (request()->controller() != 'Sellerlogin') {
            if (!session('member_id')) {
                $this->redirect('home/Sellerlogin/login');
            }
            if (!session('seller_id')) {
                $this->redirect('home/Sellerlogin/login');
            }

            // 验证店铺是否存在
            $store_model = model('store');
            $this->store_info = $store_model->getStoreInfoByID(session('store_id'));
            if (empty($this->store_info)) {
                $this->redirect('home/Sellerlogin/login');
            }

            // 店铺关闭标志
            if (intval($this->store_info['store_state']) === 0) {
                View::assign('store_closed', true);
                View::assign('store_close_info', $this->store_info['store_close_info']);
            }

            // 店铺等级
                $store_grade = rkcache('storegrade', true);
                $this->store_grade = @$store_grade[$this->store_info['grade_id']];
            if (session('seller_is_admin') !== 1 && request()->controller() !== 'Seller' && request()->controller() !== 'Sellerlogin') {
                if (!in_array(request()->controller(), session('seller_limits'))) {
                    $this->error(lang('have_no_legalpower'), 'Seller/index');
                }
            }
        }
    }

    /**
     * 记录卖家日志
     *
     * @param $content 日志内容
     * @param $state 1成功 0失败
     */
    protected function recordSellerlog($content = '', $state = 1) {
        $seller_info = array();
        $seller_info['sellerlog_content'] = $content;
        $seller_info['sellerlog_time'] = TIMESTAMP;
        $seller_info['sellerlog_seller_id'] = session('seller_id');
        $seller_info['sellerlog_seller_name'] = session('seller_name');
        $seller_info['sellerlog_store_id'] = session('store_id');
        $seller_info['sellerlog_seller_ip'] = request()->ip();
        $seller_info['sellerlog_url'] = 'home/' . request()->controller() . '/' . request()->action();
        $seller_info['sellerlog_state'] = $state;
        $sellerlog_model = model('sellerlog');
        $sellerlog_model->addSellerlog($seller_info);
    }

    /**
     * 记录店铺费用
     *
     * @param $storecost_price 费用金额
     * @param $storecost_remark 费用备注
     */
    protected function recordStorecost($storecost_price, $storecost_remark) {

        
        Db::startTrans();
        try {
            $storecost_model = model('storecost');
            $param = array();
            $param['storecost_store_id'] = session('store_id');
            $param['storecost_seller_id'] = session('seller_id');
            $param['storecost_price'] = $storecost_price;
            $param['storecost_remark'] = $storecost_remark;
            $param['storecost_state'] = 0;
            $param['storecost_time'] = TIMESTAMP;
            $storecost_model->addStorecost($param);
            $storemoneylog_model = model('storemoneylog');
            //扣除店铺费用
            $data = array(
                'store_id' => session('store_id'),
                'storemoneylog_type' => $storemoneylog_model::TYPE_ORDER_SUCCESS,
                'storemoneylog_state' => $storemoneylog_model::STATE_VALID,
                'storemoneylog_add_time' => TIMESTAMP,
                'store_avaliable_money' => -$storecost_price,
                'storemoneylog_desc' => $storecost_remark,
            );
            $storemoneylog_model->changeStoremoney($data);
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
        }


        // 发送店铺消息
        $param = array();
        $param['code'] = 'store_cost';
        $param['store_id'] = session('store_id');
        $param['ali_param'] = array(
            'price' => $storecost_price,
            'seller_name' => session('seller_name'),
            'remark' => $storecost_remark
        );
        $param['ten_param'] = array(
            $storecost_price,
            session('seller_name'),
            $storecost_remark
        );
        $param['param'] = $param['ali_param'];
        //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_store_site_url').'/pages/seller/cost/CostList',
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $storecost_price,
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => date('Y-m-d H:i'),
                            "color" => "#333"
                        )
                    ),
                );
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize($param)));
    }
    

    /**
     * 添加到任务队列
     *
     * @param array $goods_array
     * @param boolean $ifdel 是否删除以原记录
     */
    protected function addcron($data = array(), $ifdel = false) {
        $cron_model = model('cron');
        if (isset($data[0])) { // 批量插入
            $where = array();
            foreach ($data as $k => $v) {
                // 删除原纪录条件
                if ($ifdel) {
                    $where[] = '(cron_type = "' . $data['cron_type'] . '" and cron_value = "' . $data['cron_value'] . '")';
                }
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(implode(',', $where));
            }
            $cron_model->addCronAll($data);
        } else { // 单条插入
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(array('cron_type' => $data['cron_type'], 'cron_value' => $data['cron_value']));
            }
            $cron_model->addCron($data);
        }
    }

    /**
     *    当前选中的栏目
     */
    protected function setSellerCurItem($curitem = '') {
        View::assign('seller_item', $this->getSellerItemList());
        View::assign('curitem', $curitem);
    }

    /**
     *    当前选中的子菜单
     */
    protected function setSellerCurMenu($cursubmenu = '') {
        $seller_menu = self::getSellerMenuList($this->store_info);
        View::assign('seller_menu', $seller_menu);
        $curmenu = '';
        foreach ($seller_menu as $key => $menu) {
            foreach ($menu['submenu'] as $subkey => $submenu) {
                if ($submenu['name'] == $cursubmenu) {
                    $curmenu = $menu['name'];
                }
            }
        }
        //当前一级菜单
        View::assign('curmenu', $curmenu);
        //当前二级菜单
        View::assign('cursubmenu', $cursubmenu);
    }

    /*
     * 获取卖家栏目列表,针对控制器下的栏目
     */

    protected function getSellerItemList() {
        return array();
    }

    /*
     * 获取卖家菜单列表
     */

    public static function getSellerMenuList($store_info=false) {
        //controller  注意第一个字母要大写
        $menu_list = array(
            'sellergoods' =>
            array(
                'ico'=>'&#xe732;',
                'name' => 'sellergoods',
                'text' => lang('site_search_goods'),
                'url' => url('Sellergoodsonline/index'),
                'submenu' => array(
                    array('name' => 'sellergoodsadd', 'text' => lang('goods_released'), 'controller' => 'Sellergoodsadd', 'url' => url('Sellergoodsadd/index'),),
                    array('name' => 'sellergoodsonline', 'text' => lang('goods_on_sale'), 'controller' => 'Sellergoodsonline', 'url' => url('Sellergoodsonline/index'),),
                    array('name' => 'sellergoodsoffline', 'text' => lang('warehouse_goods'), 'controller' => 'Sellergoodsoffline', 'url' => url('Sellergoodsoffline/index'),),
                    array('name' => 'sellerplate', 'text' => lang('associated_format'), 'controller' => 'Sellerplate', 'url' => url('Sellerplate/index'),),
                    array('name' => 'sellerspec', 'text' => lang('product_specifications'), 'controller' => 'Sellerspec', 'url' => url('Sellerspec/index'),),
                    array('name' => 'selleralbum', 'text' => lang('image_space'), 'controller' => 'Selleralbum', 'url' => url('Selleralbum/index'),),
                )
            ),
            'sellerorder' =>
            array(
                'ico'=>'&#xe71f;',
                'name' => 'sellerorder',
                'text' => lang('pointsorderdesc_1'),
                'url' => url('Sellerorder/index'),
                'submenu' => array(
                    array('name' => 'sellerorder', 'text' => lang('order_physical_transaction'), 'controller' => 'Sellerorder', 'url' => url('Sellerorder/index'),),
                    array('name' => 'sellerevaluate', 'text' => lang('evaluation_management'), 'controller' => 'Sellerevaluate', 'url' => url('Sellerevaluate/index'),),
                )
            ),
            'Sellerpromotionxianshi' =>
            array(
                'ico'=>'&#xe704;',
                'name' => 'Sellerpromotionxianshi',
                'text' => lang('sales_promotion'),
                'url' => url('Sellerpromotionxianshi/index'),
                'submenu' => array(
                    array('name' => 'Sellerpromotionxianshi', 'text' => lang('time_discount'), 'controller' => 'Sellerpromotionxianshi', 'url' => url('Sellerpromotionxianshi/index'),),
                    array('name' => 'Sellerpromotionmansong', 'text' => lang('free_on_delivery'), 'controller' => 'Sellerpromotionmansong', 'url' => url('Sellerpromotionmansong/index'),),
                    array('name' => 'Sellervoucher', 'text' => lang('voucher_management'), 'controller' => 'Sellervoucher', 'url' => url('Sellervoucher/templatelist'),),
                )
            ),
            'seller' =>
            array(
                'ico'=>'&#xe663;',
                'name' => 'seller',
                'text' => lang('site_search_store'),
                'url' => url('Seller/index'),
                'submenu' => array(
                    array('name' => 'seller_index', 'text' => lang('store_overview'), 'controller' => 'Seller', 'url' => url('Seller/index'),),
                    array('name' => 'seller_o2o', 'text' => lang('baseseller_store_o2o'), 'controller' => 'seller_o2o', 'url' => url('seller_o2o/index'),),
                    array('name' => 'seller_setting', 'text' => lang('store_setup'), 'controller' => 'Sellersetting', 'url' => url('Sellersetting/setting'),),
                    array('name' => 'seller_navigation', 'text' => lang('store_navigation'), 'controller' => 'Sellernavigation', 'url' => url('Sellernavigation/index'),),
                    array('name' => 'sellergoodsclass', 'text' => lang('store_classification'), 'controller' => 'Sellergoodsclass', 'url' => url('Sellergoodsclass/index'),),
                    array('name' => 'seller_brand', 'text' => lang('brand_application'), 'controller' => 'Sellerbrand', 'url' => url('Sellerbrand/index'),),
                )
            ),
            'sellerconsult' =>
            array(
                'ico'=>'&#xe6ab;',
                'name' => 'sellerconsult',
                'text' => lang('after_sales_service'),
                'url' => url('Sellerconsult/index'),
                'submenu' => array(
                    array('name' => 'seller_consult', 'text' => lang('consulting_management'), 'controller' => 'Sellerconsult', 'url' => url('Sellerconsult/index'),),
                    array('name' => 'seller_complain', 'text' => lang('complaint_record'), 'controller' => 'Sellercomplain', 'url' => url('Sellercomplain/index'),),
                    array('name' => 'seller_refund', 'text' => lang('refund_paragraph'), 'controller' => 'Sellerrefund', 'url' => url('Sellerrefund/index'),),
                )
            ),
            'sellerstatistics' =>
            array(
                'ico'=>'&#xe6a3;',
                'name' => 'sellerstatistics',
                'text' => lang('statistics'),
                'url' => url('Statisticsgeneral/index'),
                'submenu' => array(
                    array('name' => 'Statisticsgeneral', 'text' => lang('store_overview'), 'controller' => 'Statisticsgeneral', 'url' => url('Statisticsgeneral/index'),),
                    array('name' => 'Statisticsgoods', 'text' => lang('commodity_analysis'), 'controller' => 'Statisticsgoods', 'url' => url('Statisticsgoods/index'),),
                    array('name' => 'Statisticssale', 'text' => lang('operational_report'), 'controller' => 'Statisticssale', 'url' => url('Statisticssale/index'),),
                    array('name' => 'Statisticsindustry', 'text' => lang('industry_analysis'), 'controller' => 'Statisticsindustry', 'url' => url('Statisticsindustry/index'),),
                    array('name' => 'Statisticsflow', 'text' => lang('traffic_statistics'), 'controller' => 'Statisticsflow', 'url' => url('Statisticsflow/index'),),
                )
            ),
            'sellercallcenter' =>
            array(
                'ico'=>'&#xe61c;',
                'name' => 'sellercallcenter',
                'text' => lang('news_service'),
                'url' => url('Sellercallcenter/index'),
                'submenu' => array(
                    array('name' => 'Sellercallcenter', 'text' => lang('setting_service'), 'controller' => 'Sellercallcenter', 'url' => url('Sellercallcenter/index'),),
                    array('name' => 'Sellermsg', 'text' => lang('system_message'), 'controller' => 'Sellermsg', 'url' => url('Sellermsg/index'),),
                )
            ),
            'selleraccount' =>
            array(
                'ico'=>'&#xe702;',
                'name' => 'selleraccount',
                'text' => lang('account'),
                'url' => url('Selleraccount/account_list'),
                'submenu' => array(
                    array('name' => 'selleraccount', 'text' => lang('account_list'), 'controller' => 'Selleraccount', 'url' => url('Selleraccount/account_list'),),
                    array('name' => 'selleraccountgroup', 'text' => lang('account_group'), 'controller' => 'Selleraccountgroup', 'url' => url('Selleraccountgroup/group_list'),),
                    array('name' => 'sellerlog', 'text' => lang('account_log'), 'controller' => 'Sellerlog', 'url' => url('Sellerlog/log_list'),),
                )
            ),
            'seller_o2o_distributor' =>
            array(
                'ico'=>'&#xe6d5;',
                'name' => 'seller_o2o_distributor',
                'text' => lang('baseseller_o2o_distributor'),
                'url' => url('seller_o2o_distributor/index'),
                'submenu' => array(
                    array('name' => 'seller_o2o_distributor', 'text' => lang('baseseller_o2o_distributor_magage'), 'controller' => 'seller_o2o_distributor', 'url' => url('seller_o2o_distributor/index'),),
                    array('name' => 'seller_o2o_complaint', 'text' => lang('baseseller_o2o_complaint'), 'controller' => 'seller_o2o_complaint', 'url' => url('seller_o2o_complaint/index'),),
                )
            ),
        );
            $menu_list['seller']['submenu'] = array_merge(array(array('name' => 'seller_money', 'text' => lang('store_money'), 'action' => null, 'controller' => 'Sellermoney', 'url' => (string) url('Sellermoney/index'),), array('name' => 'seller_deposit', 'text' => lang('store_deposit'), 'action' => null, 'controller' => 'Sellerdeposit', 'url' => (string) url('Sellerdeposit/index'),),array('name' => 'sellerinfo', 'text' => lang('store_information'), 'action' => null, 'controller' => 'Sellerinfo', 'url' => (string) url('Sellerinfo/index'),),), $menu_list['seller']['submenu']);
            $menu_list['selleraccount']['submenu'] = array_merge(array(array('name' => 'sellercost', 'text' => lang('store_consumption'), 'action' => null, 'controller' => 'Sellercost', 'url' => (string) url('Sellercost/cost_list'),)), $menu_list['selleraccount']['submenu']);
        if (config('ds_config.inviter_open')) {
            $menu_list['sellerinviter'] = array(
                'ico'=>'&#xe6ed;',
                'name' => 'sellerinviter',
                'text' => lang('distribution'),
                'url' => url('Sellerinviter/goods_list'),
                'submenu' => array(
                    array('name' => 'sellerinviter_goods', 'text' => lang('distribution_management'), 'controller' => 'Sellerinviter', 'url' => url('Sellerinviter/goods_list'),),
                    array('name' => 'sellerinviter_order', 'text' => lang('distribution_earnings'), 'controller' => 'Sellerinviter', 'url' => url('Sellerinviter/order_list'),),
                )
            );
        }
        $store_model = model('store');
        if(config('ds_config.dada_open') && $store_model->isO2oSupport($store_info) && $store_info['store_o2o_distribute_type']==0){
            $menu_list['seller']['submenu'] = array_merge(array(array('name' => 'seller_dada_shop', 'text' => lang('baseseller_dada_shop'), 'controller' => 'seller_dada_shop', 'url' => url('seller_dada_shop/index'),)), $menu_list['seller']['submenu']);
        }
        if(config('ds_config.yly_open')){
            $menu_list['seller']['submenu'] = array_merge(array(array('name' => 'seller_o2o_printer', 'text' => lang('baseseller_o2o_printer'), 'controller' => 'seller_o2o_printer', 'url' => url('seller_o2o_printer/index'),)), $menu_list['seller']['submenu']);
        }
        return $menu_list;
    }


}

?>
