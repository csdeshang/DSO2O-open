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
class  O2oFuwuOrder extends BaseModel {

    public $page_info;

    /**
     * 取得服务订单列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oFuwuOrderList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_fuwu_order_id desc', $limit = 0) {
        if ($pagesize) {
            $result = Db::name('o2o_fuwu_order')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('o2o_fuwu_order')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
    }

    /**
     * 取得服务订单单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oFuwuOrderInfo($condition = array(), $fields = '*',$extend = array()) {
        $o2o_fuwu_order_info = Db::name('o2o_fuwu_order')->where($condition)->field($fields)->find();
        
        //追加返回订单日志
        if (in_array('orderlog', $extend)) {
            //取商品列表
            $orderlog_list = model('orderlog')->getOrderlogList(array('order_id' => $o2o_fuwu_order_info['o2o_fuwu_order_id'],'log_type' => 'fuwu'));
            foreach ($orderlog_list as $orderlog_key => $orderlog) {
                $orderlog_list[$orderlog_key]['log_time_desc'] = date('Y-m-d H:i:s',$orderlog['log_time']);
            }
            $o2o_fuwu_order_info['extend_orderlog'] = $orderlog_list;
        }
        
        return $o2o_fuwu_order_info;
    }

    /*
     * 获取订单状态
     */

    public function getO2oFuwuOrderStateText($state) {
        $lang = '';
        switch ($state) {
            case 0:
                $lang = '已取消';
                break;
            case 10:
                $lang = '待付款';
                break;
            case 20:
                $lang = '待服务';
                break;
            case 30:
                $lang = '服务中';
                break;
            case 40:
                $lang = '已完成';
                break;
        }
        return $lang;
    }

    /**
     * 编辑服务订单
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuOrder($data, $condition = array()) {
        return Db::name('o2o_fuwu_order')->where($condition)->update($data);
    }

    /**
     * 删除服务订单
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @return type
     */
    public function delO2oFuwuOrder($condition) {
        return Db::name('o2o_fuwu_order')->where($condition)->delete();
    }
    /**
     * 取得订单数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getO2oFuwuOrderCount($condition) {
        return Db::name('o2o_fuwu_order')->where($condition)->count();
    }
}

?>
