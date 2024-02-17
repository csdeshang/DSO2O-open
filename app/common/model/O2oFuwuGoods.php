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
class  O2oFuwuGoods extends BaseModel {
    public $page_info;

    
    /**
     * 取得服务商品列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @param int $limit 数量限制
     * @return array
     */
    public function getO2oFuwuGoodsList($condition = array(), $fields = '*', $pagesize = null, $order = 'o2o_fuwu_goods_sort_order asc,o2o_fuwu_goods_id asc', $limit = 0) {
        if($pagesize){
            $result = Db::name('o2o_fuwu_goods')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            return Db::name('o2o_fuwu_goods')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
        }
        
        
    }

    /**
     * 取得服务商品单条
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @return array
     */
    public function getO2oFuwuGoodsInfo($condition = array(), $fields = '*') {
        return Db::name('o2o_fuwu_goods')->where($condition)->field($fields)->find();
    }

    /**
     * 添加服务商品
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addO2oFuwuGoods($data) {
        return Db::name('o2o_fuwu_goods')->insertGetId($data);
    }
    
    /**
     * 编辑服务商品
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editO2oFuwuGoods($data, $condition = array()) {
        return Db::name('o2o_fuwu_goods')->where($condition)->update($data);
    }
    
    /**
     * 删除服务商品
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param array $o2o_fuwu_goods 服务商品信息
     * @return type
     */
    public function delO2oFuwuGoods($condition,$o2o_fuwu_goods=array()) {
        if(empty($o2o_fuwu_goods)){
            $o2o_fuwu_goods = $this->getO2oFuwuGoodsList($condition,'o2o_fuwu_goods_id,o2o_fuwu_organization_id');
            if(!$o2o_fuwu_goods){
                return 1;
            }
        }
        foreach($o2o_fuwu_goods as $item){
            //删除规格
            Db::name('o2o_fuwu_goods_spec')->where(array('o2o_fuwu_goods_id'=>$item['o2o_fuwu_goods_id']))->delete();
            //删除图片
            model('o2o_fuwu_upload')->delO2oFuwuUpload(array(array('o2o_fuwu_upload_type','in',array(O2O_FUWU_UPLOAD_GOODS_IMAGE,O2O_FUWU_UPLOAD_GOODS_BODY)),array('o2o_fuwu_upload_item_id','=',$item['o2o_fuwu_goods_id'])));
        }

        return Db::name('o2o_fuwu_goods')->where($condition)->delete();
    }


}

?>
