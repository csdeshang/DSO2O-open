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
class  Storegrade extends BaseModel {

 
    /**
     * 列表
     * @access public
     * @author csdeshang
     * @param type $condition 检索条件
     * @param type $order 排序
     * @return type
     */
    public function getStoregradeList($condition = array(),$order = 'storegrade_sort asc') {
        $result = Db::name('storegrade')->where($condition)->order($order)->select()->toArray();
        return $result;
    }


    /**
     * 取单个内容
     * @access public
     * @author csdeshang
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getOneStoregrade($id) {
        if (intval($id) > 0) {
            $result  = Db::name('storegrade')->where('storegrade_id',$id)->find();
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addStoregrade($data) {
        if (empty($data)) {
            return false;
        }
        $result = Db::name('storegrade')->insertGetId($data);
        return $result;
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editStoregrade($storegrade_id,$data) {
        if (empty($data)) {
            return false;
        }
        $result = Db::name('storegrade')->where('storegrade_id',$storegrade_id)->update($data);
        return $result;
    }

    /**
     * 删除分类
     * @access public
     * @author csdeshang
     * @param int $id 记录ID
     * @return bool 布尔类型的返回结果
     */
    public function delStoregrade($storegrade_id) {
        $storegrade_id = intval($storegrade_id);
        if ($storegrade_id > 0) {
            $result = Db::name('storegrade')->where('storegrade_id',  $storegrade_id)->delete();
            return $result;
        } else {
            return false;
        }
    }

}

?>
