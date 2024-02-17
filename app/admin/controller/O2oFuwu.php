<?php

/**
 * 微信配置
 */

namespace app\admin\controller;

use think\facade\View;
use think\facade\Lang;
use think\Validate;
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
 * 控制器
 */
class O2oFuwu extends AdminControl {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/o2o_fuwu.lang.php');
    }

    public function index() {
        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $condition = array();
        $search_field_value = input('search_field_value');
        $search_field_name = input('search_field_name');
        if ($search_field_value != '') {
            $condition[]=array('o2o_fuwu_organization_name','like','%' . trim($search_field_value) . '%');
        }
        $search_state = input('search_state');
        switch ($search_state) {
            case '1':
                $condition[]=array('o2o_fuwu_organization_state','=','1');
                break;
            case '0':
                $condition[]=array('o2o_fuwu_organization_state','=','0');
                break;
            case '2':
                $condition[]=array('o2o_fuwu_organization_state','=','2');
                break;
            case '3':
                $condition[]=array('o2o_fuwu_organization_state','=','3');
                break;
            default:
                if (input('verify')) {
                    $condition[]=array('o2o_fuwu_organization_state','in',array(2, 3));
                } else {
                    $condition[]=array('o2o_fuwu_organization_state','in',array(0, 1));
                }
                break;
        }
        $filtered = 0;
        if ($condition) {
            $filtered = 1;
        }

        $order = 'o2o_fuwu_organization_add_time desc';
        $o2o_fuwu_list = $o2o_fuwu_organization_model->getO2oFuwuOrganizationList($condition, '*', 10, $order);
        View::assign('o2o_fuwu_list', $o2o_fuwu_list);
        View::assign('show_page', $o2o_fuwu_organization_model->page_info->render());

        View::assign('search_field_name', trim($search_field_name));
        View::assign('search_field_value', trim($search_field_value));

        View::assign('filtered', $filtered); //是否有查询条件

        if (input('verify')) {
            $this->setAdminCurItem('verify');
        } else {
            $this->setAdminCurItem('index');
        }
        return View::fetch();
    }

    public function notice() {
        $id = intval(input('param.id'));
        if (!$id) {
            $this->error(lang('param_error'));
        }
        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $o2o_fuwu_array = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $id));
        if (!$o2o_fuwu_array) {
            $this->error(lang('o2o_fuwu_empty'));
        }
        if (!request()->isPost()) {
            return View::fetch('notice');
        } else {
            $content = input('param.content');
            if (!$content) {
                $this->error('请填写信息');
            }
            $o2o_fuwu_notice_model = model('o2o_fuwu_notice');
            $o2o_fuwu_notice_model->addO2oFuwuNotice(array(
                'o2o_fuwu_account_id' => $o2o_fuwu_array['o2o_fuwu_account_id'],
                'o2o_fuwu_account_name' => $o2o_fuwu_array['o2o_fuwu_account_name'],
                'o2o_fuwu_notice_type' => 0,
                'order_id' => 0,
                'o2o_fuwu_notice_title' => '平台通知',
                'o2o_fuwu_notice_content' => $content,
                'o2o_fuwu_notice_add_time' => TIMESTAMP,
            ));
            dsLayerOpenSuccess('发送成功', url('O2oFuwu/index', ['verify' => in_array($o2o_fuwu_array['o2o_fuwu_organization_state'], array(2, 3))]));
        }
    }

    public function add() {
        if (!request()->isPost()) {
            return View::fetch('add');
        } else {
            $o2o_fuwu_account_model = model('o2o_fuwu_account');
            $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
            $o2o_fuwu_account_data = array(
                'o2o_fuwu_account_phone' => input('post.o2o_fuwu_account_phone'),
                'o2o_fuwu_account_name' => input('post.o2o_fuwu_account_name'),
                'o2o_fuwu_account_password' => input('post.o2o_fuwu_account_password'),
            );
            $o2o_fuwu_account_validate = ds_validate('o2o_fuwu_account');
            if (!$o2o_fuwu_account_validate->scene('o2o_fuwu_account_add')->check($o2o_fuwu_account_data)) {
                $this->error($o2o_fuwu_account_validate->getError());
            }

            $o2o_fuwu_organization_data = array(
                'o2o_fuwu_organization_type' => input('post.o2o_fuwu_organization_type'),
            );

            $o2o_fuwu_organization_validate = ds_validate('o2o_fuwu_organization');
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_add')->check($o2o_fuwu_organization_data)) {
                $this->error($o2o_fuwu_organization_validate->getError());
            }

            $result = $o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_name' => $o2o_fuwu_account_data['o2o_fuwu_account_name']));
            if ($result) {
                $this->error('该服务机构账号已经存在了，请您换一个');
            }
            $result = $o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_phone' => $o2o_fuwu_account_data['o2o_fuwu_account_phone']));
            if ($result) {
                $this->error('该手机号已经存在了，请您换一个');
            }

            $o2o_fuwu_account_data['o2o_fuwu_account_password'] = md5($o2o_fuwu_account_data['o2o_fuwu_account_password']);
            $o2o_fuwu_account_data['o2o_fuwu_account_add_time'] = TIMESTAMP;
            Db::startTrans();
            try {
                $o2o_fuwu_account_id = $o2o_fuwu_account_model->addO2oFuwuAccount($o2o_fuwu_account_data);
                if (!$o2o_fuwu_account_id) {
                    throw new \think\Exception('生成服务机构账号失败', 10006);
                }
                $o2o_fuwu_organization_data['o2o_fuwu_account_id'] = $o2o_fuwu_account_id;
                $o2o_fuwu_organization_data['o2o_fuwu_account_name'] = $o2o_fuwu_account_data['o2o_fuwu_account_name'];
                $o2o_fuwu_organization_data['o2o_fuwu_organization_state'] = 3;
                $o2o_fuwu_organization_data['o2o_fuwu_organization_add_time'] = TIMESTAMP;
                $o2o_fuwu_organization_id = $o2o_fuwu_organization_model->addO2oFuwuOrganization($o2o_fuwu_organization_data);
                if (!$o2o_fuwu_organization_id) {
                    throw new \think\Exception('新增服务机构失败', 10006);
                }
                if (!$o2o_fuwu_account_model->editO2oFuwuAccount(array('o2o_fuwu_organization_id' => $o2o_fuwu_organization_id), array('o2o_fuwu_account_id' => $o2o_fuwu_account_id))) {
                    throw new \think\Exception('绑定服务机构失败', 10006);
                }
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();

            $this->log(lang('ds_add') . '服务机构账号' . "[{$o2o_fuwu_account_data['o2o_fuwu_account_name']}]");
            dsLayerOpenSuccess(lang('ds_common_op_succ'), url('O2oFuwu/edit', ['id' => $o2o_fuwu_organization_id]));
        }
    }

    public function edit() {
        $id = intval(input('param.id'));
        if (!$id) {
            $this->error(lang('param_error'));
        }
        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $o2o_fuwu_array = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $id));
        if (!$o2o_fuwu_array) {
            $this->error(lang('o2o_fuwu_empty'));
        }
        if (!request()->isPost()) {
            $this->get_common_data();
            View::assign('o2o_fuwu_array', $o2o_fuwu_array);
            $o2o_fuwu_upload_model = model('o2o_fuwu_upload');
            $o2o_fuwu_upload_1_list = $o2o_fuwu_upload_model->getO2oFuwuUploadList(array('o2o_fuwu_upload_type' => 1, 'o2o_fuwu_organization_id' => $o2o_fuwu_array['o2o_fuwu_organization_id'],));
            View::assign('o2o_fuwu_upload_1_list', $o2o_fuwu_upload_1_list);

            $o2o_fuwu_upload_2_list = $o2o_fuwu_upload_model->getO2oFuwuUploadList(array('o2o_fuwu_upload_type' => 2, 'o2o_fuwu_organization_id' => $o2o_fuwu_array['o2o_fuwu_organization_id'],));
            View::assign('o2o_fuwu_upload_2_list', $o2o_fuwu_upload_2_list);
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            $this->setAdminCurItem('edit');
            return View::fetch('form');
        } else {

            $data = $this->post_data();


            $o2o_fuwu_organization_validate = ds_validate('o2o_fuwu_organization');
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_edit_base')->check($data)) {
                $this->error($o2o_fuwu_organization_validate->getError());
            }
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_edit_operate')->check($data)) {
                $this->error($o2o_fuwu_organization_validate->getError());
            }
            $data['o2o_fuwu_organization_birthday'] = strtotime($data['o2o_fuwu_organization_birthday']);
            //头像
            if (!empty($_FILES['_pic']['name'])) {
                $res=ds_upload_pic(ATTACH_O2O_FUWU_ORGANIZATION . '/' . $o2o_fuwu_array['o2o_fuwu_organization_id'],'_pic');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                    $data['o2o_fuwu_organization_avatar'] = $file_name;
                }else{
                    $this->error($res['msg']);
                }

            }
            if (isset($data['o2o_fuwu_organization_avatar']) && $o2o_fuwu_array['o2o_fuwu_organization_avatar']) {
                @unlink($upload_file . '/' . $o2o_fuwu_array['o2o_fuwu_organization_avatar']);
            }
            $result = $o2o_fuwu_organization_model->editO2oFuwuOrganization($data, array('o2o_fuwu_organization_id' => $id));
            if ($result) {
                $this->log(lang('ds_edit') . lang('o2o_fuwu_manage') . '[' . $o2o_fuwu_array['o2o_fuwu_organization_name'] . ']', 1);
                $content = input('param.content');
                if ($content) {
                    $o2o_fuwu_notice_model = model('o2o_fuwu_notice');
                    $o2o_fuwu_notice_model->addO2oFuwuNotice(array(
                        'o2o_fuwu_account_id' => $o2o_fuwu_array['o2o_fuwu_account_id'],
                        'o2o_fuwu_account_name' => $o2o_fuwu_array['o2o_fuwu_account_name'],
                        'o2o_fuwu_notice_type' => 0,
                        'o2o_fuwu_notice_title' => '平台通知',
                        'o2o_fuwu_notice_content' => $content,
                        'o2o_fuwu_notice_add_time' => TIMESTAMP,
                    ));
                }
                $this->success(lang('ds_common_save_succ'), url('O2oFuwu/index', ['verify' => in_array($o2o_fuwu_array['o2o_fuwu_organization_state'], array(2, 3))]));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    public function del() {
        $id = intval(input('param.id'));
        if (!$id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $o2o_fuwu_array = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $id));
        if (!$o2o_fuwu_array) {
            ds_json_encode(10001, lang('o2o_fuwu_empty'));
        }
        //金额不为0，不可删除
        if ($o2o_fuwu_array['o2o_fuwu_organization_avaliable_money'] > 0 || $o2o_fuwu_array['o2o_fuwu_organization_freeze_money'] > 0) {
            ds_json_encode(10001, '该服务机构金额不为0，不可删除');
        }
        //有未完成的订单，不可删除
//        $o2o_fuwu_order_model=model('o2o_fuwu_order');
//        if($o2o_fuwu_order_model->getO2oFuwuOrderInfo(array('o2o_fuwu_organization_id'=>$id,array('o2o_fuwu_order_state','not in',array(O2O_FUWU_ORDER_STATE_CANCEL,O2O_FUWU_ORDER_STATE_SUCCESS))),'o2o_fuwu_order_id')){
//            ds_json_encode(10001,'该服务机构有未完成的订单，不可删除');
//        }
        $result = $o2o_fuwu_organization_model->delO2oFuwuOrganization(array('o2o_fuwu_organization_id' => $id), array($o2o_fuwu_array));
        if (!$result) {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        } else {
            $this->log(lang('ds_del') . lang('o2o_fuwu_manage') . '[' . $o2o_fuwu_array['o2o_fuwu_organization_name'] . ']', 1);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
    }

    public function get_common_data() {
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        $class_list = $o2o_fuwu_class_model->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id' => 0));
        View::assign('class_list', $class_list);
    }

    public function post_data() {
        $open = explode(',', input('post.o2o_fuwu_organization_open'));
        if (count($open) < 2) {
            $this->error(lang('param_error'));
        }
        $data = array(
            'o2o_fuwu_organization_if_auth' => input('post.o2o_fuwu_organization_if_auth'),
            'o2o_fuwu_organization_state' => input('post.o2o_fuwu_organization_state'),
            'o2o_fuwu_organization_name' => input('post.o2o_fuwu_organization_name'),
            'o2o_fuwu_organization_phone' => input('post.o2o_fuwu_organization_phone'),
            'o2o_fuwu_organization_birthday' => input('post.o2o_fuwu_organization_birthday'),
            'o2o_fuwu_organization_region_id' => input('post.o2o_fuwu_organization_region_id'),
            'o2o_fuwu_organization_region_name' => input('post.o2o_fuwu_organization_region_name'),
            'o2o_fuwu_organization_city_id' => input('post.o2o_fuwu_organization_city_id'),
//            'o2o_fuwu_organization_city_name' => input('post.organization_city_name'),
            'o2o_fuwu_organization_address' => input('post.o2o_fuwu_organization_address'),
            'o2o_fuwu_organization_lng' => input('post.o2o_fuwu_organization_lng'),
            'o2o_fuwu_organization_lat' => input('post.o2o_fuwu_organization_lat'),
            'o2o_fuwu_organization_open_start' => $open[0],
            'o2o_fuwu_organization_open_end' => $open[1],
            'o2o_fuwu_class_id_1' => input('post.o2o_fuwu_class_id_1'),
//            'o2o_fuwu_class_name_1' => input('post.class_name_1'),
            'o2o_fuwu_class_id_2' => input('post.o2o_fuwu_class_id_2'),
//            'o2o_fuwu_class_name_2' => input('post.class_name_2'),
            'o2o_fuwu_class_id_3' => input('post.o2o_fuwu_class_id_3'),
//            'o2o_fuwu_class_name_3' => input('post.class_name_3'),
            'o2o_fuwu_organization_detail' => input('post.o2o_fuwu_organization_detail'),
        );
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        if ($data['o2o_fuwu_class_id_1']) {
            $o2o_fuwu_class = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $data['o2o_fuwu_class_id_1']));
            if (!$o2o_fuwu_class) {
                $this->error('服务分类不存在');
            }
            $data['o2o_fuwu_class_name_1'] = $o2o_fuwu_class['o2o_fuwu_class_name'];
        }
        if ($data['o2o_fuwu_class_id_2']) {
            $o2o_fuwu_class = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $data['o2o_fuwu_class_id_2']));
            if (!$o2o_fuwu_class) {
                $this->error('服务分类不存在');
            }
            $data['o2o_fuwu_class_name_2'] = $o2o_fuwu_class['o2o_fuwu_class_name'];
        }
        if ($data['o2o_fuwu_class_id_3']) {
            $o2o_fuwu_class = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $data['o2o_fuwu_class_id_3']));
            if (!$o2o_fuwu_class) {
                $this->error('服务分类不存在');
            }
            $data['o2o_fuwu_class_name_3'] = $o2o_fuwu_class['o2o_fuwu_class_name'];
        }
        $temp = explode(' ', $data['o2o_fuwu_organization_region_name']);
        if (isset($temp[1])) {
            $data['o2o_fuwu_organization_city_name'] = $temp[1];
        } else {
            $data['o2o_fuwu_organization_city_name'] = $data['o2o_fuwu_organization_region_name'];
        }
        return $data;
    }

    public function check_o2o_fuwu_account_phone() {
        $o2o_fuwu_account_phone = input('get.o2o_fuwu_account_phone');
        $o2o_fuwu_account_model = model('o2o_fuwu_account');
        echo json_encode($o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_phone' => $o2o_fuwu_account_phone)) ? false : true);
        exit;
    }

    public function check_o2o_fuwu_account_name() {
        $o2o_fuwu_account_name = input('get.o2o_fuwu_account_name');
        $o2o_fuwu_account_model = model('o2o_fuwu_account');
        echo json_encode($o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_name' => $o2o_fuwu_account_name)) ? false : true);
        exit;
    }

    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_list'),
                'url' => url('O2oFuwu/index')
            ),
            array(
                'name' => 'verify',
                'text' => lang('ds_verify'),
                'url' => url('O2oFuwu/index', ['verify' => 1])
            ),
            array(
                'name' => 'add',
                'text' => lang('ds_add'),
                'url' => "javascript:dsLayerOpen('" . url('O2oFuwu/add') . "','添加服务机构')"
            ),
        );
        return $menu_array;
    }

}
