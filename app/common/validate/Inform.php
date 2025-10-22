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
class Inform extends Validate {

    protected $rule = [
        'informsubject_content' => 'require|length:0,50',
        'inform_handle_message' => 'require|length:0,100',
        'inform_content' => 'require|length:0,100',
    ];
    protected $message = [
        'informsubject_content.require' => '举报内容不能为空且不能大于50个字符',
        'informsubject_content.length' => '举报内容不能为空且不能大于50个字符',
        'inform_handle_message.require' => '处理信息不能为空且不能大于100个字符',
        'inform_handle_message.length' => '处理信息不能为空且不能大于100个字符',
        'inform_content.require' => '举报内容不能为空且不能大于100个字符',
        'inform_content.length' => '举报内容不能为空且不能大于100个字符',
    ];
    protected $scene = [
        'inform_handle' => ['inform_handle_message'],
        'add' => ['inform_content', 'informsubject_content'],
    ];
}
