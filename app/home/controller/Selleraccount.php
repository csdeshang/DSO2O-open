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
class  Selleraccount extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/selleraccount.lang.php');
    }
    

    public function account_list() {
        $seller_model = model('seller');
        $condition = array();
        $condition[] = array('seller.store_id','=',session('store_id'));
        $condition[] = array('seller.sellergroup_id','>',0);
        
        $seller_list = $seller_model->getSellerList($condition);
        View::assign('seller_list', $seller_list);

        $sellergroup_model = model('sellergroup');
        $seller_group_list = $sellergroup_model->getSellergroupList(array('store_id' => session('store_id')));
        $seller_group_array = array_under_reset($seller_group_list, 'sellergroup_id');
        View::assign('seller_group_array', $seller_group_array);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('selleraccount');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('account_list');
        return View::fetch($this->template_dir.'account_list');
    }

    public function account_add() {
        if (!request()->isPost()) {
            $sellergroup_model = model('sellergroup');
            $seller_group_list = $sellergroup_model->getSellergroupList(array('store_id' => session('store_id')));
            if (empty($seller_group_list)) {
                $this->error(lang('please_set_account_group_first'), url('Selleraccountgroup/group_add'));
            }
            View::assign('seller_group_list', $seller_group_list);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('selleraccount');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('account_add');
            return View::fetch($this->template_dir . 'account_add');
        } else {
            $member_name = input('post.member_name');
            $password = input('post.password');
            $member_info = $this->_check_seller_member($member_name, $password);
            if (!$member_info) {
                ds_json_encode(10001,lang('user_authentication_failed'));
            }

            $seller_name = $member_name;

            $group_id = intval(input('post.group_id'));

            $seller_info = array(
                'seller_name' => $seller_name,
                'member_id' => $member_info['member_id'],
                'sellergroup_id' => $group_id,
                'store_id' => session('store_id'),
                'is_admin' => 0
            );
            $seller_model = model('seller');
            $result = $seller_model->addSeller($seller_info);

            if ($result) {
                $this->recordSellerlog(lang('add_account_successfully') . $result);
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                $this->recordSellerlog(lang('failed_add_account'));
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    public function account_edit() {
        if (!request()->isPost()) {
            $seller_id = intval(input('param.seller_id'));
            if ($seller_id <= 0) {
                $this->error(lang('param_error'));
            }
            $seller_model = model('seller');
            $seller_info = $seller_model->getSellerInfo(array('seller_id' => $seller_id));
            if (empty($seller_info) || intval($seller_info['store_id']) !== intval(session('store_id'))) {
                $this->error(lang('account_not_exist'));
            }
            View::assign('seller_info', $seller_info);

            $sellergroup_model = model('sellergroup');
            $seller_group_list = $sellergroup_model->getSellergroupList(array('store_id' => session('store_id')));
            if (empty($seller_group_list)) {
                $this->error(lang('please_set_account_group_first'), url('Selleraccountgroup/group_add'));
            }
            View::assign('seller_group_list', $seller_group_list);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('selleraccount');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('account_edit');
            return View::fetch($this->template_dir . 'account_edit');
        } else {
            $param = array('sellergroup_id' => intval(input('post.group_id')));
            $condition = array(
                'seller_id' => intval(input('post.seller_id')),
                'store_id' => session('store_id')
            );
            $seller_model = model('seller');
            $result = $seller_model->editSeller($param, $condition);
            if ($result) {
                $this->recordSellerlog(lang('edit_account_successfully') . input('post.seller_id'));
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                $this->recordSellerlog(lang('edit_account_failed') . input('post.seller_id'), 0);
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }


    public function account_del() {
        $seller_id = intval(input('post.seller_id'));
        if($seller_id > 0) {
            $condition = array();
            $condition[] = array('seller_id','=',$seller_id);
            $condition[] = array('store_id','=',session('store_id'));
            $seller_model = model('seller');
            $result = $seller_model->delSeller($condition);
            if($result) {
                $this->recordSellerlog(lang('delete_account_successfully').$seller_id);
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                $this->recordSellerlog(lang('deletion_account_failed').$seller_id);
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        } else {
            ds_json_encode(10001,lang('param_error'));
        }
    }

    public function check_seller_name_exist() {
        $seller_name = input('get.seller_name');
        $result = $this->_is_seller_name_exist($seller_name);
        if($result) {
            echo 'true';
        } else {
            echo 'false';
        }
    }

    private function _is_seller_name_exist($seller_name) {
        $condition = array();
        $condition[] = array('seller_name','=',$seller_name);
        $seller_model = model('seller');
        return $seller_model->isSellerExist($condition);
    }

    public function check_seller_member() {
        $member_name = input('get.member_name');
        $password = input('get.password');
        $result = $this->_check_seller_member($member_name, $password);
        if($result) {
            echo 'true';
        } else {
            echo 'false';
        }
    }

    private function _check_seller_member($member_name, $password) {
        $member_info = $this->_check_member_password($member_name, $password);
        if($member_info && !$this->_is_seller_member_exist($member_info['member_id'])) {
            return $member_info;
        } else {
            return false;
        }
    }

    private function _check_member_password($member_name, $password) {
        $condition = array();
        $condition[] = array('member_name','=',$member_name);
        $condition[] = array('member_password','=',md5($password));
        $member_model = model('member');
        $member_info = $member_model->getMemberInfo($condition);
        return $member_info;
    }

    private function _is_seller_member_exist($member_id) {
        $condition = array();
        $condition[] = array('member_id','=',$member_id);
        $seller_model = model('seller');
        return $seller_model->isSellerExist($condition);
    }

    
    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $menu_array[] = array(
            'name' => 'account_list',
            'text' => lang('account_list'),
            'url' => url('Selleraccount/account_list'),
        );

        if (request()->action() === 'account_add') {
            $menu_array[] = array(
                'name' => 'account_add',
                'text' => lang('add_account'),
                'url' => url('Selleraccount/account_add'),
            );
        }
        if (request()->action() === 'group_edit') {
            $menu_array[] = array(
                'name' => 'account_edit',
                'text' => lang('edit_account'),
                'url' => 'javascript:void(0)',
            );
        }
        
        return $menu_array;
    }
    
    
}
