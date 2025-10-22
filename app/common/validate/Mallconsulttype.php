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
class Mallconsulttype extends Validate {

    protected $rule = [
        'mallconsulttype_name' => 'require|length:0,50',
        'mallconsulttype_sort' => 'number|between:0,255',
    ];
    protected $message = [
        'mallconsulttype_name.require' => '请填写咨询类型名称',
        'mallconsulttype_name.length' => '咨询类型名称不能大于50个字符',
        'mallconsulttype_sort.number' => '排序必须为0-255间数字',
        'mallconsulttype_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['mallconsulttype_name', 'mallconsulttype_sort'],
        'edit' => ['mallconsulttype_name', 'mallconsulttype_sort'],
    ];
}
