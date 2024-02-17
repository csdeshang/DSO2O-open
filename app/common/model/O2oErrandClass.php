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
class  O2oErrandClass extends BaseModel {
    public $page_info;

    
    /**
     * 取得跑腿类别列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oErrandClassList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_errand_class_sort_order asc,o2o_errand_class_id asc', $limit = 0) {
        if($pagesize){
            $result = Db::name('o2o_errand_class')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('o2o_errand_class')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
        
        
    }

    /**
     * 取得跑腿类别单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oErrandClassInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_errand_class')->where($condition)->field($fields)->find();
    }

    /**
     * 添加跑腿类别
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oErrandClass($data) {
        return Db::name('o2o_errand_class')->insertGetId($data);
    }
    
    /**
     * 编辑跑腿类别
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oErrandClass($data, $condition = array()) {
        return Db::name('o2o_errand_class')->where($condition)->update($data);
    }
    
    /**
     * 删除跑腿类别
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param array $o2o_distributor_errand 配送员信息
     * @return type
     */
    public function delO2oErrandClass($condition,$o2o_distributor_errand=array()) {
        if(empty($o2o_distributor_errand)){
            $o2o_distributor_errand = $this->getO2oErrandClassList($condition,'o2o_errand_class_logo');
            if(!$o2o_distributor_errand){
                return 1;
            }
        }
        foreach($o2o_distributor_errand as $item){
            //删除图片
            if($item['o2o_errand_class_logo']){
                @unlink(BASE_UPLOAD_PATH . '/' . ATTACH_O2O_ERRAND_CLASS . '/' . $item['o2o_errand_class_logo']);
            }
        }
        return Db::name('o2o_errand_class')->where($condition)->delete();
    }

}

?>
