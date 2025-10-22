<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 * 通用代码
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  Goodscommon extends Validate
{
    protected $rule = [
        'goods_name'=>'require|length:3,50',
        'goods_price'=>'require',
        'goods_storage_alarm'=>'between:0,255'
    ];
    protected $message = [
        'goods_name.require'=>'商品名称不能为空',
        'goods_name.length'=>'商品名称长度应该在3至50字符之间',
        'goods_price.require'=>'商品价格不能为空',
        'goods_storage_alarm.between'=>'库存预警值不能超过255',
    ];
    protected $scene = [
        'add' => ['goods_name', 'goods_price','goods_storage_alarm'],
        'edit' => ['goods_name', 'goods_price','goods_storage_alarm'],
    ];
}