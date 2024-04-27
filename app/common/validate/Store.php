<?php

namespace app\common\validate;


use think\Validate;

class  Store extends Validate
{
    protected $rule = [
        'store_name'=>'require|min:2|max:50',
        'store_mainbusiness'=>'max:255',
        'store_vrcode_prefix'=>'max:3',
        'store_qq'=>'min:5|max:20',
        'store_ww'=>'min:5|max:20',
        'store_phone'=>'min:6|max:20',
        'store_keywords'=>'max:255',
        'store_description'=>'max:255',
    ];
    protected $message = [
        'store_name.require'=>'店铺名称为必填',
        'store_name.min'=>'店铺名称长度不能小于2',
        'store_name.max'=>'店铺名称长度不能大于50',
        'store_mainbusiness.max'=>'主营商品长度不能大于50',
        'store_vrcode_prefix.max'=>'兑换码生成前缀长度不能大于3',
        'store_qq.min'=>'QQ长度不能小于5',
        'store_qq.max'=>'QQ长度不能大于20',
        'store_ww.min'=>'阿里旺旺长度不能小于5',
        'store_ww.max'=>'阿里旺旺长度不能大于20',
        'store_phone.min'=>'店铺电话长度不能小于6',
        'store_phone.max'=>'店铺电话长度不能大于20',
        'store_description.max'=>'店铺描述长度不能大于255',
    ];
    protected $scene = [
        'seller_setting' => ['store_name','store_mainbusiness','store_vrcode_prefix','store_qq','store_ww','store_phone','store_keywords','store_description'],
    ];
}