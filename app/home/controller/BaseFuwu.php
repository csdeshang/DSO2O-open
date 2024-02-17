<?php

/*
 * 服务相关控制中心
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
class  BaseFuwu extends BaseMall {

    //店铺信息
    protected $o2o_fuwu_organization_info = array();

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/basemember.lang.php');
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/basefuwu.lang.php');
        //卖家中心模板路径
        $this->template_dir = 'default/fuwu/' . strtolower(request()->controller()) . '/';
        if (request()->controller() != 'FuwuManageLogin' && request()->controller() != 'FuwuManageRegister') {

            if (!session('o2o_fuwu_account_id')) {
                $this->redirect('home/FuwuManageLogin/login');
            }
            if (!session('o2o_fuwu_organization_id')) {
                $this->redirect('home/FuwuManageLogin/login');
            }
            $o2o_fuwu_account_info=model('o2o_fuwu_account')->getO2oFuwuAccountInfo(array('o2o_fuwu_account_id'=>session('o2o_fuwu_account_id')));
            if(!$o2o_fuwu_account_info){
                $this->error('服务机构账号不存在');
            }
            $this->o2o_fuwu_account_info = $o2o_fuwu_account_info;
            // 验证服务机构是否存在
            $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
            $this->o2o_fuwu_organization_info = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id'=>session('o2o_fuwu_organization_id')));
            if (empty($this->o2o_fuwu_organization_info)) {
                $this->redirect('home/FuwuManageLogin/login');
            }

            // 判断是否是已开启的服务机构
            if (!in_array($this->o2o_fuwu_organization_info['o2o_fuwu_organization_state'],array(1,2,3))) {
                $this->error(lang('base_fuwu_state_notice_text')[$this->o2o_fuwu_organization_info['o2o_fuwu_organization_state']]);
            }
            View::assign('o2o_fuwu_organization_info',$this->o2o_fuwu_organization_info);
        }
    }


    /**
     *    当前选中的栏目
     */
    protected function setFuwuCurItem($curitem = '') {
        View::assign('fuwu_item', $this->getFuwuItemList());
        View::assign('curitem', $curitem);
    }

    /**
     *    当前选中的子菜单
     */
    protected function setFuwuCurMenu($cursubmenu = '') {
        $fuwu_menu = $this->getFuwuMenuList();
        View::assign('fuwu_menu', $fuwu_menu);
        $curmenu = '';
        foreach ($fuwu_menu as $key => $menu) {
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

    protected function getFuwuItemList() {
        return array();
    }

    /*
     * 获取卖家菜单列表
     */

    private function getFuwuMenuList() {
        //controller  注意第一个字母要大写
        $menu_list = array(

            'fuwu_setting' =>
            array(
                'ico'=>'&#xe734;',
                'name' => 'fuwu_setting',
                'text' => lang('base_fuwu_setting'),
                'url' => url('FuwuManageInfo/base'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_info_base', 'text' => lang('base_fuwu_manage_info_base'), 'controller' => 'FuwuManageInfo', 'url' => url('FuwuManageInfo/base'),),
                    array('name' => 'fuwu_manage_info_operate', 'text' => lang('base_fuwu_manage_info_operate'), 'controller' => 'FuwuManageInfo', 'url' => url('FuwuManageInfo/operate'),),
                    array('name' => 'fuwu_manage_account', 'text' => lang('base_fuwu_manage_account'), 'controller' => 'FuwuManageAccount', 'url' => url('FuwuManageAccount/edit_phone'),),
                )
            ),
            'fuwu_notice' =>
            array(
                'ico'=>'&#xe71b;',
                'name' => 'fuwu_notice',
                'text' => lang('base_fuwu_notice'),
                'url' => url('FuwuManageNotice/index'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_notice', 'text' => lang('base_fuwu_manage_notice'), 'controller' => 'FuwuManageNotice', 'url' => url('FuwuManageNotice/index'),),
                )
            ),
        );
        if($this->o2o_fuwu_organization_info['o2o_fuwu_organization_state']==1){
            $menu_list['fuwu_goods']=array(
                'ico'=>'&#xe732;',
                'name' => 'fuwu_goods',
                'text' => lang('base_fuwu_goods'),
                'url' => url('FuwuManageGoods/index'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_goods_on', 'text' => lang('base_fuwu_manage_goods_state_text')[1], 'controller' => 'FuwuManageGoods', 'url' => url('FuwuManageGoods/index'),),
                    array('name' => 'fuwu_manage_goods_off', 'text' => lang('base_fuwu_manage_goods_state_text')[0], 'controller' => 'FuwuManageGoods', 'url' => url('FuwuManageGoods/index',['o2o_fuwu_goods_state'=>0]),),
                    array('name' => 'fuwu_manage_goods_close', 'text' => lang('base_fuwu_manage_goods_state_text')[10], 'controller' => 'FuwuManageGoods', 'url' => url('FuwuManageGoods/index',['o2o_fuwu_goods_state'=>10]),),
                )
            );
            $menu_list['fuwu_order']=array(
                'ico'=>'&#xe657;',
                'name' => 'fuwu_order',
                'text' => lang('base_fuwu_order'),
                'url' => url('FuwuManageOrder/index'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_order', 'text' => lang('base_fuwu_manage_order'), 'controller' => 'FuwuManageOrder', 'url' => url('FuwuManageOrder/index'),),
                )
            );
            $menu_list['fuwu_money']=array(
                'ico'=>'&#xe6f7;',
                'name' => 'fuwu_money',
                'text' => lang('base_fuwu_money'),
                'url' => url('FuwuManageMoney/index'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_money', 'text' => lang('base_fuwu_manage_money'), 'controller' => 'FuwuManageMoney', 'url' => url('FuwuManageMoney/index'),),
                    array('name' => 'fuwu_manage_money_withdraw_list', 'text' => lang('base_fuwu_manage_money_withdraw_list'), 'controller' => 'FuwuManageMoney', 'url' => url('FuwuManageMoney/withdraw_list'),),
                )
            );
        }
        if($this->o2o_fuwu_organization_info['o2o_fuwu_organization_if_auth']==0){
            $menu_list['fuwu_authen']=array(
                'ico'=>'&#xe662;',
                'name' => 'fuwu_authen',
                'text' => lang('base_fuwu_authen'),
                'url' => url('FuwuManageAuthen/quality'),
                'submenu' => array(
                    array('name' => 'fuwu_manage_authen_quality', 'text' => lang('base_fuwu_manage_authen_quality'), 'controller' => 'FuwuManageAuthen', 'url' => url('FuwuManageAuthen/quality'),),
                    array('name' => 'fuwu_manage_authen_scene', 'text' => lang('base_fuwu_manage_authen_scene'), 'controller' => 'FuwuManageAuthen', 'url' => url('FuwuManageAuthen/scene'),),
                )
            );
        }
        return $menu_list;
    }


}

?>
