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
class  O2oComplaint extends BaseModel {
    public $page_info;

    
    /**
     * 取得配送员投诉列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oComplaintList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_complaint_addtime desc', $limit = 0) {
        if($pagesize){
            $result = Db::name('o2o_complaint')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('o2o_complaint')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
        
        
    }

    /**
     * 取得配送员投诉单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oComplaintInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_complaint')->where($condition)->field($fields)->find();
    }

    /**
     * 添加投诉单
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oComplaint($data) {
        return Db::name('o2o_complaint')->insertGetId($data);
    }
    
    /**
     * 编辑投诉单
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oComplaint($data, $condition = array()) {
        return Db::name('o2o_complaint')->where($condition)->update($data);
    }
    
    /**
     * 删除投诉单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @return type
     */
    public function delO2oComplaint($condition) {
        return Db::name('o2o_complaint')->where($condition)->delete();
    }

}

?>
