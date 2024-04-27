<?php

namespace app\common\validate;

use think\Validate;
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
 * 验证器
 */
class  O2oDistributor extends Validate {

    protected $rule = [
        'o2o_distributor_state' => 'require|in:0,1,2,3',
        'o2o_distributor_region_id' => 'number',
        'o2o_distributor_name' => 'require|length:3,12',
        'o2o_distributor_password' => 'require|length:6,20',
        'o2o_distributor_phone' => 'require|mobile',
        'o2o_distributor_realname' => 'length:2,12',
        'o2o_distributor_email' => 'email',
        'o2o_distributor_receipt' => 'require|in:0,1',
        'o2o_distributor_introduce' => 'max:255',
        'o2o_distributor_remark' => 'max:255',
        'o2o_distributor_payment_account' => 'max:500',
        'o2o_distributor_new_order_ring_remind' => 'require|in:0,1',
        'o2o_distributor_new_order_shock_remind' => 'require|in:0,1',
        'o2o_distributor_urge_order_ring_remind' => 'require|in:0,1',
        'o2o_distributor_urge_order_shock_remind' => 'require|in:0,1',
    ];
    protected $regex = ['mobile' => '^1[0-9]{10}$'];
    protected $message = [
        'o2o_distributor_state.require' => '请选择状态',
        'o2o_distributor_state.in' => '状态错误',
        'o2o_distributor_region_id.number' => '配送地区错误',
        'o2o_distributor_name.require' => '用户名必填',
        'o2o_distributor_name.length' => '用户名长度在3到12位',
        'o2o_distributor_password.require' => '密码为必填',
        'o2o_distributor_password.length' => '密码长度必须为6-20之间',
        'o2o_distributor_phone.require' => '手机号必填',
        'o2o_distributor_phone.mobile' => '请填写正确的手机号码',
        'o2o_distributor_realname.require' => '真实姓名必填',
        'o2o_distributor_email.email' => '邮箱格式错误',
        'o2o_distributor_receipt.in' => '接单状态错误',
        'o2o_distributor_introduce.max' => '自我介绍长度必须小于255',
        'o2o_distributor_remark.max' => '备注长度必须小于255',
        'o2o_distributor_payment_account.max' => '收款账号长度必须小于500',
    ];
    protected $scene = [
        'o2o_distributor_login' => ['o2o_distributor_name', 'o2o_distributor_password'],
        'o2o_distributor_register' => ['o2o_distributor_state','o2o_distributor_name', 'o2o_distributor_password', 'o2o_distributor_realname', 'o2o_distributor_phone', 'o2o_distributor_email', 'o2o_distributor_introduce', 'o2o_distributor_remark'],
        'o2o_distributor_edit' => ['o2o_distributor_password' => 'length:6,20', 'o2o_distributor_realname', 'o2o_distributor_phone', 'o2o_distributor_email'],
        'o2o_distributor_update_receipt' => ['o2o_distributor_receipt'],
        'o2o_distributor_update_information' => ['o2o_distributor_realname', 'o2o_distributor_introduce', 'o2o_distributor_region_id'],
        'o2o_distributor_update_payment_account' => ['o2o_distributor_payment_account'],
        'o2o_distributor_update_phone' => ['o2o_distributor_phone'],
        'o2o_distributor_update_email' => ['o2o_distributor_email'],
        'o2o_distributor_update_password' => ['o2o_distributor_password'],
    ];

}
