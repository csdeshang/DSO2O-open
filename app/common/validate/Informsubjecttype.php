<?php

namespace app\common\validate;

use think\Validate;

/**
 * ============================================================================
 * 通用代码
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class Informsubjecttype extends Validate {

    protected $rule = [
        'informtype_name' => 'require|length:0,50',
        'informtype_desc' => 'require|length:0,100',
    ];
    protected $message = [
        'informtype_name.require' => '举报类型不能为空且不能大于50个字符',
        'informtype_name.length' => '举报类型不能为空且不能大于50个字符',
        'informtype_desc.require' => '举报类型描述不能为空且不能大于100个字符',
        'informtype_desc.length' => '举报类型描述不能为空且不能大于100个字符',
    ];
    protected $scene = [
        'add' => ['informtype_name', 'informtype_desc'],
        'edit' => ['informtype_name', 'informtype_desc'],
    ];
}
