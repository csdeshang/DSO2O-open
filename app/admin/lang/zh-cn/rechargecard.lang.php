<?php

$lang['rechargecard_add_card_help1'] = '平台发布充值卡的方式：';
$lang['rechargecard_add_card_help2'] = '1. 输入可选输入的卡号前缀，以及总数，由系统自动生成指定总数、前缀的充值卡卡号（系统自动生成部分长度为32）；';
$lang['rechargecard_add_card_help3'] = '2. 上传文本文件导入的充值卡卡号，文件中每行为一个卡号。';
$lang['rechargecard_add_card_help4'] = '3. 在文本框中手动输入多个充值卡卡号，每行为一个卡号；';
$lang['rechargecard_add_card_help5'] = '您可以设置本批次添加卡号的批次标识，方便检索；充值卡卡号为50位之内的字母数字组合。';
$lang['rechargecard_add_card_help6'] = '新增的充值卡卡号与已经存在的的充值卡卡号冲突，则系统自动忽略它们。';

$lang['rechargecard_index_help1'] = '平台发布充值卡，用户可在会员中心输入正确充值卡卡号的进行充值。';

$lang['choose_publishing_mode'] = '选择发布方式';
$lang['choose_publishing_mode_0'] = '输入总数，自动生成';
$lang['choose_publishing_mode_1'] = '上传文件，导入卡号';
$lang['choose_publishing_mode_2'] = '手动输入，每行一号';

$lang['rechargecard_total'] = '总数';
$lang['rechargecard_prefix'] = '前缀';
$lang['rechargecard_total_tips'] = '请输入总数，总数为1~5000之间的整数；可以输入随机生成卡号的统一前缀，16字之内字母数字的组合';
$lang['rechargecard_file_tips'] = '请上传卡号文件，文件为纯文本格式，每行一个卡号；卡号为字母数字组合，限制50字之内；不合法卡号将被自动过滤';
$lang['rechargecard_manual_tips'] = '请输入卡号，每行一个卡号；卡号为字母数字组合，限制50字之内；不合法卡号将被自动过滤';

$lang['rechargecard_denomination'] = '面额(元)';
$lang['rechargecard_denomination_tips'] = '请输入面额，面额不可超过1000';
$lang['rechargecard_batchflag'] = '批次标识';
$lang['rechargecard_batchflag_tips'] = '可以输入20字之内“批次标识”，用于标识和区分不同批次添加的充值卡，便于检索';

$lang['rechargecard_denomination_required'] = '请填写面额';
$lang['rechargecard_denomination_min'] = '面额不能小于0.01';
$lang['rechargecard_denomination_max'] = '面额不能大于1000';

$lang['rechargecard_batchflag_maxlength'] = '请输入20字之内的批次标识';


$lang['rc_sn'] = '充值卡卡号';
$lang['rc_batchflag'] = '批次标识';
$lang['rc_denomination'] = '面额(元)';
$lang['rc_admin_name'] = '发布管理员';
$lang['rc_tscreated'] = '发布时间';
$lang['rc_state'] = '领取状态';
$lang['rc_state_receive'] = '已被领取';
$lang['rc_state_not_receive'] = '未被领取';
$lang['rechargecard_at'] = '在';

$lang['r0total_message'] = '总数必须是1~5000之间的整数';
$lang['r0prefix_message'] = '前缀必须是16字之内字母数字的组合';
$lang['r1textfile_message'] = '请选择纯文本格式充值卡卡号文件';
$lang['r2manual_message'] = '请输入充值卡卡号';

$lang['file_upload_fail'] = '文件上传失败';
$lang['file_empty'] = '未找到已上传的文件';
$lang['recharge_number_error'] = '只能在一次操作中增加1~9999个充值卡号';
$lang['recharge_number_invalid'] = '请输入至少一个合法的卡号';
$lang['recharge_number_exist'] = '所有新增的卡号都与已有的卡号冲突';
$lang['receive_user'] = '领取人';

$lang['rechargecard'] = '充值卡';

$lang['rechargecard_number_exist_error'] = '有 %s 个卡号与已有的未使用卡号冲突';

return $lang;