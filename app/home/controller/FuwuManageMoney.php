<?php

namespace app\home\controller;
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
 * 服务机构资金控制器
 */
class FuwuManageMoney extends BaseFuwu {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/fuwu_manage_money.lang.php');
    }



    public function index() {
        $condition = array(array('o2o_fuwu_organization_id' ,'=', $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']));

        $query_start_date = input('param.query_start_date');
        $query_end_date = input('param.query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? (strtotime($query_end_date) + 86399) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[] = array('o2o_fuwu_money_log_add_time','between', array($start_unixtime, $end_unixtime));
        }

        $o2o_fuwu_money_log_desc = input('param.o2o_fuwu_money_log_desc');
        if ($o2o_fuwu_money_log_desc) {
            $condition[]=array('o2o_fuwu_money_log_desc','like', '%' . $o2o_fuwu_money_log_desc . '%');
        }
        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        $log_list = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogList($condition, 10, '*', 'o2o_fuwu_money_log_id desc');

        View::assign('log_list',$log_list);
        View::assign('o2o_fuwu_organization_info',$this->o2o_fuwu_organization_info);
        View::assign('show_page', $o2o_fuwu_money_log_model->page_info->render());
        $this->setFuwuCurMenu('fuwu_manage_money');
        $this->setFuwuCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }
    

    public function withdraw_list() {
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_id','=',$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']);
        $condition[] = array('o2o_fuwu_money_log_type','=',O2oFuwuMoneyLog::TYPE_WITHDRAW);
        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[] = array('o2o_fuwu_money_log_state','=',intval($paystate_search));
        }

        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        $log_list = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogList($condition, 10, '*', 'o2o_fuwu_money_log_id desc');

        View::assign('withdraw_list',$log_list);
        View::assign('o2o_fuwu_organization_info',$this->o2o_fuwu_organization_info);
        View::assign('show_page', $o2o_fuwu_money_log_model->page_info->render());
        $this->setFuwuCurMenu('fuwu_manage_money_withdraw_list');
        $this->setFuwuCurItem('withdraw_list');
        return View::fetch($this->template_dir . 'withdraw_list');
    }


    public function withdraw_add() {
        if(!request()->isPost()){
            View::assign('store_withdraw_min',config('ds_config.store_withdraw_min'));
            View::assign('store_withdraw_max',config('ds_config.store_withdraw_max'));
            View::assign('store_withdraw_cycle',config('ds_config.store_withdraw_cycle'));
            View::assign('o2o_fuwu_organization_info',$this->o2o_fuwu_organization_info);
            return View::fetch($this->template_dir . 'withdraw_add');
        }else{
        $data = [
            'pdc_amount' => floatval(input('post.pdc_amount')),
        ];
        $o2o_fuwu_money_log_validate = ds_validate('O2oFuwuMoneyLog');
        if (!$o2o_fuwu_money_log_validate->scene('o2o_fuwu_money_log_save')->check($data)) {
            ds_json_encode(10001, $o2o_fuwu_money_log_validate->getError());
        }

        $pdc_amount = $data['pdc_amount'];
        $o2o_fuwu_money_log_model = model('o2o_fuwu_money_log');
        //是否超过提现周期
        $last_withdraw = $o2o_fuwu_money_log_model->getO2oFuwuMoneyLogInfo(array(array('o2o_fuwu_organization_id' ,'=', $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id']), array('o2o_fuwu_money_log_state','in', [O2oFuwuMoneyLog::STATE_WAIT, O2oFuwuMoneyLog::STATE_AGREE]), array('o2o_fuwu_money_log_type' ,'=', O2oFuwuMoneyLog::TYPE_WITHDRAW), array('o2o_fuwu_money_log_add_time','>', TIMESTAMP - intval(config('ds_config.store_withdraw_cycle')) * 86400)), 'o2o_fuwu_money_log_add_time');
        if ($last_withdraw) {
            ds_json_encode(10001, lang('o2o_fuwu_manage_money_last_withdraw_time_error') . date('Y-m-d', $last_withdraw['o2o_fuwu_money_log_add_time']));
        }
        //是否不小于最低提现金额
        if ($pdc_amount < floatval(config('ds_config.store_withdraw_min'))) {
            ds_json_encode(10001, lang('o2o_fuwu_manage_money_withdraw_min') . config('ds_config.store_withdraw_min') . '元');
        }
        //是否不超过最高提现金额
        if ($pdc_amount > floatval(config('ds_config.store_withdraw_max'))) {
            ds_json_encode(10001, lang('o2o_fuwu_manage_money_withdraw_max') . config('ds_config.store_withdraw_max') . '元');
        }
        $data = array(
            'o2o_fuwu_organization_id' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],
            'o2o_fuwu_organization_name' => $this->o2o_fuwu_organization_info['o2o_fuwu_organization_name'],
            'o2o_fuwu_money_log_type' => O2oFuwuMoneyLog::TYPE_WITHDRAW,
            'o2o_fuwu_money_log_state' => O2oFuwuMoneyLog::STATE_WAIT,
            'o2o_fuwu_money_log_add_time' => TIMESTAMP,
        );
        $data['o2o_fuwu_organization_avaliable_money'] = -$pdc_amount;
        $data['o2o_fuwu_organization_freeze_money'] = $pdc_amount;


        $sml_desc=$this->o2o_fuwu_organization_info['o2o_fuwu_organization_payment_account'];
        if(!$sml_desc){
            ds_json_encode(30002, '请先设置收款账号');
        }
        $data['o2o_fuwu_money_log_desc'] = $sml_desc;
        try {
            Db::startTrans();
            $o2o_fuwu_money_log_model->changeO2oFuwuMoney($data);
            Db::commit();
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } catch (\Exception $e) {
            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        }

    }

    
    /**
     * 导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getFuwuItemList() {
        if(request()->action()=='index'){
            $menu_array = array(
                array(
                    'name' => 'index', 'text' => '资金明细',
                    'url' => url('FuwuManageMoney/index')
                ),
            );
        }else{
            $menu_array = array(
                array(
                    'name' => 'withdraw_list', 'text' => '提现列表',
                    'url' => url('FuwuManageMoney/withdraw_list')
                ),
            );
        }
        return $menu_array;
    }


}
