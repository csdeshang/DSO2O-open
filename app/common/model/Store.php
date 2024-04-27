<?php

/**
 * 店铺设置
 *
 */

namespace app\common\model;
use app\common\model\Storedepositlog;
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
class  Store extends BaseModel {

    public $page_info;
    


    /**
     * 查询店铺列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @param string $limit 限制条数
     * @return array
     */
    public function getStoreList($condition, $pagesize = null, $order = '', $field = '*', $limit = 0) {
        if ($pagesize) {
            $result = Db::name('store')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('store')->field($field)->where($condition)->order($order)->limit($limit)->select()->toArray();
            return $result;
        }
    }

    /**
     * 查询有效店铺列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStoreOnlineList($condition, $pagesize = null, $order = '', $field = '*') {
        $condition[]=array('store_state','=',1);
        return $this->getStoreList($condition, $pagesize, $order, $field);
    }

    /**
     * 店铺数量
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getStoreCount($condition) {
        return Db::name('store')->where($condition)->count();
    }

    /**
     * 按店铺编号查询店铺的信息
     * @access public
     * @author csdeshang
     * @param type $storeid_array 店铺ID编号
     * @param type $field 字段
     * @return type
     */
    public function getStoreMemberIDList($storeid_array, $field = 'store_id,member_id,store_name') {
        $store_list = Db::name('store')->where(array(array('store_id','in', $storeid_array)))->field($field)->select()->toArray();
        $store_list = ds_change_arraykey($store_list, 'store_id');
        return $store_list;
    }

    /**
     * 查询店铺信息
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getStoreInfo($condition) {
        $store_info = Db::name('store')->where($condition)->find();
        if (!empty($store_info)) {
            if (!empty($store_info['store_presales']))
                $store_info['store_presales'] = unserialize($store_info['store_presales']);
            if (!empty($store_info['store_aftersales']))
                $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);

            //商品数
            $goods_model = model('goods');
            $store_info['goods_count'] = $goods_model->getGoodsCommonOnlineCount(array(array('store_id' ,'=', $store_info['store_id'])));

            //店铺评价
            $evaluatestore_model = model('evaluatestore');
            $store_evaluate_info = $evaluatestore_model->getEvaluatestoreInfoByStoreID($store_info['store_id'], $store_info['storeclass_id']);

            $store_info = array_merge($store_info, $store_evaluate_info);
        }
        return $store_info;
    }

    /**
     * 通过店铺编号查询店铺信息
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺编号
     * @return array
     */
    public function getStoreInfoByID($store_id) {
        $prefix = 'store_info';

        $store_info = rcache($store_id, $prefix);
        if (empty($store_info)) {
            $store_info = $this->getStoreInfo(array('store_id' => $store_id));
            $cache = array();
            $cache['store_info'] = serialize($store_info);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $store_info = unserialize($store_info['store_info']);
        }

        return $store_info;
    }
    
    /**
     * 获取店铺信息根据店铺id
     * @access public
     * @author csdeshang
     * @param type $store_id 店铺ID
     * @return type 
     */
    public function getStoreOnlineInfoByID($store_id) {
        $store_info = $this->getStoreInfoByID($store_id);
        if (empty($store_info) || $store_info['store_state'] == '0') {
            return array();
        } else {
            return $store_info;
        }
    }
    
    /**
     * 获取店铺ID字符串
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return string
     */
    public function getStoreIDString($condition) {
        $condition[]=array('store_state','=',1);
        $store_list = $this->getStoreList($condition);
        $store_id_string = '';
        foreach ($store_list as $value) {
            $store_id_string .= $value['store_id'] . ',';
        }
        return $store_id_string;
    }

    /**
     * 添加店铺
     * @access public
     * @author csdeshang
     * @param type $data 店铺数据
     * @return type
     */
    public function addStore($data) {
        return Db::name('store')->insertGetId($data);
    }

