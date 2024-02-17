<?php

namespace app\home\controller;

use think\facade\Db;
use think\facade\Lang;
use GatewayClient\Gateway;

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
 * 用户消息控制器
 */
class MemberInstantMessage extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/live.lang.php');
    }

    public function add() {
        if (!config('ds_config.instant_message_register_url')) {
            ds_json_encode(10001, lang('instant_message_register_url_empty'));
        }
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
        try {
            Gateway::$registerAddress = config('ds_config.instant_message_register_url');
            $instant_message_model = model('instant_message');
        } catch (\Exception $e) {
            ds_json_encode(10001, $e->getMessage());
        }
        $to_id = input('param.to_id');
        $to_type = input('param.to_type', 0);
        $message = input('param.message');
        $message_type = input('param.message_type', 0);

        $res=word_filter($message);
        if(!$res['code']){
            ds_json_encode(10001,$res['msg']);
        }
        $message=$res['data']['text'];
        $instant_message_data = array(
            'instant_message_from_id' => $this->member_info['member_id'],
            'instant_message_from_type' => 0,
            'instant_message_from_name' => $this->member_info['member_name'],
            'instant_message_from_ip' => request()->ip(),
            'instant_message_to_id' => $to_id,
            'instant_message_to_type' => $to_type,
            'instant_message' => $message,
            'instant_message_type' => $message_type,
            'instant_message_add_time' => TIMESTAMP,
        );

        $instant_message_validate = ds_validate('instant_message');
        if (!$instant_message_validate->scene('instant_message_save')->check($instant_message_data)) {
            ds_json_encode(10001, $instant_message_validate->getError());
        }
        Db::startTrans();
        try {
            $instant_message_data = $instant_message_model->addInstantMessage($instant_message_data);
            $res = $instant_message_model->sendInstantMessage($instant_message_data, true);
            if (!$res['code']) {
                throw new \think\Exception($res['msg'], 10006);
            }
        } catch (\Exception $e) {
            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        Db::commit();
        ds_json_encode(10000, lang('message_send_success'), array('instant_message_data' => $instant_message_data));
    }

    public function set_message() {
        $max_id = intval(input('param.max_id'));
        $f_id = intval(input('param.f_id'));
        if (!$max_id || !$f_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $instant_message_model = model('instant_message');
        $condition = array();
        $condition[] = array('instant_message_to_id', '=', $this->member_info['member_id']);
        $condition[] = array('instant_message_to_type', '=', 0);
        $condition[] = array('instant_message_from_id', '=', $f_id);
        $condition[] = array('instant_message_from_type', '=', 0);
        $condition[] = array('instant_message_id', '<=', $max_id);
        $instant_message_model->editInstantMessage(array('instant_message_state' => 1), $condition);
        ds_json_encode(10000);
    }

    public function join() {
        $client_id = input('param.client_id');
        if (!$client_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        if (!config('ds_config.instant_message_register_url')) {
            ds_json_encode(10001, lang('instant_message_register_url_empty'));
        }
        $instant_message_model = model('instant_message');
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
        try {
            Gateway::$registerAddress = config('ds_config.instant_message_register_url');
            // client_id与uid绑定
            Gateway::bindUid($client_id, '0:' . $this->member_info['member_id']);
            $online_item = array(
                'instant_message_from_avatar' => get_member_avatar($this->member_info['member_avatar']),
                'instant_message_from_id' => $this->member_info['member_id'],
                'instant_message_from_type' => 0,
                'instant_message_from_name' => $this->member_info['member_name']
            );
            Gateway::setSession($client_id, $online_item);
            $condition = array();
            $condition[] = array('instant_message_to_id', '=', $this->member_info['member_id']);
            $condition[] = array('instant_message_to_type', '=', 0);
            $condition[] = array('instant_message_from_type', '=', 0);
            $condition[] = array('instant_message_state', '=', 2);
            $msg_list = $instant_message_model->getInstantMessageList($condition,'','instant_message_id asc');
            foreach ($msg_list as $key => $val) {
                $msg_list[$key] = $instant_message_model->formatInstantMessage($val);
            }
            //发送未读消息
            Gateway::sendToClient($client_id, json_encode(array(
                'type' => 'get_msg',
                'msg_list' => $msg_list,
            )));
        } catch (\Exception $e) {
            ds_json_encode(10001, $e->getMessage());
        }
        ds_json_encode(10000, '');
    }

    public function get_user_list() {
        $condition1 = array();
        $condition1[] = array('instant_message_from_id', '=', $this->member_info['member_id']);
        $condition1[] = array('instant_message_from_type', '=', 0);
        $condition1[] = array('instant_message_to_type', '=', 0);
        $condition2 = array();
        $condition2[] = array('instant_message_to_id', '=', $this->member_info['member_id']);
        $condition2[] = array('instant_message_to_type', '=', 0);
        $condition2[] = array('instant_message_from_type', '=', 0);
        //最近联系人最多取100个
        $instant_message_list = Db::name('instant_message')->whereOr([$condition1, $condition2])->distinct(true)->field('instant_message_to_id,instant_message_to_type,instant_message_to_name,instant_message_from_id,instant_message_from_type,instant_message_from_name')->order('instant_message_add_time desc')->limit(100)->select()->toArray();
        $user_list = array();
        $member_model = model('member');
        $snsfriend_model = model('snsfriend');
        $instant_message_model = model('instant_message');
        $snsfriend_list=$snsfriend_model->getSnsfriendList(array('friend_frommid' => $this->member_info['member_id']), '*', 100, 'simple');
        
        foreach ($instant_message_list as $val) {
            $_condition1 = $condition1;
            $_condition2 = $condition2;

            if ($val['instant_message_from_id'] == $this->member_info['member_id'] && $val['instant_message_from_type'] == 0) {
                $type = $val['instant_message_to_type'];
                $id = $val['instant_message_to_id'];
                $name = $val['instant_message_to_name'];
            } else {
                $type = $val['instant_message_from_type'];
                $id = $val['instant_message_from_id'];
                $name = $val['instant_message_from_name'];
            }
            $_condition1[] = array('instant_message_to_id', '=', $id);
            $_condition2[] = array('instant_message_from_id', '=', $id);
            if (!isset($user_list[$type . '_' . $id])) {
                $user_info = array(
                        'u_id' => $id,
                        'u_type' => $type,
                        'u_name' => $name,
                        'avatar' => get_member_avatar_for_id($id)
                    );

                if (!empty($user_info)) {
                    $instant_message_info = Db::name('instant_message')->whereOr([$_condition1, $_condition2])->order('instant_message_add_time desc')->find();
                    if ($instant_message_info) {
                        if ($type == 0) {
                            $snsfriend_info = $snsfriend_model->getOneSnsfriend(array('friend_frommid' => $this->member_info['member_id'], 'friend_tomid' => $id));
                        } else {
                            $snsfriend_info = false;
                        }
                        $user_info['recent'] = 1;
                        $user_info['friend'] = ($snsfriend_info && $snsfriend_info['friend_followstate']==2) ? 1 : 0;
                        $user_info['follow'] = ($snsfriend_info && $snsfriend_info['friend_followstate']==1) ? 1 : 0;
                        $user_info['message_type'] = $instant_message_info['instant_message_type'];
                        $message = $instant_message_info['instant_message'];
                        if ($instant_message_info['instant_message_type'] == 1) {
                            $message = '[商品]';
                        }
                        $user_info['r_state'] = $instant_message_info['instant_message_state'];
                        $user_info['message'] = $message;
                        $user_info['time'] = date("Y-m-d H:i:s", $instant_message_info['instant_message_add_time']);
                        $user_list[$type . '_' . $id] = $user_info;
                    }
                }
            }
        }
        foreach($snsfriend_list as $val){
            $id=$val['friend_tomid'];
            if(!isset($user_list['0_' . $id])){
                $user_list['0_' . $id] = array(
                        'u_id' => $id,
                        'u_type' => 0,
                        'u_name' => $val['friend_tomname'],
                        'avatar' => get_member_avatar_for_id($id),
                        'recent' => 0,
                        'friend' => ($val['friend_followstate']==2) ? 1 : 0,
                        'follow' => ($val['friend_followstate']==1) ? 1 : 0,
                        'message_type' => 0,
                        'r_state' => 1,
                        'message' => '',
                        'time' => '',
                    );
            }
        }
        $user_list = array_values($user_list);
        ds_json_encode(10000, '', array('user_list' => $user_list));
    }

    public function get_chat_log() {
        $instant_message_model = model('instant_message');
        $t_id = intval(input('param.t_id'));
        $key = input('param.t');
        $add_time_to = date("Y-m-d");
        $time_from = array();
        $time_from['7'] = strtotime($add_time_to) - 60 * 60 * 24 * 7;
        $time_from['15'] = strtotime($add_time_to) - 60 * 60 * 24 * 15;
        $time_from['30'] = strtotime($add_time_to) - 60 * 60 * 24 * 30;
        $condition1 = array();
        $condition1[] = array('instant_message_from_id', '=', $this->member_info['member_id']);
        $condition1[] = array('instant_message_from_type', '=', 0);
        $condition1[] = array('instant_message_to_type', '=', 0);
        $condition1[] = array('instant_message_to_id', '=', $t_id);
        $condition2 = array();
        $condition2[] = array('instant_message_to_id', '=', $this->member_info['member_id']);
        $condition2[] = array('instant_message_to_type', '=', 0);
        $condition2[] = array('instant_message_from_type', '=', 0);
        $condition2[] = array('instant_message_from_id', '=', $t_id);
        if (!isset($time_from[$key])) {
            $key = '7';
        }
        $condition = array();
        $condition[] = array('instant_message_add_time', '>=', $time_from[$key]);
        //最近联系人最多取100个
        $result = Db::name('instant_message')->where(function ($query) use($condition1, $condition2) {
                    $query->whereOr([$condition1, $condition2]);
                })->where($condition)->order('instant_message_add_time desc')->paginate(['list_rows' => 10, 'query' => request()->param()], false);
        $instant_message_list = $result->items();
        foreach ($instant_message_list as $key => $val) {
            $instant_message_list[$key] = $instant_message_model->formatInstantMessage($val);
        }
        ds_json_encode(10000, '', array('instant_message_list' => $instant_message_list, 'total_page' => $result->lastPage()));
    }

    /**
     * 店铺推荐商品图片和名称
     *
     */
    public function get_goods_list() {
        $s_id = intval(input('s_id'));
        if ($s_id > 0) {
            $goods_model = model('goods');
            $list = $goods_model->getGoodsCommendList($s_id, 4);
            if (!empty($list) && is_array($list)) {
                foreach ($list as $k => $v) {
                    $v['goods_promotion_price'] = ds_price_format($v['goods_promotion_price']);
                    $v['url'] = (string) url('Goods/index', array('goods_id' => $v['goods_id']));
                    $v['pic'] = goods_thumb($v, 240);
                    $list[$k] = $v;
                }
            }
            ds_json_encode(10000, '', array('goods_list' => $list));
        }
    }

    public function get_info() {
        $member_id = intval(input('param.u_id'));
        $member_model = model('member');
        $condition = array();
        $condition[] = array('member_id', '=', $member_id);
        $member = $member_model->getMemberInfo($condition, 'member_id,member_name,member_avatar');
        if (empty($member)) {
            ds_json_encode(10001, lang('user_not_exist'));
        }
        $member['store_id'] = '';
        $member['store_name'] = '';
        $member['store_avatar'] = '';
        $member['grade_id'] = '';
        $member['member_avatar'] = get_member_avatar($member['member_avatar']);
        $seller_model = model('seller');
        $seller = $seller_model->getSellerInfo(array('member_id' => $member['member_id']));
        if (!empty($seller) && $seller['store_id'] > 0) {
            $store_info = Db::name('store')->field('store_id,store_name,grade_id,store_avatar')->where(array('store_id' => $seller['store_id']))->find();
            if (is_array($store_info) && !empty($store_info)) {
                $member['store_id'] = $store_info['store_id'];
                $member['store_name'] = $store_info['store_name'];
                $member['seller_name'] = $seller['seller_name'];
                $member['grade_id'] = $store_info['grade_id'];
                $member['store_avatar'] = get_store_logo($store_info['store_avatar']);
            }
        }
        ds_json_encode(10000, '', array('user_info' => $member));
    }

}

?>
