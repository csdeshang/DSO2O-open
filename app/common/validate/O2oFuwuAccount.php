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
class  O2oFuwuAccount extends Validate {

    protected $rule = [
        'o2o_fuwu_account_name' => 'require|max:60',
        'o2o_fuwu_account_password' => 'require|length:6,20',
        'o2o_fuwu_account_phone' => 'require|length:11',
    ];
    protected $message = [
        'o2o_fuwu_account_name.require' => '用户名必填',
        'o2o_fuwu_account_name.max' => '用户名长度不能超过60',
        'o2o_fuwu_account_password.require' => '密码为必填',
        'o2o_fuwu_account_password.length' => '密码长度必须为6-20之间',
        'o2o_fuwu_account_phone.require' => '手机号必填',
        'o2o_fuwu_account_phone.length' => '手机格式错误',
    ];
    protected $scene = [
        'o2o_fuwu_account_add' => ['o2o_fuwu_account_name', 'o2o_fuwu_account_password', 'o2o_fuwu_account_phone'],
        'o2o_fuwu_account_login' => ['o2o_fuwu_account_name', 'o2o_fuwu_account_password'],
        'o2o_fuwu_account_edit_phone' => ['o2o_fuwu_account_phone'],
        'o2o_fuwu_account_edit_password' => ['o2o_fuwu_account_password'],
    ];

}
