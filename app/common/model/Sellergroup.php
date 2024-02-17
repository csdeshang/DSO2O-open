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
class  Sellergroup extends BaseModel {


    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSellergroupList($condition) {
        $result = Db::name('sellergroup')->where($condition)->select()->toArray();
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSellergroupInfo($condition) {
        $result = Db::name('sellergroup')->where($condition)->find();
        return $result;
    }


    /**
     * 判断是否存在
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return boolean
     */
    public function isSellergroupExist($condition) {
        $result = Db::name('sellergroup')->where($condition)->find();
        if (empty($result)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 增加 
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addSellergroup($data) {
        return Db::name('sellergroup')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param array $update 数据
     * @param array $condition 条件
     * @return bool
     */
    public function editSellergroup($update, $condition) {
        return Db::name('sellergroup')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delSellergroup($condition) {
        return Db::name('sellergroup')->where($condition)->delete();
    }

}
