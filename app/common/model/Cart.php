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
class  Cart extends BaseModel {

    /**
     * 购物车商品总金额
     */
    private $cart_all_price = 0;

    /**
     * 购物车商品总数
     */
    private $cart_goods_num = 0;
    public $error_message = '';
    public $error_code = 0;

    /**
     * 取属性值魔术方法
     * @access public
     * @author csdeshang 
     * @param type $name 名称
     * @return type
     */
    public function __get($name) {
        return $this->$name;
    }

    /**
     * 检查购物车内商品是否存在
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @return bool
     */
    public function checkCart($condition = array()) {
        return Db::name('cart')->where($condition)->find();
    }

    /**
     * 会员购物车内商品数 
     * @access public
     * @author csdeshang 
     * @param int $memberId 会员ID
     * @return int
     */
    public function getCartCountByMemberId($memberId,$store_id = 0) {
        $memberId = intval($memberId);
        $condition = array();
        $condition[] = array('buyer_id','=',$memberId);
        if($store_id > 0){
            $condition[] = array('store_id','=',$store_id);
        }
        return Db::name('cart')->where($condition)->sum('goods_num');
    }

    /**
     * 取得 单条购物车信息
     * @access public
     * @author csdeshang  
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getCartInfo($condition = array(), $field = '*') {
        return Db::name('cart')->field($field)->where($condition)->find();
    }

    /**
     * 将商品添加到购物车中
     * @access public
     * @author csdeshang
     * @param array	$data	商品数据信息
     * @param string $save_type 保存类型，可选值 db,cookie
     * @param int $quantity 购物数量
     * @return type
     */
    public function addCart($data = array(), $save_type = '', $quantity = null) {
        //判断店铺是否开启了接单，是否在营业时间内
        $store_model = model('store');
        $store_info = $store_model->getOneStore(array('store_id' => $data['store_id']), 'store_name,store_state,store_o2o_receipt,store_o2o_auto_receipt,store_o2o_open_start,store_o2o_open_end');
        if (!$store_info) {
            $this->error_code = 10001;
            $this->error_message = '店铺[store_id:' . $data['store_id'] . ']不存在';
            return false;
        }
        $ret = $store_model->getO2oState($store_info);
        if (!$ret['code']) {
            $this->error_code = 10001;
            $this->error_message = $ret['msg'];
            return false;
        }

        $method = '_addCartdb';
        $result = $this->$method($data, $quantity);
        if(!$result){
            return false;
        }
        //更改购物车总商品数和总金额，传递数组参数只是给DB使用
        $this->getCartNum('db', array('buyer_id' => isset($data['buyer_id']) ? $data['buyer_id'] : 0));
        return $result;
    }

    /**
     * 添加数据库购物车
     * @access public
     * @author csdeshang
     * @param array $goods_info 商品信息
     * @param int $quantity 购物数量
     * @return type
     */
    private function _addCartDb($goods_info = array(), $quantity) {
        //验证购物车商品是否已经存在
        $condition = array();
        $condition[] = array('goods_id','=',$goods_info['goods_id']);
        $condition[] = array('buyer_id','=',$goods_info['buyer_id']);
        //如果购物车
        $check_cart = $this->checkCart($condition);
        
        if (!empty($check_cart)) {
            $quantity = $check_cart['goods_num'] + $quantity;
            if($quantity>$goods_info['goods_storage']){
                $this->error_code = 10001;
                $this->error_message = '库存不足';
                return false;
            }
            //如果商品存在则更新数量
            return Db::name('cart')->where($condition)->update(array('goods_num' => $quantity));
        } else {
            //如果商品存在则插入
            $array = array();
            $array['buyer_id'] = $goods_info['buyer_id'];
            $array['store_id'] = $goods_info['store_id'];
            $array['goods_id'] = $goods_info['goods_id'];
            $array['goods_name'] = $goods_info['goods_name'];
            $array['goods_price'] = $goods_info['goods_price'];
            $array['goods_num'] = $quantity;
            $array['goods_image'] = $goods_info['goods_image'];
            $array['store_name'] = $goods_info['store_name'];
            return Db::name('cart')->insertGetId($array);
        }
    }


    /**
     * 更新购物车
     * @access public
     * @author csdeshang
     * @param	array	$data 商品信息
     * @param	array	$condition 检索条件
     * @return bool
     */
    public function editCart($data, $condition,$buyer_id) {
        $result = Db::name('cart')->where($condition)->update($data);
        if ($result) {
            $this->getCartNum('db', array('buyer_id' => $buyer_id));
        }
        return $result;
    }

    /**
     * 购物车列表
     * @access public
     * @author csdeshang
     * @param string $type 存储类型 db,cookie
     * @param array $condition 检索条件
     * @param int $limit 限制
     * @return array
     */
    public function getCartList($type, $condition = array(), $limit = 0) {
 
            $cart_list = Db::name('cart')->where($condition)->limit($limit)->select()->toArray();
 
        $cart_list = is_array($cart_list) ? $cart_list : array();
        //顺便设置购物车商品数和总金额
        $this->cart_goods_num = count($cart_list);
        $cart_all_price = 0;
        if (is_array($cart_list)) {
            foreach ($cart_list as $val) {
                $cart_all_price += $val['goods_price'] * $val['goods_num'];
            }
        }
        $this->cart_all_price = ds_price_format($cart_all_price);
        return !is_array($cart_list) ? array() : $cart_list;
    }

    /**
     * 删除购物车商品
     * @access public
     * @author csdeshang
     * @param string $type 存储类型 db,cookie
     * @param array $condition 检索条件
     * @return bool
     */
    public function delCart($type, $condition = array(),$buyer_id) {

            $result = Db::name('cart')->where($condition)->delete();

        //重新计算购物车商品数和总金额
        if ($result) {
            $this->getCartNum('db', array('buyer_id' => $buyer_id));
        }
        return $result;
    }

    /**
     * 清空购物车
     * @access public
     * @author csdeshang 
     * @param string $type 存储类型 db,cookie
     * @param array $condition 检索条件
     * @return bool
     */
    public function clearCart($type, $condition = array()) {
       
            //数据库暂无浅清空操作
      
    }

    /**
     * 计算购物车总商品数和总金额
     * @access public
     * @author csdeshang 
     * @param string $type 购物车信息保存类型 db,cookie
     * @param array $condition 只有登录后操作购物车表时才会用到该参数
     * @return type
     */
    public function getCartNum($type, $condition = array()) {
   
            $cart_all_price = 0;
            $cart_goods = $this->getCartList('db', $condition);
            $this->cart_goods_num = count($cart_goods);
            if (!empty($cart_goods) && is_array($cart_goods)) {
                foreach ($cart_goods as $val) {
                    $cart_all_price += $val['goods_price'] * $val['goods_num'];
                }
            }
            $this->cart_all_price = ds_price_format($cart_all_price);

        @cookie('cart_goods_num', $this->cart_goods_num, 2 * 3600);
        return $this->cart_goods_num;
    }


}

?>