    /**
     * 编辑店铺
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStore($update, $condition) {
        //清空缓存
        $store_list = $this->getStoreList($condition);
        foreach ($store_list as $value) {
            dcache($value['store_id'], 'store_info');
        }

        return Db::name('store')->where($condition)->update($update);
    }

    /**
     * 删除店铺
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delStore($condition) {
        $store_info = $this->getStoreInfo($condition);
        //删除店铺相关图片
        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_logo']);
        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_banner']);
        if (isset($store_info['store_slide'])&&$store_info['store_slide'] != '') {
            foreach (explode(',', $store_info['store_slide']) as $val) {
                @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_SLIDE . DIRECTORY_SEPARATOR . $val);
            }
        }

        //清空缓存
        dcache($store_info['store_id'], 'store_info');

        return Db::name('store')->where($condition)->delete();
    }

    /**
     * 完全删除店铺 包括店主账号、店铺的管理员账号、店铺相册、店铺扩展
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     */
    public function delStoreEntirely($condition) {
        $this->delStore($condition);
        model('seller')->delSeller($condition);
        model('sellergroup')->delSellergroup($condition);
        model('album')->delAlbum($condition['store_id']);
        model('storeextend')->delStoreextend($condition);
        model('storegoodsclass')->delStoregoodsclass($condition,$condition['store_id']);
        model('storemsg')->delStoremsg($condition);
        model('storenavigation')->delStorenavigation(array('storenav_store_id'=>$condition['store_id']));
        model('storeplate')->delStoreplate($condition);
        model('storereopen')->delStorereopen(array('storereopen_store_id'=>$condition['store_id']));
        model('storewatermark')->delStorewatermark($condition);

        
    }

    /**
     * 获取商品销售排行(每天更新一次)
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺编号
     * @param int $limit 限制数量
     * @return array
     */
    public function getHotSalesList($store_id, $limit = 5) {
        $prefix = 'store_hot_sales_list_' . $limit;
        $hot_sales_list = rcache($store_id, $prefix);
        if (empty($hot_sales_list)) {
            $goods_model = model('goods');
            $hot_sales_list = $goods_model->getGoodsOnlineList(array(array('store_id' ,'=', $store_id)), '*', 0, 'goods_salenum desc', $limit);
            $cache = array();
            $cache['hot_sales'] = serialize($hot_sales_list);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $hot_sales_list = unserialize($hot_sales_list['hot_sales']);
        }
        return $hot_sales_list;
    }

    /**
     * 获取商品收藏排行(每天更新一次)
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺编号
     * @param int $limit 限制数量
     * @return array	商品信息
     */
    public function getHotCollectList($store_id, $limit = 5) {
        $prefix = 'store_collect_sales_list_' . $limit;
        $hot_collect_list = rcache($store_id, $prefix);
        if (empty($hot_collect_list)) {
            $goods_model = model('goods');
            $hot_collect_list = $goods_model->getGoodsOnlineList(array(array('store_id' ,'=', $store_id)), '*', 0, 'goods_collect desc', $limit);
            $cache = array();
            $cache['collect_sales'] = serialize($hot_collect_list);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $hot_collect_list = unserialize($hot_collect_list['collect_sales']);
        }
        return $hot_collect_list;
    }

    /**
     * 获取店铺列表页附加信息
     * @access public
     * @author csdeshang
     * @param array $store_array 店铺数组
     * @return array 包含近期销量和8个推荐商品的店铺数组
     */
    public function getStoreSearchList($store_array) {
        $store_array_new = array();
        if (!empty($store_array)) {
            $no_cache_store = array();
            foreach ($store_array as $value) {
                //$store_search_info = rcache($value['store_id']);
                //print_r($store_array);exit();
                //if($store_search_info !== FALSE) {
                //	$store_array_new[$value['store_id']] = $store_search_info;
                //} else {
                //	$no_cache_store[$value['store_id']] = $value;
                //}
                $no_cache_store[$value['store_id']] = $value;
            }
            if (!empty($no_cache_store)) {
                //获取店铺商品数
                $no_cache_store = $this->getStoreInfoBasic($no_cache_store);
                //获取店铺近期销量
                $no_cache_store = $this->getGoodsCountJq($no_cache_store);
                //获取店铺推荐商品
                $no_cache_store = $this->getGoodsListBySales($no_cache_store);
                //写入缓存
                foreach ($no_cache_store as $value) {
                    wcache($value['store_id'], $value, 'store_search_info');
                }
                $store_array_new = array_merge($store_array_new, $no_cache_store);
            }
        }
        return $store_array_new;
    }

