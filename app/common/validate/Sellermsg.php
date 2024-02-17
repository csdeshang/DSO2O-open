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
class  Sellermsg extends Validate
{
    protected $rule = [
        'storems_short_number'=>'regex:^1[0-9]{10}$',
        'storems_mail_number'=>'email'
    ];
    protected $message = [
        'storems_short_number.regex'=>'请填写正确的手机号码',
        'storems_mail_number.email'=>'请填写正确的邮箱'
    ];
    protected $scene = [
        'save_msg_setting' => ['storems_short_number','storems_mail_number'],
    ];
}