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
class FuwuManageInfo extends BaseFuwu {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/fuwu_manage_info.lang.php');
    }

    public function base() {
        if (!request()->isPost()) {
            View::assign('o2o_fuwu_organization_info', $this->o2o_fuwu_organization_info);
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            /* 设置机构当前菜单 */
            $this->setFuwuCurMenu('fuwu_manage_info_base');
            /* 设置机构当前栏目 */
            $this->setFuwuCurItem('base');
            return View::fetch($this->template_dir . 'base');
        } else {

            $validate_data = array(
                'o2o_fuwu_organization_name' => input('post.organization_name'),
                'o2o_fuwu_organization_phone' => input('post.organization_phone'),
                'o2o_fuwu_organization_birthday' => input('post.organization_birthday'),
                'o2o_fuwu_organization_region_id' => input('post.organization_region_id'),
                'o2o_fuwu_organization_region_name' => input('post.organization_region_name'),
                'o2o_fuwu_organization_city_id' => input('post.organization_city_id'),
//            'o2o_fuwu_organization_city_name' => input('post.organization_city_name'),
                'o2o_fuwu_organization_address' => input('post.organization_address'),
                'o2o_fuwu_organization_lng' => input('post.organization_lng'),
                'o2o_fuwu_organization_lat' => input('post.organization_lat'),
            );
            if ($validate_data['o2o_fuwu_organization_city_id']) {
                $city_info = model('area')->getAreaInfo(array('area_id' => $validate_data['o2o_fuwu_organization_city_id']));
                if (!$city_info) {
                    ds_json_encode(10001, '没有该城市');
                }
                $validate_data['o2o_fuwu_organization_city_name'] = $city_info['area_name'];
            }
            $o2o_fuwu_organization_validate = ds_validate('o2o_fuwu_organization');
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_edit_base')->check($validate_data)) {
                ds_json_encode(10001, $o2o_fuwu_organization_validate->getError());
            }

            $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
            $validate_data['o2o_fuwu_organization_birthday'] = strtotime($validate_data['o2o_fuwu_organization_birthday']);
            $result = $o2o_fuwu_organization_model->editO2oFuwuOrganization($validate_data, array('o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']));
            if ($result) {
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        }
    }

    public function operate() {
        if (!request()->isPost()) {
            $o2o_fuwu_class_model = model('o2o_fuwu_class');
            $o2o_fuwu_class_list = $o2o_fuwu_class_model->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id' => 0));
            View::assign('o2o_fuwu_class_list', $o2o_fuwu_class_list);
            View::assign('o2o_fuwu_organization_info', $this->o2o_fuwu_organization_info);
            /* 设置机构当前菜单 */
            $this->setFuwuCurMenu('fuwu_manage_info_operate');
            /* 设置机构当前栏目 */
            $this->setFuwuCurItem('operate');
            return View::fetch($this->template_dir . 'operate');
        } else {
            $organization_open_time = explode(',', input('post.organization_open_time'));
            $validate_data = array(
                'o2o_fuwu_organization_open_start' => $organization_open_time[0],
                'o2o_fuwu_organization_open_end' => $organization_open_time[1],
                'o2o_fuwu_class_id_1' => input('post.class_id_1'),
//            'o2o_fuwu_class_name_1' => input('post.class_name_1'),
                'o2o_fuwu_class_id_2' => input('post.class_id_2'),
//            'o2o_fuwu_class_name_2' => input('post.class_name_2'),
                'o2o_fuwu_class_id_3' => input('post.class_id_3'),
//            'o2o_fuwu_class_name_3' => input('post.class_name_3'),
                'o2o_fuwu_organization_detail' => input('post.organization_detail'),
                'o2o_fuwu_organization_payment_account' => input('post.organization_payment_account'),
            );
            $o2o_fuwu_class_model = model('o2o_fuwu_class');
            if ($validate_data['o2o_fuwu_class_id_1']) {
                $o2o_fuwu_class_info = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $validate_data['o2o_fuwu_class_id_1']));
                if (!$o2o_fuwu_class_info) {
                    ds_json_encode(10001, '没有该服务分类');
                }
                $validate_data['o2o_fuwu_class_name_1'] = $o2o_fuwu_class_info['o2o_fuwu_class_name'];
            } else {
                $validate_data['o2o_fuwu_class_name_1'] = '';
            }

            if ($validate_data['o2o_fuwu_class_id_2']) {
                $o2o_fuwu_class_info = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $validate_data['o2o_fuwu_class_id_2']));
                if (!$o2o_fuwu_class_info) {
                    ds_json_encode(10001, '没有该服务分类');
                }
                $validate_data['o2o_fuwu_class_name_2'] = $o2o_fuwu_class_info['o2o_fuwu_class_name'];
            } else {
                $validate_data['o2o_fuwu_class_name_2'] = '';
            }

            if ($validate_data['o2o_fuwu_class_id_3']) {
                $o2o_fuwu_class_info = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $validate_data['o2o_fuwu_class_id_3']));
                if (!$o2o_fuwu_class_info) {
                    ds_json_encode(10001, '没有该服务分类');
                }
                $validate_data['o2o_fuwu_class_name_3'] = $o2o_fuwu_class_info['o2o_fuwu_class_name'];
            } else {
                $validate_data['o2o_fuwu_class_name_3'] = '';
            }

            $o2o_fuwu_organization_validate = ds_validate('o2o_fuwu_organization');
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_edit_operate')->check($validate_data)) {
                ds_json_encode(10001, $o2o_fuwu_organization_validate->getError());
            }
            $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
            $result = $o2o_fuwu_organization_model->editO2oFuwuOrganization($validate_data, array('o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']));
            if ($result) {
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        }
    }

    public function del_info_file() {
        $o2o_fuwu_upload_id = intval(input('param.id'));
        if ($o2o_fuwu_upload_id < 1) {
            ds_json_encode(10001, lang('param_error'));
        }
        $o2o_fuwu_upload_model = model('o2o_fuwu_upload');
        $result = $o2o_fuwu_upload_model->delO2oFuwuUpload(array(
            'o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],
            'o2o_fuwu_upload_id' => $o2o_fuwu_upload_id,
        ));
        if ($result) {
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    public function upload_info_file() {
        $item_id = intval(input('param.item_id'));
        $type = input('param.type');
        switch ($type) {
            case 'avatar':
                $key = 'o2o_fuwu_organization_avatar';
                break;
            case 'quality':
                $key = O2O_FUWU_UPLOAD_QUALIFY;
                break;
            case 'scene':
                $key = O2O_FUWU_UPLOAD_SCENE;
                break;
            case 'goods_body':
                $key = O2O_FUWU_UPLOAD_GOODS_BODY;
                break;
            case 'goods_image':
                $key = O2O_FUWU_UPLOAD_GOODS_IMAGE;
                break;
            default:
                ds_json_encode(10001, '类型错误');
        }
        //头像
        if (!empty($_FILES['file']['name'])) {
                $res=ds_upload_pic(ATTACH_O2O_FUWU_ORGANIZATION . '/' . $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],'file');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                    $file_url = $file_name;
                if ($type == 'avatar') {
                    if ($this->o2o_fuwu_organization_info[$key]) {
                        @unlink($upload_file . DIRECTORY_SEPARATOR . $this->o2o_fuwu_organization_info[$key]);
                    }
                    $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
                    $result = $o2o_fuwu_organization_model->editO2oFuwuOrganization(array($key => $file_url), array('o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']));
                } else {
                    $o2o_fuwu_upload_model = model('o2o_fuwu_upload');
                    if (in_array($key, array(O2O_FUWU_UPLOAD_GOODS_BODY, O2O_FUWU_UPLOAD_GOODS_IMAGE))) {
                        $path = input('param.path');
                        if ($path) {//删除游离图片
                            if (!$item_id) {
                                $o2o_fuwu_upload_model->delO2oFuwuUpload(array('o2o_fuwu_upload_url' => $path));
                            } else {
                                $path_list = $o2o_fuwu_upload_model->getO2oFuwuUploadList(array(array('o2o_fuwu_upload_type', 'in', array(O2O_FUWU_UPLOAD_GOODS_BODY, O2O_FUWU_UPLOAD_GOODS_IMAGE)), array('o2o_fuwu_upload_item_id', '=', $item_id)), 'o2o_fuwu_upload_url');
                                if (!$path_list || !in_array($path, $path_list)) {
                                    $o2o_fuwu_upload_model->delO2oFuwuUpload(array('o2o_fuwu_upload_url' => $path));
                                }
                            }
                        }
                    }
                    $result = $o2o_fuwu_upload_model->addO2oFuwuUpload(array(
                        'o2o_fuwu_upload_url' => $file_url,
                        'o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],
                        'o2o_fuwu_upload_type' => $key,
                        'o2o_fuwu_upload_item_id' => $item_id,
                    ));
                }
                if ($result) {
                    ds_json_encode(10000, '', array('id' => $result, 'url' => get_o2o_fuwu_file($this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'], $file_url, $type), 'path' => $file_url));
                } else {
                    ds_json_encode(10001, lang('ds_common_save_fail'));
                }
                }else{
                    ds_json_encode(10001, $res['msg']);
                }
            

        } else {
            ds_json_encode(10001, lang('o2o_fuwu_info_file_empty'));
        }
    }

    /**
     *    栏目菜单
     */
    function getFuwuItemList() {
        if (request()->action == 'base') {
            $item_list = array(
                array(
                    'name' => 'base',
                    'text' => '基本信息',
                    'url' => url('FuwuManageInfo/base'),
                ),
            );
        } else {
            $item_list = array(
                array(
                    'name' => 'operate',
                    'text' => '经营信息',
                    'url' => url('FuwuManageInfo/operate'),
                ),
            );
        }


        return $item_list;
    }

}

?>
