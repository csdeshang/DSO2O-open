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
class  O2oDistributorToken extends BaseModel {

    /**
     * 生成配送员令牌信息
     * @access public
     * @author csdeshang 
     * @param array $o2o_distributor 配送员信息
     * @param string $client_type 客户端
     * @return string
     */
    public function genO2oDistributorToken($o2o_distributor) {
        if(!isset($o2o_distributor['o2o_distributor_name']) || !$o2o_distributor['o2o_distributor_name']){
            return '';
        }
        return md5($o2o_distributor['o2o_distributor_name'] . strval(TIMESTAMP) . strval(rand(0, 999999)));
    }

    /**
     * 取得配送员令牌信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getO2oDistributorTokenInfo($condition = array(), $fields = '*') {
        return Db::name('O2oDistributorToken')->where($condition)->field($fields)->find();
    }
    
    /**
     * 添加配送员令牌信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oDistributorToken($data) {
        return Db::name('O2oDistributorToken')->insertGetId($data);
    }
    
    /**
     * 编辑配送员令牌信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oDistributorToken($data, $condition = array()) {
        return Db::name('O2oDistributorToken')->where($condition)->update($data);
    }
    
    public function delO2oDistributorTokenInfo($condition){
        return Db::name('O2oDistributorToken')->where($condition)->delete();
    }

}

?>
