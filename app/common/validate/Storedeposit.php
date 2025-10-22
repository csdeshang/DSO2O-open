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
class Storedeposit extends Validate {

    protected $rule = [
        'store_id' => 'require|number',
        'amount' => 'require|float|between:0.01,1000000',
        'operatetype' => 'require',
    ];
    protected $message = [
        'store_id.require' => '请输入店主用户名',
        'store_id.number' => '店主信息错误',
        'amount.require' => '请添加金额',
        'amount.float' => '金额格式不正确',
        'amount.between' => '金额为0.01至100万之间',
        'operatetype.require' => '请输入增减类型',
    ];
    protected $scene = [
        'adjust' => ['store_id', 'amount', 'operatetype'],
    ];
}
