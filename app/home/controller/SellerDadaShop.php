<?php

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
class SellerDadaShop extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_dada_shop.lang.php');
    }

    public function index() {
        include_once root_path() . 'extend/dada/index.php';
        if (!request()->isPost()) {
            //获取地区
            if ($this->store_info['region_id']) {
                $area_model = model('area');
                $area_info = $area_model->getAreaInfo(array(array('area_id', '=', $this->store_info['region_id'])));
                if ($area_info) {
                    $area_info['parent'] = $area_model->getAreaInfo(array(array('area_id', '=', $area_info['area_parent_id'])));
                }
            } else {
                $area_info = false;
            }
            //转换坐标
            if ($this->store_info['store_longitude'] && $this->store_info['store_latitude']) {
                $res = convert_coordinate($this->store_info['store_longitude'], $this->store_info['store_latitude']);
                $res = json_decode($res, true);
                if ($res['status'] == '1') {
                    $temp = explode(',', $res['locations']);
                }
            }
            $dada_info = array(
                'station_name' => $this->store_info['store_name'],
                'city_name' => ($area_info && $area_info['parent']) ? $area_info['parent']['area_name'] : '',
                'area_name' => $area_info ? $area_info['area_name'] : '',
                'station_address' => $this->store_info['store_address'],
                'lng' => isset($temp) ? $temp[0] : 0,
                'lat' => isset($temp) ? $temp[1] : 0,
                'phone' => $this->store_info['store_phone'],
            );
            if ($this->store_info['dada_shop_no']) {
                $body = array(
                    'origin_shop_id' => $this->store_info['dada_shop_no'],
                );
                $res = query_dada('/api/shop/detail', json_encode($body));
                if ($res->status == 'success') {
                    $dada_info = array(
                        'origin_shop_id' => $res->result['origin_shop_id'],
                        'station_name' => $res->result['station_name'],
                        'business' => $res->result['business'],
                        'city_name' => $res->result['city_name'],
                        'area_name' => $res->result['area_name'],
                        'station_address' => $res->result['station_address'],
                        'lng' => $res->result['lng'],
                        'lat' => $res->result['lat'],
                        'contact_name' => $res->result['contact_name'],
                        'phone' => $res->result['phone'],
                        'status' => $res->result['status'],
                    );
                }
            }
            $cache_key = "dada-city-code";
            $result = rcache($cache_key);
            if (empty($result)) {
                $res = query_dada('/api/cityCode/list', '');
                if ($res->status != 'success') {
                    $this->error($res->msg);
                }
                $result = array('city_code' => $res->result);
                wcache($cache_key, $result);
            }
            View::assign('city_code',json_encode($result['city_code'],JSON_UNESCAPED_UNICODE));
            View::assign('dada_info', $dada_info);
            View::assign('store_info', $this->store_info);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_dada_shop');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem();
            return View::fetch($this->template_dir . 'index');
        } else {
            if(!input('param.dada_city_code')){
                ds_json_encode(10001, lang('dada_city_code_not_exist'));
            }
            $store_model = model('store');
            //如果没有门店编码则新增
            if (!input('param.origin_shop_id')) {
                $body = array(array(
                        'station_name' => input('param.station_name'),
                        'business' => input('param.business'),
                        'city_name' => input('param.city_name'),
                        'area_name' => input('param.area_name'),
                        'station_address' => input('param.station_address'),
                        'lng' => input('param.lng'),
                        'lat' => input('param.lat'),
                        'contact_name' => input('param.contact_name'),
                        'phone' => input('param.phone'),
                ));
                $res = query_dada('/api/shop/add', json_encode($body));
                if ($res->status != 'success') {
                    ds_json_encode(10001, $res->msg.lang('dada_station_name_exist'));
                } else {
                    $result = $res->result;
                    if ($result['success'] > 0) {
                        $store_model->editStore(array('dada_shop_no' => $result['successList'][0]['originShopId'],'dada_city_code'=>input('param.dada_city_code'), 'dada_lng_lat' => $body[0]['lng'] . ',' . $body[0]['lat']), array(array('store_id', '=', $this->store_info['store_id'])));
                    } else {
                        ds_json_encode(10001, $result['failedList'][0]['msg']);
                    }
                }
            } else {
                $body = array(
                    'origin_shop_id' => input('param.origin_shop_id'),
                    'station_name' => input('param.station_name'),
                    'business' => input('param.business'),
                    'city_name' => input('param.city_name'),
                    'area_name' => input('param.area_name'),
                    'station_address' => input('param.station_address'),
                    'lng' => input('param.lng'),
                    'lat' => input('param.lat'),
                    'contact_name' => input('param.contact_name'),
                    'phone' => input('param.phone'),
                );
                $res = query_dada('/api/shop/update', json_encode($body));
                if ($res->status != 'success') {
                    ds_json_encode(10001, $res->msg);
                } else {
                    $result = $res->result;
                    $store_model->editStore(array('dada_city_code'=>input('param.dada_city_code'),'dada_lng_lat' => $body['lng'] . ',' . $body['lat']), array(array('store_id', '=', $this->store_info['store_id'])));
                }
            }

            $this->recordSellerlog(lang('ds_edit') . lang('baseseller_dada_shop'));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    protected function getSellerItemList() {
        $menu_array = array(
            1 => array(
                'name' => 'seller_dada_shop', 'text' => lang('baseseller_store_o2o'),
                'url' => url('seller_dada_shop/index')
            ),
        );
        return $menu_array;
    }

}

?>
