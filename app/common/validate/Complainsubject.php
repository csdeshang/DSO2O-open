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
class Complainsubject extends Validate {

    protected $rule = [
        'complainsubject_content' => 'require|length:0,50',
        'complainsubject_desc' => 'require|length:0,100',
    ];
    protected $message = [
        'complainsubject_content.require' => '投诉主题不能为空',
        'complainsubject_content.length' => '投诉主题必须小于50个字符',
        'complainsubject_desc.require' => '投诉主题描述不能为空',
        'complainsubject_desc.length' => '投诉主题描述必须小于100个字符',
    ];
    protected $scene = [
        'add' => ['complainsubject_content', 'complainsubject_desc'],
    ];
}
