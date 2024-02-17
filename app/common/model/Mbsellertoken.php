<?php

/**
 * 手机端买家令牌模型
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
class  Mbsellertoken extends BaseModel {
    
/**
     * 查询
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbsellertokenInfo($condition) {
        return Db::name('mbsellertoken')->where($condition)->find();
    }
    
    /**
     * 获取卖家令牌
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @return type
     */
    public function getMbsellertokenInfoByToken($token) {
        if (empty($token)) {
            return null;
        }
        return $this->getMbsellertokenInfo(array('seller_token' => $token));
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbsellertoken($data) {
        return Db::name('mbsellertoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbsellertoken($condition) {
        return Db::name('mbsellertoken')->where($condition)->delete();
    }
    
    
}