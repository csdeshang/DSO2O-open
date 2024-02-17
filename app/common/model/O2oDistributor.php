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
class  O2oDistributor extends BaseModel {
    const STATE_OPEN=1;
    const STATE_CLOSE=0;
    
    public $page_info;

    /**
     * 取得配送员
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oDistributorList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_distributor_id desc', $limit = 0) {
        if($pagesize){
            $result = Db::name('O2oDistributor')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('O2oDistributor')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
    }


    /**
     * 取得配送员信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getO2oDistributorInfo($condition = array(), $fields = '*') {
        return Db::name('O2oDistributor')->where($condition)->field($fields)->find();
    }
    
    /**
     * 添加配送员信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oDistributor($data) {
        return Db::name('O2oDistributor')->insertGetId($data);
    }
    
    /**
     * 编辑配送员信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oDistributor($data, $condition = array()) {
        return Db::name('O2oDistributor')->where($condition)->update($data);
    }
    /**
     * 删除配送员信息
     * @access public
     * @author csdeshang  
     * @param array $condition 条件
     * @param array $o2o_distributor_array 配送员信息
     * @return int
     */
    public function delO2oDistributorInfo($condition,$o2o_distributor_array=array()){
        if(empty($o2o_distributor_array)){
            $o2o_distributor_array = $this->getO2oDistributorList($condition,'o2o_distributor_avatar');
            if(!$o2o_distributor_array){
                return 1;
            }
        }
        foreach($o2o_distributor_array as $item){
            //删除头像
            if($item['o2o_distributor_avatar']){
                @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_O2O_DISTRIBUTOR . DIRECTORY_SEPARATOR . $item['o2o_distributor_avatar']);
            }
        }
        return Db::name('O2oDistributor')->where($condition)->delete();
    }
    

}

?>
