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
class  O2oOrderBill extends Validate
{
    protected $rule = [
        'o2o_order_bill_state'=>'in:0,1,2,3',
        'o2o_order_bill_start_time'=>'integer',
        'o2o_order_bill_end_time'=>'integer',
    ];
    protected $message  =   [
        'o2o_order_bill_state.in' => '结算状态错误',
        'o2o_order_bill_start_time.integer' => '开始时间错误',
        'o2o_order_bill_end_time.integer' => '结束时间错误',
    ];
    protected $scene = [
        'o2o_order_bill_search' => ['o2o_order_bill_state', 'o2o_order_bill_start_time', 'o2o_order_bill_end_time'],
    ];
}