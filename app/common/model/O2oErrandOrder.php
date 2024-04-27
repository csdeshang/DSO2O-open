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
class O2oErrandOrder extends BaseModel {

    public $page_info;

    /**
     * 取得跑腿订单列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oErrandOrderList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_errand_order_id desc', $limit = 0) {
        if ($pagesize) {
            $result = Db::name('o2o_errand_order')->where($condition)->fieldRaw($fields)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('o2o_errand_order')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
    }

    /**
     * 取得跑腿订单单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oErrandOrderInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_errand_order')->where($condition)->field($fields)->find();
    }

    /*
     * 获取订单状态
     */

    public function getO2oErrandOrderStateText($state) {
        $lang = '';
        switch ($state) {
            case 0:
                $lang = '已取消';
                break;
            case 10:
                $lang = '待付款';
                break;
            case 20:
                $lang = '待接单';
                break;
            case 26:
                $lang = '待取货';
                break;
            case 30:
                $lang = '配送中';
                break;
            case 40:
                $lang = '已完成';
                break;
        }
        return $lang;
    }

    

    /**
     * 添加跑腿订单
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oErrandOrder($data) {
        return Db::name('o2o_errand_order')->insertGetId($data);
    }

    /**
     * 编辑跑腿订单
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oErrandOrder($data, $condition = array()) {
        return Db::name('o2o_errand_order')->where($condition)->update($data);
    }

    /**
     * 删除跑腿订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @return type
     */
    public function delO2oErrandOrder($condition) {
        return Db::name('o2o_errand_order')->where($condition)->delete();
    }

    

    

    

    /**
     * 取得订单数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getO2oErrandOrderCount($condition) {
        return Db::name('o2o_errand_order')->where($condition)->count();
    }

    

}

?>
