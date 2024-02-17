<?php
/**
 * 店铺模型管理
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
class  Storeplate extends BaseModel {
    public $page_info;
    
    /**
     * 版式列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param int $pagesize 分页
     * @return array
     */
    public function getStoreplateList($condition, $field = '*', $pagesize = 0) {
        if($pagesize){
            $result = Db::name('storeplate')->field($field)->where($condition)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('storeplate')->field($field)->where($condition)->select()->toArray();
        }
        
    }
    
    /**
     * 版式详细信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array
     */
    public function getStoreplateInfo($condition) {
        return Db::name('storeplate')->where($condition)->find();
    }
    
    public function getStoreplateInfoByID($storeplate_id) {
        $info = $this->_rStoreplateCache($storeplate_id);
        if (empty($info)) {
            $info = $this->getStoreplateInfo(array('storeplate_id' => $storeplate_id));
            $this->_wStoreplateCache($storeplate_id, $info);
        }
        return $info;
    }
    
    /**
     * 添加版式
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return boolean
     */
    public function addStoreplate($data) {
        return Db::name('storeplate')->insertGetId($data);
    }
    
    /**
     * 更新版式
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editStoreplate($update, $condition) {
        $list = $this->getStoreplateList($condition, 'storeplate_id');
        if (empty($list)) {
            return true;
        }
        $result = Db::name('storeplate')->where($condition)->update($update);
        if ($result) {
            foreach ($list as $val) {
                $this->_dStoreplateCache($val['storeplate_id']);
            }
        }
        return $result;
    }
    
    /**
     * 删除版式
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function delStoreplate($condition) {
        $list = $this->getStoreplateList($condition, 'storeplate_id');
        if (empty($list)) {
            return true;
        }
        $result = Db::name('storeplate')->where($condition)->delete();
        if ($result) {
            foreach ($list as $val) {
                $this->_dStoreplateCache($val['storeplate_id']);
            }
        }
        return $result;
    }
    
    /**
     * 读取店铺关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 店铺关联版式id
     * @return array
     */
    private function _rStoreplateCache($storeplate_id) {
        return rcache($storeplate_id, 'store_plate');
    }
    
    /**
     * 写入店铺关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 店铺关联版式id
     * @param array $info
     * @return boolean
     */
    private function _wStoreplateCache($storeplate_id, $info) {
        return wcache($storeplate_id, $info, 'store_plate');
    }
    
    /**
     * 删除店铺关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 店铺关联版式id
     * @return boolean
     */
    private function _dStoreplateCache($storeplate_id) {
        return dcache($storeplate_id, 'store_plate');
    }
    
}

