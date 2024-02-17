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
class  Sellerplate extends Validate
{
    protected $rule = [
        'storeplate_name'=>'require',
        'storeplate_content'=>'require',
    ];
    protected $message = [
        'storeplate_name.require'=>'请填写版式名称',
        'storeplate_content.require'=>'请填写版式内容',
    ];
    protected $scene = [
        'plate_add' => ['storeplate_name', 'storeplate_content'],
        'plate_edit' => ['storeplate_name', 'storeplate_content'],
    ];
}