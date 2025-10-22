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
class Mallconsult extends Validate {

    protected $rule = [
        'mallconsulttype_id' => 'require|number',
        'mallconsult_content' => 'require|length:0,255'
    ];
    protected $message = [
        'mallconsulttype_id.require' => '请选择咨询类型',
        'mallconsulttype_id.number' => '请选择咨询类型',
        'mallconsult_content.require' => '请填写咨询内容',
        'mallconsult_content.length' => '咨询内容不能大于255个字符',
    ];
    protected $scene = [
        'add' => ['mallconsulttype_id', 'mallconsult_content'],
        'edit' => ['mallconsult_content'],
    ];
}
