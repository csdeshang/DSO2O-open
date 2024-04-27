<?php

namespace app\admin\controller;
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
class  O2oDistributorMoney extends AdminControl {

    public function initialize() {
        parent::initialize();
    }


    /*
     * 资金明细
     */

    public function index() {
        $condition = array();
        $condition[] = array('o2o_distributor_moneylog_type','not in','cash_apply');
        
        $stime = input('get.stime');
        $etime = input('get.etime');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_date ? strtotime($stime) : null;
        $end_unixtime = $if_end_date ? (strtotime($etime)+86399) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[] = array('o2o_distributor_moneylog_add_time','between', array($start_unixtime, $end_unixtime));
        }
        $mname = input('get.mname');
        if (!empty($mname)) {
//            $condition[]=array('o2o_distributor_name','like','%'.$mname.'%');
        }
        $o2o_distributor_moneylog_model = model('o2o_distributor_moneylog');
        $list_log = $o2o_distributor_moneylog_model->getO2oDistributorMoneylogList($condition, 10, '*');
        View::assign('show_page', $o2o_distributor_moneylog_model->page_info->render());
        View::assign('list_log', $list_log);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /*
     * 提现申请列表
     */
    public function withdraw_list() {
        $condition = array();
        $condition[]=array('o2o_distributor_moneylog_type','=','cash_apply');
        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[]=array('o2o_distributor_moneylog_payment_state','=',intval($paystate_search));
        }

        $o2o_distributor_moneylog_model = model('o2o_distributor_moneylog');
        $withdraw_list = $o2o_distributor_moneylog_model->getO2oDistributorMoneylogList($condition, 10, '*');
        View::assign('show_page', $o2o_distributor_moneylog_model->page_info->render());
        View::assign('withdraw_list', $withdraw_list);
        
        View::assign('filtered', input('get.') ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('withdraw_list');
        return View::fetch();
    }

    /*
     * 提现设置
     */
//    public function withdraw_set(){
//        $config_model = model('config');
//        if(!request()->isPost()){
//            $list_setting = rkcache('config', true);
//            View::assign('list_setting',$list_setting);
//            $this->setAdminCurItem('withdraw_set');
//            return View::fetch();
//        }else{
//            $update_array=array(
//                'store_withdraw_min'=>abs(round(input('post.store_withdraw_min'),2)),
//                'store_withdraw_max'=>abs(round(input('post.store_withdraw_max'),2)),
//                'store_withdraw_cycle'=>abs(intval(input('post.store_withdraw_cycle'))),
//            );
//            $result = $config_model->editConfig($update_array);
//            if ($result) {
//                $this->log(lang('ds_update').lang('admin_storemoney_withdraw_set'),1);
//                $this->success(lang('ds_common_op_succ'), 'O2oDistributorMoney/withdraw_set');
//            }else{
//                $this->log(lang('ds_update').lang('admin_storemoney_withdraw_set'),0);
//            }
//        }
//    }

