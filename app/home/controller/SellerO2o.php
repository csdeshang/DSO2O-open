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
class SellerO2o extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_o2o.lang.php');
    }

    public function index() {
        if (!request()->isPost()) {
            View::assign('o2o_open', config('ds_config.o2o_open'));
            View::assign('store_info', $this->store_info);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_o2o');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('seller_o2o');
            return View::fetch($this->template_dir . 'index');
        } else {
            $store_o2o_open_time = explode(',', input('post.store_o2o_open_time'));
            $data = array(
//                'store_o2o_open' => intval(input('post.store_o2o_open')),
                'store_o2o_receipt' => abs(intval(input('post.store_o2o_receipt'))),
                'store_o2o_receipt_limit' => abs(intval(input('post.store_o2o_receipt_limit'))),
                'store_o2o_complaint_fine' => abs(floatval(input('post.store_o2o_complaint_fine'))),
                'store_o2o_distribute_type' => (((!$this->store_info['is_platform_store'] && $this->store_info['store_o2o_support']) || $this->store_info['is_platform_store']) && config('ds_config.o2o_open')) ? intval(input('post.store_o2o_distribute_type')) : 1,
                'store_o2o_open_start' => $store_o2o_open_time[0],
                'store_o2o_open_end' => $store_o2o_open_time[1],
                'store_o2o_auto_receipt' => intval(input('post.store_o2o_auto_receipt')),
                'store_o2o_auto_deliver' => intval(input('post.store_o2o_auto_deliver')),
                'store_o2o_min_cost' => abs(intval(input('post.store_o2o_min_cost'))),
                'store_o2o_reject_time' => abs(intval(input('post.store_o2o_reject_time'))),
            );
            $store_model = model('store');
            $store_model->editStore($data, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('edit_seller_o2o'));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    public function distance_price() {
        $store_model = model('store');
        if (!request()->isPost()) {
            $o2o_distance_price = $this->store_info['store_o2o_distance_price'];
            if (!$o2o_distance_price) {
                $o2o_distance_price = config('ds_config.o2o_distance_price');
                $store_model->editStore(array('store_o2o_distance_price' => $o2o_distance_price), array('store_id' => $this->store_info['store_id']));
            }
            $o2o_distance_price = unserialize($o2o_distance_price);
            View::assign('list_config', $o2o_distance_price);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_o2o');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('distance_price');
            return View::fetch($this->template_dir . 'distance_price');
        } else {
            $update_array = array();
            $update_array['store_o2o_distance_price'] = $this->formatO2oDistancePrice();
            $store_model->editStore($update_array, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('ds_edit') . lang('o2o_distance_price'), 1);
            $this->success(lang('ds_common_save_succ'), 'SellerO2o/distance_price');
        }
    }

    public function weight_price() {
        $store_model = model('store');
        if (!request()->isPost()) {
            $o2o_weight_price = $this->store_info['store_o2o_weight_price'];
            if (!$o2o_weight_price) {
                $o2o_weight_price = config('ds_config.o2o_weight_price');
                $store_model->editStore(array('store_o2o_weight_price' => $o2o_weight_price), array('store_id' => $this->store_info['store_id']));
            }
            $o2o_weight_price = unserialize($o2o_weight_price);
            View::assign('list_config', $o2o_weight_price);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_o2o');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('weight_price');
            return View::fetch($this->template_dir . 'weight_price');
        } else {
            $update_array = array();
            $update_array['store_o2o_weight_price'] = $this->formatO2oWeightPrice();
            $store_model->editStore($update_array, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('ds_edit') . lang('o2o_weight_price'), 1);
            $this->success(lang('ds_common_save_succ'), 'SellerO2o/weight_price');
        }
    }

    public function time_price() {
        $store_model = model('store');
        if (!request()->isPost()) {
            $o2o_time_price = $this->store_info['store_o2o_time_price'];
            if (!$o2o_time_price) {
                $o2o_time_price = config('ds_config.o2o_time_price');
                $store_model->editStore(array('store_o2o_time_price' => $o2o_time_price), array('store_id' => $this->store_info['store_id']));
            }
            $o2o_time_price = unserialize($o2o_time_price);
            View::assign('list_config', $o2o_time_price);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_o2o');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('time_price');
            return View::fetch($this->template_dir . 'time_price');
        } else {
            $update_array = array();
            $update_array['store_o2o_time_price'] = $this->formatO2oTimePrice();
            $store_model->editStore($update_array, array('store_id' => $this->store_info['store_id']));
            $this->recordSellerlog(lang('ds_edit') . lang('o2o_time_price'), 1);
            $this->success(lang('ds_common_save_succ'), 'SellerO2o/time_price');
        }
    }

    private function formatO2oDistancePrice() {
        $o2o_distance_price = input('param.o2o_distance_price/a');
        if (!$o2o_distance_price) {
            $this->error(lang('o2o_distance_price_require'));
        }
        $o2o_distance_price = array_values($o2o_distance_price);
        $len = count($o2o_distance_price);
        if ($len == 1) {
            //默认起始值
            $j = 0;
            $o2o_distance_price[$j] = $this->getDistanceItem($o2o_distance_price[$j], 0);
        }
        //冒泡排序法，按照从小到大排序
        for ($i = 1; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i; $j++) {
                $o2o_distance_price[$j] = $this->getDistanceItem($o2o_distance_price[$j], ($j > 0 && isset($o2o_distance_price[$j - 1]['end_distance'])) ? $o2o_distance_price[$j - 1]['end_distance'] : 0);
                $o2o_distance_price[$j + 1] = $this->getDistanceItem($o2o_distance_price[$j + 1], $o2o_distance_price[$j]['end_distance']);

                if ($o2o_distance_price[$j]['end_distance'] > $o2o_distance_price[$j + 1]['end_distance']) {
                    $temp = $o2o_distance_price[$j + 1];
                    $o2o_distance_price[$j + 1] = $o2o_distance_price[$j];
                    $o2o_distance_price[$j] = $temp;
                }
//                if ($o2o_distance_price[$j + 1]['start_distance'] < $o2o_distance_price[$j]['end_distance']) {
//                    $this->error(sprintf(lang('o2o_distance_content_error'),$o2o_distance_price[$j]['title'],$o2o_distance_price[$j+1]['title']));
//                }
            }
        }
        return serialize($o2o_distance_price);
    }

    private function getDistanceItem($item, $start_distance) {
        $item['start_distance'] = $start_distance;
        $item['end_distance'] = abs(floatval($item['end_distance']));
        $item['interval_distance'] = abs(floatval($item['interval_distance']));
        $item['price'] = abs(floatval($item['price']));
        if (!$item['if_fixed'] && $item['interval_distance'] == 0) {
            $this->error('非固定费用时距离增量不能为0');
        }
        $item['title'] = $item['start_distance'] . '~' . $item['end_distance'] . lang('o2o_kilometre');
        $item['content'] = $item['if_fixed'] ? ($item['price'] . lang('ds_yuan')) : sprintf(lang('o2o_distance_content'), $item['interval_distance'], $item['price']);
        return $item;
    }

    private function formatO2oWeightPrice() {
        $o2o_weight_price = input('param.o2o_weight_price/a');
        if (!$o2o_weight_price) {
            $this->error(lang('o2o_weight_price_require'));
        }
        $o2o_weight_price = array_values($o2o_weight_price);
        $len = count($o2o_weight_price);
        if ($len == 1) {
            //默认起始值
            $j = 0;
            $o2o_weight_price[$j] = $this->getWeightItem($o2o_weight_price[$j], 0);
        }
        //冒泡排序法，按照从小到大排序
        for ($i = 1; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i; $j++) {
                $o2o_weight_price[$j] = $this->getWeightItem($o2o_weight_price[$j], ($j > 0 && isset($o2o_weight_price[$j - 1]['end_weight'])) ? $o2o_weight_price[$j - 1]['end_weight'] : 0);
                $o2o_weight_price[$j + 1] = $this->getWeightItem($o2o_weight_price[$j + 1], $o2o_weight_price[$j]['end_weight']);

                if ($o2o_weight_price[$j]['start_weight'] > $o2o_weight_price[$j + 1]['start_weight']) {
                    $temp = $o2o_weight_price[$j + 1];
                    $o2o_weight_price[$j + 1] = $o2o_weight_price[$j];
                    $o2o_weight_price[$j] = $temp;
                }

//                if ($o2o_weight_price[$j + 1]['start_weight'] < $o2o_weight_price[$j]['end_weight']) {
//                    $this->error(sprintf(lang('o2o_weight_content_error'),$o2o_weight_price[$j]['title'],$o2o_weight_price[$j+1]['title']));
//                }
            }
        }
        return serialize($o2o_weight_price);
    }

    private function getWeightItem($item, $start_weight) {
        $item['start_weight'] = $start_weight;
        $item['end_weight'] = abs(floatval($item['end_weight']));
        $item['interval_weight'] = abs(floatval($item['interval_weight']));
        $item['price'] = abs(floatval($item['price']));
        if (!$item['if_fixed'] && $item['interval_weight'] == 0) {
            $this->error('非固定费用时重量增量不能为0');
        }
        $item['title'] = $item['start_weight'] . '~' . $item['end_weight'] . lang('o2o_kilogram');
        $item['content'] = $item['if_fixed'] ? ($item['price'] . lang('ds_yuan')) : sprintf(lang('o2o_weight_content'), $item['interval_weight'], $item['price']);
        return $item;
    }

    private function formatO2oTimePrice() {
        $o2o_time_price = input('param.o2o_time_price/a');
        if (!$o2o_time_price) {
            $this->error(lang('o2o_time_price_require'));
        }
        $o2o_time_price = array_values($o2o_time_price);
        $len = count($o2o_time_price);
        if ($len == 1) {
            //默认起始值
            $j = 0;
            $o2o_time_price[$j] = $this->getPriceItem($o2o_time_price[$j]);
        }
        //冒泡排序法，按照从小到大排序
        for ($i = 1; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i; $j++) {

                $o2o_time_price[$j] = $this->getPriceItem($o2o_time_price[$j]);
                $o2o_time_price[$j + 1] = $this->getPriceItem($o2o_time_price[$j + 1]);

                if ($o2o_time_price[$j]['start_time'] > $o2o_time_price[$j + 1]['start_time']) {
                    $temp = $o2o_time_price[$j + 1];
                    $o2o_time_price[$j + 1] = $o2o_time_price[$j];
                    $o2o_time_price[$j] = $temp;
                }
                if ($o2o_time_price[$j + 1]['start_time'] < $o2o_time_price[$j]['end_time']) {
                    $this->error(sprintf(lang('o2o_time_content_error'), $o2o_time_price[$j]['title'], $o2o_time_price[$j + 1]['title']));
                }
            }
        }
        return serialize($o2o_time_price);
    }

    private function getPriceItem($item) {
        if (isset($item['time'])) {
            $temp = explode(',', $item['time']);
            unset($item['time']);
            $item['start_time'] = $temp[0];
            $item['end_time'] = $temp[1];
        }
        $item['price'] = abs(floatval($item['price']));
        $temp = $item['start_time'];
        if ($temp > 1440) {
            $this->error(lang('o2o_time_start_error'));
        }
        $start_time = (($temp < 600) ? '0' : '') . ($temp / 60) . ':00';
        $temp = $item['end_time'];
        $end_time = (($temp < 600) ? '0' : (($temp >= 1440) ? '次日0' : '')) . (($temp / 60) % 24) . ':00';
        $item['title'] = $start_time . '~' . $end_time;
        $item['content'] = $item['price'] . lang('ds_yuan');
        return $item;
    }

    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'seller_o2o', 'text' => lang('baseseller_store_o2o'),
                'url' => url('seller_o2o/index')
            ),
            array(
                'name' => 'distance_price', 'text' => lang('o2o_distance_price'),
                'url' => url('seller_o2o/distance_price')
            ),
            array(
                'name' => 'weight_price', 'text' => lang('o2o_weight_price'),
                'url' => url('seller_o2o/weight_price')
            ),
            array(
                'name' => 'time_price', 'text' => lang('o2o_time_price'),
                'url' => url('seller_o2o/time_price')
            ),
        );
        return $menu_array;
    }

}

?>
