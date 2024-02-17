<?php

namespace app\common\validate;

use think\Validate;
/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  InstantMessage extends Validate
{
    protected $rule = [
        'instant_message_from_id'=>'require',
        'instant_message_from_type'=>'require',
        'instant_message_to_id'=>'require',
        'instant_message_to_type'=>'require',
        'instant_message_type'=>'require',
        'instant_message'=>'require',
    ];
    protected $message  =   [
        'instant_message_from_id.require' => '发送ID不能为空',
        'instant_message_from_type.require' => '发送类型不能为空',
        'instant_message_to_id.require' => '接收ID不能为空',
        'instant_message_to_type.require' => '接收类型不能为空',
        'instant_message_type.require' => '消息类型不能为空',
        'instant_message.require' => '消息内容不能为空',
    ];
    protected $scene = [
        'instant_message_save' => ['instant_message_from_id', 'instant_message_from_type', 'instant_message_to_id', 'instant_message_to_type', 'instant_message_type', 'instant_message'],
    ];
}