<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 * DSO2O多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  Sellervoucher extends Validate
{
    protected $rule = [
        'txt_template_title'=>'require|length:1,50',
        'txt_template_total'=>'require|number',
        'select_template_price'=>'require|number',
        'txt_template_limit'=>'require',
        'txt_template_describe'=>'require|length:1,255'
    ];
    protected $message = [
        'txt_template_title.require'=>'模版名称不能为空且不能大于50个字符',
        'txt_template_title.length'=>'模版名称不能为空且不能大于50个字符',
        'txt_template_total.require'=>'可发放数量不能为空且必须为整数',
        'txt_template_total.number'=>'可发放数量不能为空且必须为整数',
        'select_template_price.require'=>'模版面额不能为空且必须为整数，且面额不能大于限额',
        'select_template_price.number'=>'模版面额不能为空且必须为整数，且面额不能大于限额',
        'txt_template_limit.require'=>'模版使用消费限额不能为空且必须是数字',
        'txt_template_describe.require'=>'模版描述不能为空且不能大于255个字符',
        'txt_template_describe.length'=>'模版描述不能为空且不能大于255个字符'
    ];
    protected $scene = [
        'templateadd' => ['txt_template_title', 'txt_template_total', 'select_template_price', 'txt_template_limit', 'txt_template_describe'],
        'templateedit' => ['txt_template_title', 'txt_template_total', 'select_template_price', 'txt_template_limit', 'txt_template_describe'],
    ];
}