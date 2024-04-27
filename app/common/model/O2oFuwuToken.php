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
class  O2oFuwuToken extends BaseModel {

    /**
     * 生成服务机构令牌信息
     * @access public
     * @author csdeshang 
     * @param array $o2o_fuwu 服务机构信息
     * @return string
     */
    public function genO2oFuwuToken($o2o_fuwu) {
        if(!isset($o2o_fuwu['o2o_fuwu_account_name']) || !$o2o_fuwu['o2o_fuwu_account_name']){
            return '';
        }
        return md5($o2o_fuwu['o2o_fuwu_account_name'] . strval(TIMESTAMP) . strval(rand(0, 999999)));
    }

    /**
     * 取得服务机构令牌信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getO2oFuwuTokenInfo($condition = array(), $fields = '*') {
        return Db::name('O2oFuwuToken')->where($condition)->field($fields)->find();
    }
    
    /**
     * 添加服务机构令牌信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oFuwuToken($data) {
        return Db::name('O2oFuwuToken')->insertGetId($data);
    }
    
    /**
     * 编辑服务机构令牌信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuToken($data, $condition = array()) {
        return Db::name('O2oFuwuToken')->where($condition)->update($data);
    }
    
    public function delO2oFuwuTokenInfo($condition){
        return Db::name('O2oFuwuToken')->where($condition)->delete();
    }

}

?>
