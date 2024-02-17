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
class  Storegrade extends Validate
{
    protected $rule = [
        'storegrade_name'=>'require',
        'storegrade_sort'=>'require|number|between:1,255'

    ];
    protected $message = [
        'storegrade_name.require'=>'店铺等级名称必填',
        'storegrade_sort.require'=>'排序为必填',
        'storegrade_sort.number'=>'排序必须是数字',
        'storegrade_sort.between'=>'等级级别不能大于255'

    ];
    protected $scene = [
        'add' => ['storegrade_name', 'storegrade_sort'],
        'edit' => ['storegrade_name', 'storegrade_sort'],
    ];
}