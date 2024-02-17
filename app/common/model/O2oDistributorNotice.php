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
class  O2oDistributorNotice extends BaseModel {
    const READ_YES=1;
    const READ_NO=0;

    public $page_info;
    /**
     * 获取配送员通知列表
     * @access public
     * @author csdeshang
     * @param type $condition
     * @param type $pagesize
     * @param type $order
     * @return type
     */
    public function getO2oDistributorNoticeList($condition,$pagesize='',$order='o2o_distributor_notice_id desc'){
        if ($pagesize) {
            $result = Db::name('O2oDistributorNotice')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('O2oDistributorNotice')->where($condition)->order($order)->limit(10)->select()->toArray();
        }
    }
    /**
     * 取得配送员通知信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getO2oDistributorNoticeInfo($condition = array(), $fields = '*') {
        return Db::name('O2oDistributorNotice')->where($condition)->field($fields)->find();
    }
    
    /**
     * 添加配送员通知信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oDistributorNotice($data) {
        return Db::name('O2oDistributorNotice')->insertGetId($data);
    }
    
    /**
     * 编辑配送员通知信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oDistributorNotice($data, $condition = array()) {
        return Db::name('O2oDistributorNotice')->where($condition)->update($data);
    }

    /**
     * 获取配送员通知数量
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return bool
     */
    public function getO2oDistributorNoticeCount($condition = array()) {
        return Db::name('O2oDistributorNotice')->where($condition)->count();
    }

}

?>
