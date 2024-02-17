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
class  Memberaddress extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/memberaddress.lang.php');
    }

    /*
     * 收货地址列表
     */

    public function index() {
        $o2o_errand_type=intval(input('param.o2o_errand_type'));
        $address_model=model('address');
        $address_list = $address_model->getAddressList(array('member_id'=>session('member_id'),'address_o2o_errand_type'=>$o2o_errand_type));
        if(input('param.ajax')){
            ds_json_encode(10000,'',array('address_list'=>$address_list));
        }
        View::assign('address_list', $address_list);

        /* 设置买家当前菜单 */
        $this->setMemberCurMenu($o2o_errand_type?'member_address2':'member_address');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('my_address');
        return View::fetch($this->template_dir . 'index');
    }

    public function add() {
        $o2o_errand_type=intval(input('param.o2o_errand_type'));
        if (!request()->isPost()) {
            $area_mod=model('area');
            $region_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
            View::assign('region_list', $region_list);
            $address = array(
                'address_realname' => '',
                'area_id' => '',
                'city_id' => '',
                'address_detail' => '',
				'house_number' => '',
                'address_tel_phone' => '',
                'address_mob_phone' => '',
                'address_is_default' => '',
                'area_info' => '',
                'address_longitude' => '',
                'address_latitude' => '',
            );
            View::assign('address', $address);
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu($o2o_errand_type?'member_address2':'member_address');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem('my_address_add');
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            return View::fetch($this->template_dir . 'form');
        } else {
            $address_is_default = input('post.is_default') == 1 ? 1 : 0;
            $data = array(
                'address_o2o_errand_type' => $o2o_errand_type,
                'member_id' => session('member_id'),
                'address_realname' => input('post.true_name'),
                'area_id' => input('post.area_id'),
                'city_id' => input('post.city_id'),
                'address_detail' => input('post.address'),
				'house_number' =>input('post.house_number'),
                'address_longitude' => input('post.longitude'),
                'address_latitude' => input('post.latitude'),
                'address_tel_phone' => input('post.tel_phone'),
                'address_mob_phone' => input('post.mob_phone'),
                'address_is_default' => $address_is_default,
                'area_info' => input('post.area_info'),
            );
            $memberaddress_validate = ds_validate('memberaddress');
            if (!$memberaddress_validate->scene('add')->check($data)) {
                ds_json_encode(10001,$memberaddress_validate->getError());
            }
            
            //当默认地址为1时,把当前用户的地址设置为非默认地址
            if($address_is_default == 1){
                $condition = array();
                $condition[] = array('member_id','=',session('member_id'));
                $condition[] = array('address_o2o_errand_type','=',$o2o_errand_type);
                model('address')->editAddress(array('address_is_default'=>0), $condition);
            }

            $address_model=model('address');
            $result = $address_model->addAddress($data);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'),array('address_id'=>$result));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    public function edit() {
        $o2o_errand_type=intval(input('param.o2o_errand_type'));
        $address_id = intval(input('param.address_id'));
        if (0 >= $address_id) {
            ds_json_encode(10001,lang('param_error'));
        }
        $address_model=model('address');
        $address = $address_model->getAddressInfo(array('member_id' => session('member_id'), 'address_id' => $address_id));
        if (empty($address)) {
            ds_json_encode(10001,lang('address_does_not_exist'));
        }
        if (!request()->isPost()) {
            $area_mod=model('area');
            $region_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
            View::assign('region_list', $region_list);
            View::assign('address', $address);
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu($o2o_errand_type?'member_address2':'member_address');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem('my_address_edit');
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            return View::fetch($this->template_dir . 'form');
        } else {
            $address_is_default = input('post.is_default') == 1 ? 1 : 0;
            $data = array(
                'address_o2o_errand_type' => $o2o_errand_type,
                'address_realname' => input('post.true_name'),
                'area_id' => input('post.area_id'),
                'city_id' => input('post.city_id'),
                'address_detail' => input('post.address'),
				'house_number' =>input('post.house_number'),
                'address_longitude' => input('post.longitude'),
                'address_latitude' => input('post.latitude'),
                'address_tel_phone' => input('post.tel_phone'),
                'address_mob_phone' => input('post.mob_phone'),
                'address_is_default' => $address_is_default,
                'area_info' => input('post.area_info'),
            );
            $memberaddress_validate = ds_validate('memberaddress');
            if (!$memberaddress_validate->scene('edit')->check($data)) {
                ds_json_encode(10001,$memberaddress_validate->getError());
            }
            
            //当默认地址为1时,把当前用户的地址设置为非默认地址
            if($address_is_default == 1){
                $condition = array();
                $condition[] = array('member_id','=',session('member_id'));
                $condition[] = array('address_o2o_errand_type','=',$o2o_errand_type);
                model('address')->editAddress(array('address_is_default'=>0), $condition);
            }
            
            $condition=array();
            $condition[]=array('member_id' ,'=', session('member_id'));
            $condition[]=array('address_id' ,'=', $address_id);
            $result = $address_model->editAddress($data,$condition);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    public function drop() {
        $address_id = intval(input('param.address_id'));
        if (0 >= $address_id) {
            ds_json_encode(10001,lang('empty_error'));
        }
        $address_model=model('address');
        $condition = array();
        $condition[] = array('address_id','=',$address_id);
        $condition[] = array('member_id','=',session('member_id'));
        $result = $address_model->delAddress($condition);
        if ($result) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }
    public function gaode_map(){
        $address_id=intval(input('param.address_id'));
        $address_model=model('address');
        if($address_id){
            $address_info=$address_model->getAddressInfo(array(array('address_id','=',$address_id),array('member_id','=',session('member_id'))));
        }
        if (!request()->isPost()) {
            if(!$address_info){
                $this->error('地址不存在');
            }
            View::assign('address_info', $address_info);
            return View::fetch($this->template_dir . 'gaode_map');
        } else {
            if(!$address_info){
            ds_json_encode(10001,'地址不存在');
        }
            $lng=input('param.lng');
            $lat=input('param.lat');
            if(!$lng || !$lat){
                ds_json_encode(10001,lang('param_error'));
            }
            $result = $address_model->editAddress(array('dada_lng_lat'=>$lng.','.$lat),array(array('address_id','=',$address_info['address_id'])));
            if ($result>=0) {
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_op_fail'));
            }
        }
    }
    /**
     *    栏目菜单
     */
    function getMemberItemList() {
        $o2o_errand_type=intval(input('param.o2o_errand_type'));
        $item_list = array(
            array(
                'name' => 'my_address',
                'text' => lang('my_address'),
                'url' => url('Memberaddress/index',['o2o_errand_type'=>$o2o_errand_type]),
            ),
            array(
                'name' => 'my_address_add',
                'text' => lang('new_address'),
                'url' => url('Memberaddress/add',['o2o_errand_type'=>$o2o_errand_type]),
            ),
        );
        if (request()->action() == 'edit') {
            $item_list[] = array(
                'name' => 'my_address_edit',
                'text' => lang('edit_address'),
                'url' => "javascript:void(0)",
            );
        }
        return $item_list;
    }

}

?>
