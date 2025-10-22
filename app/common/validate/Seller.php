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
class Seller extends Validate {

    protected $rule = [
        'seller_name' => 'require|alphaDash|length:3,20',
        'password' => 'require|length:6,20',
    ];
    protected $message = [
        'seller_name.require' => '卖家用户名必填',
        'seller_name.alphaDash' => '卖家用户名只能为字母、数字、下划线、破折号',
        'seller_name.length' => '用户名长度在3到20位',
        'password.require' => '密码为必填',
        'password.length' => '密码长度必须为6-20之间',
    ];
    protected $scene = [
        'login' => ['seller_name', 'password'],
    ];
}
