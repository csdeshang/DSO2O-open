<?php

/*
 * 交易投诉
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
class MemberO2oFuwuOrder extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/member_o2o_fuwu_order.lang.php');
    }

    /*
     * 订单列表
     */

    public function index() {
        /*
         * 得到当前用户的服务订单列表
         */
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
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
            $condition[] = array('o2o_fuwu_order_add_time','between',array($start_unixtime?$start_unixtime:0, $end_unixtime?($end_unixtime+86399):TIMESTAMP));
        }
        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[] = array('o2o_fuwu_order_sn','=',$order_sn);
        }
        $o2o_fuwu_order_list = $o2o_fuwu_order_model->getO2oFuwuOrderList($condition, '*', 20);
        foreach ($o2o_fuwu_order_list as $key => $val) {
            $o2o_fuwu_order_list[$key]['o2o_fuwu_order_state_text'] = $o2o_fuwu_order_model->getO2oFuwuOrderStateText($val['o2o_fuwu_order_state']);
            $btn_list = $o2o_fuwu_order_model->getO2oFuwuOrderBtn($val, 'buyer');
            $o2o_fuwu_order_list[$key] = array_merge($o2o_fuwu_order_list[$key], $btn_list);
        }
        View::assign('order_list', $o2o_fuwu_order_list);
        View::assign('show_page', $o2o_fuwu_order_model->page_info->render());
        $this->setMemberCurMenu('member_o2o_fuwu_order');
        $this->setMemberCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }

    public function view() {

        $o2o_fuwu_order_model = model('o2o_fuwu_order');

        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        if (!$o2o_fuwu_order_id) {
            $this->error(lang('param_error'));
        }
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $condition[] = array('o2o_fuwu_order_id','=',$o2o_fuwu_order_id);
        $o2o_fuwu_order_info = $o2o_fuwu_order_model->getO2oFuwuOrderInfo($condition);
        if (!$o2o_fuwu_order_info) {
            $this->error('订单不存在');
        }
        $o2o_fuwu_order_info['o2o_fuwu_order_state_text'] = $o2o_fuwu_order_model->getO2oFuwuOrderStateText($o2o_fuwu_order_info['o2o_fuwu_order_state']);

        View::assign('o2o_fuwu_order_info', $o2o_fuwu_order_info);
        View::assign('o2o_fuwu_order_goods_spec_list', model('o2o_fuwu_order_goods_spec')->getO2oFuwuOrderGoodsSpecList(array('o2o_fuwu_order_id' => $o2o_fuwu_order_id)));
        $this->setMemberCurMenu('member_o2o_fuwu_order');
        $this->setMemberCurItem('view');
        return View::fetch($this->template_dir . 'view');
    }

    /*
     * 服务下单
     */

    public function buy() {
        //服务
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
        $o2o_fuwu_goods_spec_default = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecList(array('o2o_fuwu_goods_id' => $o2o_fuwu_goods_id, 'o2o_fuwu_goods_spec_type' => 0));
        if (!$o2o_fuwu_goods_spec_default) {
            $this->error('服务项目不存在');
        }
        $o2o_fuwu_goods_spec_added = $o2o_fuwu_goods_spec_model->getO2oFuwuGoodsSpecList(array('o2o_fuwu_goods_id' => $o2o_fuwu_goods_id, 'o2o_fuwu_goods_spec_type' => 1));
        View::assign('o2o_fuwu_goods_info', $o2o_fuwu_goods_info);
        View::assign('o2o_fuwu_goods_spec_default', $o2o_fuwu_goods_spec_default);
        View::assign('o2o_fuwu_goods_spec_added', $o2o_fuwu_goods_spec_added);
        View::assign('week_array', array("周日", "周一", "周二", "周三", "周四", "周五", "周六"));
        View::assign('if_predeposit', $this->member_info['available_predeposit'] > 0);
        View::assign('if_rc_balance', $this->member_info['available_rc_balance'] > 0);
        //服务地址
        $address_model = model('address');
        $address_receive_info = $address_model->getAddressInfo(array('member_id' => $this->member_info['member_id'], 'address_o2o_errand_type' => 0), 'address_is_default desc');
        View::assign('address_info', $address_receive_info);
        return View::fetch($this->template_dir . 'buy');
    }

    public function add() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');
        $data = array(
            'o2o_fuwu_goods_id' => input('param.o2o_fuwu_goods_id'),
            'address_id' => input('param.address_id'),
            'spec_quantity_list' => htmlspecialchars_decode(input('param.spec_quantity_list')),
            'o2o_fuwu_order_appointment_time' => input('param.o2o_fuwu_order_appointment_time'),
            'pd_pay' => input('param.pd_pay'),
            'password' => input('param.password'),
            'rcb_pay' => input('param.rcb_pay'),
            'o2o_fuwu_order_remark' => input('param.o2o_fuwu_order_remark'),
        );

        $o2o_fuwu_order_validate = ds_validate('o2o_fuwu_order');
        if (!$o2o_fuwu_order_validate->scene('o2o_fuwu_order_add')->check($data)) {
            ds_json_encode(10001, $o2o_fuwu_order_validate->getError());
        }


        $result = $o2o_fuwu_order_model->addO2oFuwuOrder($data, $this->member_info);
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'), array('pay_sn' => $result['data']['o2o_fuwu_order_sn']));
    }

    public function cancel() {
        $o2o_fuwu_order_model = model('o2o_fuwu_order');

        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        if (!$o2o_fuwu_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }

        $result = $o2o_fuwu_order_model->cancelO2oFuwuOrder(array('o2o_fuwu_order_id' => $o2o_fuwu_order_id, 'member_id' => $this->member_info['member_id']), 'buyer', $this->member_info['member_name']);
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    public function evaluate() {
        $o2o_fuwu_order_id = input('param.o2o_fuwu_order_id');
        if (!$o2o_fuwu_order_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $order_model = model('o2o_fuwu_order');
        $condition = array();
        $condition[] = array('o2o_fuwu_order_id','=',$o2o_fuwu_order_id);
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $o2o_fuwu_order_info = $order_model->getO2oFuwuOrderInfo($condition);
        if (!$o2o_fuwu_order_info) {
            ds_json_encode(10001, '订单不存在');
        }
        if ($o2o_fuwu_order_info['o2o_fuwu_order_state'] != O2O_FUWU_ORDER_STATE_SUCCESS || $o2o_fuwu_order_info['o2o_fuwu_order_if_evaluate']) {
            ds_json_encode(10001, '订单不可评');
        }
        if (request()->isPost()) {
            $o2o_fuwu_order_model = model('o2o_fuwu_order');


            $comment = input('param.comment');
            $score = intval(input('param.score'));
            if ($score < 1 || $score > 5) {
                ds_json_encode(10001, '评分错误');
            }

            Db::startTrans();
            try {
                $result = $o2o_fuwu_order_model->editO2oFuwuOrder(array('o2o_fuwu_order_if_evaluate' => 1, 'o2o_fuwu_order_evaluate_time' => TIMESTAMP, 'o2o_fuwu_order_evaluate_content' => $comment, 'o2o_fuwu_order_evaluate_score' => $score), $condition);
                if (!$result) {
                    throw new \think\Exception('评论保存失败', 10006);
                }
                //更新服务机构评分
                $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
                $o2o_fuwu_organization_info = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $o2o_fuwu_order_info['o2o_fuwu_organization_id']), 'o2o_fuwu_organization_id,o2o_fuwu_organization_score,o2o_fuwu_organization_comment_count');
                if ($o2o_fuwu_organization_info) {
                    $count = $o2o_fuwu_organization_info['o2o_fuwu_organization_comment_count'] + 1;
                    $score = round(($score + ($o2o_fuwu_organization_info['o2o_fuwu_organization_score'] * $o2o_fuwu_organization_info['o2o_fuwu_organization_comment_count'])) / $count, 2);
                    if (!$o2o_fuwu_organization_model->editO2oFuwuOrganization(array('o2o_fuwu_organization_comment_count' => $count, 'o2o_fuwu_organization_score' => $score), array('o2o_fuwu_organization_id' => $o2o_fuwu_organization_info['o2o_fuwu_organization_id']))) {
                        throw new \think\Exception('评论更新失败', 10006);
                    }
                    if ($o2o_fuwu_order_info['o2o_fuwu_organization_parent_id']) {
                        $o2o_fuwu_organization_info = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $o2o_fuwu_order_info['o2o_fuwu_organization_parent_id']), 'o2o_fuwu_organization_id,o2o_fuwu_organization_score,o2o_fuwu_organization_comment_count');
                        if ($o2o_fuwu_organization_info) {
                            $count = $o2o_fuwu_organization_info['o2o_fuwu_organization_comment_count'] + 1;
                            $score = round(($score + ($o2o_fuwu_organization_info['o2o_fuwu_organization_score'] * $o2o_fuwu_organization_info['o2o_fuwu_organization_comment_count'])) / $count, 2);
                            if (!$o2o_fuwu_organization_model->editO2oFuwuOrganization(array('o2o_fuwu_organization_comment_count' => $count, 'o2o_fuwu_organization_score' => $score), array('o2o_fuwu_organization_id' => $o2o_fuwu_organization_info['o2o_fuwu_organization_id']))) {
                                throw new \think\Exception('评论更新失败', 10006);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                ds_json_encode(10001, $e->getMessage());
            }
            Db::commit();

            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            View::assign('o2o_fuwu_order_info',$o2o_fuwu_order_info);
            return View::fetch($this->template_dir . 'evaluate');
        }
    }

    public function receive() {
        $order_id = intval(input('param.o2o_fuwu_order_id'));
        if (!$order_id) {
            ds_json_encode(10001, 'Hacking Attempt');
        }

        $order_model = model('o2o_fuwu_order');
        $condition = array();
        $condition[] = array('member_id','=',$this->member_info['member_id']);
        $condition[] = array('o2o_fuwu_order_state','=',ORDER_STATE_SEND);
        $condition[] = array('o2o_fuwu_order_id','=',$order_id);
        $o2o_fuwu_order_info = $order_model->getO2oFuwuOrderInfo($condition);
        if (!$o2o_fuwu_order_info) {
            ds_json_encode(10001, '订单不存在');
        }

        $result = $order_model->receiveO2oFuwuOrder($condition, 'buyer', $this->member_info['member_name']);
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        }


        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /**
     *    栏目菜单
     */
    function getMemberItemList() {
        $item_list = array(
            array(
                'name' => 'index',
                'text' => lang('ds_member_path_order_list'),
                'url' => url('MemberO2oFuwuOrder/index'),
            ),
        );
        if (request()->action() == 'view') {
            $item_list[] = array(
                'name' => 'view',
                'text' => lang('ds_member_path_show_order'),
                'url' => 'javascript:void(0)',
            );
        }
        return $item_list;
    }

}

?>
