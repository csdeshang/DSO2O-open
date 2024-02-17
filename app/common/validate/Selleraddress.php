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
class  Selleraddress extends Validate
{
    protected $rule = [
        'seller_name'=>'require',
        'area_id'=>'require|number',
        'city_id'=>'require|number',
        'area_info'=>'require',
        'daddress_detail'=>'require',
    ];
    protected $message = [
        'seller_name.require'=>'收件人不能为空',
        'area_id.require'=>'请选择地址|请选择地址',
        'area_id.number'=>'请选择地址|请选择地址',
        'city_id.require'=>'请选择地址|请选择地址',
        'city_id.number'=>'请选择地址|请选择地址',
        'area_info.require'=>'请选择地址',
        'daddress_detail.require'=>'详细地址不能为空',
    ];
    protected $scene = [
        'address_add' => ['seller_name', 'area_id', 'city_id', 'area_info', 'daddress_detail', 'daddress_telphone'],
    ];
}