    /**
     * 获得店铺标志、信用、商品数量、店铺评分等信息
     * @access public
     * @author csdeshang
     * @param type $list 店铺数组
     * @param type $day  天数
     * @return type
     */
    public function getStoreInfoBasic($list, $day = 0) {
        $list_new = array();
        if (!empty($list) && is_array($list)) {
            foreach ($list as $key => $value) {
                if (!empty($value)) {
                    $value['store_logo'] = get_store_logo($value['store_logo']);
                    //店铺评价
                    $evaluatestore_model = model('evaluatestore');
                    $store_evaluate_info = $evaluatestore_model->getEvaluatestoreInfoByStoreID($value['store_id'], $value['storeclass_id']);
                    $value = array_merge($value, $store_evaluate_info);

                    if (!empty($value['store_presales']))
                        $value['store_presales'] = unserialize($value['store_presales']);
                    if (!empty($value['store_aftersales']))
                        $value['store_aftersales'] = unserialize($value['store_aftersales']);
                    $list_new[$value['store_id']] = $value;
                    $list_new[$value['store_id']]['goods_count'] = 0;
                }
            }
            //全部商品数直接读取缓存
            if ($day > 0) {
                $store_id_string = implode(',', array_keys($list_new));
                //指定天数直接查询数据库
                $condition = array();
                $condition[]=array('goods_show','=','1');
                $condition[]=array('store_id','in', $store_id_string);
                $condition[] = array('goods_addtime','>', strtotime("-{$day} day"));
                $goods_count_array = Db::name('goods')->field('store_id,count(*) as goods_count')->where($condition)->group('store_id')->select()->toArray();
                if (!empty($goods_count_array)) {
                    foreach ($goods_count_array as $value) {
                        $list_new[$value['store_id']]['goods_count'] = $value['goods_count'];
                    }
                }
            } else {
                $list_new = $this->getGoodsCountByStoreArray($list_new);
            }
        }
        return $list_new;
    }

    /**
     * 获取店铺商品数
     * @access public
     * @author csdeshang
     * @param type $store_array 店铺数组
     * @return type
     */
    public function getGoodsCountByStoreArray($store_array) {
        $store_array_new = array();
        $no_cache_store = '';

        foreach ($store_array as $value) {
            $goods_count = rcache($value['store_id'], 'store_goods_count');

            if (!empty($goods_count) && $goods_count !== FALSE) {
                //有缓存的直接赋值
                $value['goods_count'] = $goods_count;
            } else {
                //没有缓存记录store_id，统计从数据库读取
                $no_cache_store .= $value['store_id'] . ',';
                $value['goods_count'] = '0';
            }
            $store_array_new[$value['store_id']] = $value;
        }

        if (!empty($no_cache_store)) {

            //从数据库读取店铺商品数赋值并缓存
            $no_cache_store = rtrim($no_cache_store, ',');
            $condition = array();
            $condition[]=array('goods_state','=','1');
            $condition[]=array('store_id','in', $no_cache_store);
            $goods_count_array = Db::name('goods')->field('store_id,count(*) as goods_count')->where($condition)->group('store_id')->select()->toArray();
            if (!empty($goods_count_array)) {
                foreach ($goods_count_array as $value) {
                    $store_array_new[$value['store_id']]['goods_count'] = $value['goods_count'];
                    wcache($value['store_id'], $value['goods_count'], 'store_goods_count');
                }
            }
        }
        return $store_array_new;
    }

    /**
     * 获取近期销量
     * @access public
     * @author csdeshang
     * @param type $store_array 店铺数组
     * @return type
     */
    private function getGoodsCountJq($store_array) {
        $order_count_array = Db::name('order')->field('store_id,count(*) as order_count')->where(array(array('store_id','in', implode(',', array_keys($store_array))),array('order_state','<>',"0"), array('add_time','>', TIMESTAMP - 3600 * 24 * 90)))->group('store_id')->select()->toArray();
        foreach ((array) $order_count_array as $value) {
            $store_array[$value['store_id']]['num_sales_jq'] = $value['order_count'];
        }
        return $store_array;
    }

