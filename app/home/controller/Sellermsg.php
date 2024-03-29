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
class  Sellermsg extends BaseSeller {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellermsg.lang.php');
    }

    /**
     * 消息列表
     */
    public function index() {
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        if (!session('seller_is_admin')) {
            $condition[] = array('storemt_code','in',session('seller_smt_limits'));
        }
        $storemsg_model = model('storemsg');
        $msg_list = $storemsg_model->getStoremsgList($condition, '*', 10);

        // 整理数据
        if (!empty($msg_list)) {
            foreach ($msg_list as $key => $val) {
                $msg_list[$key]['storemsg_readids'] = explode(',', $val['storemsg_readids']);
            }
        }
        View::assign('msg_list', $msg_list);
        View::assign('show_page', $storemsg_model->page_info->render());


        $this->setSellerCurMenu('Sellermsg');
        $this->setSellerCurItem('msg_list');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 消息详细
     */
    public function msg_info() {
        $storemsg_id = intval(input('param.storemsg_id'));
        if ($storemsg_id <= 0) {
            $this->error(lang('param_error'));
        }
        $storemsg_model = model('storemsg');
        $condition = array();
        $condition[] = array('storemsg_id','=',$storemsg_id);
        if (session('seller_smt_limits') !== false) {
            $condition[] = array('storemt_code','in',session('seller_smt_limits'));
            
        }
        $msg_info = $storemsg_model->getStoremsgInfo($condition);
        if (empty($msg_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('msg_list', $msg_info);

        // 验证时候已读
        $storemsg_readids = explode(',', $msg_info['storemsg_readids']);
        if (!in_array(session('seller_id'), $storemsg_readids)) {
            // 消息阅读表插入数据
            $data = array();
            $data['seller_id'] = session('seller_id');
            $data['storemsg_id'] = $storemsg_id;
            model('storemsgread')->addStoremsgread($data);

            $update = array();
            $storemsg_readids[] = session('seller_id');
            $update['storemsg_readids'] = implode(',', $storemsg_readids) . ',';
            $storemsg_model->editStoremsg(array('storemsg_id' => $storemsg_id), $update);
        }
        return View::fetch($this->template_dir . 'msg_info');
    }

    /**
     * AJAX标记为已读
     */
    public function mark_as_read() {
        $smids = input('param.smids');
        if (!preg_match('/^[\d,]+$/i', $smids)) {
            ds_json_encode(10001,lang('param_error'));
        }

        $smids = explode(',', $smids);
        $storemsgread_model = model('storemsgread');
        $storemsg_model = model('storemsg');
        foreach ($smids as $val) {
            $data = array();
            $data['seller_id'] = session('seller_id');
            $data['storemsg_id'] = $val;
            $read_info = $storemsgread_model->getStoremsgreadInfo($data);
            if (empty($read_info)) {
                // 消息阅读表插入数据
                $storemsgread_model->addStoremsgread($condition);

                // 更新店铺消息表
                $storemsg_info = $storemsg_model->getStoremsgInfo(array('storemsg_id' => $val));
                $storemsg_readids = explode(',', $storemsg_info['storemsg_readids']);
                $storemsg_readids[] = session('seller_id');
                $storemsg_readids = array_unique($storemsg_readids);
                $update = array();
                $update['storemsg_readids'] = implode(',', $storemsg_readids) . ',';
                $storemsg_model->editStoremsg(array('storemsg_id' => $val), $update);
            }
        }
        ds_json_encode(10000,lang('ds_common_op_succ'));
    }

    /**
     * AJAX删除消息
     */
    public function del_msg() {
        // 验证参数
        $smids = input('param.smids');
        if (!preg_match('/^[\d,]+$/i', $smids)) {
            ds_json_encode(10001,lang('param_error'));
        }
        $smid_array = explode(',', $smids);

        // 验证是否为管理员
        if (!$this->checkIsAdmin()) {
            ds_json_encode(10001,lang('param_error'));
        }

        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $condition[]=array('storemsg_id','in', $smid_array);
        // 删除消息记录
        model('storemsg')->delStoremsg($condition);
        // 删除阅读记录
        $condition = array();
        $condition[] = array('storemsg_id', 'in', $smid_array);
        model('storemsgread')->delStoremsgread($condition);
        ds_json_encode(10000,lang('ds_common_op_succ'));
    }

    /**
     * 消息接收设置
     */
    public function msg_setting() {
        // 验证是否为管理员
        if (!$this->checkIsAdmin()) {
            $this->error(lang('param_error'));
        }

        // 店铺消息模板列表
        $smt_list = model('storemsgtpl')->getStoremsgtplList(array(), 'storemt_code,storemt_name,storemt_message_switch,storemt_message_forced,storemt_short_switch,smt_short_forced,storemt_mail_switch,storemt_mail_forced,storemt_weixin_switch,storemt_weixin_forced');

        // 店铺接收设置
        $setting_list = model('storemsgsetting')->getStoremsgsettingList(array('store_id' => session('store_id')), '*', 'storemt_code');

        if (!empty($smt_list)) {
            foreach ($smt_list as $key => $val) {
                // 站内信消息模板是否开启
                if ($val['storemt_message_switch']) {
                    // 是否强制接收，强制接收必须开启
                    $smt_list[$key]['storems_message_switch'] = $val['storemt_message_forced'] ? 1 : (isset($setting_list[$val['storemt_code']])?intval($setting_list[$val['storemt_code']]['storems_message_switch']):0);

                    // 已开启接收模板
                    if ($smt_list[$key]['storems_message_switch']) {
                        $smt_list[$key]['is_opened'][] = '商家消息';
                    }
                }
                // 短消息模板是否开启
                if ($val['storemt_short_switch']) {
                    // 是否强制接收，强制接收必须开启
                    $smt_list[$key]['storems_short_switch'] = $val['smt_short_forced'] ? 1 : (isset($setting_list[$val['storemt_code']])?intval($setting_list[$val['storemt_code']]['storems_short_switch']):0);

                    // 已开启接收模板
                    if ($smt_list[$key]['storems_short_switch']) {
                        $smt_list[$key]['is_opened'][] = '手机短信';
                    }
                }
                // 邮件模板是否开启
                if ($val['storemt_mail_switch']) {
                    // 是否强制接收，强制接收必须开启
                    $smt_list[$key]['storems_mail_switch'] = $val['storemt_mail_forced'] ? 1 : (isset($setting_list[$val['storemt_code']])?intval($setting_list[$val['storemt_code']]['storems_mail_switch']):0);

                    // 已开启接收模板
                    if ($smt_list[$key]['storems_mail_switch']) {
                        $smt_list[$key]['is_opened'][] = '邮件';
                    }
                }
                // 微信模板是否开启
                if ($val['storemt_weixin_switch']) {
                    // 是否强制接收，强制接收必须开启
                    $smt_list[$key]['storems_weixin_switch'] = $val['storemt_weixin_forced'] ? 1 : (isset($setting_list[$val['storemt_code']])?intval($setting_list[$val['storemt_code']]['storems_weixin_switch']):0);

                    // 已开启接收模板
                    if ($smt_list[$key]['storems_weixin_switch']) {
                        $smt_list[$key]['is_opened'][] = lang('weixin_alerts');
                    }
                }

                if (is_array($smt_list[$key]['is_opened'])) {
                    $smt_list[$key]['is_opened'] = implode('&nbsp;|&nbsp;&nbsp;', $smt_list[$key]['is_opened']);
                }
            }
        }
        View::assign('smt_list', $smt_list);

        $this->setSellerCurMenu('Sellermsg');
        $this->setSellerCurItem('msg_setting');
        return View::fetch($this->template_dir . 'msg_setting');
    }

    /**
     * 编辑店铺消息接收设置
     */
    public function edit_msg_setting() {
        // 验证是否为管理员
        if (!$this->checkIsAdmin()) {
            $this->error(lang('param_error'));
        }
        $code = trim(input('param.code'));
        if ($code == '') {
            return false;
        }
        // 店铺消息模板
        $smt_info = model('storemsgtpl')->getStoremsgtplInfo(array('storemt_code' => $code), 'storemt_code,storemt_name,storemt_message_switch,storemt_message_forced,storemt_short_switch,smt_short_forced,storemt_mail_switch,storemt_mail_forced,storemt_weixin_switch,storemt_weixin_forced');
        if (empty($smt_info)) {
            return false;
        }

        // 店铺消息接收设置
        $setting_info = model('storemsgsetting')->getStoremsgsettingInfo(array(
            'storemt_code' => $code,
            'store_id' => session('store_id')
        ));
        View::assign('smt_info', $smt_info);
        View::assign('smsetting_info', $setting_info);
        return View::fetch($this->template_dir . 'setting_edit');
    }

    /**
     * 保存店铺接收设置
     */
    public function save_msg_setting()
    {
        // 验证是否为管理员
        if (!$this->checkIsAdmin()) {
            ds_json_encode(10001, lang('param_error'));
        }
        $code = trim(input('post.code'));
        if ($code == '') {
            ds_json_encode(10001, lang('param_error'));
        }
        
        $data = [
            'storems_short_number' => input('post.storems_short_number'),
            'storems_mail_number' => input('post.storems_mail_number'),
        ];
        $sellermsg_validate = ds_validate('sellermsg');
        if (!$sellermsg_validate->scene('save_msg_setting')->check($data)) {
            ds_json_encode(10001, $sellermsg_validate->getError());
        }
        
        $smt_info = model('storemsgtpl')->getStoremsgtplInfo(array('storemt_code' => $code), 'storemt_code,storemt_name,storemt_message_switch,storemt_message_forced,storemt_short_switch,smt_short_forced,storemt_mail_switch,storemt_mail_forced,storemt_weixin_switch,storemt_weixin_forced');

        // 保存
        $data = array();
        $data['storemt_code'] = $smt_info['storemt_code'];
        $data['store_id'] = session('store_id');
        // 验证站内信是否开启
        if ($smt_info['storemt_message_switch']) {
            $data['storems_message_switch'] = $smt_info['storemt_message_forced'] ? 1 : intval(input('post.message_forced'));
        } else {
            $data['storems_message_switch'] = 0;
        }
        // 验证短消息是否开启
        if ($smt_info['storemt_short_switch']) {
            $data['storems_short_switch'] = $smt_info['smt_short_forced'] ? 1 : intval(input('post.short_forced'));
        } else {
            $data['storems_short_switch'] = 0;
        }
        $data['storems_short_number'] = input('post.storems_short_number', '');
        // 验证邮件是否开启
        if ($smt_info['storemt_mail_switch']) {
            $data['storems_mail_switch'] = $smt_info['storemt_mail_forced'] ? 1 : intval(input('post.mail_forced'));
        } else {
            $data['storems_mail_switch'] = 0;
        }
        // 验证微信是否开启
        if ($smt_info['storemt_weixin_switch']) {
            $data['storems_weixin_switch'] = $smt_info['storemt_weixin_forced'] ? 1 : intval(input('post.weixin_forced'));
        } else {
            $data['storems_weixin_switch'] = 0;
        }
        $data['storems_mail_number'] = input('post.storems_mail_number', '');
        $conditiion = array();
        $conditiion['storemt_code'] = $smt_info['storemt_code'];
        $conditiion['store_id'] = session('store_id');
        $storemsgsetting_info = model('storemsgsetting')->getStoremsgsettingInfo($conditiion);
        // 插入数据
        if (empty($storemsgsetting_info)){
            $result = model('storemsgsetting')->addStoremsgsetting($data);
        }else{
            $result = model('storemsgsetting')->editStoremsgsetting($data,$conditiion);
        }
        if ($result) {
            ds_json_encode(10000,lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_op_fail'));
        }
    }

    private function checkIsAdmin() {
        return session('seller_is_admin') ? true : false;
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $name 当前导航的name
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            1 => array('name' => 'msg_list', 'text' => '消息列表', 'url' => url('Sellermsg/index')), 2 => array(
                'name' => 'msg_setting', 'text' => '消息接收设置', 'url' => url('Sellermsg/msg_setting')
            ),
        );
        if (!$this->checkIsAdmin()) {
            unset($menu_array[2]);
        }
        return $menu_array;
    }

}
