<?php

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
class O2oFuwuClass extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/o2o_fuwu_class.lang.php');
    }

    /**
     * 分类管理
     */
    public function index() {
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        //父ID
        $parent_id = input('param.o2o_fuwu_class_parent_id') ? intval(input('param.o2o_fuwu_class_parent_id')) : 0;

        //列表
        $tmp_list = $o2o_fuwu_class_model->getTreeClassList(2);
        $class_list = array();
        if (is_array($tmp_list)) {
            foreach ($tmp_list as $k => $v) {
                if ($v['o2o_fuwu_class_parent_id'] == $parent_id) {
                    //判断是否有子类
                    if (isset($tmp_list[$k + 1]['deep']) && $tmp_list[$k + 1]['deep'] > $v['deep']) {
                        $v['have_child'] = 1;
                    }
                    $class_list[] = $v;
                }
            }
        }

        if (input('param.ajax') == '1') {
            $output = json_encode($class_list);
            echo $output;
            exit;
        } else {
            View::assign('class_list', $class_list);
            $this->setAdminCurItem('index');
            return View::fetch('index');
        }
    }

    /**
     * 商品分类添加
     */
    public function add() {
        if (!(request()->isPost())) {
            $this->get_common_data();
            return View::fetch('form');
        } else {
            $data = $this->post_data();
            $o2o_fuwu_class_model = model('o2o_fuwu_class');
            $result = $o2o_fuwu_class_model->addO2oFuwuClass($data);
            if ($result) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('error'));
            }
        }
    }

    /**
     * 编辑
     */
    public function edit() {
        $o2o_fuwu_class_id = input('param.o2o_fuwu_class_id');
        if (empty($o2o_fuwu_class_id)) {
            $this->error(lang('param_error'));
        }
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        $o2o_fuwu_class = $o2o_fuwu_class_model->getO2oFuwuClassInfo(array('o2o_fuwu_class_id' => $o2o_fuwu_class_id));
        if (!$o2o_fuwu_class) {
            $this->error(lang('o2o_fuwu_class_empty'));
        }
        if (!request()->isPost()) {
            $this->get_common_data();
            View::assign('o2o_fuwu_class', $o2o_fuwu_class);
            return View::fetch('form');
        } else {
            $data = $this->post_data();
            if ($data['o2o_fuwu_class_parent_id'] == $o2o_fuwu_class['o2o_fuwu_class_id']) {
                $this->error('父分类不能等于自身');
            }

            $condition=array();
            $condition[]=array('o2o_fuwu_class_id','=',$o2o_fuwu_class_id);


            if (isset($data['o2o_fuwu_class_logo']) && $o2o_fuwu_class['o2o_fuwu_class_logo']) {
                @unlink(BASE_UPLOAD_PATH . '/' . ATTACH_O2O_FUWU_CLASS . '/' . $o2o_fuwu_class['o2o_fuwu_class_logo']);
            }
            $result = $o2o_fuwu_class_model->editO2oFuwuClass($data, $condition);
            if ($result >= 0) {
                if ($data['o2o_fuwu_class_parent_id']) {
                    //将子分类移动到该分类下
                    $o2o_fuwu_class_model->editO2oFuwuClass(array('o2o_fuwu_class_parent_id' => $data['o2o_fuwu_class_parent_id']), array('o2o_fuwu_class_parent_id' => $o2o_fuwu_class['o2o_fuwu_class_id']));
                }
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    public function get_common_data() {
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        $o2o_fuwu_class_list = $o2o_fuwu_class_model->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id' => 0));
        View::assign('o2o_fuwu_class_list', $o2o_fuwu_class_list);
    }

    public function post_data() {
        $data = array(
            'o2o_fuwu_class_name' => input('post.o2o_fuwu_class_name'),
            'o2o_fuwu_class_parent_id' => input('post.o2o_fuwu_class_parent_id'),
            'o2o_fuwu_class_sort_order' => input('post.o2o_fuwu_class_sort_order'),
            'o2o_fuwu_class_remark' => input('post.o2o_fuwu_class_remark'),
        );
        $o2o_fuwu_class_validate = ds_validate('o2o_fuwu_class');
        if (!$o2o_fuwu_class_validate->scene('o2o_fuwu_class_save')->check($data)) {
            $this->error($o2o_fuwu_class_validate->getError());
        }
        //图片
        if (!empty($_FILES['_pic']['name'])) {
                $res=ds_upload_pic(ATTACH_O2O_FUWU_CLASS,'_pic');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                    $data['o2o_fuwu_class_logo'] = $file_name;
                }else{
                    $this->error($res['msg']);
                }
            
        }
        return $data;
    }

    /**
     * 删除分类
     */
    public function del() {
        $o2o_fuwu_class_id = input('param.o2o_fuwu_class_id');
        $o2o_fuwu_class_id_array = ds_delete_param($o2o_fuwu_class_id);
        if ($o2o_fuwu_class_id_array === FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        foreach ($o2o_fuwu_class_id_array as $o2o_fuwu_class_id) {
            $o2o_fuwu_class_model->delO2oFuwuClass(array('o2o_fuwu_class_id' => $o2o_fuwu_class_id));
        }
        ds_json_encode(10000, lang('ds_common_del_succ'));
    }

    /**
     * ajax操作
     */
    public function ajax() {
        $branch = input('param.branch');

        switch ($branch) {
            case 'o2o_fuwu_class_name':
            case 'o2o_fuwu_class_sort_order':
                $o2o_fuwu_class_model = model('o2o_fuwu_class');
                $where = array('o2o_fuwu_class_id' => intval(input('param.id')));
                $update_array = array();
                $update_array[input('param.column')] = input('param.value');
                $o2o_fuwu_class_model->editO2oFuwuClass($update_array, $where);
                echo 'true';
                exit;
                break;
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_manage'),
                'url' => url('O2oFuwuClass/index')
            ),
        );
        if (request()->action() == 'add' || request()->action() == 'index') {
            $menu_array[] = array(
                'name' => 'add',
                'text' => lang('ds_new'),
                'url' => "javascript:dsLayerOpen('" . url('O2oFuwuClass/add') . "','" . lang('ds_add') . "')"
            );
        }
        if (request()->action() == 'edit') {
            $menu_array[] = array(
                'name' => 'edit',
                'text' => lang('ds_edit'),
                'url' => "javascript:dsLayerOpen('" . url('O2oFuwuClass/edit', ['o2o_fuwu_class_id' => input('param.o2o_fuwu_class_id')]) . "','" . lang('ds_edit') . "')"
            );
        }

        return $menu_array;
    }

}

?>
