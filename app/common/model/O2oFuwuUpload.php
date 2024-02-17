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
class  O2oFuwuUpload extends BaseModel {
    public $page_info;

    
    /**
     * 取得服务文件列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oFuwuUploadList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_fuwu_upload_id asc', $limit = 0) {
        if($pagesize){
            $result = Db::name('o2o_fuwu_upload')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('o2o_fuwu_upload')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
        
        
    }

    /**
     * 取得服务文件单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oFuwuUploadInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_fuwu_upload')->where($condition)->field($fields)->find();
    }

    /**
     * 添加服务文件
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oFuwuUpload($data) {
        return Db::name('o2o_fuwu_upload')->insertGetId($data);
    }
    
    /**
     * 编辑服务文件
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuUpload($data, $condition = array()) {
        return Db::name('o2o_fuwu_upload')->where($condition)->update($data);
    }
    
    /**
     * 删除服务文件
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param array $o2o_fuwu_upload 服务文件信息
     * @return type
     */
    public function delO2oFuwuUpload($condition,$o2o_fuwu_upload=array()) {
        if(empty($o2o_fuwu_upload)){
            $o2o_fuwu_upload = $this->getO2oFuwuUploadList($condition,'o2o_fuwu_upload_url');
            if(!$o2o_fuwu_upload){
                return 1;
            }
        }
        foreach($o2o_fuwu_upload as $item){
            //删除图片
            if($item['o2o_fuwu_upload_url']){
                @unlink(BASE_UPLOAD_PATH . '/' . ATTACH_O2O_FUWU_ORGANIZATION . '/' . $item['o2o_fuwu_organization_id'] . '/' . $item['o2o_fuwu_upload_url']);
            }
        }
        return Db::name('o2o_fuwu_upload')->where($condition)->delete();
    }

}

?>
