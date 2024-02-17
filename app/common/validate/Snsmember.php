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
class  Snsmember extends Validate
{
    protected $rule = [
        'membertag_name'=>'require',
        'membertag_sort'=>'require|number',
    ];
    protected $message = [
        'membertag_name.require'=>'会员标签名称不能为空',
        'membertag_sort.require'=>'会员标签排序只能为数字',
        'membertag_sort.number'=>'会员标签排序只能为数字',
    ];
    protected $scene = [
        'tag_add' => ['membertag_name', 'membertag_sort'],
        'tag_edit' => ['membertag_name', 'membertag_sort'],
    ];
}