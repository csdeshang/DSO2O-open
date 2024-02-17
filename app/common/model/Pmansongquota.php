<?php

/**
 * 满即送套餐模型
 *
 */

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
class Pmansongquota extends BaseModel
{
    public $page_info;

    /**
     * 读取满即送套餐列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 满即送套餐列表
     *
     */
    public function getMansongquotaList($condition, $pagesize = null, $order = '', $field = '*')
    {
        if($pagesize){
        $res = Db::name('pmansongquota')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info = $res;
        $result = $res->items();
        }else{
            $result = Db::name('pmansongquota')->field($field)->where($condition)->order($order)->select()->toArray();
        }
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getMansongquotaInfo($condition)
    {
        $result = Db::name('pmansongquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺id
     * @return array
     */
    public function getMansongquotaCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id', '=', $store_id);
        $condition[] = array('mansongquota_endtime', '>', TIMESTAMP);
        return $this->getMansongquotaInfo($condition);
    }

    /**
     * 增加 
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return bool
     */
    public function addMansongquota($data)
    {
        return Db::name('pmansongquota')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param array $update 更新数据 
     * @param array $condition 条件
     * @return bool
     */
    public function editMansongquota($update, $condition)
    {
        return Db::name('pmansongquota')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delMansongquota($condition)
    {
        return Db::name('pmansongquota')->where($condition)->delete();
    }

}
