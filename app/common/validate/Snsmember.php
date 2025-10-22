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
class Snsmember extends Validate {

    protected $rule = [
        'mtag_name' => 'require|length:0,20',
        'mtag_sort' => 'number|between:0,255',
        'mtag_desc' => 'length:0,50',
    ];
    protected $message = [
        'mtag_name.require' => '会员标签名称不能为空',
        'mtag_name.length' => '会员标签名称长度不能大于20',
        'mtag_sort.number' => '排序必须为0-255间数字',
        'mtag_sort.between' => '排序必须为0-255间数字',
        'mtag_desc.length' => '会员标签描述长度不能大于50',
    ];
    protected $scene = [
        'tag_add' => ['mtag_name', 'mtag_sort', 'mtag_desc'],
        'tag_edit' => ['mtag_name', 'mtag_sort', 'mtag_desc'],
    ];
}
