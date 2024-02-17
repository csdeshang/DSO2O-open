<?php

/**
 * 店铺分类
 */

namespace app\admin\controller;

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
class Storeclass extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/storeclass.lang.php');
    }

    /**
     * 店铺分类
     */
    public function store_class() {
        $storeclass_model = model('storeclass');

        $store_class_list = $storeclass_model->getStoreclassList(array(), 20);
        View::assign('class_list', $store_class_list);
        View::assign('show_page', $storeclass_model->page_info->render());
        $this->setAdminCurItem('store_class');
        return View::fetch('index');
    }

    /**
     * 商品分类添加
     */
    public function store_class_add() {
        $storeclass_model = model('storeclass');

        if (!request()->isPost()) {
            $this->setAdminCurItem('store_class_add');
            return View::fetch('form');
        } else {
            $insert_array = array();
            $insert_array['storeclass_name'] = input('post.storeclass_name');
            $insert_array['storeclass_bail'] = intval(input('post.storeclass_bail'));
            $insert_array['storeclass_sort'] = intval(input('post.storeclass_sort'));
            //新增店铺分类图片
            if (!empty($_FILES['pic']['name'])) {//上传图片
                $res = ds_upload_pic(ATTACH_STORECLASS, 'pic');
                if ($res['code']) {
                    $file_name = $res['data']['file_name'];
                    $insert_array['storeclass_image'] = $file_name;
                } else {
                    $this->error($res['msg']);
                }
            }
            $storeclass_validate = ds_validate('storeclass');
            if (!$storeclass_validate->scene('store_class_add')->check($insert_array)) {
                $this->error($storeclass_validate->getError());
            }

            $result = $storeclass_model->addStoreclass($insert_array);

            if ($result) {

                $this->log(lang('ds_add') . lang('store_class') . '[' . input('post.storeclass_name') . ']', 1);
                dsLayerOpenSuccess(lang('ds_common_save_succ'), url('Storeclass/store_class'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 编辑
     */
    public function store_class_edit() {
        $storeclass_model = model('storeclass');
        $storeclass_id = intval(input('param.storeclass_id'));


        if (!request()->isPost()) {
            $storeclass = $storeclass_model->getStoreclassInfo(array('storeclass_id' => $storeclass_id));
            if (empty($storeclass)) {
                $this->error(lang('illegal_parameter'));
            }

            View::assign('storeclass', $storeclass);
            $this->setAdminCurItem('store_class_edit');
            return View::fetch('form');
        } else {
            $update_array = array();
            $update_array['storeclass_name'] = input('post.storeclass_name');
            $update_array['storeclass_bail'] = intval(input('post.storeclass_bail'));
            $update_array['storeclass_sort'] = intval(input('post.storeclass_sort'));
            if (!empty($_FILES['pic']['name'])) {//上传图片
                if (!empty($_FILES['pic']['name'])) {//上传图片
                    $res = ds_upload_pic(ATTACH_STORECLASS, 'pic');
                    if ($res['code']) {
                        $file_name = $res['data']['file_name'];
                        $update_array['storeclass_image'] = $file_name;
                    } else {
                        $this->error($res['msg']);
                    }
                }
            }
            $storeclass_validate = ds_validate('storeclass');
            if (!$storeclass_validate->scene('store_class_edit')->check($update_array)) {
                $this->error($storeclass_validate->getError());
            }

            $result = $storeclass_model->editStoreclass($update_array, array('storeclass_id' => $storeclass_id));


            if ($result >= 0) {
                $this->log(lang('ds_edit') . lang('store_class') . '[' . input('post.storeclass_name') . ']', 1);
                dsLayerOpenSuccess(lang('ds_common_save_succ'), url('Storeclass/store_class'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 删除分类
     */
    public function store_class_del() {
        $storeclass_model = model('storeclass');
        $storeclass_id = input('param.storeclass_id');
        $storeclass_id_array = ds_delete_param($storeclass_id);
        if ($storeclass_id_array === FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition = array();
        $condition[] = array('storeclass_id', 'in', $storeclass_id_array);

        $result = $storeclass_model->delStoreclass($condition);
        if ($result) {
            $this->log(lang('ds_del') . lang('store_class') . '[ID:' . $storeclass_id . ']', 1);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
    }

    /**
     * ajax操作
     */
    public function ajax() {
        $storeclass_model = model('storeclass');
        $update_array = array();
        $branch = input('param.branch');
        switch ($branch) {
            //分类：验证是否有重复的名称
            case 'store_class_name':
                $condition = array();
                $condition[] = array('storeclass_name', '=', input('get.value'));
                $condition[] = array('storeclass_id', '<>', intval(input('param.id')));
                $class_list = $storeclass_model->getStoreclassList($condition);
                if (empty($class_list)) {
                    $update_array['storeclass_name'] = input('get.value');
                    $update = $storeclass_model->editStoreclass($update_array, array('storeclass_id' => intval(input('param.id'))));
                    $return = 'true';
                } else {
                    $return = 'false';
                }
                break;
            //分类： 排序 显示 设置
            case 'store_class_sort':
                $update_array['storeclass_sort'] = intval(input('get.value'));
                $result = $storeclass_model->editStoreclass($update_array, array('storeclass_id' => intval(input('param.id'))));
                $return = 'true';
                break;
            //分类：添加、修改操作中 检测类别名称是否有重复
            case 'check_class_name':
                $condition[] = array('storeclass_name', '=', input('get.storeclass_name'));
                $class_list = $storeclass_model->getStoreclassList($condition);
                $return = empty($class_list) ? 'true' : 'false';
                break;
        }
        exit($return);
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'store_class',
                'text' => '管理',
                'url' => url('Storeclass/store_class')
            ),
            array(
                'name' => 'store_class_add',
                'text' => '新增店铺分类',
                'url' => "javascript:dsLayerOpen('" . url('Storeclass/store_class_add') . "','新增店铺分类')"
            )
        );
        return $menu_array;
    }

}

?>
