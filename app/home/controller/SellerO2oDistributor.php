<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;
use think\Validate;
use GatewayClient\Gateway;

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
class SellerO2oDistributor extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_o2o_distributor.lang.php');
    }

    public function index() {
        $o2o_distributor_model = model('o2o_distributor');
        $condition = array();
        $search_field_value = input('search_field_value');
        $search_field_name = input('search_field_name');
        if ($search_field_value != '') {
            switch ($search_field_name) {
                case 'o2o_distributor_name':
                    $condition[] = array('o2o_distributor_name','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'o2o_distributor_realname':
                    $condition[] = array('o2o_distributor_realname','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'o2o_distributor_phone':
                    $condition[] = array('o2o_distributor_phone','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'o2o_distributor_email':
                    $condition[] = array('o2o_distributor_email','like', '%' . trim($search_field_value) . '%');
                    break;
            }
        }
        $search_state = input('search_state');
        switch ($search_state) {
            case '1':
                $condition[] = array('o2o_distributor_state','=','1');
                break;
            case '0':
                $condition[] = array('o2o_distributor_state','=','0');
                break;
        }
        $filtered = 0;
        if ($condition) {
            $filtered = 1;
        }

        $condition[] = array('store_id','=',session('store_id'));
        $order='o2o_distributor_addtime desc';

        $o2o_distributor_list = $o2o_distributor_model->getO2oDistributorList($condition, '*', 10, $order);
        View::assign('o2o_distributor_list', $o2o_distributor_list);
        View::assign('show_page', $o2o_distributor_model->page_info->render());
        View::assign('search_field_name', trim($search_field_name));
        View::assign('search_field_value', trim($search_field_value));
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_o2o_distributor');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('o2o_distributor_list');
        return View::fetch($this->template_dir . 'index');
    }

    public function get_distributor_list(){
        $o2o_distributor_model = model('o2o_distributor');
        $order_model = model('order');
        $condition = array();
        $region_id=input('param.region_id');
        $keyword=input('param.keyword');
        if($region_id){
            $area_model=model('area');
            $ids=$area_model->getChildrenIDs($region_id);
            $ids[]=$region_id;
            $condition[]=array('o2o_distributor_region_id','in',$ids);
        }
        if($keyword){
            $condition[]=array('o2o_distributor_name|o2o_distributor_realname|o2o_distributor_phone|o2o_distributor_email','like','%'.$keyword.'%');
        }
        $condition = array_merge($condition, array(
            array('store_id' ,'=', session('store_id')),
            array('o2o_distributor_state','=','1')
        ));
        $order = 'o2o_distributor_addtime desc';
        $o2o_distributor_list = $o2o_distributor_model->getO2oDistributorList($condition, '*', 10, $order);
        if(config('ds_config.instant_message_open')){
            Gateway::$registerAddress = config('ds_config.instant_message_register_url');
        }
        foreach ($o2o_distributor_list as $key => $val) {
            $o2o_distributor_list[$key]['o2o_distributor_avatar'] = get_o2o_distributor_file($val['o2o_distributor_avatar'],'avatar');
            $o2o_distributor_list[$key]['count_wait'] = $order_model->getOrderCount(array(array('o2o_order_deliver_time', '>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id', '=', $val['o2o_distributor_id']), array('order_state', 'in', [ORDER_STATE_DELIVER, ORDER_STATE_SEND])));
            $o2o_distributor_list[$key]['count_complete'] = $order_model->getOrderCount(array(array('o2o_order_deliver_time', '>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id', '=', $val['o2o_distributor_id']), array('order_state', 'in', [ORDER_STATE_SUCCESS])));
            $state=0;
            if(config('ds_config.instant_message_open')){
                if(Gateway::isUidOnline('5:'.$val['o2o_distributor_id'])){
                    $state=1;
                }
            }
            $o2o_distributor_list[$key]['state'] = $state;
        }
        ds_json_encode(10000, '', $o2o_distributor_list);
    }
    
    public function join(){
        session('name');
        $client_id = input('param.client_id');
        if (config('ds_config.instant_message_open')) {
            // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
            try {
                Gateway::$registerAddress = config('ds_config.instant_message_register_url');
                // 加入某个群组（可调用多次加入多个群组）
                Gateway::joinGroup($client_id, 'seller');
            } catch (\Exception $e) {
                ds_json_encode(10001, $e->getMessage());
            }
        }
        ds_json_encode(10000, '');
    }
    
    public function map(){
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_o2o_distributor');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('map');
        return View::fetch($this->template_dir . 'map');
    }
    public function add() {
        if (!request()->isPost()) {
            $this->setSellerCurMenu('seller_o2o_distributor');
            $this->setSellerCurItem('add');
            return View::fetch($this->template_dir . 'form');
        } else {
            $o2o_distributor_model = model('o2o_distributor');
            $data = $this->post_data();


            $o2o_distributor_validate = ds_validate('o2o_distributor');
            if (!$o2o_distributor_validate->scene('o2o_distributor_register')->check($data)) {
                ds_json_encode(10001, $o2o_distributor_validate->getError());
            }
            $condition = array();
            $condition[] = array('o2o_distributor_name','=',$data['o2o_distributor_name']);
            $result = $o2o_distributor_model->getO2oDistributorInfo($condition);
            if ($result) {
                ds_json_encode(10001, lang('o2o_distributor_name_remote'));
            }
            $data['o2o_distributor_password'] = md5($data['o2o_distributor_password']);
            $result = $o2o_distributor_model->addO2oDistributor($data);
            if ($result) {
                $this->recordSellerlog(lang('ds_new') . lang('seller_o2o_distributor') . '[' . $data['o2o_distributor_name'] . ']', 1);
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    public function edit() {
        $id = intval(input('param.id'));
        if (!$id) {
            $this->error(lang('param_error'));
        }
        $o2o_distributor_model = model('o2o_distributor');
        $o2o_distributor_array = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $id, 'store_id' => session('store_id')));
        if (!$o2o_distributor_array) {
            $this->error(lang('o2o_distributor_empty'));
        }
        if (!request()->isPost()) {
            View::assign('o2o_distributor_array', $o2o_distributor_array);
            $this->setSellerCurMenu('seller_o2o_distributor');
            $this->setSellerCurItem('edit');
            return View::fetch($this->template_dir . 'form');
        } else {

            $data = $this->post_data();


            $o2o_distributor_validate = ds_validate('o2o_distributor');
            if (!$o2o_distributor_validate->scene('o2o_distributor_edit')->check($data)) {
                ds_json_encode(10001, $o2o_distributor_validate->getError());
            }
            if (isset($data['o2o_distributor_password'])) {
                $data['o2o_distributor_password'] = md5($data['o2o_distributor_password']);
            }
            $result = $o2o_distributor_model->editO2oDistributor($data, array('o2o_distributor_id' => $id, 'store_id' => session('store_id')));
            if ($result) {
                $this->recordSellerlog(lang('ds_edit') . lang('seller_o2o_distributor') . '[' . $o2o_distributor_array['o2o_distributor_name'] . ']', 1);
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    public function del() {
        $id = intval(input('param.id'));
        if (!$id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $o2o_distributor_model = model('o2o_distributor');
        $o2o_distributor_array = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $id, 'store_id' => session('store_id')));
        if (!$o2o_distributor_array) {
            ds_json_encode(10001, lang('o2o_distributor_empty'));
        }
        //如果有正在配送的订单则不能删除
        $order_model=model('order');
        $o2o_errand_order_model=model('o2o_errand_order');
        if($order_model->getOrderCount(array(array('o2o_distributor_id','=',$id),array('order_state','in',array(ORDER_STATE_DELIVER,ORDER_STATE_SEND)))) || $o2o_errand_order_model->getO2oErrandOrderCount(array(array('o2o_distributor_id','=',$id),array('o2o_errand_order_state','in',array(ORDER_STATE_DELIVER,ORDER_STATE_SEND))))){
            ds_json_encode(10001, '该配送员有未完成的订单');
        }
        $result = $o2o_distributor_model->delO2oDistributorInfo(array('o2o_distributor_id' => $id, 'store_id' => session('store_id')), array($o2o_distributor_array));
        if (!$result) {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        } else {
            $this->recordSellerlog(lang('ds_del') . lang('seller_o2o_distributor') . '[' . $o2o_distributor_array['o2o_distributor_name'] . ']', 1);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
    }

    public function post_data() {
        $data = array(
            'o2o_distributor_realname' => input('post.o2o_distributor_realname'),
            'store_id' => session('store_id'),
            'o2o_distributor_phone' => input('post.o2o_distributor_phone'),
            'o2o_distributor_email' => input('post.o2o_distributor_email'),
            'o2o_distributor_remark' => input('post.o2o_distributor_remark'),
            'o2o_distributor_introduce' => input('post.o2o_distributor_introduce'),
            'o2o_distributor_state' => intval(input('post.o2o_distributor_state')),
            'o2o_distributor_avatar' => input('post.o2o_distributor_avatar_value'),
        );
        if (request()->action() == 'add') {
            $data['o2o_distributor_name'] = input('post.o2o_distributor_name');
            $data['o2o_distributor_addtime'] = TIMESTAMP;
        }
        if (input('post.o2o_distributor_password')) {
            $data['o2o_distributor_password'] = input('post.o2o_distributor_password');
        }

        return $data;
    }

    public function image_upload() {
        $upload_file = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_O2O_DISTRIBUTOR;
        $store_image_name = input('param.type');
        $id = input('param.id');
        $file = input('param.file');
        if (!in_array($store_image_name, array('o2o_distributor_avatar'))) {
            exit;
        }

        if (!empty($_FILES[$store_image_name]['name'])) {
            
                $res=ds_upload_pic(ATTACH_O2O_DISTRIBUTOR,$store_image_name);
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                }else{
                    json_encode(array('error' => $res['msg']));
                    exit;
                }
            
        }
        $o2o_distributor_model = model('o2o_distributor');
        $result = 1;
        if ($id) {
            //删除原图
            $o2o_distributor_array = $o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $id, 'store_id' => session('store_id')));
            if ($o2o_distributor_array['o2o_distributor_avatar']) {
                @unlink($upload_file . DIRECTORY_SEPARATOR . $o2o_distributor_array['o2o_distributor_avatar']);
            }
            $result = $o2o_distributor_model->editO2oDistributor(array($store_image_name => $file_name), array('o2o_distributor_id' => $id));
        }

        if ($result) {
            $data = array();
            $data['file_name'] = $file_name;
            $data['file_path'] = UPLOAD_SITE_URL . '/' . ATTACH_O2O_DISTRIBUTOR . '/' . $file_name;
            /**
             * 整理为json格式
             */
            $output = json_encode($data);
            echo $output;
            exit;
        }
    }

    public function ajax() {
        $o2o_distributor_model = model('o2o_distributor');
        switch (input('param.branch')) {
            /**
             * 品牌名称
             */
            case 'o2o_distributor_name':
                /**
                 * 判断是否有重复
                 */
                $condition = array();
                $condition[] = array('o2o_distributor_name','=',trim(input('param.value')));
                $condition[] = array('o2o_distributor_id','<>',intval(input('param.id')));
                $result = $o2o_distributor_model->getO2oDistributorInfo($condition);
                if (empty($result)) {
                    echo 'true';
                    exit;
                } else {
                    echo 'false';
                    exit;
                }
                break;
        }
    }

    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $menu_array[] = array(
            'name' => 'o2o_distributor_list',
            'text' => lang('seller_o2o_distributor_list'),
            'url' => url('seller_o2o_distributor/index'),
        );
        $menu_array[] = array(
            'name' => 'map',
            'text' => '地图',
            'url' => url('seller_o2o_distributor/map'),
        );

        return $menu_array;
    }

}
