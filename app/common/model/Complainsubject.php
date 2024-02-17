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
class Complainsubject extends BaseModel
{
    public $page_info;

    /**
     * 增加投诉主题
     * @access public
     * @author csdeshang 
     * @param array $data 参数内容
     * @return bool
     */
    public function addComplainsubject($data)
    {
        return Db::name('complainsubject')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang 
     * @param array $update_array 更新数据
     * @param array $condition 更新条件
     * @return bool
     */
    public function editComplainsubject($update_array, $condition)
    {
        return Db::name('complainsubject')->where($condition)->update($update_array);
    }

    /**
     * 删除投诉主题
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @return bool
     */
    public function delComplainsubject($condition)
    {
        return Db::name('complainsubject')->where($condition)->delete();
    }

    /**
     * 获得投诉主题列表
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getComplainsubject($condition = '', $pagesize = '',$order = 'complainsubject_id desc')
    {
        if($pagesize){
            $res= Db::name('complainsubject')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            return $res->items();
        }else{
            return Db::name('complainsubject')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 获得有效投诉主题列表
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getActiveComplainsubject($condition = '', $pagesize = '',$order='complainsubject_id desc ')
    {
        //搜索条件
        $condition[] = array('complainsubject_state','=',1);
        if($pagesize){
            $res=Db::name('complainsubject')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            return $res->items();
        }else{
            return Db::name('complainsubject')->where($condition)->order($order)->select()->toArray();
        }
    }

}