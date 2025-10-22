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
class Voucher extends Validate {

    protected $rule = [
        'voucherprice' => 'require|number|length:0,11',
        'voucherprice_describe' => 'require|length:0,255',
        'voucherprice_defaultpoints' => 'require|number|length:0,11',
    ];
    protected $message = [
        'voucherprice.require' => '代金券面额应为大于0的整数',
        'voucherprice.number' => '代金券面额应为大于0的整数',
        'voucherprice.length' => '代金券面额长度应该小于11位',
        'voucherprice_describe.require' => '描述不能为空',
        'voucherprice_describe.length' => '描述长度应该小于255',
        'voucherprice_defaultpoints.require' => '默认兑换积分数应为大于0的整数',
        'voucherprice_defaultpoints.number' => '默认兑换积分数应为大于0的整数',
        'voucherprice_defaultpoints.length' => '默认兑换积分数长度应该小于11位',
    ];
    protected $scene = [
        'priceadd' => ['voucherprice', 'voucherprice_describe', 'voucherprice_defaultpoints'],
        'priceedit' => ['voucherprice', 'voucherprice_describe', 'voucherprice_defaultpoints'],
    ];
}
