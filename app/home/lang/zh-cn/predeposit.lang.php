<?php

/**
 * 预存款功能公用
 */
$lang['predeposit_no_record']	 			= '没有符合条件的记录';
$lang['predeposit_unavailable']	 			= '系统未开启预存款功能';
$lang['predeposit_parameter_error']			= '参数错误';
$lang['predeposit_record_error']			= '记录信息错误';
$lang['predeposit_userrecord_error']		= '会员信息错误';
$lang['predeposit_payment']					= '支付方式';
$lang['predeposit_addtime']					= '创建时间';
$lang['predeposit_apptime']					= '申请时间';
$lang['predeposit_checktime']					= '审核时间';
$lang['predeposit_paytime']					= '付款时间';
$lang['predeposit_addtime_to']				= '至';
$lang['predeposit_trade_no']				= '交易号';
$lang['predeposit_adminremark']				= '管理员备注';
$lang['predeposit_recordstate']				= '记录状态';
$lang['predeposit_paystate']				= '状态';
$lang['predeposit_backlist']				= '返回列表';
$lang['predeposit_pricetype']				= '预存款类型';
$lang['predeposit_pricetype_available']		= '可用金额';
$lang['predeposit_pricetype_freeze']		= '冻结金额';
$lang['predeposit_price']					= '金额';
$lang['predeposit_payment_error']			= '支付方式错误';
/**
 * 充值功能公用
 */
$lang['predeposit_rechargesn']					= '充值单号';
$lang['predeposit_rechargewaitpaying']			= '未支付';
$lang['predeposit_rechargepaysuccess']			= '已支付';
$lang['predeposit_rechargestate_auditing']		= '审核中';
$lang['predeposit_rechargestate_approved']		= '已审核';
$lang['predeposit_rechargestate_completed']		= '已完成';
$lang['predeposit_rechargestate_closed']		= '已关闭';
$lang['predeposit_recharge_price']				= '充值金额';
$lang['predeposit_recharge_huikuanname']		= '汇款人姓名';
$lang['predeposit_recharge_huikuanbank']		= '汇款银行';
$lang['predeposit_recharge_huikuandate']		= '汇款日期';
$lang['predeposit_recharge_memberremark']		= '会员备注';
$lang['predeposit_recharge_success']			= '充值成功';
$lang['predeposit_recharge_fail']				= '充值失败';
$lang['predeposit_recharge_pay']				= '支&nbsp;付';
$lang['predeposit_recharge_view']				= '查看详单';
$lang['predeposit_recharge_paydesc']			= '预存款充值订单';
$lang['predeposit_recharge_pay_offline']		= '待确认';
/**
 * 充值添加
 */
$lang['predeposit_recharge_add_pricenull_error']			= '请添加充值金额';
$lang['predeposit_recharge_add_pricemin_error']				= '充值金额为大于或者等于0.01的数字';
/**
 * 充值信息删除
 */
$lang['predeposit_recharge_del_success']		= '充值信息删除成功';
$lang['predeposit_recharge_del_fail']		= '充值信息删除失败';
/**
 * 提现功能公用
 */
$lang['predeposit_cashsn']				= '申请单号';
$lang['predeposit_cashmanage']			= '提现管理';
$lang['predeposit_cashwaitpaying']		= '等待支付';
$lang['predeposit_cashpaysuccess']		= '支付成功';
$lang['predeposit_cashstate_auditing']	= '审核中';
$lang['predeposit_cashstate_completed']	= '已完成';
$lang['predeposit_cashstate_closed']		= '已关闭';
$lang['predeposit_cash_price']				= '提现金额';
$lang['predeposit_cash_shoukuanname']			= '开户人姓名';
$lang['predeposit_cash_shoukuanbank']			= '收款银行';
$lang['predeposit_cash_shoukuanaccount']		= '收款账号';
$lang['predeposit_cash_shortprice_error']		= '预存款金额不足';
$lang['predeposit_cash_price_tip']				= '当前可用金额';

$lang['predeposit_cash_availablereducedesc']	=  '会员申请提现减少预存款金额';
$lang['predeposit_cash_freezeadddesc']	=  '会员申请提现增加冻结预存款金额';
$lang['predeposit_cash_availableadddesc']	=  '会员删除提现增加预存款金额';
$lang['predeposit_cash_freezereducedesc']	=  '会员删除提现减少冻结预存款金额';

