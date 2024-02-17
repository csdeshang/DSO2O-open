<?php

/*
 * 交易投诉
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
class  MemberO2oComplaint extends BaseMember {


    public function initialize() {
        parent::initialize(); 
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/member_o2o_complaint.lang.php');
    }

    /*
     * 我的投诉页面
     */

    public function index() {
        /*
         * 得到当前用户的投诉列表
         */
        $o2o_complaint_model = model('o2o_complaint');
        $condition = array();
        $condition[] = array('member_id','=',session('member_id'));
        $select_complain_state=input('param.select_complain_state');
        if($select_complain_state!==null && $select_complain_state!==''){
            $condition[] = array('o2o_complaint_state','=',intval($select_complain_state));
        }

        $complain_list = $o2o_complaint_model->getO2oComplaintList($condition,'*',10);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_o2o_complaint');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('complain_list');
        View::assign('complain_list', $complain_list);
        View::assign('show_page', $o2o_complaint_model->page_info);

        return View::fetch($this->template_dir . 'index');
    }

    /*
     * 新投诉
     */

    public function add() {
        $o2o_complaint_model=model('o2o_complaint');
        $order_id = intval(input('order_id'));
        if ($order_id < 1) {//参数验证
            ds_json_encode(10001,lang('param_error'));
        }

        //检查订单是否可以投诉
        $order_model = model('order');
        $order_info = $order_model->getOrderInfo(array(array('order_id','=',$order_id),array('buyer_id','=',session('member_id')),array('finnshed_time','>',0),array('order_state','=',ORDER_STATE_SUCCESS)));
        if (!$order_info) {
            ds_json_encode(10001,lang('member_o2o_complaint_error'));
        }
        //检查是不是正在进行投诉
        if ($o2o_complaint_model->getO2oComplaintInfo(array('order_id'=>$order_id))) {
            ds_json_encode(10001,lang('member_o2o_complaint_repeat')); //'您已经投诉了该订单请等待处理'
        }

        if(request()->isPost()){
            $content=input('post.content');
            if(!$content){
                ds_json_encode(10001,lang('o2o_complaint_content_required'));
            }
            $o2o_complaint_model->addO2oComplaint(array(
                'order_id'=>$order_id,
                'order_sn'=>$order_info['order_sn'],
                'member_id'=>session('member_id'),
                'member_name'=>session('member_name'),
                'o2o_distributor_id'=>$order_info['o2o_distributor_id'],
                'o2o_distributor_name'=>$order_info['o2o_distributor_name'],
                'store_id'=>$order_info['store_id'],
                'store_name'=>$order_info['store_name'],
                'o2o_complaint_content'=>$content,
                'o2o_complaint_addtime'=>TIMESTAMP,
            ));
            ds_json_encode(10000,lang('ds_common_op_succ'));
        }else{
            return View::fetch($this->template_dir . 'form');
        }
        
    }
    /*
     * 删除投诉
     */
    public function del(){
        $o2o_complaint_model=model('o2o_complaint');
        $order_id = intval(input('order_id'));
        if ($order_id < 1) {//参数验证
            ds_json_encode(10001,lang('param_error'));
        }
        if($o2o_complaint_model->delO2oComplaint(array('member_id'=>session('member_id'),'o2o_order_bill_id'=>0,'order_id'=>$order_id))){
            ds_json_encode(10000,lang('ds_common_op_succ'));
        }else{
            ds_json_encode(10001,lang('ds_common_op_fail'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param array $array 附加菜单
     * @return
     */
    public function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'complain_list',
                'text' => lang('member_o2o_complaint_manage'),
                'url' => url('MemberO2oComplaint/index')
            )
        );
        return $menu_array;
    }

}

?>
