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
class  O2oErrandClass extends Validate {

    protected $rule = [
        'o2o_errand_class_name' => 'require|max:60',
        'o2o_errand_class_sort_order' => 'number|max:255',
        'o2o_errand_class_recommend' => 'in:0,1',
        'o2o_errand_class_remark' => 'max:255',
    ];
    protected $regex = ['mobile' => '^1[0-9]{10}$'];
    protected $message = [
        'o2o_errand_class_name.require' => '跑腿类别名称必填',
        'o2o_errand_class_name.max' => '跑腿类别名称长度不能超过60',
        'o2o_errand_class_sort_order.number' => '排序仅可以为数字',
        'o2o_errand_class_sort_order.max' => '排序最大255',
        'o2o_errand_class_recommend.in' => '推荐设置错误',
        'o2o_errand_class_remark.max' => '备注长度不能超过60',
    ];
    protected $scene = [
        'o2o_errand_class_replace' => ['o2o_errand_class_name', 'o2o_errand_class_sort_order', 'o2o_errand_class_recommend', 'o2o_errand_class_remark'],
    ];

}
