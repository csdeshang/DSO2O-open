<?php

namespace app\common\model;
use think\facade\Db;

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
 * 数据层模型
 */
class  Storemsgtpl extends BaseModel {
    

    /**
     * 店铺消息模板列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $order 排序
     * @return array 
     */
    public function getStoremsgtplList($condition, $field = '*',  $order = 'storemt_code asc') {
        return Db::name('storemsgtpl')->field($field)->where($condition)->order($order)->select()->toArray();
    }

    /**
     * 店铺消息模板详细信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array 
     */
    public function getStoremsgtplInfo($condition, $field = '*') {
        return Db::name('storemsgtpl')->field($field)->where($condition)->find();
    }

    /**
     * 编辑店铺消息模板
     * @access public
     * @author csdeshang
     * @param arrat $condition 条件
     * @param array $update 更新数据
     * @return array 
     */
    public function editStoremsgtpl($condition, $update) {
        return Db::name('storemsgtpl')->where($condition)->update($update);
    }
    
}
?>
