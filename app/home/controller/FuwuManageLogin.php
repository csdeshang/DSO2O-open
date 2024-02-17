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
class  FuwuManageLogin extends BaseFuwu {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/fuwu_manage_login.lang.php');
    }
    
    
    function login() {
        if (!request()->isPost()) {
            return View::fetch($this->template_dir.'login');
        } else {

            $o2o_fuwu_account_model = model('o2o_fuwu_account');
            $o2o_fuwu_account_info = $o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_name' => input('post.o2o_fuwu_account_name'),'o2o_fuwu_account_password'=>md5(input('post.o2o_fuwu_account_password'))));
            if ($o2o_fuwu_account_info) {
                session('o2o_fuwu_organization_id',$o2o_fuwu_account_info['o2o_fuwu_organization_id']);
                session('o2o_fuwu_account_id',$o2o_fuwu_account_info['o2o_fuwu_account_id']);
                session('o2o_fuwu_account_name',$o2o_fuwu_account_info['o2o_fuwu_account_name']);
                $this->redirect('home/FuwuManageGoods/index');
            } else {
                $this->error('没有该账号');
            }
        }
    }

    function logout() {
        session(null);
        $this->redirect('home/FuwuManageLogin/login');
    }

}

?>
