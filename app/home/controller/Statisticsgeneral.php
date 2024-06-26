<?php
/**
 * 店铺概况统计
 */

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
 * 控制器
 */
class  Statisticsgeneral extends BaseSeller
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        include_once root_path(). 'extend/mall/statistics.php';
        Lang::load(base_path().'home/lang/'.config('lang.default_lang').'/statistics.lang.php');
    }

    /**
     * 促销分析
     */
    public function index(){
        $stat_model = model('stat');
        //统计的日期0点
        $stat_time = strtotime(date('Y-m-d',TIMESTAMP)) - 86400;
        /*
         * 近30天
         */
        $stime = $stat_time - (86400*29);//30天前
        $etime = $stat_time + 86400 - 1;//昨天23:59

        $statnew_arr = array();

        //查询订单表下单量、下单金额、下单客户数
        $condition = array();
        $condition[] = array('order_isvalid','=',1);//计入统计的有效订单
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_add_time','between',array($stime,$etime));
        $field = ' COUNT(*) as ordernum, SUM(order_amount) as orderamount, COUNT(DISTINCT buyer_id) as ordermembernum, AVG(order_amount) as avgorderamount ';
        $stat_order = $stat_model->getoneByStatorder($condition, $field);
        $statnew_arr['ordernum'] = ($t = $stat_order['ordernum'])?$t:0;
        $statnew_arr['orderamount'] = ds_price_format(($t = $stat_order['orderamount'])?$t:(0));
        $statnew_arr['ordermembernum'] = ($t = $stat_order['ordermembernum']) > 0?$t:0;
        $statnew_arr['avgorderamount'] = ds_price_format(($t = $stat_order['avgorderamount'])?$t:(0));
        unset($stat_order);

        //下单高峰期
        $condition = array();
        $condition[] = array('order_isvalid','=',1);//计入统计的有效订单
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_add_time','between',array($stime,$etime));
        $field = ' HOUR(FROM_UNIXTIME(order_add_time)) as hourval,COUNT(*) as ordernum ';
        $orderlist = $stat_model->statByStatorder($condition, $field, 0, 0, 'ordernum desc,hourval asc', 'hourval');

        foreach ((array)$orderlist as $k=>$v){
            if ($k < 2){//取前两个订单量高的时间段
                if (!isset($statnew_arr['hothour'])){
                    $statnew_arr['hothour'] = ($v['hourval'].":00~".($v['hourval']+1).":00");
                } else {
                    $statnew_arr['hothour'] .= ("，".($v['hourval'].":00~".($v['hourval']+1).":00"));
                }
            }
        }
        unset($orderlist);

        //查询订单商品表下单商品数
        $condition = array();
        $condition[] = array('order_isvalid','=',1);//计入统计的有效订单
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_add_time','between',array($stime,$etime));
        $field = ' SUM(goods_num) as ordergoodsnum, AVG(goods_pay_price/goods_num) as avggoodsprice ';
        $stat_ordergoods = $stat_model->getoneByStatordergoods($condition, $field);
        $statnew_arr['ordergoodsnum'] = ($t = $stat_ordergoods['ordergoodsnum'])?$t:0;
        $statnew_arr['avggoodsprice'] = ds_price_format(($t = $stat_ordergoods['avggoodsprice'])?$t:(0));
        unset($stat_ordergoods);

        //商品总数、收藏量
        $goods_list = $stat_model->statByGoods(array('store_id'=>session('store_id')),'COUNT(*) as goodsnum, SUM(goods_collect) as gcollectnum');
        $statnew_arr['goodsnum'] = ($t = $goods_list[0]['goodsnum']) > 0?$t:0;
        $statnew_arr['gcollectnum'] = ($t = $goods_list[0]['gcollectnum']) > 0?$t:0;

        //店铺收藏量
        $store_list = $stat_model->getOneByStore(array('store_id'=>session('store_id')),'store_collect');
        $statnew_arr['store_collect'] = ($t = $store_list['store_collect']) > 0?$t:0;

        /*
         * 销售走势
         */
        //构造横轴数据
        for($i=$stime; $i<$etime; $i+=86400){
            //当前数据的时间
            $timetext = date('n',$i).'-'.date('j',$i);
            //统计图数据
            $stat_list[$timetext] = 0;
            //横轴
            $stat_arr['xAxis']['categories'][] = $timetext;
        }
        $condition = array();
        $condition[] = array('order_isvalid','=',1);//计入统计的有效订单
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_add_time','between',array($stime,$etime));
        $field = ' order_add_time,SUM(order_amount) as orderamount,MONTH(FROM_UNIXTIME(order_add_time)) as monthval,DAY(FROM_UNIXTIME(order_add_time)) as dayval ';
        $stat_order = $stat_model->statByStatorder($condition, $field, 0, 0, '','monthval,dayval');
        if($stat_order){
            foreach($stat_order as $k => $v){
                $stat_list[$v['monthval'].'-'.$v['dayval']] = floatval($v['orderamount']);
            }
        }
        $stat_arr['legend']['enabled'] = false;
        $stat_arr['series'][0]['name'] = lang('place_order_amount');
        $stat_arr['series'][0]['data'] = array_values($stat_list);
        //得到统计图数据
        $stat_arr['title'] = lang('sales_trends_last_days');
        $stat_arr['yAxis'] = lang('place_order_amount');
        $stattoday_json = getStatData_LineLabels($stat_arr);
        unset($stat_arr);

        /*
         * 7日内商品销售TOP30
         */
        $stime = $stat_time - 86400*6;//7天前0点
        $etime = $stat_time + 86400 - 1;//今天24点
        $condition = array();
        $condition[] = array('order_isvalid','=',1);//计入统计的有效订单
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('order_add_time','between',array($stime,$etime));
        $field = ' sum(goods_num) as ordergoodsnum, goods_id, goods_name ';
        $goodstop30_arr = $stat_model->statByStatordergoods($condition, $field, 0, 30,'ordergoodsnum desc', 'goods_id');

        /**
         * 7日内同行热卖商品
         */
        $where = array();
        $where[]=array('order_isvalid','=',1);//计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime,$etime));
        $where[] = array('store_id','<>',session('store_id'));
            //查询店铺经营类目
            $store_bindclass = model('storebindclass')->getStorebindclassList(array('store_id'=>session('store_id')));
            $goodsclassid_arr = array();
            foreach ((array)$store_bindclass as $k=>$v){
                if (intval($v['class_3']) > 0){
                    $goodsclassid_arr[3][] = intval($v['class_3']);
                } elseif (intval($v['class_2']) > 0){
                    $goodsclassid_arr[2][] = intval($v['class_2']);
                } elseif (intval($v['class_1']) > 0){
                    $goodsclassid_arr[1][] = intval($v['class_1']);
                }
            }
            //拼接商品分类条件
            if ($goodsclassid_arr) {
                ksort($goodsclassid_arr);
                $gc_parentidwhere_keyarr = array();
                $gc_parentidwhere_arr = array();
                foreach ((array) $goodsclassid_arr as $k => $v) {
                    $gc_parentidwhere_keyarr[] = 'gc_parentid_' . $k;
                    $gc_parentidwhere_arr = array_merge($gc_parentidwhere_arr,$goodsclassid_arr[$k]);
                }
                if (count($gc_parentidwhere_keyarr) == 1) {
                    $where[] = array($gc_parentidwhere_keyarr[0], 'in', $gc_parentidwhere_arr);
                } else {
                    $where[] = array(implode('|', $gc_parentidwhere_keyarr), 'in', $gc_parentidwhere_arr);
                }
            }
        $field = ' sum(goods_num) as ordergoodsnum, goods_id, goods_name ';
        $othergoodstop30_arr = $stat_model->statByStatordergoods($where, $field, 0, 30,'ordergoodsnum desc', 'goods_id');

        View::assign('goodstop30_arr',$goodstop30_arr);
        View::assign('othergoodstop30_arr',$othergoodstop30_arr);
        View::assign('stattoday_json',$stattoday_json);
        View::assign('statnew_arr',$statnew_arr);
        View::assign('stat_time',$stat_time);
        $this->setSellerCurMenu('Statisticsgeneral');
        $this->setSellerCurItem('index');
       return View::fetch($this->template_dir.'index');
    }

    /**
     * 价格区间设置
     */
    public function pricesetting(){
        $storeextend_model = model('storeextend');
        if (request()->isPost()){
            $update_array = array();
            $pricerange_array = input('post.pricerange/a');#获取数组
            if (!empty($pricerange_array)){
                foreach ($pricerange_array as $k=>$v){
                    $pricerange_arr[] = $v;
                }
                $update_array['pricerange'] = serialize($pricerange_arr);
            } else {
                $update_array['pricerange'] = '';
            }
            $result = $storeextend_model->editStoreextend($update_array,array('store_id'=>session('store_id')));
            if ($result){
                $this->success(lang('ds_common_save_succ'),'statisticsgeneral/pricesetting');
            }else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
        $pricerange = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'pricerange');
        $pricerange = $pricerange?unserialize($pricerange):array();
        View::assign('pricerange',$pricerange);
        $this->setSellerCurItem('pricesetting');
        $this->setSellerCurMenu('Statisticsgeneral');
       return View::fetch($this->template_dir.'pricesetting');
    }

    /**
     * 订单价格区间设置
     */
    public function orderprange(){
        $storeextend_model = model('storeextend');
        if (request()->isPost()){
            $update_array = array();
            $pricerange_array = input('post.pricerange/a');#获取数组
            if (!empty($pricerange_array)){
                foreach ($pricerange_array as $k=>$v){
                    $pricerange_arr[] = $v;
                }
                $update_array['orderpricerange'] = serialize($pricerange_arr);
            } else {
                $update_array['orderpricerange'] = '';
            }
            $result = $storeextend_model->editStoreextend($update_array,array('store_id'=>session('store_id')));
            if ($result){
                $this->success(lang('ds_common_save_succ'),'statisticsgeneral/orderprange');
            }else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
        $pricerange = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'orderpricerange');
        $pricerange = $pricerange?unserialize($pricerange):array();
        View::assign('pricerange',$pricerange);
        $this->setSellerCurMenu('Statisticsgeneral');
        $this->setSellerCurItem('orderprange');
       return View::fetch($this->template_dir.'orderpricerange');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('store_profile'),
                'url' => url('Statisticsgeneral/index')
            ),
             array(
                'name' => 'pricesetting', 'text' => lang('commodity_price_range'),
                'url' => url('Statisticsgeneral/pricesetting')
            ),  array(
                'name' => 'orderprange', 'text' => lang('order_amount_range'),
                'url' => url('Statisticsgeneral/orderprange')
            )
        );
        return $menu_array;
    }
}