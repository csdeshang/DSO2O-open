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
class  O2oFuwuGoods extends Validate {

    protected $rule = [
        'o2o_fuwu_goods_name' => 'require',
        'o2o_fuwu_goods_image' => 'require',
        'o2o_fuwu_class_id' => 'require',
        'o2o_fuwu_goods_body' => 'require',
    ];
    protected $message = [
        'o2o_fuwu_goods_name.require' => '请填写服务名称',
        'o2o_fuwu_goods_image.require' => '请上传服务图片',
        'o2o_fuwu_class_id.require' => '请选择服务分类',
        'o2o_fuwu_goods_body.require' => '请完成服务详情',
    ];
    protected $scene = [
        'o2o_fuwu_goods_save' => ['o2o_fuwu_goods_name','o2o_fuwu_goods_image','o2o_fuwu_class_id','o2o_fuwu_goods_body',],
    ];

}
