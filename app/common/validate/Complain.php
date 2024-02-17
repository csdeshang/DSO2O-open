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
class  Complain extends Validate
{
    protected $rule = [
        'final_handle_message'=>'require|max:255|min:1',
        'complain_subject_content'=>'require|max:50|min:1',
        'complain_subject_desc'=>'require|max:50|min:1',
    ];
    protected $message = [
        'final_handle_message.require'=>'处理意见不能为空',
        'final_handle_message.max'=>'必须小于255个字符',
        'final_handle_message.min'=>'必须大于1个字符',
        'complain_subject_content.require'=>'投诉主题不能为空',
        'complain_subject_content.max'=>'必须小于50个字符',
        'complain_subject_content.min'=>'必须大于1个字符',
        'complain_subject_desc.require'=>'投诉主题描述不能为空',
        'complain_subject_desc.max'=>'必须小于50个字符',
        'complain_subject_desc.min'=>'必须大于1个字符',
    ];
    protected $scene = [
        'complain_close' => ['final_handle_message'],
        'complain_subject_add' => ['complain_subject_content','complain_subject_desc'],
    ];
}