    /**
     * 获取店铺8个销量最高商品
     * @access public
     * @author csdeshang
     * @param type $store_array 店铺数组
     * @return type
     */
    private function getGoodsListBySales($store_array) {
        $field = 'goods_id,store_id,goods_name,goods_image,goods_price,goods_salenum';
        foreach ($store_array as $value) {
            $store_array[$value['store_id']]['search_list_goods'] = Db::name('goods')->field($field)->where(array('store_id' => $value['store_id'], 'goods_state' => 1))->order('goods_salenum desc')->limit(8)->select()->toArray();
        }
        return $store_array;
    }
    /**
     * 编辑
     * @param type $condition
     * @param type $data
     * @return type
     */
    public function editGoodscommon($condition,$data){
        return Db::name('goodscommon')->where($condition)->update($data);
    }
    /**
     * 编辑商品
     * @param type $condition
     * @param type $data
     * @return type
     */
    public function editGoods($condition,$data){
        return Db::name('goods')->where($condition)->update($data);
    }
    /**
     * 插入店铺扩展表
     * @param type $condition
     * @return type
     */
    public function addStoreextend($condition){
        return Db::name('storeextend')->insert($condition);
    }
    /**
     * 获取单个店铺
     * @param type $condition
     * @param type $field
     * @return type
     */
    public function getOneStore($condition,$field){
        return Db::name('store')->field($field)->where($condition)->find();
    }
    /**
     * 判断店铺配送状态
     * @param array $store_info
     * @return array
     */
    public function getO2oState($store_info) {
        if (!$store_info) {
            return ds_callback(false, '店铺不存在');
        }
        if ($store_info['store_state'] != 1) {
            return ds_callback(false, '店铺[' . $store_info['store_name'] . ']未开启');
        }

        if (!$store_info['store_o2o_receipt']) {
            return ds_callback(false, '店铺[' . $store_info['store_name'] . ']暂停接单');
        }
        
        //根据店铺设置的营业时间判断是否可以下单
        /*
        $start_time = strtotime(date('Y-m-d 0:0:0')) + $store_info['store_o2o_open_start'] * 60;
        $start_time_text = floor($store_info['store_o2o_open_start'] / 60) . ':' . str_pad(strval($store_info['store_o2o_open_start'] % 60), 2, '0', STR_PAD_LEFT);
        $end_time = strtotime(date('Y-m-d 0:0:0')) + $store_info['store_o2o_open_end'] * 60;
        $end_hour = floor($store_info['store_o2o_open_end'] / 60);
        $end_time_text = (($end_hour < 24) ? $end_hour : ('次日' . ($end_hour % 24))) . ':' . str_pad(strval($store_info['store_o2o_open_end'] % 60), 2, '0', STR_PAD_LEFT);
        //是否在营业时间内
        if ($start_time > TIMESTAMP) {
            return ds_callback(false, '店铺[' . $store_info['store_name'] . ']营业时间为' . $start_time_text . '到'.$end_time_text.'，请您耐心等待');
        }
        if ($end_time < TIMESTAMP) {
            return ds_callback(false, '店铺[' . $store_info['store_name'] . ']营业时间为' . $start_time_text . '到'.$end_time_text.'，请您明日再来');
        }
         */
        
        
        
        return ds_callback(true);
    }
    