/**
 * 提现添加
 */
$lang['predeposit_cash_add_shoukuannamenull_error']		= '请填写收款人姓名';
$lang['predeposit_cash_add_shoukuanbanknull_error']		= '请填写收款银行';
$lang['predeposit_cash_add_pricemin_error']				= '提现金额为大于或者等于0.01的数字';
$lang['predeposit_cash_add_enough_error']				= '账户余额不足';
$lang['predeposit_cash_add_pricenull_error']			= '请填写提现金额';
$lang['predeposit_cash_add_shoukuanaccountnull_error']	= '请填写收款账号';
$lang['predeposit_cash_add_success']					= '您的提现申请已成功提交，请等待系统处理';
$lang['predeposit_cash_add_fail']						= '提现信息添加失败';
/**
 * 提现信息删除
 */
$lang['predeposit_cash_del_success']	= '提现信息删除成功';
$lang['predeposit_cash_del_fail']		= '提现信息删除失败';
/**
 * 支付接口
 */
$lang['predeposit_payment_pay_fail']		= '充值失败';
$lang['predeposit_payment_pay_success']		= '充值成功，正在前往我的订单';
$lang['predepositrechargedesc']	=  '充值';
/**
 * 出入明细 
 */
$lang['predeposit_log_stage'] 			= '类型';
$lang['predeposit_log_stage_recharge']	= '充值';
$lang['predeposit_log_stage_cash']		= '提现';
$lang['predeposit_log_stage_order']		= '消费';
$lang['predeposit_log_stage_artificial']= '手动修改';
$lang['predeposit_log_stage_system']	= '系统';
$lang['predeposit_log_stage_income']	= '收入';
$lang['predeposit_log_desc']			= '变更说明';

//pd_cash
$lang['withdrawal_amount'] = '提现金额';
$lang['current_available_amount'] = '当前可用金额';
$lang['collection_bank'] = '收款银行';
$lang['withdrawal_information3'] = '还未设置支付密码';
$lang['immediately_set'] = '马上设置';
$lang['confirm_withdrawal'] = '确认提现';
$lang['cancel_return'] = '取消并返回';
$lang['enter_withdrawal_amount_correctly'] = '请正确输入提现金额';
$lang['enter_payment_password'] = '请输入支付密码';
$lang['predeposit_withdraw_cycle']             = '提现间隔周期';
$lang['payment_password'] = '支付密码';

//pd_cash_list
$lang['predeposit_application_withdrawal']	= '申请提现';

//pd_log_list
$lang['predeposit_online_recharge']	= '在线充值';
$lang['predeposit_spending']	= '支出';
$lang['predeposit_freeze']	= '冻结';
$lang['predeposit_pay']	= '支付';
$lang['predeposit_recharge_card_recharge']	= '充值卡充值';
$lang['predeposit_available_balance']	= '可用充值卡余额';
$lang['predeposit_freeze_balance']	= '冻结充值卡余额';

//rechargecard_add
$lang['predeposit_recharge_card_number']	= '平台充值卡号';
$lang['predeposit_enter_card_number']	= '请输入平台充值卡号';
$lang['predeposit_card_length_less']	= '平台充值卡号长度小于50';


//controller
$lang['platform_recharge_card_number_cannot_empty']	= '平台充值卡卡号不能为空且长度不能大于50';
$lang['platform_recharge_card_successfully_used']	= '平台充值卡使用成功';
$lang['please_enter_payment_password']	= '请输入支付密码';
$lang['payment_password_error']	= '支付密码错误';
$lang['detail_list']	= '明细列表';
$lang['prepaid_phone_list']	= '充值列表';
$lang['withdrawal_list']	= '提现列表';
$lang['balance_recharge_card']	= '充值卡余额';

$lang['predeposit_withdraw_min']             = '最低提现金额';
$lang['predeposit_withdraw_max']             = '最高提现金额';
$lang['predeposit_last_withdraw_time_error']             = '不能频繁提现，下次的提现时间是';

$lang['pay_method_alipay'] ='支付宝';
$lang['pay_method_wechat'] ='微信';
return $lang;

