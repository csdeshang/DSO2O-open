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
class  Storegrade extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/storegrade.lang.php');
    }

    public function index() {
        $like_storegrade_name = trim(input('param.like_storegrade_name'));
        $condition[]=array('storegrade_name','like', "%" . $like_storegrade_name . "%");
        $storegrade_list = model('storegrade')->getStoregradeList($condition);
        // 获取分页显示
        View::assign('storegrade_list', $storegrade_list);
        View::assign('like_storegrade_name', $like_storegrade_name);
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    public function add() {
        if (!request()->isPost()) {
            return View::fetch('form');
        } else {
            $data = array(
                'storegrade_name' => input('post.storegrade_name'),
                'storegrade_goods_limit' => input('post.storegrade_goods_limit'),
                'storegrade_album_limit' => input('post.storegrade_album_limit'),
                'storegrade_space_limit' => input('post.storegrade_space_limit'),
                //默认附加超文本编辑
                'storegrade_function' => implode('|', array('editor_multimedia')),
                'storegrade_price' => intval(input('post.storegrade_price')),
                'storegrade_description' => input('post.storegrade_description'),
                'storegrade_sort' => input('post.storegrade_sort'),
            );

            $storegrade_validate = ds_validate('storegrade');
            if (!$storegrade_validate->scene('add')->check($data)){
                $this->error($storegrade_validate->getError());
            }

            //验证等级名称
            if (!$this->checkGradeName(array('storegrade_name' => trim(input('post.storegrade_name'))))) {
                $this->error(lang('now_store_grade_name_is_there'));
            }
            //验证级别是否存在
            if (!$this->checkGradeSort(array('storegrade_sort' => trim(input('post.storegrade_sort'))))) {
                $this->error(lang('add_gradesortexist'));
            }
            $result = model('storegrade')->addStoregrade($data);
            if ($result) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'),url('Storegrade/index'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    public function edit() {
        //注：pathinfo地址参数不能通过get方法获取，查看“获取PARAM变量”
        $storegrade_id = input('param.storegrade_id');
        if (empty($storegrade_id)) {
            $this->error(lang('param_error'));
        }
        if (!request()->isPost()) {
            $storegrade = model('storegrade')->getOneStoregrade($storegrade_id);
            View::assign('storegrade', $storegrade);
            return View::fetch('form');
        } else {

            $data = array(
                'storegrade_name' => input('post.storegrade_name'),
                'storegrade_goods_limit' => input('post.storegrade_goods_limit'),
                'storegrade_album_limit' => input('post.storegrade_album_limit'),
                'storegrade_space_limit' => input('post.storegrade_space_limit'),
                //默认附加超文本编辑
                'storegrade_function' => implode('|', array('editor_multimedia')),
                'storegrade_price' => intval(input('post.storegrade_price')),
                'storegrade_description' => input('post.storegrade_description'),
                'storegrade_sort' => input('post.storegrade_sort'),
            );
            $storegrade_validate = ds_validate('storegrade');
            if (!$storegrade_validate->scene('edit')->check($data)){
                $this->error($storegrade_validate->getError());
            }
            //验证等级名称
            if (!$this->checkGradeName(array('storegrade_name' => trim(input('post.storegrade_name')), 'storegrade_id' => intval(input('param.storegrade_id'))))) {
                $this->error(lang('now_store_grade_name_is_there'));
            }
            //验证级别是否存在
            if (!$this->checkGradeSort(array('storegrade_sort' => trim(input('post.storegrade_sort')), 'storegrade_id' => intval(input('param.storegrade_id'))))) {
                $this->error(lang('add_gradesortexist'));
            }
            $result = model('storegrade')->editStoregrade($storegrade_id,$data);
            if ($result>=0) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'),url('Storegrade/index'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    public function drop() {
        //注：pathinfo地址参数不能通过get方法获取，查看“获取PARAM变量”
        $storegrade_id = intval(input('param.storegrade_id'));
        if ($storegrade_id<=0) {
            ds_json_encode(10001, lang('param_error'));
        }
        if ($storegrade_id == '1') {
            ds_json_encode(10001, lang('default_store_grade_no_del'));
        }
        //判断该等级下是否存在店铺，存在的话不能删除
        if (!$this->isable_delStoregrade($storegrade_id)) {
            $this->error(lang('del_gradehavestore'), url('Storegrade/index'));
        }
        $result = model('storegrade')->delStoregrade($storegrade_id);
        if ($result) {
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 查询店铺等级名称是否存在
     */
    private function checkGradeName($param) {
        $storegrade_model = model('storegrade');
        $condition[]=array('storegrade_name','=',$param['storegrade_name']);

        if (isset($param['storegrade_id'])) {
            $storegrade_id = intval($param['storegrade_id']);
            $condition[]=array('storegrade_id','<>', $storegrade_id);
        }
        $list = $storegrade_model->getStoregradeList($condition);
        if (empty($list)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询店铺等级是否存在
     */
    private function checkGradeSort($param) {
        $storegrade_model = model('storegrade');
        $condition = array();
        $condition[]=array('storegrade_sort','=',$param['storegrade_sort']);
        if (isset($param['storegrade_id'])) {
            $storegrade_id = intval($param['storegrade_id']);
            $condition[]=array('storegrade_id','<>', $storegrade_id);
        }
        $list = array();
        $list = $storegrade_model->getStoregradeList($condition);
        if (is_array($list) && count($list) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断店铺等级是否能删除
     */
    public function isable_delStoregrade($storegrade_id) {
        //判断该等级下是否存在店铺，存在的话不能删除
        $store_model = model('store');
        $store_list = $store_model->getStoreList(array('grade_id' => $storegrade_id));
        if (count($store_list) > 0) {
            return false;
        }
        return true;
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => '管理',
                'url' => url('Storegrade/index')
            ),
        );

        if (request()->action() == 'add' || request()->action() == 'index') {
            $menu_array[] = array(
                'name' => 'add',
                'text' => '新增',
                'url' => "javascript:dsLayerOpen('".url('Storegrade/add')."','新增用户')"
            );
        }
        return $menu_array;
    }

}

?>
