<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  Vouchertemplate extends Validate
{
    protected $rule = [
        'vouchertemplate_title'=>'require|length:1,50',
        'vouchertemplate_total'=>'require|number',
        'vouchertemplate_price'=>'require|number',
        'vouchertemplate_limit'=>'require',
        'vouchertemplate_desc'=>'require|length:1,255'
    ];
    protected $message = [
        'vouchertemplate_title.require'=>'模版名称不能为空且不能大于50个字符',
        'vouchertemplate_title.length'=>'模版名称不能为空且不能大于50个字符',
        'vouchertemplate_total.require'=>'可发放数量不能为空且必须为整数',
        'vouchertemplate_total.number'=>'可发放数量不能为空且必须为整数',
        'vouchertemplate_price.require'=>'模版面额不能为空且必须为整数，且面额不能大于限额',
        'vouchertemplate_price.number'=>'模版面额不能为空且必须为整数，且面额不能大于限额',
        'vouchertemplate_limit.require'=>'模版使用消费限额不能为空且必须是数字',
        'vouchertemplate_desc.require'=>'模版描述不能为空且不能大于255个字符',
        'vouchertemplate_desc.length'=>'模版描述不能为空且不能大于255个字符'
    ];
    protected $scene = [
        'add' => ['vouchertemplate_title', 'vouchertemplate_total', 'vouchertemplate_price', 'vouchertemplate_limit', 'vouchertemplate_desc'],
        'edit' => ['vouchertemplate_title', 'vouchertemplate_total', 'vouchertemplate_price', 'vouchertemplate_limit', 'vouchertemplate_desc'],
    ];
}