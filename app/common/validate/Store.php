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
class Store extends Validate {

    protected $rule = [
        'store_name' => 'unique:store|min:2|max:50',
        'store_mainbusiness' => 'max:255',
        'store_vrcode_prefix' => 'max:3',
        'store_qq' => 'min:5|max:20',
        'store_ww' => 'min:5|max:20',
        'store_phone' => 'min:6|max:20',
        'store_keywords' => 'max:255',
        'store_description' => 'max:255',
    ];
    protected $message = [
        'store_name.unique' => '已存在相同店铺名称',
        'store_name.min' => '店铺名称长度不能小于2',
        'store_name.max' => '店铺名称长度不能大于50',
        'store_mainbusiness.max' => '主营商品长度不能大于50',
        'store_vrcode_prefix.max' => '兑换码生成前缀长度不能大于3',
        'store_qq.min' => 'QQ长度不能小于5',
        'store_qq.max' => 'QQ长度不能大于20',
        'store_ww.min' => '阿里旺旺长度不能小于5',
        'store_ww.max' => '阿里旺旺长度不能大于20',
        'store_phone.min' => '店铺电话长度不能小于6',
        'store_phone.max' => '店铺电话长度不能大于20',
        'store_description.max' => '店铺描述长度不能大于255',
    ];
    protected $scene = [
        'model_add' => ['store_name', 'store_mainbusiness', 'store_vrcode_prefix', 'store_qq', 'store_ww', 'store_phone', 'store_keywords', 'store_description'],
        'model_edit' => ['store_name', 'store_mainbusiness', 'store_vrcode_prefix', 'store_qq', 'store_ww', 'store_phone', 'store_keywords', 'store_description'],
    ];
}
