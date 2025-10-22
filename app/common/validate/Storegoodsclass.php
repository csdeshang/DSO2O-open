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
class Storegoodsclass extends Validate {

    protected $rule = [
        'store_id' => 'require',
        'storegc_name' => 'require|length:0,50',
        'storegc_sort' => 'between:0,255',
    ];
    protected $message = [
        'store_id.require' => '店铺ID必填',
        'storegc_name.require' => '分类名称必填',
        'storegc_name.length' => '分类名称长度应该在1至50字符之间',
        'storegc_sort.between' => '排序应在0至255之间',
    ];
    protected $scene = [
        'save' => ['store_id', 'storegc_name', 'storegc_sort'],
    ];
}
