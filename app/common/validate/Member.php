<?php

namespace app\common\validate;

use think\Validate;

/**
 * ============================================================================
 * 通用文件
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class Member extends Validate {

    protected $rule = [
        'member_name' => 'require|alphaDash|length:3,20|unique:member',
        'member_password' => 'require|length:6,20',
        'member_truename' => 'length:1,20',
        'member_nickname' => 'max:20',
        'member_idcard' => 'idCard',
        'member_email' => 'email',
        'member_mobile' => 'mobile|unique:member',
        'member_qq' => 'length:5,12',
        'member_ww' => 'length:5,20',
        'member_qqopenid' => 'unique:member',
        'member_sinaopenid' => 'unique:member',
        'member_wxunionid' => 'unique:member',
    ];
    protected $message = [
        'member_name.require' => '用户名必填',
        'member_name.alphaDash' => '用户名只能为字母、数字、下划线、破折号',
        'member_name.length' => '用户名长度在3到20位',
        'member_name.unique' => '用户名已存在',
        'member_password.require' => '密码为必填',
        'member_password.length' => '密码长度必须为6-20之间',
        'member_truename.length' => '真实姓名不能超过20字符',
        'member_nickname.max' => '会员昵称长度不能超过20位',
        'member_idcard.idCard' => '身份证错误',
        'member_email.email' => '邮箱格式错误',
        'member_mobile.mobile' => '手机格式错误',
        'member_mobile.unique' => '手机号已被绑定',
        'member_qq.length' => 'QQ的长度应该在5至12位之间',
        'member_ww.length' => '旺旺的长度应该在5至20位之间',
        'member_qqopenid.unique' => 'QQ号已被绑定',
        'member_sinaopenid.unique' => 'Sina号已被绑定',
        'member_wxunionid.unique' => '微信号已被绑定',
    ];
    protected $scene = [
        'model_add' => ['member_name', 'member_password', 'member_truename', 'member_nickname', 'member_idcard', 'member_email', 'member_mobile', 'member_qq', 'member_ww', 'member_qqopenid', 'member_sinaopenid', 'member_wxunionid'],
        'model_edit' => ['member_truename', 'member_nickname', 'member_idcard', 'member_email', 'member_mobile', 'member_qq', 'member_ww', 'member_qqopenid', 'member_sinaopenid', 'member_wxunionid'],
    ];
}
