<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
class FuwuManageRegister extends BaseFuwu {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/fuwu_manage_register.lang.php');
    }

    function step1() {
        if (!request()->isPost()) {
            $document_info = model('document')->getOneDocumentByCode('o2o_fuwu');
            View::assign('document_info', $document_info);
            return View::fetch($this->template_dir . 'step1');
        } else {
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }
    }

    function step2() {
        if (!request()->isPost()) {
            return View::fetch($this->template_dir . 'step2');
        } else {
            $this->check_verify_code();
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }
    }

    function step3() {
        if (!request()->isPost()) {
            return View::fetch($this->template_dir . 'step3');
        } else {
            $o2o_fuwu_account_model = model('o2o_fuwu_account');
            $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
            $password2 = input('post.password2');

            $o2o_fuwu_account_data = array(
                'o2o_fuwu_account_name' => input('post.user_name'),
                'o2o_fuwu_account_password' => input('post.password'),
                'o2o_fuwu_account_phone' => input('post.phone'),
            );

            $o2o_fuwu_organization_data = array(
                'o2o_fuwu_organization_type' => input('post.account_type'),
            );

            if ($password2 != $o2o_fuwu_account_data['o2o_fuwu_account_password']) {
                ds_json_encode(10001, '密码不一致');
            }

            $this->check_verify_code();


            $o2o_fuwu_account_validate = ds_validate('o2o_fuwu_account');
            if (!$o2o_fuwu_account_validate->scene('o2o_fuwu_account_add')->check($o2o_fuwu_account_data)) {
                ds_json_encode(10001, $o2o_fuwu_account_validate->getError());
            }

            $o2o_fuwu_organization_validate = ds_validate('o2o_fuwu_organization');
            if (!$o2o_fuwu_organization_validate->scene('o2o_fuwu_organization_add')->check($o2o_fuwu_organization_data)) {
                ds_json_encode(10001, $o2o_fuwu_organization_validate->getError());
            }
            $condition = array();
            $condition[] = array('o2o_fuwu_account_name','=',$o2o_fuwu_account_data['o2o_fuwu_account_name']);
            $result = $o2o_fuwu_account_model->getO2oFuwuAccountInfo($condition);
            if ($result) {
                ds_json_encode(10001, '该服务机构账号已经存在了，请您换一个');
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
                ds_json_encode(10001, $e->getMessage());
            }
            Db::commit();
            session('o2o_fuwu_organization_id',$o2o_fuwu_organization_id);
            session('o2o_fuwu_account_id',$o2o_fuwu_account_id);
            session('o2o_fuwu_account_name',$o2o_fuwu_account_data['o2o_fuwu_account_name']);
            
        }
        ds_json_encode(10000, lang('ds_common_op_succ').'！请去完善入驻信息','','',false);
    }

    public function check_verify_code() {
        $verify_code = input('post.verify_code');
        if (!$verify_code) {
            ds_json_encode(10001, lang('param_error'));
        }
        $validate_data = array(
            'verify_code' => $verify_code,
        );
        $verify_code_validate = ds_validate('verify_code');
        if (!$verify_code_validate->scene('verify_code_search')->check($validate_data)) {
            ds_json_encode(10001, $verify_code_validate->getError());
        }
        $verify_code_model = model('verify_code');
        if (!$verify_code_model->getVerifyCodeInfo(array(array('verify_code_type' ,'=', 2), array('verify_code_user_type' ,'=', 4), array('verify_code' ,'=', $verify_code), array('verify_code_add_time','>', TIMESTAMP - VERIFY_CODE_INVALIDE_MINUTE * 60)))) {
            ds_json_encode(14001, '验证码错误');
        }
    }

    public function send_verify_code() {
        $verify_code_type = 2;
        $phone = input('param.phone');
        if (!config('ds_config.sms_register')) {
            ds_json_encode(10001, '平台没开启短信注册');
        }
        if (!$phone) {
            ds_json_encode(10001, lang('param_error'));
        }
        $o2o_fuwu_account_model = model('o2o_fuwu_account');
        $o2o_fuwu_account_info = $o2o_fuwu_account_model->getO2oFuwuAccountInfo(array('o2o_fuwu_account_phone' => $phone), 'o2o_fuwu_account_id');
        if ($o2o_fuwu_account_info) {
            ds_json_encode(10001, '该手机号已被使用');
        }
        //验证发送频率
        $verify_code_model = model('verify_code');
        $result = $verify_code_model->isVerifyCodeFrequant($verify_code_type, 4);
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        }
        $verify_code = $verify_code_model->genVerifyCode($verify_code_type, 4);
        if ($verify_code) {
            $sms_type = 1;
            $mailmt_code = 'register';
            $tpl_info = model('mailtemplates')->getTplInfo(array('mailmt_code' => $mailmt_code));
            try {

                $param = array();
                $param['code'] = $verify_code;
                $message = ds_replace_text($tpl_info['mailmt_content'], $param);
                $smslog_param=array(
                    'ali_template_code'=>$tpl_info['ali_template_code'],
                    'ali_template_param'=>$param,
                    'message'=>$message,
                );
                //发送短信
                $result = model('smslog')->sendSms($phone, $smslog_param, $sms_type, $verify_code, 0, '');

                if (!$result['state']) {
                    throw new \think\Exception($result['message'], 10006);
                }
                $ip = request()->ip();
                $flag = $verify_code_model->addVerifyCode(array(
                    'verify_code_type' => $verify_code_type,
                    'verify_code' => $verify_code,
                    'verify_code_user_type' => 4,
                    'verify_code_add_time' => TIMESTAMP,
                    'verify_code_ip' => $ip,
                ));
                if (!$flag) {
                    throw new \think\Exception('新增验证码记录失败', 10006);
                }
            } catch (\Exception $e) {
                ds_json_encode(10001, $e->getMessage());
            }
        } else {
            ds_json_encode(10001, '请重试');
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

}

?>
