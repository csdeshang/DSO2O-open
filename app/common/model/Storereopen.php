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
class  Storereopen extends BaseModel {
    public $page_info;
 
    /**
     * 取得列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getStorereopenList($condition = array(), $pagesize = '', $order = 'storereopen_id desc') {
        if($pagesize){
            $result =  Db::name('storereopen')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('storereopen')->where($condition)->order($order)->select()->toArray();
        }
        
    }

    /**
     * 增加新记录
     * @access public
     * @author csdeshang
     * @param arrat $data 参数内容
     * @return bool
     */
    public function addStorereopen($data) {
        return Db::name('storereopen')->insertGetId($data);
    }

    /**
     * 取单条信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getStorereopenInfo($condition) {
        return Db::name('storereopen')->where($condition)->find();
    }

    /**更新记录
     * @access public
     * @author csdeshang
     * @param type $data 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStorereopen($data, $condition) {
        return Db::name('storereopen')->where($condition)->update($data);
    }

    /**
     * 取得数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStorereopenCount($condition) {
        return Db::name('storereopen')->where($condition)->count();
    }

    /**
     * 删除记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delStorereopen($condition) {
        return Db::name('storereopen')->where($condition)->delete();
    }

}

?>