    public function isO2oSupport($store_info){
        if($store_info['store_o2o_distribute_type']==1 || ($store_info['store_o2o_distribute_type']==0 && config('ds_config.o2o_open'))){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     *  店铺流量统计入库
     */
    public function flowstat_record($store_id,$goods_id,$controller_param,$action_param,$store_info) {
        if(!empty($store_info)){
            if ($store_id <= 0 || $store_info['store_id'] == $store_id) {
                return false;
            }
        }
        //确定统计分表名称
        $last_num = $store_id % 10; //获取店铺ID的末位数字
        $tablenum = ($t = intval(config('ds_config.flowstat_tablenum'))) > 1 ? $t : 1; //处理流量统计记录表数量
        $flow_tablename = ($t = ($last_num % $tablenum)) > 0 ? "flowstat_$t" : 'flowstat';
        //判断是否存在当日数据信息
        $stattime = strtotime(date('Y-m-d', TIMESTAMP));
        $stat_model = model('stat');
        //查询店铺流量统计数据是否存在
        // halt($flow_tablename);
        if ($flow_tablename == 'flowstat') {
            $flow_tablename_condition = array('flowstat_stattime' => $stattime, 'store_id' => $store_id, 'flowstat_type' => 'sum');
        } else {
            $flow_tablename_condition = array('flowstat_stattime' => $stattime, 'store_id' => $store_id, 'flowstat_type' => 'sum');
        }
        $store_exist = $stat_model->getoneByFlowstat($flow_tablename, $flow_tablename_condition);
        if ($controller_param == 'Goods' && $action_param == 'index') {//统计商品页面流量
            $goods_id = intval($goods_id);
            if ($goods_id <= 0) {
                return false;
            }
            if ($flow_tablename == 'flowstat') {
                $flow_tablename_condition = array('flowstat_stattime' => $stattime, 'goods_id' => $goods_id, 'flowstat_type' => 'goods');
            } else {
                $flow_tablename_condition = array('flowstat_stattime' => $stattime, 'goods_id' => $goods_id, 'flowstat_type' => 'goods');
            }
            $goods_exist = $stat_model->getoneByFlowstat($flow_tablename, $flow_tablename_condition);
        }
        //向数据库写入访问量数据
        $insert_arr = array();
        if ($store_exist) {
            Db::name($flow_tablename)->where(array('flowstat_stattime' => $stattime, 'store_id' => $store_id, 'flowstat_type' => 'sum'))->inc('flowstat_clicknum')->update();
        } else {
            $insert_arr[] = array('flowstat_stattime' => $stattime, 'flowstat_clicknum' => 1, 'store_id' => $store_id, 'flowstat_type' => 'sum', 'goods_id' => 0);
        }
        if ($controller_param == 'Goods' && $action_param == 'index') {//已经存在数据则更新
            if ($goods_exist) {
                Db::name($flow_tablename)->where(array('flowstat_stattime' => $stattime, 'goods_id' => $goods_id, 'flowstat_type' => 'goods'))->inc('flowstat_clicknum')->update();
            } else {
                $insert_arr[] = array('flowstat_stattime' => $stattime, 'flowstat_clicknum' => 1, 'store_id' => $store_id, 'flowstat_type' => 'goods', 'goods_id' => $goods_id);
            }
        }
        if ($insert_arr) {
            Db::name($flow_tablename)->insertAll($insert_arr);
        }
    }
	
	/**
	 * 店铺开店成功
	 * @param type $condition
	 * @param type $field
	 * @return type
	 */
	public function setStoreOpen($joinin_detail,$param){
		$storejoinin_model = model('storejoinin');
		$seller_model = model('seller');
		//验证卖家用户名是否已经存在
		if ($seller_model->isSellerExist(array('seller_name' => $joinin_detail['seller_name']))) {
			throw new \think\Exception('卖家用户名已存在', 10006);
		}
			$predeposit_model = model('predeposit');
			//下单，支付被冻结的充值卡
			$rcb_amount = floatval($joinin_detail['rcb_amount']);
			if ($rcb_amount > 0) {
				$data_pd = array();
				$data_pd['member_id'] = $joinin_detail['member_id'];
				$data_pd['member_name'] = $joinin_detail['member_name'];
				$data_pd['amount'] = $rcb_amount;
				$data_pd['order_sn'] = $joinin_detail['pay_sn'];
				$predeposit_model->changeRcb('storejoinin_comb_pay', $data_pd);
			}

			//下单，支付被冻结的预存款
			$pd_amount = floatval($joinin_detail['pd_amount']);
			if ($pd_amount > 0) {
				$data_pd = array();
				$data_pd['member_id'] = $joinin_detail['member_id'];
				$data_pd['member_name'] = $joinin_detail['member_name'];
				$data_pd['amount'] = $pd_amount;
				$data_pd['order_sn'] = $joinin_detail['pay_sn'];
				$predeposit_model->changePd('storejoinin_comb_pay', $data_pd);
			}
			//开店
			$shop_array = array();
			$shop_array['member_id'] = $joinin_detail['member_id'];
			$shop_array['member_name'] = $joinin_detail['member_name'];
			$shop_array['seller_name'] = $joinin_detail['seller_name'];
			$shop_array['grade_id'] = $joinin_detail['storegrade_id'];
			$shop_array['store_name'] = $joinin_detail['store_name'];
			$shop_array['storeclass_id'] = $joinin_detail['storeclass_id'];
			$shop_array['store_company_name'] = $joinin_detail['company_name'];
			$shop_array['region_id'] = $joinin_detail['company_province_id'];
			$shop_array['store_longitude'] = $joinin_detail['store_longitude'];
			$shop_array['store_latitude'] = $joinin_detail['store_latitude'];
			$shop_array['area_info'] = $joinin_detail['company_address'];

			$shop_array['store_address'] = $joinin_detail['company_address_detail'];
			$shop_array['store_zip'] = '';
			$shop_array['store_mainbusiness'] = '';
			$shop_array['store_state'] = 1;
			$shop_array['store_addtime'] = TIMESTAMP;
			$shop_array['store_endtime'] = strtotime(date('Y-m-d 23:59:59', strtotime('+1 day')) . " +" . intval($joinin_detail['joinin_year']) . " year");
			//$shop_array['store_avaliable_deposit']=$joinin_detail['storeclass_bail'];
			$store_id = $this->addStore($shop_array);

			if ($store_id) {
				//记录保证金
				if ($joinin_detail['storeclass_bail'] > 0) {
					$storedepositlog_model = model('storedepositlog');
			 
						$storedepositlog_model->changeStoredeposit(array(
							'store_id' => $store_id,
							'storedepositlog_type' => Storedepositlog::TYPE_PAY,
							'storedepositlog_state' => Storedepositlog::STATE_VALID,
							'storedepositlog_add_time' => TIMESTAMP,
							'store_avaliable_deposit' => $joinin_detail['storeclass_bail'],
							'storedepositlog_desc' => '店铺入驻保证金',
						));

				}
				//写入卖家账号
				$seller_array = array();
				$seller_array['seller_name'] = $joinin_detail['seller_name'];
				$seller_array['member_id'] = $joinin_detail['member_id'];
				$seller_array['sellergroup_id'] = 0;
				$seller_array['store_id'] = $store_id;
				$seller_array['is_admin'] = 1;
				$state = $seller_model->addSeller($seller_array);
				//改变店铺状态
				$storejoinin_model->editStorejoinin($param, array('member_id' => $joinin_detail['member_id']));
			}else{
				throw new \think\Exception('店铺新增失败', 10006);
			}

			if ($state) {
				// 添加相册默认
				$album_model = model('album');
				$album_arr = array();
				$album_arr['aclass_name'] = '默认相册';
				$album_arr['store_id'] = $store_id;
				$album_arr['aclass_des'] = '';
				$album_arr['aclass_sort'] = '255';
				$album_arr['aclass_cover'] = '';
				$album_arr['aclass_uploadtime'] = TIMESTAMP;
				$album_arr['aclass_isdefault'] = '1';
				$album_model->addAlbumclass($album_arr);

				//插入店铺扩展表
				$this->addStoreextend(array('store_id' => $store_id));

				//插入店铺绑定分类表
				$store_bind_class_array = array();
				$store_bind_class = unserialize($joinin_detail['store_class_ids']);
				$store_bind_commis_rates = explode(',', $joinin_detail['store_class_commis_rates']);
				for ($i = 0, $length = count($store_bind_class); $i < $length; $i++) {
					@list($class1, $class2, $class3) = explode(',', $store_bind_class[$i]);
					$store_bind_class_array[] = array(
						'store_id' => $store_id,
						'commis_rate' => $store_bind_commis_rates[$i],
						'class_1' => intval($class1),
						'class_2' => intval($class2),
						'class_3' => intval($class3),
						'storebindclass_state' => 1
					);
				}
				$storebindclass_model = model('storebindclass');
				$storebindclass_model->addStorebindclassAll($store_bind_class_array);
			} else {
				throw new \think\Exception('店铺新增失败', 10006);
			}
			return true;
	}

}
