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
class Consult extends Validate {

    protected $rule = [
        'consult_content' => 'require|length:0,255',
        'consult_reply' => 'require|length:0,255',
    ];
    protected $message = [
        'consult_content.require' => '咨询内容不能为空',
        'consult_content.length' => '咨询内容名称长度不能大于255',
        'consult_reply.require' => '内容不能为空',
        'consult_reply.length' => '内容长度不能超过255字符',
    ];
    protected $scene = [
        'add' => ['consult_content'],
        'edit' => ['consult_reply'],
    ];
}
