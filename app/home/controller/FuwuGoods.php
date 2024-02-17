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
class FuwuGoods extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/fuwu.lang.php');
    }

    public function view() {
        $o2o_fuwu_goods_id = intval(input('param.o2o_fuwu_goods_id'));
        $o2o_fuwu_goods_model = model('o2o_fuwu_goods');
        $condition = array();
        $condition[] = array('o2o_fuwu_goods_state','=',1);
        $condition[] = array('o2o_fuwu_goods_id','=',$o2o_fuwu_goods_id);
        $o2o_fuwu_goods_info = $o2o_fuwu_goods_model->getO2oFuwuGoodsInfo($condition);
        if (!$o2o_fuwu_goods_info) {
            $this->error('服务不存在');
        }
        $o2o_fuwu_goods_spec_model = model('o2o_fuwu_goods_spec');
        $o2o_fuwu_goods_spec_default = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecList(array('o2o_fuwu_goods_id' => $o2o_fuwu_goods_id,'o2o_fuwu_goods_spec_type'=>0));
        if (!$o2o_fuwu_goods_spec_default) {
            $this->error('服务项目不存在');
        }
        $o2o_fuwu_goods_spec_added = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecList(array('o2o_fuwu_goods_id' => $o2o_fuwu_goods_id,'o2o_fuwu_goods_spec_type'=>1));
        $o2o_fuwu_goods_info['o2o_fuwu_goods_body'] = json_decode(htmlspecialchars_decode($o2o_fuwu_goods_info['o2o_fuwu_goods_body']), true);
        View::assign('o2o_fuwu_goods_info',$o2o_fuwu_goods_info);
        View::assign('o2o_fuwu_goods_spec_default',$o2o_fuwu_goods_spec_default);
        View::assign('o2o_fuwu_goods_spec_added',$o2o_fuwu_goods_spec_added);
        //获取评论
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        $evaluate_list = $o2o_fuwu_order_model->getO2oFuwuOrderList(array('o2o_fuwu_goods_id' => $o2o_fuwu_goods_id, 'o2o_fuwu_order_if_evaluate' => 1), 'member_id,member_name,o2o_fuwu_order_evaluate_time,o2o_fuwu_order_evaluate_score,o2o_fuwu_order_evaluate_content');
        View::assign('evaluate_list',$evaluate_list);
        View::assign('show_page', is_object($o2o_fuwu_order_model->page_info)?$o2o_fuwu_order_model->page_info->render():"");
        // 当前位置导航
        $nav_link_list = array();
        $nav_link_list[] = array('title' => lang('homepage'), 'link' => url('home/Index/index'));
        $nav_link_list[] = array('title' => lang('fuwu_index'), 'link' => url('home/Fuwu/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_list'), 'link' => url('home/FuwuOrganization/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_info'), 'link' => url('home/FuwuOrganization/view',['organization_id'=>$o2o_fuwu_goods_info['o2o_fuwu_organization_id']]));
        $nav_link_list[] = array('title' => lang('fuwu_goods_info'));
        View::assign('nav_link_list', $nav_link_list);
        //SEO 设置
        $seo = model('seo')->type('index')->show();
        $this->_assign_seo($seo);
        return View::fetch($this->template_dir . 'view');
    }

}
