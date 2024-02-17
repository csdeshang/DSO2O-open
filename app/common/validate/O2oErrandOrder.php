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
class  O2oErrandOrder extends Validate {

    protected $rule = [
        'o2o_errand_order_type' => 'require|in:0,1',
        'o2o_errand_order_sn' => 'require',
        'member_id' => 'require|number',
        'o2o_errand_order_add_time' => 'require|number',
        'o2o_errand_order_appointment_time' => 'number',
        'o2o_errand_order_distance' => 'require|float',
        'o2o_errand_order_pickup_name' => 'require|max:60',
        'o2o_errand_order_pickup_address' => 'require|max:255',
        'o2o_errand_order_pickup_phone' => 'max:60',
        'o2o_errand_order_pickup_lng' => 'require',
        'o2o_errand_order_pickup_lat' => 'require',
        'o2o_errand_order_deliver_name' => 'require|max:60',
        'o2o_errand_order_deliver_address' => 'require|max:255',
        'o2o_errand_order_deliver_phone' => 'require|max:60',
        'o2o_errand_order_deliver_lng' => 'require',
        'o2o_errand_order_deliver_lat' => 'require',
        'o2o_errand_order_detail' => 'require',
        'o2o_errand_order_state' => 'require|in:0,10,20,26,30,40',
        'o2o_errand_order_amount' => 'require|float|min:0',
        'o2o_errand_order_distance_price' => 'require|float|min:0',
        'o2o_errand_order_weight_price' => 'float|min:0',
        'o2o_errand_order_time_price' => 'float|min:0',
        'o2o_errand_order_goods_price' => 'float|min:0',
        'o2o_errand_order_gratuity' => 'float|min:0',
        'o2o_errand_order_weight' => 'float|min:0',
        'o2o_errand_order_remark' => 'max:255',
        'o2o_errand_order_check_receive' => 'in:0,1',
    ];
    protected $regex = ['mobile' => '^1[0-9]{10}$'];
    protected $message = [
        'o2o_errand_order_type.require' => '缺少跑腿订单类型',
        'o2o_errand_order_type.in' => '跑腿订单类型错误',
        'o2o_errand_order_sn.require' => '缺少订单号',
        'member_id.require' => '缺少订单用户ID',
        'member_id.number' => '订单用户ID错误',
        'o2o_errand_order_add_time.require' => '缺少下单时间',
        'o2o_errand_order_add_time.number' => '下单时间错误',
        'o2o_errand_order_appointment_time.number' => '预约时间错误',
        'o2o_errand_order_distance.require' => '缺少配送距离',
        'o2o_errand_order_distance.float' => '配送距离错误',
        'o2o_errand_order_pickup_name.require' => '缺少取货点名称',
        'o2o_errand_order_pickup_name.max' => '取货点名称长度不能超过60',
        'o2o_errand_order_pickup_address.require' => '缺少取货点地址',
        'o2o_errand_order_pickup_address.max' => '取货点地址长度不能超过255',
        'o2o_errand_order_pickup_phone.max' => '取货点手机长度不能超过255',
        'o2o_errand_order_pickup_lng.require' => '缺少取货点经度',
        'o2o_errand_order_pickup_lat.require' => '缺少取货点纬度',
        'o2o_errand_order_deliver_name.require' => '缺少送货点名称',
        'o2o_errand_order_deliver_name.max' => '送货点名称长度不能超过60',
        'o2o_errand_order_deliver_address.require' => '缺少送货点地址',
        'o2o_errand_order_deliver_address.max' => '送货点地址长度不能超过255',
        'o2o_errand_order_deliver_phone.require' => '缺少送货点手机',
        'o2o_errand_order_deliver_phone.max' => '送货点手机长度不能超过255',
        'o2o_errand_order_deliver_lng.require' => '缺少送货点经度',
        'o2o_errand_order_deliver_lat.require' => '缺少送货点经度',
        'o2o_errand_order_detail.require' => '缺少跑腿订单详情',
        'o2o_errand_order_state.require' => '缺少跑腿订单状态',
        'o2o_errand_order_state.in' => '跑腿订单状态错误',
        'o2o_errand_order_amount.require' => '缺少订单总费用',
        'o2o_errand_order_amount.float' => '订单总费用错误',
        'o2o_errand_order_amount.min' => '订单总费用错误',
        'o2o_errand_order_distance_price.require' => '缺少基础运费',
        'o2o_errand_order_distance_price.float' => '基础运费错误',
        'o2o_errand_order_distance_price.min' => '基础运费错误',
        'o2o_errand_order_weight_price.float' => '重量附加费错误',
        'o2o_errand_order_time_price.float' => '特殊时段费错误',
        'o2o_errand_order_time_price.min' => '特殊时段费错误',
        'o2o_errand_order_goods_price.float' => '预估商品价格错误',
        'o2o_errand_order_goods_price.min' => '预估商品价格错误',
        'o2o_errand_order_gratuity.float' => '小费错误',
        'o2o_errand_order_gratuity.min' => '小费错误',
        'o2o_errand_order_weight.float' => '重量错误',
        'o2o_errand_order_weight.min' => '重量错误',
        'o2o_errand_order_remark.max' => '备注长度不能超过255',
        'o2o_errand_order_check_receive.in' => '当面收设置错误',
    ];
    protected $scene = [
        'o2o_errand_order_add' => ['o2o_errand_order_type', 'o2o_errand_order_sn', 'member_id', 'o2o_errand_order_add_time', 'o2o_errand_order_appointment_time', 'o2o_errand_order_distance', 'o2o_errand_order_pickup_name', 'o2o_errand_order_pickup_address', 'o2o_errand_order_pickup_phone', 'o2o_errand_order_pickup_address', 'o2o_errand_order_pickup_lng', 'o2o_errand_order_pickup_lat', 'o2o_errand_order_deliver_name', 'o2o_errand_order_deliver_address', 'o2o_errand_order_deliver_phone', 'o2o_errand_order_deliver_lng', 'o2o_errand_order_deliver_lat', 'o2o_errand_order_detail', 'o2o_errand_order_state', 'o2o_errand_order_amount', 'o2o_errand_order_distance_price', 'o2o_errand_order_weight_price', 'o2o_errand_order_time_price', 'o2o_errand_order_goods_price', 'o2o_errand_order_gratuity', 'o2o_errand_order_weight', 'o2o_errand_order_remark','o2o_errand_order_check_receive'],
    ];

}