    /**
     * 查看提现信息
     */
    public function withdraw_view() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('param_error'));
        }
        $o2o_distributor_moneylog_model = model('o2o_distributor_moneylog');
        $condition = array();
        $condition[]=array('o2o_distributor_moneylog_id','=',$id);
        $condition[]=array('o2o_distributor_moneylog_payment_state','=',2);
        $info = $o2o_distributor_moneylog_model->getO2oDistributorMoneyLogInfo($condition);
        if (empty($info)) {
            $this->error('配送员申请提现状态错误');
        }
        if(!request()->isPost()){
            View::assign('info', $info);
            return View::fetch();
        }else{
            
            $verify_reason = input('param.verify_reason');
            if(empty($verify_reason)){
                $this->error('请输入备注信息');
            }
            
            $verify_state = intval(input('param.verify_state'));
            
            try {
                Db::startTrans();

                if ($verify_state == 1) {
                    //审核通过,扣除申请冻结资金
                    $distributor_money = array(
                        'o2o_distributor_id' => $info['o2o_distributor_id'],
                        'amount' => $info['o2o_distributor_moneylog_freeze_money'],
                        'desc' => '管理员审核通过,备注信息：' . $verify_reason,
                    );
                    $o2o_distributor_moneylog_model->changeO2oDistributorMoney('cash_pay', $distributor_money);
                    $payment_state = 3;
                } else {
                    //审核通过,扣除申请冻结资金
                    $distributor_money = array(
                        'o2o_distributor_id' => $info['o2o_distributor_id'],
                        'amount' => $info['o2o_distributor_moneylog_freeze_money'],
                        'desc' => '备注信息：' . $verify_reason,
                    );
                    $o2o_distributor_moneylog_model->changeO2oDistributorMoney('cash_del', $distributor_money);
                    $payment_state = 4;
                }
                
                //修改配送员申请提现的状态
                $o2o_distributor_moneylog_model->editO2oDistributorMoneylog($condition,array('o2o_distributor_moneylog_payment_state'=>$payment_state));
                
                Db::commit();
                $this->log('审核配送员提现', 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log('审核配送员提现', 0);
                $this->error($e->getMessage());
            }
        }
    }

    /*
     * 调节资金
     */

    /*
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
                $this->error(lang('admin_storemoney_userrecord_error'), 'O2oDistributorMoney/adjust');
            }
            $o2o_fuwu_organization_avaliable_money = floatval($store_info['o2o_fuwu_organization_avaliable_money']);
            $o2o_fuwu_organization_freeze_money = floatval($store_info['o2o_fuwu_organization_freeze_money']);
            if ($operatetype == 2 && $money > $o2o_fuwu_organization_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortprice_error') . $o2o_fuwu_organization_avaliable_money, 'O2oDistributorMoney/adjust');
            }
            if ($operatetype == 3 && $money > $o2o_fuwu_organization_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $o2o_fuwu_organization_avaliable_money, 'O2oDistributorMoney/adjust');
            }
            if ($operatetype == 4 && $money > $o2o_fuwu_organization_freeze_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $o2o_fuwu_organization_freeze_money, 'O2oDistributorMoney/adjust');
            }
            $o2o_distributor_moneylog_model = model('o2o_distributor_moneylog');
            #生成对应订单号
            $admininfo = $this->getAdminInfo();
            $data=array(
                'o2o_fuwu_organization_id'=>$store_info['o2o_fuwu_organization_id'],
                'o2o_fuwu_organization_name'=>$store_info['o2o_fuwu_organization_name'],
                'o2o_distributor_moneylog_type'=>O2oDistributorMoneyLog::TYPE_ADMIN,
                'o2o_distributor_moneylog_state'=>O2oDistributorMoneyLog::STATE_VALID,
                'o2o_distributor_moneylog_add_time'=>TIMESTAMP,
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
                    $this->error(lang('ds_common_op_fail'), 'O2oDistributorMoney/index');
                    break;
            }
            $data['o2o_distributor_moneylog_desc']=$log_msg;
            try {
                Db::startTrans();
                $o2o_distributor_moneylog_model->changeO2oDistributorMoney($data);
                Db::commit();
                $this->log($log_msg, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'O2oDistributorMoney/index');
            }
        }
    }
     * 
     */

    /*
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
     * 
     */
    
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('o2o_distributor_money'),
                'url' => url('O2oDistributorMoney/index')
            ),
            array(
                'name' => 'withdraw_list',
                'text' => '提现管理',
                'url' => url('O2oDistributorMoney/withdraw_list')
            ),
//            array(
//                'name' => 'withdraw_set',
//                'text' => lang('admin_storemoney_withdraw_set'),
//                'url' => url('O2oDistributorMoney/withdraw_set')
//            ),
//            array(
//                'name' => 'adjust',
//                'text' => lang('admin_storemoney_adjust'),
//                'url' => "javascript:dsLayerOpen('".url('O2oDistributorMoney/adjust')."','".lang('admin_storemoney_adjust')."')"
//            ),
        );
        return $menu_array;
    }
}

?>
