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
class  O2oFuwuGoodsSpec extends Validate {

    protected $rule = [
        'o2o_fuwu_goods_spec_unit' => 'require',
        'o2o_fuwu_goods_spec_name' => 'require',
        'o2o_fuwu_goods_spec_price' => 'require',
    ];
    protected $message = [
        'o2o_fuwu_goods_spec_unit.require' => '请填写服务项目单位',
        'o2o_fuwu_goods_spec_name.require' => '请填写服务项目名称',
        'o2o_fuwu_goods_spec_price.require' => '请填写服务项目价格',
    ];
    protected $scene = [
        'o2o_fuwu_goods_spec_save' => ['o2o_fuwu_goods_spec_unit','o2o_fuwu_goods_spec_name','o2o_fuwu_goods_spec_price',],
    ];

}
