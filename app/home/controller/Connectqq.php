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
class  Connectqq extends BaseMall {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/connectqq.lang.php');
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/login-register.lang.php');
        /**
         * 判断qq互联功能是否开启
         */
        if (config('ds_config.qq_isuse') != 1) {
            $this->error(lang('home_qqconnect_unavailable')); //'系统未开启QQ互联功能'
        }
        /**
         * 初始化测试数据
         */
        if (!session('openid')) {
            $this->error(lang('home_qqconnect_error')); //'系统错误'
        }
        View::assign('hidden_nctoolbar', 1);
    }

    /**
     * 首页
     */
    public function index() {
        /**
         * 检查登录状态
         */
        if (session('is_login') == '1') {
            //qq绑定
            $this->bindqq();
        } else {
            $this->autologin();
            $this->register();
        }
    }

    private function checkWapQQlogin() {
        if ((session('m'))) {
            return true;
        }
        return false;
    }

    /**
     * qq绑定新用户
     */
    public function register() {
        //实例化模型
        $member_model = model('member');
        //检查登录状态
        $member_model->checkloginMember();
        //获取qq账号信息
        require_once(PLUGINS_PATH . '/login/qq/user/get_user_info.php');
        $qquser_info = get_user_info();
        if (request()->isPost()) {
            $type=input('param.type');
            $user=input('param.user');
            $email=input('param.email');
            $password=input('param.password');
            $password2=input('param.password2');
                $reg_info = array(
                    'member_qqopenid' => session('openid'),
                    'nickname' => isset($qquser_info['nickname'])?$qquser_info['nickname']:'',
                    'headimgurl' => isset($qquser_info['figureurl_qq_2'])?$qquser_info['figureurl_qq_2']:'',
                );
                $data=array(
                    'member_name'=>$user,
                    'member_password'=>$password,
                    'member_email'=>$email,
                    'member_qqopenid' => $reg_info['member_qqopenid'],
                    'member_qqinfo' =>  serialize($reg_info),
                    'member_nickname'=>$reg_info['nickname'],
                );
            if($type==1){//注册


                $login_validate = ds_validate('member');
                if (!$login_validate->scene('register')->check($data)) {
                    $this->error($login_validate->getError());
                }
                $member_info = $member_model->register($data);
                if (!isset($member_info['error'])) {
                    $member_model->createSession($member_info, 'register');
                    $headimgurl = $reg_info['headimgurl'];
                    $avatar = @copy($headimgurl, BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . "/avatar_".$member_info['member_id'].".jpg");
                    if ($avatar) {
                        $member_model->editMember(array('member_id' => $member_info['member_id']), array('member_avatar' => "avatar_".$member_info['member_id'].".jpg"),$member_info['member_id']);
                    }
                } else {
                    $this->error($member_info['error']);
                }
            }else{//绑定
       
                $login_validate = ds_validate('member');
                if (!$login_validate->scene('login')->check($data)) {
                    ds_json_encode(10001, $login_validate->getError());
                }
                $map = array(
                    'member_name' => $data['member_name'],
                    'member_password' => md5($data['member_password']),
                );
                $member_info = $member_model->getMemberInfo($map);
                if ($member_info) {
                    $member_model->editMember(array('member_id' => $member_info['member_id']), array('member_qqopenid' => $data['member_qqopenid'],'member_qqinfo' => $data['member_qqinfo']),$member_info['member_id']);
                }else{
                    $this->error(lang('login_register_bind_fail'));
                }
                $member_model->createSession($member_info, 'register');
            }
            
            
            $this->success(lang('ds_common_save_succ'), HOME_SITE_URL);
        } else {
            
            

            if(config('ds_config.auto_register')){//如果开启了自动注册
                $logic_connect_api = model('connectapi', 'logic');
                //注册会员信息 返回会员信息
                $reg_info = array(
                    'member_qqopenid' => session('openid'),
                    'nickname' => isset($qquser_info['nickname'])?$qquser_info['nickname']:'',
                    'headimgurl' => isset($qquser_info['figureurl_qq_2'])?$qquser_info['figureurl_qq_2']:'',
                );
                $wx_member = $logic_connect_api->wx_register($reg_info, 'qq');
                if ($wx_member) {
                    if (!$wx_member['member_state']) {
                        $this->error(lang('login_index_account_stop'), 'Index/index');
                    }
                    $member_model->createSession($wx_member, 'register');
                    if ($this->checkWapQQlogin()) {
                        @header('location: ' . API_SITE_URL . '/index.php/login/qq');
                        exit;
                    } else {
                        $success_message = lang('login_index_login_success');
                        $this->success($success_message, HOME_SITE_URL);
                    }
                } else {
                    $this->error(lang('login_usersave_regist_fail'), 'login/register'); //"会员注册失败"
                }
            }else{
                View::assign('qquser_info', $qquser_info);
                View::assign('user_passwd', '');
                echo View::fetch($this->template_dir . 'connect_register');
            }

        }
    }

    /**
     * 已有用户绑定QQ
     */
    public function bindqq() {
        $member_model = model('member');
        //验证QQ账号用户是否已经存在
        $array = array();
        $array['member_qqopenid'] = session('openid');
        $member_info = $member_model->getMemberInfo($array);
        if (is_array($member_info) && count($member_info) > 0) {
            session('openid', null);
            $this->error(lang('home_qqconnect_binding_exist'), 'memberconnect/qqbind'); //'该QQ账号已经绑定其他商城账号,请使用其他QQ账号与本账号绑定'
        }
        //获取qq账号信息
        require_once(PLUGINS_PATH . '/login/qq/user/get_user_info.php');
        $qquser_info = get_user_info();
        $edit_state = $member_model->editMember(array('member_id' => session('member_id')), array(
            'member_qqopenid' => session('openid'), 'member_qqinfo' => serialize($qquser_info)
        ),session('member_id'));
        if ($edit_state) {
            $this->success(lang('home_qqconnect_binding_success'), 'memberconnect/qqbind');
        } else {
            $this->error(lang('home_qqconnect_binding_fail'), 'memberconnect/qqbind'); //'绑定QQ失败'
        }
    }

    /**
     * 绑定qq后自动登录
     */
    public function autologin() {
        //查询是否已经绑定该qq,已经绑定则直接跳转
        $member_model = model('member');
        $array = array();
        $array['member_qqopenid'] = session('openid');
        $member_info = $member_model->getMemberInfo($array);
        if (is_array($member_info) && count($member_info) > 0) {
            if (!$member_info['member_state']) {//1为启用 0 为禁用
                $this->error(lang('login_index_account_stop'));
            }
            $member_model->createSession($member_info,'login');

            //是否有卖家账户
            $seller_model = model('seller');
            $seller_info = $seller_model->getSellerInfo(array('member_id' => $member_info['member_id']));
            if ($seller_info) {
                // 更新卖家登陆时间
                $seller_model->editSeller(array('last_logintime' => TIMESTAMP), array('seller_id' => $seller_info['seller_id']));

                $sellergroup_model = model('sellergroup');
                $seller_group_info = $sellergroup_model->getSellergroupInfo(array('sellergroup_id' => $seller_info['sellergroup_id']));

                $store_model = model('store');
                $store_info = $store_model->getStoreInfoByID($seller_info['store_id']);

                $seller_model->createSellerSession($member_info, $store_info, $seller_info, is_array($seller_group_info) ? $seller_group_info : array());
            }
            if ($this->checkWapQQlogin()) {
                @header('location: ' . API_SITE_URL . '/login/type/qq');
                exit;
            } else {
                $success_message = lang('login_index_login_success');
                $this->success($success_message, HOME_SITE_URL);
            }
        }
    }

    /**
     * 更换绑定QQ号码
     */
    public function changeqq() {
        //如果用户已经登录，进入此链接则显示错误
        if (session('is_login') == '1') {
            $this->error(lang('home_qqconnect_error'), 'index/index'); //'系统错误'
        }
        session('openid', null);
        @header('Location:' . HOME_SITE_URL . '/api/toqq');
        exit;
    }

}
