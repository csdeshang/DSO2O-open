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
class  Predeposit extends Validate
{
    protected $rule = [
        //后台调节用户预存款
        'member_id'=>'require|number',
        'amount'=>'require|float|between:0.01,1000000',
        'operatetype'=>'require',
        'lg_desc'=>'length:0,150',
        //申请提现
        'pdc_amount'=>'require|float|between:0.01,1000000',
        'password'=>'require'
    ];
    protected $message = [
        'member_id.require'=>'用户名必须存在',
        'member_id.number'=>'用户名错误',
        'amount.require'=>'金额为必填',
        'amount.float'=>'金额格式不正确',
        'amount.between'=>'金额为0.01至100万之间',
        'operatetype.require'=>'增减类型为必填',
        'lg_desc.length' => '描述信息长度不能大于150',
        //申请提现
        'pdc_amount.require'=>'提现金额为大于或者等于0.01的数字',
        'pdc_amount.float'=>'提现金额格式不正确',
        'pdc_amount.between'=>'提现金额为0.01至100万',
        'password.require'=>'请输入支付密码'
    ];
    protected $scene = [
        //后台调节用户预存款
        'pd_add' => ['member_id', 'amount', 'operatetype','lg_desc'],
        //申请提现
        'pd_cash_add' => ['pdc_amount',  'password'],
    ];
}