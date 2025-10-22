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
class Albumclass extends Validate {

    protected $rule = [
        'aclass_name' => 'require|length:0,50',
        'aclass_des' => 'length:0,255',
        'aclass_sort' => 'between:0,255',
    ];
    protected $message = [
        'aclass_name.require' => '相册名称必填',
        'aclass_name.length' => '相册名称长度不能大于50',
        'aclass_des.length' => '相册描述内容长度不能大于255',
        'aclass_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['aclass_name', 'aclass_des', 'aclass_sort'],
        'edit' => ['aclass_name', 'aclass_des', 'aclass_sort']
    ];
}
