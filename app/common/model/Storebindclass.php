<?php

/**
 * 店铺分类分佣比例
 *
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
class  Storebindclass extends BaseModel {

    
    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStorebindclassList($condition, $pagesize = '', $order = '', $field = '*') {
        if($pagesize){
            $result = Db::name('storebindclass')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        }else{
            $result = Db::name('storebindclass')->field($field)->where($condition)->order($order)->select()->toArray();
            return $result;
        }
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getStorebindclassInfo($condition) {
        $result = Db::name('storebindclass')->where($condition)->find();
        return $result;
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return bool
     */
    public function addStorebindclass($data) {
        return Db::name('storebindclass')->insertGetId($data);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addStorebindclassAll($data) {
        return Db::name('storebindclass')->insertAll($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStorebindclass($update, $condition) {
        return Db::name('storebindclass')->where($condition)->update($update);
    }


    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delStorebindclass($condition) {
        return Db::name('storebindclass')->where($condition)->delete();
    }

    /**
     * 总数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStorebindclassCount($condition = array()) {
        return Db::name('storebindclass')->where($condition)->count();
    }

    /**
     * 取得店铺下商品分类佣金比例
     * @access public
     * @author csdeshang
     * @param array $goods_list 商品列表
     * @return array
     */
    public function getStoreGcidCommisRateList($goods_list) {

        if (empty($goods_list) || !is_array($goods_list))
            return array();

        // 获取绑定所有类目的自营店
        $own_shop_ids = model('store')->getOwnShopIds(true);

        //定义返回数组
        $store_gc_id_commis_rate = array();

        //取得每个店铺下有哪些商品分类
        $store_gc_id_list = array();
        foreach ($goods_list as $goods) {
            if (!intval($goods['gc_id']))
                continue;
            if (empty($store_gc_id_list) || empty($store_gc_id_list[$goods['store_id']]) || !in_array($goods['gc_id'], $store_gc_id_list[$goods['store_id']])) {
                if (in_array($goods['store_id'], $own_shop_ids)) {
                    //平台店铺佣金为0
                    //$store_gc_id_commis_rate[$goods['store_id']][$goods['gc_id']] = 0;
                } else {
                    //$store_gc_id_list[$goods['store_id']][] = $goods['gc_id'];
                }
                $store_gc_id_list[$goods['store_id']][] = $goods['gc_id'];
            }
        }

        if (empty($store_gc_id_list))
            return $store_gc_id_commis_rate;

        $condition = array();
        foreach ($store_gc_id_list as $store_id => $gc_id_list) {
            $condition[]=array('store_id','=',$store_id);
            $condition[]=array('class_1|class_2|class_3','in', $gc_id_list);
            $bind_list = $this->getStorebindclassList($condition);
            if (!$bind_list) {
                $condition = array();
                $condition[] = array('store_id','=',$store_id);
                $condition[] = array('class_1','=',0);
                $condition[] = array('class_2','=',0);
                $condition[] = array('class_3','=',0);
                $bind_list = $this->getStorebindclassList($condition);
            }
            if (!empty($bind_list) && is_array($bind_list)) {
                foreach ($bind_list as $bind_info) {
                    if ($bind_info['store_id'] != $store_id)
                        continue;
                    //如果class_1,2,3有一个字段值匹配，就有效
                    $bind_class = array($bind_info['class_3'], $bind_info['class_2'], $bind_info['class_1']);
                    foreach ($gc_id_list as $gc_id) {
                        //if (in_array($gc_id,$bind_class)) {
                        $store_gc_id_commis_rate[$store_id][$gc_id] = $bind_info['commis_rate'];
                        //}
                    }
                }
            }
        }
        return $store_gc_id_commis_rate;
    }

}
