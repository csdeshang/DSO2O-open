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
class Goodsclass extends Validate {

    protected $rule = [
        'gc_name' => 'length:0,50',
        'type_name' => 'length:0,50',
        'gc_title' => 'length:0,50',
        'gc_sort' => 'number|between:0,255',
        'gc_keywords' => 'length:0,255',
        'gc_description' => 'length:0,255',
    ];
    protected $message = [
        'gc_name.length' => '分类标题长度不能大于50',
        'type_name.length' => '类型长度不能大于50',
        'gc_title.length' => '标题长度不能大于50',
        'gc_sort.number' => '排序应该在0至255之间',
        'gc_sort.between' => '排序应该在0至255之间',
        'gc_keywords.length' => '商品分类关键词长度不能大于255',
        'gc_description.length' => '商品分类描述长度不能大于255',
    ];
    protected $scene = [
        'add' => ['gc_name', 'type_name', 'gc_title', 'gc_sort', 'gc_keywords', 'gc_description'],
        'edit' => ['gc_name', 'type_name', 'gc_title', 'gc_sort', 'gc_keywords', 'gc_description'],
    ];
}
