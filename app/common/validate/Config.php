<?php

namespace app\common\validate;

use think\Validate;

/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class Config extends Validate {

    protected $rule = [
        //promotion_voucher
        'promotion_voucher_price' => 'require|number',
        'promotion_voucher_storetimes_limit' => 'require|number',
        'promotion_voucher_buyertimes_limit' => 'require|number|elt:100',
    ];
    protected $message = [
        //promotion_voucher
        'promotion_voucher_price.require' => '购买单价应为大于0的整数',
        'promotion_voucher_price.number' => '购买单价应为大于0的整数',
        'promotion_voucher_storetimes_limit.require' => '每月活动数量应为大于0的整数',
        'promotion_voucher_storetimes_limit.number' => '每月活动数量应为大于0的整数',
        'promotion_voucher_buyertimes_limit.require' => '最大领取数量应为大于0小于100的整数',
        'promotion_voucher_buyertimes_limit.number' => '最大领取数量应为大于0小于100的整数',
        'promotion_voucher_buyertimes_limit.elt' => '最大领取数量应为大于0小于100的整数',
    ];
    protected $scene = [
        'promotion_voucher_setting' => ['promotion_voucher_price', 'promotion_voucher_storetimes_limit', 'promotion_voucher_buyertimes_limit'],
    ];
}
