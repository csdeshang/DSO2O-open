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
class FuwuManageOrder extends BaseFuwu {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/fuwu_manage_order.lang.php');
    }

    /**
     * 订单列表
     *
     */
    public function index() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_id','=',$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']);
        $o2o_fuwu_order_state = input('param.o2o_fuwu_order_state');
        if ($o2o_fuwu_order_state !== null && $o2o_fuwu_order_state !== '') {
            $condition[] = array('o2o_fuwu_order_state','=',intval($o2o_fuwu_order_state));
        }
        $query_start_time = input('param.query_start_date');
        $query_end_time = input('param.query_end_date');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_time);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_time);
        $start_unixtime = $if_start_time ? strtotime($query_start_time) : null;
        $end_unixtime = $if_end_time ? strtotime($query_end_time) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[] = array('o2o_fuwu_order_add_time','between',array($start_unixtime ? $start_unixtime : 0, $end_unixtime ? ($end_unixtime + 86399) : TIMESTAMP));
        }
        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[] = array('o2o_fuwu_order_sn','=',$order_sn);
        }
        $o2o_fuwu_order_list = $o2o_fuwu_order_model->getO2oFuwuOrderList($condition, '*', 20);
        foreach($o2o_fuwu_order_list as $key => $val){
            $o2o_fuwu_order_list[$key]['o2o_fuwu_order_state_text']=$o2o_fuwu_order_model->getO2oFuwuOrderStateText($val['o2o_fuwu_order_state']);
            $btn_list=$o2o_fuwu_order_model->getO2oFuwuOrderBtn($val,'fuwu');
            $o2o_fuwu_order_list[$key]=array_merge($o2o_fuwu_order_list[$key],$btn_list);
        }
        View::assign('order_list', $o2o_fuwu_order_list);
        View::assign('show_page', $o2o_fuwu_order_model->page_info->render());
        $this->setFuwuCurMenu('fuwu_manage_order');
        $this->setFuwuCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 订单详细
     *
     */
    public function view() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        
        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        if (!$o2o_fuwu_order_id) {
            $this->error(lang('param_error'));
        }
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_id','=',$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']);
        $condition[] = array('o2o_fuwu_order_id','=',$o2o_fuwu_order_id);
        $o2o_fuwu_order_info = $o2o_fuwu_order_model->getO2oFuwuOrderInfo($condition);
        if(!$o2o_fuwu_order_info){
            $this->error('订单不存在');
        }
        $o2o_fuwu_order_info['o2o_fuwu_order_state_text']=$o2o_fuwu_order_model->getO2oFuwuOrderStateText($o2o_fuwu_order_info['o2o_fuwu_order_state']);
        View::assign('o2o_fuwu_order_info', $o2o_fuwu_order_info);
        View::assign('o2o_fuwu_order_goods_spec_list', model('o2o_fuwu_order_goods_spec')->getO2oFuwuOrderGoodsSpecList(array('o2o_fuwu_order_id' => $o2o_fuwu_order_id)));
        $this->setFuwuCurMenu('fuwu_manage_order');
        $this->setFuwuCurItem('view');
        return View::fetch($this->template_dir . 'view');
    }
    public function cancel() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        
        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        $reason = input('param.reason');
        if (!$o2o_fuwu_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        if(!$reason){
            ds_json_encode(10001, '请填写理由');
        }
        $result=$o2o_fuwu_order_model->cancelO2oFuwuOrder(array('o2o_fuwu_order_id'=>$o2o_fuwu_order_id,'o2o_fuwu_organization_id'=>$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']),'fuwu',$this->o2o_fuwu_organization_info['o2o_fuwu_account_name'],$reason);
        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
    public function arrange() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        
        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        if (!$o2o_fuwu_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }

        $result=$o2o_fuwu_order_model->arrangeO2oFuwuOrder(array('o2o_fuwu_order_id'=>$o2o_fuwu_order_id,'o2o_fuwu_organization_id'=>$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']),'fuwu',$this->o2o_fuwu_organization_info['o2o_fuwu_account_name']);
        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /**
     * 导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getFuwuItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => '订单列表',
                'url' => url('FuwuManageOrder/index')
            ),
        );
        if(request()->action()=='view'){
            $menu_array[] = array(
                'name' => 'view', 'text' => '订单详情',
                'url' => 'javascript:void(0)'
            );
        }
        
        return $menu_array;
    }

}