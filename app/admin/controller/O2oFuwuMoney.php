<?php

namespace app\admin\controller;
use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
use app\common\model\O2oFuwuMoneyLog;
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
class  O2oFuwuMoney extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/storemoney.lang.php');
    }


    /*
     * 资金明细
     */

    public function index() {
        $condition = array();
        $stime = input('get.stime');
        $etime = input('get.etime');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_date ? strtotime($stime) : null;
        $end_unixtime = $if_end_date ? (strtotime($etime)+86399) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[] = array('o2o_fuwu_money_log_add_time','between', array($start_unixtime, $end_unixtime));
        }
        $mname = input('get.mname');
        if (!empty($mname)) {
            $condition[]=array('o2o_fuwu_organization_name','like','%'.$mname.'%');
        }
        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        $list_log = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogList($condition, 10, '*', 'o2o_fuwu_money_log_id desc');
        View::assign('show_page', $o2o_fuwu_money_log_model->page_info->render());
        View::assign('list_log', $list_log);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /*
     * 提现列表
     */
    public function withdraw_list() {
        $condition = array();
        $condition[]=array('o2o_fuwu_money_log_type','=',O2oFuwuMoneyLog::TYPE_WITHDRAW);
        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[]=array('o2o_fuwu_money_log_state','=',intval($paystate_search));
        }

        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        $withdraw_list = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogList($condition, 10, '*', 'o2o_fuwu_money_log_id desc');
        View::assign('show_page', $o2o_fuwu_money_log_model->page_info->render());
        View::assign('withdraw_list', $withdraw_list);
        
        View::assign('filtered', input('get.') ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('withdraw_list');
        return View::fetch();
    }

    /*
     * 提现设置
     */
    public function withdraw_set(){
        $config_model = model('config');
        if(!request()->isPost()){
            $list_setting = rkcache('config', true);
            View::assign('list_setting',$list_setting);
            $this->setAdminCurItem('withdraw_set');
            return View::fetch();
        }else{
            $update_array=array(
                'store_withdraw_min'=>abs(round(input('post.store_withdraw_min'),2)),
                'store_withdraw_max'=>abs(round(input('post.store_withdraw_max'),2)),
                'store_withdraw_cycle'=>abs(intval(input('post.store_withdraw_cycle'))),
            );
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_update').lang('admin_storemoney_withdraw_set'),1);
                $this->success(lang('ds_common_op_succ'), 'O2oFuwuMoney/withdraw_set');
            }else{
                $this->log(lang('ds_update').lang('admin_storemoney_withdraw_set'),0);
            }
        }
    }

    /**
     * 查看提现信息
     */
    public function withdraw_view() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('param_error'));
        }
        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        $condition = array();
        $condition[]=array('o2o_fuwu_money_log_id','=',$id);
        $info = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogInfo($condition);
        if (!is_array($info) || count($info) < 0) {
            $this->error(lang('admin_storemoney_record_error'));
        }
        if(!request()->isPost()){
            View::assign('info', $info);
            return View::fetch();
        }else{
            if(!input('param.verify_reason')){
                $this->error(lang('ds_none_input').lang('admin_storemoney_remark'));
            }
            $data=array(
                'o2o_fuwu_organization_id'=>$info['o2o_fuwu_organization_id'],
                'o2o_fuwu_organization_name'=>$info['o2o_fuwu_organization_name'],
                'o2o_fuwu_money_log_type'=>O2oFuwuMoneyLog::TYPE_VERIFY,
                'o2o_fuwu_money_log_state'=>O2oFuwuMoneyLog::STATE_VALID,
                'o2o_fuwu_money_log_add_time'=>TIMESTAMP,
            );
            if(input('param.verify_state')==1){//通过
                    $data['o2o_fuwu_organization_freeze_money']=-$info['o2o_fuwu_organization_freeze_money'];
                    $o2o_fuwu_money_log_state=O2oFuwuMoneyLog::STATE_AGREE;
            }else{
                $data['o2o_fuwu_organization_avaliable_money']=$info['o2o_fuwu_organization_freeze_money'];
                    $data['o2o_fuwu_organization_freeze_money']=-$info['o2o_fuwu_organization_freeze_money'];
                    $o2o_fuwu_money_log_state=O2oFuwuMoneyLog::STATE_REJECT;
            }
            $admininfo = $this->getAdminInfo();
            $data['o2o_fuwu_money_log_desc']=lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".((input('param.verify_state')==1)?lang('ds_pass'):lang('ds_refuse')).'服务机构'."【" . $info['o2o_fuwu_organization_name'] . "】".lang('admin_storemoney_log_stage_cash').'：'.input('param.verify_reason');
            try {
                Db::startTrans();
                $o2o_fuwu_money_log_model->changeO2oFuwuMoney($data);
                //修提现状态
                if(!$o2o_fuwu_money_log_model->editO2oFuwuMoneyLog(array('o2o_fuwu_money_log_id'=>$id,'o2o_fuwu_money_log_state'=>O2oFuwuMoneyLog::STATE_WAIT),array('o2o_fuwu_money_log_state'=>$o2o_fuwu_money_log_state))){
                    throw new \think\Exception(lang('admin_storemoney_cash_edit_fail'), 10006);
                }
                Db::commit();
                $this->log($data['o2o_fuwu_money_log_desc'], 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($data['o2o_fuwu_money_log_desc'], 0);
                $this->error($e->getMessage());
            }
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        }
    }

    /*
     * 调节资金
     */

    public function adjust() {
        if (!(request()->isPost())) {
            $o2o_fuwu_organization_id = intval(input('get.o2o_fuwu_organization_id'));
            if($o2o_fuwu_organization_id>0){
                $condition = array();
                $condition[]=array('o2o_fuwu_organization_id','=',$o2o_fuwu_organization_id);
                $store = model('O2oFuwuOrganization')->getO2oFuwuOrganizationInfo($condition);
                if(!empty($store)){
                    View::assign('store_info',$store);
                }
            }
            return View::fetch();
        } else {

            $money = abs(floatval(input('post.amount')));
            if ($money <= 0) {
                $this->error(lang('admin_storemoney_artificial_pricemin_error'));
            }
            //查询店主信息
            $store_mod = model('o2o_fuwu_organization');
            $o2o_fuwu_organization_id = intval(input('post.o2o_fuwu_organization_id'));
            $operatetype = input('post.operatetype');
            $store_info = $store_mod->getO2oFuwuOrganizationInfo(array('o2o_fuwu_organization_id' => $o2o_fuwu_organization_id));

            if (!is_array($store_info) || count($store_info) <= 0) {
                $this->error(lang('admin_storemoney_userrecord_error'), 'O2oFuwuMoney/adjust');
            }
            $o2o_fuwu_organization_avaliable_money = floatval($store_info['o2o_fuwu_organization_avaliable_money']);
            $o2o_fuwu_organization_freeze_money = floatval($store_info['o2o_fuwu_organization_freeze_money']);
            if ($operatetype == 2 && $money > $o2o_fuwu_organization_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortprice_error') . $o2o_fuwu_organization_avaliable_money, 'O2oFuwuMoney/adjust');
            }
            if ($operatetype == 3 && $money > $o2o_fuwu_organization_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $o2o_fuwu_organization_avaliable_money, 'O2oFuwuMoney/adjust');
            }
            if ($operatetype == 4 && $money > $o2o_fuwu_organization_freeze_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $o2o_fuwu_organization_freeze_money, 'O2oFuwuMoney/adjust');
            }
            $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
            #生成对应订单号
            $admininfo = $this->getAdminInfo();
            $data=array(
                'o2o_fuwu_organization_id'=>$store_info['o2o_fuwu_organization_id'],
                'o2o_fuwu_organization_name'=>$store_info['o2o_fuwu_organization_name'],
                'o2o_fuwu_money_log_type'=>O2oFuwuMoneyLog::TYPE_ADMIN,
                'o2o_fuwu_money_log_state'=>O2oFuwuMoneyLog::STATE_VALID,
                'o2o_fuwu_money_log_add_time'=>TIMESTAMP,
            );
            switch ($operatetype) {
                case 1:
                    $data['o2o_fuwu_organization_avaliable_money']=$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').'服务机构'."【" . $store_info['o2o_fuwu_organization_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_add')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 2:
                    $data['o2o_fuwu_organization_avaliable_money']=-$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').'服务机构'."【" . $store_info['o2o_fuwu_organization_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_reduce')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 3:
                    $data['o2o_fuwu_organization_avaliable_money']=-$money;
                    $data['o2o_fuwu_organization_freeze_money']=$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').'服务机构'."【" . $store_info['o2o_fuwu_organization_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_freeze')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 4:
                    $data['o2o_fuwu_organization_avaliable_money']=$money;
                    $data['o2o_fuwu_organization_freeze_money']=-$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').'服务机构'."【" . $store_info['o2o_fuwu_organization_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_unfreeze')."】，".lang('admin_storemoney_price') . $money;
                    break;
                default:
                    $this->error(lang('ds_common_op_fail'), 'O2oFuwuMoney/index');
                    break;
            }
            $data['o2o_fuwu_money_log_desc']=$log_msg;
            try {
                Db::startTrans();
                $o2o_fuwu_money_log_model->changeO2oFuwuMoney($data);
                Db::commit();
                $this->log($log_msg, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'O2oFuwuMoney/index');
            }
        }
    }

    //取得店主信息
    public function checkseller() {
        $name = input('post.name');
        if (!$name) {
            exit(json_encode(array('id' => 0)));
            die;
        }
        $obj_store = model('o2o_fuwu_organization');
        $store_info = $obj_store->getO2oFuwuOrganizationInfo(array('o2o_fuwu_account_name' => $name));
        if (is_array($store_info) && count($store_info) > 0) {
            exit(json_encode(array('id' => $store_info['o2o_fuwu_organization_id'], 'name' => $store_info['o2o_fuwu_organization_name'], 'o2o_fuwu_organization_avaliable_money' => $store_info['o2o_fuwu_organization_avaliable_money'], 'o2o_fuwu_organization_freeze_money' => $store_info['o2o_fuwu_organization_freeze_money'])));
        } else {
            exit(json_encode(array('id' => 0)));
        }
    }
    
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('admin_storemoney_loglist'),
                'url' => url('O2oFuwuMoney/index')
            ),
            array(
                'name' => 'withdraw_list',
                'text' => lang('admin_storemoney_cashmanage'),
                'url' => url('O2oFuwuMoney/withdraw_list')
            ),
//            array(
//                'name' => 'withdraw_set',
//                'text' => lang('admin_storemoney_withdraw_set'),
//                'url' => url('O2oFuwuMoney/withdraw_set')
//            ),
            array(
                'name' => 'adjust',
                'text' => lang('admin_storemoney_adjust'),
                'url' => "javascript:dsLayerOpen('".url('O2oFuwuMoney/adjust')."','".lang('admin_storemoney_adjust')."')"
            ),
        );
        return $menu_array;
    }
}

?>
