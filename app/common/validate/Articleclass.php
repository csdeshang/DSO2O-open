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
class Articleclass extends Validate {

    protected $rule = [
        'ac_name' => 'require|length:0,50',
        'ac_sort' => 'number|between:0,255'
    ];
    protected $message = [
        'ac_name.require' => '分类名称不能为空',
        'ac_name.length' => '分类名称长度不能大于50',
        'ac_sort.number' => '分类排序仅能为数字',
        'ac_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['ac_name', 'ac_sort'],
        'edit' => ['ac_name', 'ac_sort'],
    ];
}
