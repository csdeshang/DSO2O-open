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
class  Sellerjoinin extends Validate
{
    protected $rule = [
        'company_name'=>'require|length:1,50',
//            'company_address'=>'require|length:1,50',
        'company_address_detail'=>'require|length:1,50',
        'company_registered_capital'=>'require|float',
        'contacts_name'=>'require|length:1,20',
        'contacts_phone'=>'require|length:1,20',
        'contacts_email'=>'require|email',
        'business_licence_number'=>'require|length:1,20',
//            'business_licence_address'=>'require|length:1,50',
        'business_licence_start'=>'require',
        'bank_account_name'=>'require|length:1,50',
        'bank_account_number'=>'require|length:1,30',
        'bank_name'=>'require|length:1,50',
//            'bank_address'=>'require',
        'settlement_bank_account_name'=>'require|length:1,50',
        'settlement_bank_account_number'=>'require|length:1,50',
        'settlement_bank_name'=>'require|length:1,50',
        'seller_name'=>'require',
        'store_name'=>'require|length:1,50',
        'storegrade_id'=>'require',
        'storeclass_id'=>'require',
        //sellerjoininc2c
        'business_sphere'=>'require|length:1,20',
        'business_licence_number_electronic'=>'require'
    ];
    protected $message = [
        'company_name.require'=>'公司名称不能为空|公司名称必须小于50个字',
        'company_name.length'=>'公司名称不能为空|公司名称必须小于50个字',
//            'company_address.require|length:1,50'=>'公司地址不能为空|公司地址必须小于50个字',
        'company_address_detail.require'=>'公司详细地址不能为空|公司详细地址必须小于50个字',
        'company_address_detail.length'=>'公司详细地址不能为空|公司详细地址必须小于50个字',
        'company_registered_capital.require'=>'注册资金不能为空|注册资金必须为数字',
        'company_registered_capital.float'=>'注册资金不能为空|注册资金必须为数字',
        'contacts_name.require'=>'联系人姓名不能为空|联系人姓名必须小于20个字',
        'contacts_name.length'=>'联系人姓名不能为空|联系人姓名必须小于20个字',
        'contacts_phone.require'=>'联系人电话不能为空|联系人电话必须小于20个字',
        'contacts_phone.length'=>'联系人电话不能为空|联系人电话必须小于20个字',
        'contacts_email.require'=>'电子邮箱不能为空|电子邮箱格式不正确',
        'contacts_email.email'=>'电子邮箱不能为空|电子邮箱格式不正确',
        'business_licence_number.require'=>'证件号码不能为空',
        'business_licence_number.length'=>'证件号码必须小于20个字',
//            'business_licence_address.require|length:1,50'=>'营业执照所在地不能为空|营业执照所在地必须小于50个字',
        'business_licence_start.require'=>'营业执照有效期不能为空',
        'bank_account_name.require'=>'银行开户名不能为空|银行开户名必须小于50个字',
        'bank_account_name.length'=>'银行开户名不能为空|银行开户名必须小于50个字',
        'bank_account_number.require'=>'银行账号不能为空|银行账号必须小于20个字',
        'bank_account_number.length'=>'银行账号不能为空|银行账号必须小于20个字',
        'bank_name.require'=>'开户银行支行不能为空|开户银行支行必须小于50个字',
        'bank_name.length'=>'开户银行支行不能为空|开户银行支行必须小于50个字',
//            'bank_address.require'=>'开户行所在地不能为空',
        'settlement_bank_account_name.require'=>'银行开户名不能为空|银行开户名必须小于50个字',
        'settlement_bank_account_name.length'=>'银行开户名不能为空|银行开户名必须小于50个字',
        'settlement_bank_account_number.require'=>'银行账号不能为空|银行账号必须小于50个字',
        'settlement_bank_account_number.length'=>'银行账号不能为空|银行账号必须小于50个字',
        'settlement_bank_name.require'=>'开户银行支行不能为空|开户银行支行必须小于50个字',
        'settlement_bank_name.length'=>'开户银行支行不能为空|开户银行支行必须小于50个字',
        'seller_name.require'=>'商家账号不能为空',
        'store_name.require'=>'店铺名称不能为空|店铺名称必须小于50个字',
        'store_name.length'=>'店铺名称不能为空|店铺名称必须小于50个字',
        'storegrade_id.require'=>'店铺等级不能为空',
        'storeclass_id.require'=>'店铺分类不能为空',
        //sellerjoininc2c
        'business_sphere.require'=>'姓名不能为空|姓名不能小于50个字',
        'business_sphere.length'=>'姓名不能为空|姓名不能小于50个字',
        'business_licence_number_electronic.require'=>'请上传证件照片'
    ];
    protected $scene = [
        'step2_save_valid' => ['company_name','company_address_detail','company_registered_capital','contacts_name','contacts_phone','contacts_email','business_licence_number','business_licence_start','business_licence_end','business_licence_number_electronic'],
        'step3_save_valid' => ['bank_account_name','bank_account_number','bank_name','settlement_bank_account_name','settlement_bank_account_number','settlement_bank_name'],
        'step4_save_valid' => ['seller_name','store_name','storegrade_id','storeclass_id'],
        //sellerjoininc2c
        'step2_save_valid2' => ['company_name','company_address_detail','contacts_name','contacts_phone','contacts_email','business_licence_number','business_licence_number_electronic'],
        'step3_save_valid3' => ['settlement_bank_account_name','settlement_bank_account_number'],
        'step4_save_valid4' => ['seller_name','store_name','storegrade_id','storeclass_id'],

    ];
}