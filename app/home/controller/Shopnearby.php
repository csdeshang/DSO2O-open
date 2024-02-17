<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
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
class  Shopnearby extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/shopnearby.lang.php');
    }

    /*
     * 首页显示
     */

    public function index() {
        $storeclass_list = model('storeclass')->getStoreclassList();
        View::assign('storeclass_list', $storeclass_list);
        $area_mod=model('area');
        $city_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
        $sort_city_list = array();
        foreach ($city_list as $k => $v) {
            if (!isset($sort_city_list[$v['area_region']])) {
                $sort_city_list[$v['area_region']] = array(
                    'region' => $v['area_region'],
                    'child' => array()
                );
            }
            $sort_city_list[$v['area_region']]['child'][] = $v;
        }
        View::assign('city_list', $sort_city_list);
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        return View::fetch($this->template_dir . 'index');
    }

    public function get_Own_Store_List() {
        $store_list = array();
        //查询条件
        $condition = array(array('store_state','=',1));
        if (!empty(input('get.keyword'))) {
            $condition[] = array('store_name','like', '%' . input('get.keyword') . '%');
        }
        $storeclass_id = intval(input('get.storeclass_id'));
        if ($storeclass_id) {
            $condition[]=array('storeclass_id','=', $storeclass_id);
        }
        $lat = input('get.latitude');
        $lng = input('get.longitude');
        if(!is_numeric($lat) || !is_numeric($lng)){
          ds_json_encode(10001, lang('param_error'));
        }
        if ($lat && $lng) {
            $page = intval(input('get.page'));
            $store_list = Db::name('store')->where($condition)->where('(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-store_latitude)/360),2)+COS(PI()*' . $lat . '/180)* COS(store_latitude * PI()/180)*POW(SIN(PI()*(' . $lng . '-store_longitude)/360),2)))) < 100000')->field('store_phone,store_latitude,store_longitude,store_id,is_platform_store,store_name,area_info,store_address,store_logo,store_avatar,store_banner,(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-store_latitude)/360),2)+COS(PI()*' . $lat . '/180)* COS(store_latitude * PI()/180)*POW(SIN(PI()*(' . $lng . '-store_longitude)/360),2)))) as distance')->order('distance asc')->page($page, 30)->select()->toArray();

            foreach ($store_list as $key => $value) {
                $goods_conditions = array();
                $goods_conditions[] = array('goods_verify', '=', 1);
                $goods_conditions[] = array('goods_state', '=', 1);
                $store_list[$key]['store_banner'] && $store_list[$key]['store_banner'] = ds_get_pic( ATTACH_STORE . '/' . $value['store_id'] , $value['store_banner']);
                $store_list[$key]['distance'] = round($value['distance'], 2);
                $store_list[$key]['store_logo'] = get_store_logo($value['store_logo'], 'store_logo');
                $store_list[$key]['store_avatar'] = get_store_logo($value['store_avatar'], 'store_avatar');
                $goods_conditions[] = array('store_id', '=', $value['store_id']);
                $store_list[$key]['goods_list'] = Db::name('goods')->where($goods_conditions)->field('goods_name,goods_id,goods_image,goods_price')->order('goods_addtime desc')->page($page, 4)->select()->toArray();
                if(empty($value['area_info'])){
                    $store_list[$key]['area_info'] = lang('store_doesn_fill_area');
                }
                if(empty($value['store_address'])){
                    $store_list[$key]['store_address'] = lang('store_not_filled_detailed_address');
                }
                
                foreach ($store_list[$key]['goods_list'] as $key2 => $goods) {
                    $store_list[$key]['goods_list'][$key2]['goods_image'] = goods_cthumb($goods['goods_image']);
                }
            }
        }
        if ($store_list) {
            echo json_encode($store_list);
            exit;
        } else {
            echo json_encode(false);
            exit;
        }
    }

}
