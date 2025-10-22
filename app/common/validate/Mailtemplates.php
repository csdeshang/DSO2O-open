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
class Mailtemplates extends Validate {

    protected $rule = [
        'mailmt_code' => 'require|length:0,30',
        'mailmt_title' => 'require|length:0,50',
        'mailmt_content' => 'require',
    ];
    protected $message = [
        'mailmt_code.require' => '编号不能为空',
        'mailmt_code.length' => '编号不能大于50个字符',
        'mailmt_title.require' => '标题不能为空',
        'mailmt_title.length' => '标题不能大于50个字符',
        'mailmt_content.require' => '正文不能为空',
    ];
    protected $scene = [
        'email_tpl_edit' => ['mailmt_code', 'mailmt_title', 'mailmt_content'],
    ];
}
