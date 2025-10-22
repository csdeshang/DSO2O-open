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
class Storegrade extends Validate {

    protected $rule = [
        'storegrade_name' => 'require|unique:storegrade',
        'storegrade_goods_limit' => 'number|length:0,10',
        'storegrade_album_limit' => 'number|length:0,10',
        'storegrade_space_limit' => 'number|length:0,10',
        'storegrade_price' => 'number|length:0,10',
        'storegrade_sort' => 'require|number|between:1,255|unique:storegrade'
    ];
    protected $message = [
        'storegrade_name.require' => '店铺等级名称必填',
        'storegrade_name.unique' => '店铺等级已存在',
        'storegrade_goods_limit.number' => '允许发布商品数量必须为数字',
        'storegrade_goods_limit.length' => '允许发布商品数量长度不能大于11',
        'storegrade_album_limit.number' => '允许发布图片数量必须为数字',
        'storegrade_album_limit.length' => '允许发布图片数量长度不能大于11',
        'storegrade_space_limit.number' => '允许上传空间大小必须为数字',
        'storegrade_space_limit.length' => '允许上传空间大小长度不能大于11',
        'storegrade_price.length' => '店铺等级费用必须为数字',
        'storegrade_price.length' => '店铺等级费用长度不能大于11',
        'storegrade_sort.require' => '排序为必填',
        'storegrade_sort.number' => '排序必须是数字',
        'storegrade_sort.between' => '等级级别不能大于255',
        'storegrade_sort.unique' => '店铺等级重复',
    ];
    protected $scene = [
        'add' => ['storegrade_name', 'storegrade_goods_limit', 'storegrade_album_limit', 'storegrade_space_limit', 'storegrade_price', 'storegrade_sort'],
        'edit' => ['storegrade_name', 'storegrade_goods_limit', 'storegrade_album_limit', 'storegrade_space_limit', 'storegrade_price', 'storegrade_sort'],
    ];
}
