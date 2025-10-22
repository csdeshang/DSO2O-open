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
//常用单个字段验证
class Singlefield extends Validate {

    protected $rule = [
        'password' => 'require|length:6,20',
        'member_email' => 'require|email',
        'member_mobile' => 'require|mobile',
        'verify_code' => 'require|length:6,6',
        'pdc_amount' => 'require|float|between:0.01,1000000',
        'pdr_amount' => 'require|float|between:0.01,1000000',
    ];
    protected $message = [
        'password.require' => '密码为必填',
        'password.length' => '密码长度必须为6-20之间',
        'member_email.require' => '请填写邮箱',
        'member_email.email' => '请正确填写邮箱',
        'member_mobile.require' => '请填写手机号',
        'member_mobile.mobile' => '请正确填写手机号',
        'verify_code.require' => '请正确填写手机验证码',
        'verify_code.length' => '验证码的长度为6位',
        'pdc_amount.require' => '请填写取出金额',
        'pdc_amount.float' => '取出金额必须是数字',
        'pdc_amount.between' => '提现金额为0.01至100万',
        'pdr_amount.require' => '请填写充值金额',
        'pdr_amount.float' => '充值金额必须是数字',
        'pdr_amount.between' => '充值金额为0.01至100万',
    ];
    protected $scene = [
        //单个字段验证
        'password' => ['password'],
        'member_email' => ['member_email'],
        'member_mobile' => ['member_mobile'],
        'verify_code' => ['verify_code'],
        'pdc_amount' => ['pdc_amount'],
        'pdr_amount' => ['pdr_amount'],
    ];
}
