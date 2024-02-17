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
class  O2oFuwuClass extends BaseModel {
    public $page_info;

    
    /**
     * 取得服务类别列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oFuwuClassList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_fuwu_class_id asc', $limit = 0) {
        if($pagesize){
            $result = Db::name('o2o_fuwu_class')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('o2o_fuwu_class')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
        
        
    }

    /**
     * 取得服务类别单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oFuwuClassInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_fuwu_class')->where($condition)->field($fields)->find();
    }

    /**
     * 添加服务类别
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oFuwuClass($data) {
        return Db::name('o2o_fuwu_class')->insertGetId($data);
    }
    
    /**
     * 编辑服务类别
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuClass($data, $condition = array()) {
        return Db::name('o2o_fuwu_class')->where($condition)->update($data);
    }
    
    /**
     * 删除服务类别
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param array $o2o_fuwu_class 服务类别信息
     * @return type
     */
    public function delO2oFuwuClass($condition,$o2o_fuwu_class=array()) {
        if(empty($o2o_fuwu_class)){
            $o2o_fuwu_class = $this->getO2oFuwuClassList($condition,'o2o_fuwu_class_logo');
            if(!$o2o_fuwu_class){
                return 1;
            }
        }
        foreach($o2o_fuwu_class as $item){
            //删除图片
            if($item['o2o_fuwu_class_logo']){
                @unlink(BASE_UPLOAD_PATH . '/' . ATTACH_O2O_FUWU_CLASS . '/' . $item['o2o_fuwu_class_logo']);
            }
        }
        return Db::name('o2o_fuwu_class')->where($condition)->delete();
    }

    /**
     * 取分类列表，按照深度归类
     * @access public
     * @author csdeshang
     * @param int $show_deep 显示深度
     * @return array 数组类型的返回结果
     */
    public function getTreeClassList($show_deep='2'){
        $condition	= array();
        $class_list = $this->getO2oFuwuClassList($condition, '*', null, 'o2o_fuwu_class_parent_id asc');
        $show_deep = intval($show_deep);
        $result = array();
        if(is_array($class_list) && !empty($class_list)) {
            $result = $this->_getTreeClassList($show_deep,$class_list);
        }
        return $result;
    }

    /**
     * 递归 整理分类
     * @access public
     * @author csdeshang
     * @param int $show_deep 显示深度
     * @param array $class_list 类别内容集合
     * @param int $deep 深度
     * @param int $parent_id 父类编号
     * @param int $i 上次循环编号
     * @return array $show_class 返回数组形式的查询结果
     */
    private function _getTreeClassList($show_deep,$class_list,$deep=1,$parent_id=0,$i=0){
        static $show_class = array();//树状的平行数组
        if(is_array($class_list) && !empty($class_list)) {
            $size = count($class_list);
            if($i == 0) $show_class = array();//从0开始时清空数组，防止多次调用后出现重复
            for ($i;$i < $size;$i++) {//$i为上次循环到的分类编号，避免重新从第一条开始
                $val = $class_list[$i];
                $ac_id = $val['o2o_fuwu_class_id'];
                $ac_parent_id	= $val['o2o_fuwu_class_parent_id'];
                if($ac_parent_id == $parent_id) {
                    $val['deep'] = $deep;
                    $show_class[] = $val;
                    if($deep < $show_deep && $deep < 2) {//本次深度小于显示深度时执行，避免取出的数据无用
                        $this->_getTreeClassList($show_deep,$class_list,$deep+1,$ac_id,$i+1);
                    }
                }
                if($ac_parent_id > $parent_id) break;//当前分类的父编号大于本次递归的时退出循环
            }
        }
        return $show_class;
    }
}

?>
