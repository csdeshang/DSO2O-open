<?php

namespace app\admin\controller;
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
class  Voucher extends AdminControl {

    private $quotastate_arr;
    private $templatestate_arr;

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/voucher.lang.php');
        if (config('ds_config.voucher_allow') != 1 || config('ds_config.points_isuse') != 1) {
            $this->error(lang('admin_voucher_unavailable'), 'operation/setting');
        }
        $this->quotastate_arr = array(
            'activity' => array(1, lang('admin_voucher_quotastate_activity')),
            'cancel' => array(2, lang('admin_voucher_quotastate_cancel')),
            'expire' => array(3, lang('admin_voucher_quotastate_expire'))
        );
        //代金券模板状态
        $this->templatestate_arr = array(
            'usable' => array(1, lang('admin_voucher_templatestate_usable')),
            'disabled' => array(2, lang('admin_voucher_templatestate_disabled'))
        );
        View::assign('quotastate_arr', $this->quotastate_arr);
        View::assign('templatestate_arr', $this->templatestate_arr);
    }

    /**
     * 代金券设置
     */
    public function setting() {
        $setting_model = model('config');
        if (request()->isPost()) {
            $data = [
                'promotion_voucher_price' => input('post.promotion_voucher_price'),
                'promotion_voucher_storetimes_limit' => input('post.promotion_voucher_storetimes_limit'),
                'promotion_voucher_buyertimes_limit' => input('post.promotion_voucher_buyertimes_limit')
            ];

            $voucher_validate = ds_validate('voucher');
            if (!$voucher_validate->scene('setting')->check($data)){
                $this->error($voucher_validate->getError());
            }
            //每月代金劵软件服务单价
            $promotion_voucher_price = intval(input('post.promotion_voucher_price'));
            if ($promotion_voucher_price < 0) {
                $this->error(lang('param_error'));
            }
            //每月店铺可以发布的代金劵数量
            $promotion_voucher_storetimes_limit = intval(input('post.promotion_voucher_storetimes_limit'));
            if ($promotion_voucher_storetimes_limit <= 0) {
                $promotion_voucher_storetimes_limit = 20;
            }
            //买家可以领取的代金劵总数
            $promotion_voucher_buyertimes_limit = intval(input('post.promotion_voucher_buyertimes_limit'));
            if ($promotion_voucher_buyertimes_limit <= 0) {
                $promotion_voucher_buyertimes_limit = 5;
            }
            $update_array = array();
            $update_array['promotion_voucher_price'] = $promotion_voucher_price;
            $update_array['voucher_storetimes_limit'] = $promotion_voucher_storetimes_limit;
            $update_array['voucher_buyertimes_limit'] = $promotion_voucher_buyertimes_limit;
            $result = $setting_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('admin_voucher_setting') . lang('ds_voucher_price_manage'));
                dsLayerOpenSuccess(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $setting = rkcache('config', true);
            View::assign('setting', $setting);
            return View::fetch();
        }
    }

    /*
     * 代金券面额列表
     */

    public function pricelist() {
        //获得代金券金额列表
        $voucher_model = model('voucher');
        $voucherprice_list = $voucher_model->getVoucherpriceList(10,'voucherprice asc');
        View::assign('voucherprice_list', $voucherprice_list);
        View::assign('show_page', $voucher_model->page_info->render());
        $this->setAdminCurItem('pricelist');
        return View::fetch();
    }

    /*
     * 添加代金券面额页面
     */

    public function priceadd() {
        if (request()->isPost()) {
            $voucher_model = model('voucher');
            $data = [
                'voucher_price' => input('post.voucher_price'),
                'voucher_price_describe' => input('post.voucher_price_describe'),
                'voucher_points' => input('post.voucher_points')
            ];
            $voucher_validate = ds_validate('voucher');
            if (!$voucher_validate->scene('priceadd')->check($data)){
                $this->error($voucher_validate->getError());
            }

            //验证面额是否存在
            $voucher_price = intval(input('post.voucher_price'));
            $voucher_points = intval(input('post.voucher_points'));
            $voucherprice_info = $voucher_model->getOneVoucherprice(array('voucherprice' => $voucher_price));
            if (!empty($voucherprice_info)) {
                $this->error(lang('admin_voucher_price_exist'));
            }
                //保存
                $insert_arr = array(
                    'voucherprice_describe' => trim(input('post.voucher_price_describe')),
                    'voucherprice' => $voucher_price, 'voucherprice_defaultpoints' => $voucher_points,
                );
                $rs = $voucher_model->addVoucherprice($insert_arr);
                if ($rs) {
                    $this->log(lang('ds_add') . lang('admin_voucher_priceadd') . '[' . input('post.voucher_price') . ']');
                    dsLayerOpenSuccess(lang('ds_common_save_succ'),url('voucher/pricelist'));
                } else {
                    $this->error(lang('ds_common_save_fail'), 'voucher/priceadd');
                }
            }
         else {
            return View::fetch();
        }
    }

    /*
     * 编辑代金券面额
     */

    public function priceedit() {
        $id = intval(input('param.priceid'));
        if ($id <= 0) {
            $this->error(lang('param_error'), 'voucher/pricelist');
        }
        if (request()->isPost()) {
            $data = [
                'voucher_price' => input('post.voucher_price'),
                'voucher_price_describe' => input('post.voucher_price_describe'),
                'voucher_points' => input('post.voucher_points')
            ];
            $voucher_validate = ds_validate('voucher');
            if (!$voucher_validate->scene('priceedit')->check($data)){
                $this->error($voucher_validate->getError());
            }
            //验证面额是否存在
            $voucher_price = intval(input('post.voucher_price'));
            $voucher_points = intval(input('post.voucher_points'));
            $voucher_model = model('voucher');
            $where = array();
            $where[]=array('voucherprice','=',$voucher_price);
            $where[]=array('voucherprice_id','<>', $id);
            $voucherprice_info = $voucher_model->getOneVoucherprice($where);
            if (!empty($voucherprice_info)) {
                $this->error(lang('admin_voucher_price_exist'));
            }
                $update_arr = array();
                $update_arr['voucherprice_describe'] = trim(input('post.voucher_price_describe'));
                $update_arr['voucherprice'] = $voucher_price;
                $update_arr['voucherprice_defaultpoints'] = $voucher_points;
                $rs = $voucher_model->editVoucherprice(array('voucherprice_id' => $id),$update_arr);
                if ($rs>=0) {
                    $this->log(lang('ds_edit') . lang('admin_voucher_priceadd') . '[' . input('post.voucher_price') . ']');
                    dsLayerOpenSuccess(lang('ds_common_save_succ'),url('voucher/pricelist'));
                } else {
                    $this->error(lang('ds_common_save_fail'), 'voucher/pricelist');
                }
            }
        else {
            $voucher_model = model('voucher');
            $voucherprice_info = $voucher_model->getOneVoucherprice(array('voucherprice_id' => $id));
            if (empty($voucherprice_info)) {
                $this->error(lang('param_error'), 'voucher/pricelist');
            }
            View::assign('info', $voucherprice_info);
            return View::fetch('priceadd');
        }
    }

    /*
     * 删除代金券面额
     */

    public function pricedrop() {
        $voucher_price_id = trim(input('param.voucher_price_id'));
        if (empty($voucher_price_id)) {
            $this->error(lang('param_error'), 'voucher/pricelist');
        }
        $voucher_model = model('voucher');
        $condition = array();
        $condition[]=array('voucherprice_id','in', $voucher_price_id);
        $rs = $voucher_model->delVoucherprice($condition);
        if ($rs) {
            $this->log(lang('ds_del') . lang('admin_voucher_priceadd') . '[ID:' . $voucher_price_id . ']');
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 套餐管理
     * */
    public function quotalist() {

        //更新过期套餐的状态
        $time = TIMESTAMP;
        $voucher_model = model('voucher');
        $condition = array();
        $condition[]=array('voucherquota_endtime','<', $time);
        $condition[]=array('voucherquota_state','=',$this->quotastate_arr['activity'][0]);
        $update = array();
        $update['voucherquota_state'] = $this->quotastate_arr['expire'][0];
        $voucher_model->editVoucherquota($condition,$update);

        $param = array();
        if (trim(input('param.store_name'))) {
            $param[] = array('voucherquota_storename','like', "%{input('param.store_name')}%");
        }
        $state = intval(input('param.state'));
        if ($state) {
            $param[] = array('voucherquota_state','=',$state);
        }
        $voucherquota_list = $voucher_model->getVoucherquotaList($param,10,'voucherquota_id desc');
        View::assign('show_page', $voucher_model->page_info->render());
        View::assign('voucherquota_list', $voucherquota_list);
        $this->setAdminCurItem('quotalist');
        return View::fetch();
    }

    /**
     * 代金券列表
     */
    public function index() {
        $param = array();
        if (trim(input('param.store_name'))) {
            $param[] = array('vouchertemplate_storename','like', "%{input('param.store_name')}%");
        }
        if (trim(input('param.sdate'))) {
            $sdate = strtotime(input('param.sdate'));
            $param[] = array('vouchertemplate_adddate','>=', $sdate);
        }
        if (trim(input('param.edate'))) {
            $edate = strtotime(input('param.edate'))+86399;
            $param[] = array('vouchertemplate_adddate','<=', $edate);
        }
        $state = intval(input('param.state'));
        if ($state) {
            $param[]=array('vouchertemplate_state','=',$state);
        }
        if (input('param.recommend') === '1') {
            $param[]=array('vouchertemplate_recommend','=',1);
        } elseif (input('param.recommend') === '0') {
            $param[]=array('vouchertemplate_recommend','=',0);
        }
        $voucher_model = model('voucher');
        $vouchertemplate_list = $voucher_model->getVouchertemplateList($param,'','',10,'vouchertemplate_state asc,vouchertemplate_id desc');
        View::assign('show_page', $voucher_model->page_info->render());

        View::assign('vouchertemplate_list', $vouchertemplate_list);

        // 输出自营店铺IDS
        View::assign('flippedOwnShopIds', array_flip(model('store')->getOwnShopIds()));
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /*
     * 代金券模版编辑
     */

    public function templateedit() {
        $t_id = intval(input('param.tid'));
        if ($t_id <= 0) {
            $t_id = intval(input('post.tid'));
        }
        if ($t_id <= 0) {
            $this->error(lang('param_error'), 'voucher/index');
        }
        //查询模板信息
        $param = array();
        $param['vouchertemplate_id'] = $t_id;
        $voucher_model = model('voucher');
        $t_info = $voucher_model->getVouchertemplateInfo($param);
        if (empty($t_info)) {
            $this->error(lang('param_error'), 'voucher/index');
        }
        if (request()->isPost()) {
            $points = intval(input('post.points'));
            if ($points < 0) {
                $this->error(lang('admin_voucher_template_points_error'));
            }
            $update_arr = array();
            $update_arr['vouchertemplate_points'] = $points;
            $update_arr['vouchertemplate_state'] = intval(input('post.tstate')) == $this->templatestate_arr['usable'][0] ? $this->templatestate_arr['usable'][0] : $this->templatestate_arr['disabled'][0];
            $update_arr['vouchertemplate_recommend'] = intval(input('post.recommend')) == 1 ? 1 : 0;
            $condition = array();
            $condition[] = array('vouchertemplate_id','=',$t_info['vouchertemplate_id']);
            $rs = $voucher_model->editVouchertemplate($condition,$update_arr);
            if ($rs) {
                $this->log(lang('ds_edit') . lang('ds_voucher_price_manage') . lang('admin_voucher_styletemplate') . '[ID:' . $t_id . ']');
                $this->success(lang('ds_common_save_succ'), 'voucher/index');
            } else {
                $this->error(lang('ds_common_save_fail'), 'voucher/index');
            }
        } else {
            //查询店铺分类
            $store_class = rkcache('storeclass', true);
            View::assign('store_class', $store_class);

            View::assign('t_info', $t_info);
            $this->setAdminCurItem('templateedit');
            return View::fetch();
        }
    }

    /**
     * ajax操作
     */
    public function ajax() {
        $voucher_model = model('voucher');
        switch (input('param.branch')) {
            case 'vouchertemplate_recommend':
                $voucher_model->editVouchertemplate(array('vouchertemplate_id' => intval(input('param.id'))), array(input('param.column') => intval(input('param.value'))));
                $logtext = '';
                if (intval(input('param.value')) == 1) {//推荐代金券
                    $logtext = '推荐代金券';
                } else {
                    $logtext = '取消推荐代金券';
                }
                $this->log($logtext . '[ID:' . intval(input('param.id')) . ']', 1);
                echo 'true';
                exit;
                break;
        }
    }

    /**
     * 页面内导航菜单
     * @param string $menu_key 当前导航的menu_key
     * @param array $array 附加菜单
     * @return
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('admin_voucher_template_manage'),
                'url' => url('Voucher/index')
            ), array(
                'name' => 'quotalist',
                'text' => lang('admin_voucher_quota_manage'),
                'url' => url('Voucher/quotalist')
            ), array(
                'name' => 'pricelist',
                'text' => lang('admin_voucher_pricemanage'),
                'url' => url('Voucher/pricelist')
            ), array(
                'name' => 'priceadd',
                'text' => lang('admin_voucher_priceadd'),
                'url' => "javascript:dsLayerOpen('".url('Voucher/priceadd')."','".lang('admin_voucher_priceadd')."')"
            ), array(
                'name' => 'setting',
                'text' => lang('admin_voucher_setting'),
                'url' => "javascript:dsLayerOpen('".url('Voucher/setting')."','".lang('admin_voucher_setting')."')"
            ),
        );

        if (request()->action() == 'templateedit') {
            $menu_array = array(
                array(
                    'name' => 'index',
                    'text' => lang('admin_voucher_template_manage'),
                    'url' => url('Voucher/index')
                ), array(
                    'name' => 'templateedit',
                    'text' => lang('admin_voucher_template_edit'),
                    'url' => ''
                )
            );
        }
        return $menu_array;
    }

}
