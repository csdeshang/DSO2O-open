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
class Consulttype extends Validate {

    protected $rule = array(
        'consulttype_name' => 'require|length:0,10',
        'consulttype_sort' => 'number|between:0,255',
    );
    protected $message = array(
        'consulttype_name.require' => '请填写咨询类型名称',
        'consulttype_name.length' => '咨询类型名称长度不能大于10',
        'consulttype_sort.number' => '请正确填写咨询类型排序',
        'consulttype_sort.between' => '咨询类型排序必须为0-255间数字',
    );
    protected $scene = [
        'add' => ['consulttype_name', 'consulttype_sort'],
        'edit' => ['consulttype_name', 'consulttype_sort'],
    ];
}
