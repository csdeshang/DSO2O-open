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
class  O2oFuwuOrganization extends Validate {

    protected $rule = [
        'o2o_fuwu_organization_name' => 'require|max:60',
        'o2o_fuwu_organization_type' => 'require|in:0,1',
        'o2o_fuwu_organization_address' => 'require|max:255',
        'o2o_fuwu_organization_lat' => 'require|max:255',
        'o2o_fuwu_organization_lng' => 'require|max:255',
        'o2o_fuwu_organization_detail' => 'max:255',
        'o2o_fuwu_organization_phone' => 'max:60',
        'o2o_fuwu_organization_region_id' => 'require|number',
        'o2o_fuwu_organization_region_name' => 'require|max:60',
        'o2o_fuwu_organization_city_id' => 'require|number',
        'o2o_fuwu_organization_city_name' => 'require|max:60',
        'o2o_fuwu_organization_birthday' => 'date',
        'o2o_fuwu_organization_open_start' => 'require|number|elt:1440',
        'o2o_fuwu_organization_open_end' => 'require|number|gt:o2o_fuwu_organization_open_start',
        'o2o_fuwu_class_id_1' => 'require|number',
        'o2o_fuwu_class_name_1' => 'require|max:60',
        'o2o_fuwu_class_id_2' => 'number',
        'o2o_fuwu_class_name_2' => 'max:60',
        'o2o_fuwu_class_id_3' => 'number',
        'o2o_fuwu_class_name_3' => 'max:60',
        'o2o_fuwu_organization_detail' => 'max:255',
    ];
    protected $message = [
        'o2o_fuwu_organization_name.require' => '服务机构名称必填',
        'o2o_fuwu_organization_name.max' => '服务机构名称长度不能超过60',
        'o2o_fuwu_organization_type.require' => '服务机构类型必填',
        'o2o_fuwu_organization_type.in' => '服务机构类型错误',
        'o2o_fuwu_organization_address.require' => '地址必填',
        'o2o_fuwu_organization_address.max' => '地址不能超过255',
        'o2o_fuwu_organization_lat.require' => '纬度必填',
        'o2o_fuwu_organization_lat.max' => '纬度长度不能超过255',
        'o2o_fuwu_organization_lng.require' => '经度必填',
        'o2o_fuwu_organization_lng.max' => '经度长度不能超过255',
        'o2o_fuwu_organization_detail.max' => '简介长度不能超过255',
        'o2o_fuwu_organization_phone.max' => '联系电话长度不能超过60',
        'o2o_fuwu_organization_region_id.require' => '所在地区ID必填',
        'o2o_fuwu_organization_region_id.number' => '所在地区ID错误',
        'o2o_fuwu_organization_region_name.require' => '所在地区名称必填',
        'o2o_fuwu_organization_region_name.max' => '所在地区名称长度不能超过60',
        'o2o_fuwu_organization_city_id.require' => '城市ID必填',
        'o2o_fuwu_organization_city_id.number' => '城市ID错误',
        'o2o_fuwu_organization_city_name.require' => '城市名称必填',
        'o2o_fuwu_organization_city_name.max' => '城市名称长度不能超过60',
        'o2o_fuwu_organization_birthday.date' => '日期错误',
        'o2o_fuwu_organization_open_start.require' => '请设置营业开始时间',
        'o2o_fuwu_organization_open_start.number' => '营业开始时间错误',
        'o2o_fuwu_organization_open_start.elt' => '营业开始时间不能大于次日0点',
        'o2o_fuwu_organization_open_end.require' => '请设置营业结束时间',
        'o2o_fuwu_organization_open_end.number' => '营业结束时间错误',
        'o2o_fuwu_organization_open_end.gt' => '营业结束时间不能小于营业开始时间',
        'o2o_fuwu_class_id_1.require' => '请设置主服务分类ID',
        'o2o_fuwu_class_id_1.number' => '主服务分类ID错误',
        'o2o_fuwu_class_name_1.require' => '请设置主服务分类名称',
        'o2o_fuwu_class_name_1.max' => '主服务分类名称长度不能超过60',
        'o2o_fuwu_class_id_2.number' => '次服务分类ID错误',
        'o2o_fuwu_class_name_2.max' => '次服务分类名称长度不能超过60',
        'o2o_fuwu_class_id_3.number' => '次服务分类ID错误',
        'o2o_fuwu_class_name_3.max' => '次服务分类名称长度不能超过60',
        'o2o_fuwu_organization_detail.max' => '简介长度不能超过255',
    ];
    protected $scene = [
        'o2o_fuwu_organization_add' => ['o2o_fuwu_organization_type'],
        'o2o_fuwu_organization_edit_base' => ['o2o_fuwu_organization_name', 'o2o_fuwu_organization_address', 'o2o_fuwu_organization_lat', 'o2o_fuwu_organization_lng', 'o2o_fuwu_organization_phone', 'o2o_fuwu_organization_region_id', 'o2o_fuwu_organization_region_name', 'o2o_fuwu_organization_city_id', 'o2o_fuwu_organization_city_name','o2o_fuwu_organization_birthday'],
        'o2o_fuwu_organization_edit_operate' => ['o2o_fuwu_organization_open_start','o2o_fuwu_organization_open_end','o2o_fuwu_class_id_1','o2o_fuwu_class_name_1','o2o_fuwu_class_id_2','o2o_fuwu_class_name_2','o2o_fuwu_class_id_3','o2o_fuwu_class_name_3','o2o_fuwu_organization_detail'],
    ];

}
