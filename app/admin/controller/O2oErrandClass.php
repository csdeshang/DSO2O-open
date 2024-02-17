<?php

/*
 * 跑腿类别管理
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
class  O2oErrandClass extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/o2o_errand_class.lang.php');
    }

    public function index() {
        $o2o_errand_class_model = model('o2o_errand_class');
        $o2o_errand_class_list	= $o2o_errand_class_model->getO2oErrandClassList(array(),'*', 10);
        View::assign('o2o_errand_class_list', $o2o_errand_class_list);
        View::assign('show_page', $o2o_errand_class_model->page_info->render());
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    public function add() {
        if (!(request()->isPost())) {
            return View::fetch('form');
        } else {
            $data = $this->post_data();
            $o2o_errand_class_validate = ds_validate('o2o_errand_class');
            if (!$o2o_errand_class_validate->scene('o2o_errand_class_replace')->check($data)) {
                $this->error($o2o_errand_class_validate->getError());
            }

            $o2o_errand_class_model= model('o2o_errand_class');
            $result=$o2o_errand_class_model->addO2oErrandClass($data);
            if ($result) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('error'));
            }
        }
    }

    public function edit() {
        $o2o_errand_class_id = input('param.o2o_errand_class_id');
        if (empty($o2o_errand_class_id)) {
            $this->error(lang('param_error'));
        }
        $o2o_errand_class_model= model('o2o_errand_class');
        $o2o_errand_class=$o2o_errand_class_model->getO2oErrandClassInfo(array('o2o_errand_class_id'=>$o2o_errand_class_id));
        if(!$o2o_errand_class){
            $this->error(lang('o2o_errand_class_empty'));
        }
        if (!request()->isPost()) {
            
            View::assign('o2o_errand_class', $o2o_errand_class);

            return View::fetch('form');
        } else {
            $data = $this->post_data();
            $o2o_errand_class_validate = ds_validate('o2o_errand_class');
            if (!$o2o_errand_class_validate->scene('o2o_errand_class_replace')->check($data)) {
                $this->error($o2o_errand_class_validate->getError());
            }

            $o2o_errand_class_model= model('o2o_errand_class');
            $condition=array();
            $condition[]=array('o2o_errand_class_id','=',$o2o_errand_class_id);
            
            if (isset($data['o2o_errand_class_logo']) && $o2o_errand_class['o2o_errand_class_logo']) {
                @unlink(BASE_UPLOAD_PATH . '/' . ATTACH_O2O_ERRAND_CLASS . '/' . $o2o_errand_class['o2o_errand_class_logo']);
            }
            $result=$o2o_errand_class_model->editO2oErrandClass($data, $condition);
            if ($result>=0) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    public function post_data() {
        $data = array(
            'o2o_errand_class_name' => input('post.o2o_errand_class_name'),
            'o2o_errand_class_sort_order' => intval(input('post.o2o_errand_class_sort_order')),
            'o2o_errand_class_recommend' => intval(input('post.o2o_errand_class_recommend')),
            'o2o_errand_class_remark' => input('post.o2o_errand_class_remark'),
            'o2o_errand_class_item' => input('post.o2o_errand_class_item'),
        );
        $data['o2o_errand_class_item']= str_replace('；', ';', $data['o2o_errand_class_item']);
        //图片
        if (!empty($_FILES['_pic']['name'])) {
                $res=ds_upload_pic(ATTACH_O2O_ERRAND_CLASS,'_pic');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                    $data['o2o_errand_class_logo'] = $file_name;
                }else{
                    $this->error($res['msg']);
                }

        }
        return $data;
    }
    public function del() {
        $o2o_errand_class_id = intval(input('param.o2o_errand_class_id'));

        $o2o_errand_class_model = model('o2o_errand_class');
        $result=$o2o_errand_class_model->delO2oErrandClass(array('o2o_errand_class_id' => $o2o_errand_class_id));
        if ($result) {
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_list'),
                'url' => url('O2oErrandClass/index')
            ),
            array(
                'name' => 'add',
                'text' => lang('ds_add'),
                'url' => "javascript:dsLayerOpen('".url('O2oErrandClass/add')."','".lang('ds_add')."')"
            ),
        );
        return $menu_array;
    }
}

?>
