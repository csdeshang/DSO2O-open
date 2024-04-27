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
class  Sellerplate extends BaseSeller {

     public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerplate.lang.php');
    }
    public function index() {
        $this->plate_list();
    }

    /**
     * 关联版式列表
     */
    public function plate_list() {
        // 版式列表
        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $p_name = trim(input('get.p_name'));
        if ($p_name != '') {
            $condition[]=array('storeplate_name','like', '%' . $p_name . '%');
        }
        $p_position = trim(input('get.p_position'));
        if (in_array($p_position, array('0', '1'))) {
            $condition[]=array('storeplate_position','=',$p_position);
        }
        $store_plate = model('storeplate');
        $plate_list = $store_plate->getStoreplateList($condition, '*', 10);
        View::assign('show_page', $store_plate->page_info->render());
        View::assign('plate_list', $plate_list);
        View::assign('position', array(0 => '底部', 1 => '顶部'));

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerplate');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('plate_list');
        echo View::fetch($this->template_dir . 'plate_list');
        exit;
    }

    /**
     * 关联版式添加
     */
    public function plate_add() {
        if (!request()->isPost()) {
            $plate_info = array(
                'storeplate_name' => '',
                'storeplate_position' => '',
                'storeplate_content' => '',
            );
            View::assign('plate_info', $plate_info);
            // 是否能使用编辑器
                $editor_multimedia = false;
                if ($this->store_grade['storegrade_function'] == 'editor_multimedia') {
                    $editor_multimedia = true;
                }
            View::assign('editor_multimedia', $editor_multimedia);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerplate');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('plate_add');
            return View::fetch($this->template_dir . 'plate_add');
        } else {
            
            $insert = array();
            $insert['storeplate_name'] = input('post.p_name');
            $insert['storeplate_position'] = input('post.p_position');
            $insert['storeplate_content'] = input('post.p_content');
            $insert['store_id'] = session('store_id');

            $sellerplate_validate = ds_validate('sellerplate');
            if (!$sellerplate_validate->scene('plate_add')->check($insert)) {
                ds_json_encode(10001,lang('error') . $sellerplate_validate->getError());
            }

            $result = model('storeplate')->addStoreplate($insert);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 关联版式编辑
     */
    public function plate_edit() {
        
        $storeplate_id = intval(input('param.p_id'));
        if ($storeplate_id <= 0) {
            $this->error(lang('param_error'));
        }
        if (!request()->isPost()) {

            
            $plate_info = model('storeplate')->getStoreplateInfo(array('storeplate_id' => $storeplate_id, 'store_id' => session('store_id')));
            View::assign('plate_info', $plate_info);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerplate');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('plate_edit');
            return View::fetch($this->template_dir .'plate_add');
        } else {

            $update = array();
            $update['storeplate_name'] = input('post.p_name');
            $update['storeplate_position'] = input('post.p_position');
            $update['storeplate_content'] = input('post.p_content');

            //验证数据  BEGIN
            $sellerplate_validate = ds_validate('sellerplate');
            if (!$sellerplate_validate->scene('plate_edit')->check($update)) {
                ds_json_encode(10001,lang('error') . $sellerplate_validate->getError());
            }
            //验证数据  END
            
            $condition = array();
            $condition[] = array('storeplate_id','=',$storeplate_id);
            $condition[] = array('store_id','=',session('store_id'));
            $result = model('storeplate')->editStoreplate($update, $condition);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 删除关联版式
     */
    public function drop_plate() {
        $storeplate_id = input('param.p_id');
        if (!preg_match('/^[\d,]+$/i', $storeplate_id)) {
            ds_json_encode(10001,lang('param_error'));
        }
        $plateid_array = explode(',', $storeplate_id);
        $return = model('storeplate')->delStoreplate(array(array('storeplate_id','in', $plateid_array), array('store_id' ,'=', session('store_id'))));
        if ($return) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $item_list = array(
            array(
                'name' => 'plate_list',
                'text' => '版式列表',
                'url' => url('Sellerplate/plate_list'),
            ),
        );
        if (request()->action() == 'plate_add') {
            $item_list[] = array(
                'name' => 'plate_add',
                'text' => '添加版式',
                'url' => url('Sellerplate/plate_add'),
            );
        }

        if (request()->action() == 'plate_edit') {
            $item_list[] = array(
                'name' => 'plate_add',
                'text' => '添加版式',
                'url' => url('Sellerplate/plate_add'),
            );
            $item_list[] = array(
                'name' => 'plate_edit',
                'text' => '编辑版式',
                'url' => url('Sellerplate/plate_edit'),
            );
        }
        return $item_list;
    }

}

?>
