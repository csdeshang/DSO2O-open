<?php

namespace app\admin\controller;
use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
use app\common\model\Storedepositlog;
use app\common\model\Storemoneylog;
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
class  Storedeposit extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/storedeposit.lang.php');
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
        if ($start_unixtime) {
            $condition[] = array('storedepositlog_add_time','>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('storedepositlog_add_time','<=', $end_unixtime);
        }
        $mname = input('get.mname');
        if (!empty($mname)) {
            $condition[]=array('store_name','like','%'.$mname.'%');
        }
        $storedepositlog_model = model('storedepositlog');
        $list_log = $storedepositlog_model->getStoredepositlogList($condition, 10, '*', 'storedepositlog_id desc');
        View::assign('show_page', $storedepositlog_model->page_info->render());
        View::assign('list_log', $list_log);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /*
     * 提现列表
     */
    public function withdraw_list() {
        $condition = array(array('storedepositlog_type','in',[Storedepositlog::TYPE_WITHDRAW,Storedepositlog::TYPE_RECHARGE]),);
        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[]=array('storedepositlog_state','=',intval($paystate_search));
        }

        $storedepositlog_model = model('storedepositlog');
        $withdraw_list = $storedepositlog_model->getStoredepositlogList($condition, 10, '*', 'storedepositlog_id desc');
        View::assign('show_page', $storedepositlog_model->page_info->render());
        View::assign('withdraw_list', $withdraw_list);
        
        View::assign('filtered', input('get.') ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('withdraw_list');
        return View::fetch();
    }



    /**
     * 查看提现信息
     */
    public function withdraw_view() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('param_error'));
        }
        $storedepositlog_model = model('storedepositlog');
        $condition = array();
        $condition[] = array('storedepositlog_id','=',$id);
        $info = $storedepositlog_model->getStoredepositlogInfo($condition);
        if (!is_array($info) || count($info) < 0) {
            $this->error(lang('admin_storedeposit_record_error'));
        }
        if(!request()->isPost()){
            View::assign('info', $info);
            return View::fetch();
        }else{
            if(!input('param.verify_reason')){
                $this->error(lang('ds_none_input').lang('admin_storedeposit_remark'));
            }
            $data=array(
                'store_id'=>$info['store_id'],
                'store_name'=>$info['store_name'],
                'storedepositlog_type'=>Storedepositlog::TYPE_VERIFY,
                'storedepositlog_state'=>Storedepositlog::STATE_VALID,
                'storedepositlog_add_time'=>TIMESTAMP,
            );
            if(input('param.verify_state')==1){//通过
                    $data['store_freeze_deposit']=-$info['store_freeze_deposit'];
                    $storedepositlog_state=Storedepositlog::STATE_AGREE;
            }else{
                $data['store_avaliable_deposit']=$info['store_freeze_deposit'];
                    $data['store_freeze_deposit']=-$info['store_freeze_deposit'];
                    $storedepositlog_state=Storedepositlog::STATE_REJECT;
            }
            $admininfo = $this->getAdminInfo();
            $data['storedepositlog_desc']=lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".((input('param.verify_state')==1)?lang('ds_pass'):lang('ds_refuse')).lang('seller_name')."【" . $info['store_name'] . "】".lang('admin_storedeposit_log_stage_cash').'：'.input('param.verify_reason');
            try {
                Db::startTrans();
                $storedepositlog_model->changeStoredeposit($data);
                //修提现状态
                if(!$storedepositlog_model->editStoredepositlog(array('storedepositlog_id'=>$id,'storedepositlog_state'=>Storedepositlog::STATE_WAIT),array('storedepositlog_state'=>$storedepositlog_state))){
                    throw new \think\Exception(lang('admin_storedeposit_cash_edit_fail'), 10006);
                }
                //如果是通过取出保证金，则将保证金转换为店铺可用资金
                if(input('param.verify_state')==1){
                    $storemoneylog_model = model('storemoneylog');
                    $data2=array(
                        'store_id'=>$info['store_id'],
                        'store_name'=>$info['store_name'],
                        'storemoneylog_type'=>Storemoneylog::TYPE_DEPOSIT_OUT,
                        'storemoneylog_state'=>Storemoneylog::STATE_VALID,
                        'storemoneylog_add_time'=>TIMESTAMP,
                        'store_avaliable_money'=>$info['store_freeze_deposit'],
                        'storemoneylog_desc'=>$data['storedepositlog_desc'],
                    );
                    $storemoneylog_model->changeStoremoney($data2);
                }
                Db::commit();
                $this->log($data['storedepositlog_desc'], 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($data['storedepositlog_desc'], 0);
                $this->error($e->getMessage());
            }
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        }
    }
    
    
    public function recharge_view() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('param_error'));
        }
        $storedepositlog_model = model('storedepositlog');
        $condition = array();
        $condition[] = array('storedepositlog_id','=',$id);
        $info = $storedepositlog_model->getStoredepositlogInfo($condition);
        if (!is_array($info) || count($info) < 0) {
            $this->error(lang('admin_storedeposit_record_error'));
        }
        if(!request()->isPost()){
            View::assign('info', $info);
            return View::fetch();
        }else{
            if(!input('param.verify_reason')){
                $this->error(lang('ds_none_input').lang('admin_storedeposit_remark'));
            }
            $data=array(
                'store_id'=>$info['store_id'],
                'store_name'=>$info['store_name'],
                'storedepositlog_type'=>Storedepositlog::TYPE_VIEW,
                'storedepositlog_state'=>Storedepositlog::STATE_VALID,
                'storedepositlog_add_time'=>TIMESTAMP,
            );
            if(input('param.verify_state')==1){//通过
                $data['store_avaliable_deposit']=$info['store_payable_deposit'];
                    $data['store_payable_deposit']=-$info['store_payable_deposit'];
                    $storedepositlog_state=Storedepositlog::STATE_PAYED;
            }else{
                    $data['store_payable_deposit']=-$info['store_payable_deposit'];
                    $storedepositlog_state=Storedepositlog::STATE_CANCEL;
            }
            $admininfo = $this->getAdminInfo();
            $data['storedepositlog_desc']=lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_update').lang('seller_name')."【" . $info['store_name'] . "】".lang('admin_storedeposit_pay_state').((input('param.verify_state')==1)?lang('admin_storedeposit_payed'):lang('admin_storedeposit_cancel')).'：'.input('param.verify_reason');
            try {
                Db::startTrans();
                $storedepositlog_model->changeStoredeposit($data);
                //修提现状态
                if(!$storedepositlog_model->editStoredepositlog(array('storedepositlog_id'=>$id,'storedepositlog_state'=>Storedepositlog::STATE_PAYING),array('storedepositlog_state'=>$storedepositlog_state))){
                    throw new \think\Exception(lang('admin_storedeposit_pay_state').lang('ds_update').lang('ds_fail'), 10006);
                }

                Db::commit();
                $this->log($data['storedepositlog_desc'], 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($data['storedepositlog_desc'], 0);
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
            $store_id = intval(input('get.store_id'));
            if($store_id>0){
                $condition = array();
                $condition[] = array('store_id','=',$store_id);
                $store = model('store')->getStoreInfo($condition);
                if(!empty($store)){
                    View::assign('store_info',$store);
                }
            }
            return View::fetch();
        } else {
            $data = array(
                'store_id' => input('post.store_id'),
                'amount' => input('post.amount'),
                'operatetype' => input('post.operatetype'),
                'lg_desc' => input('post.lg_desc'),
            );
            $storedeposit_validate = ds_validate('storedeposit');
            if (!$storedeposit_validate->scene('adjust')->check($data)){
                $this->error($storedeposit_validate->getError());
            }


            $money = abs(floatval(input('post.amount')));
            if ($money <= 0) {
                $this->error(lang('admin_storedeposit_artificial_pricemin_error'));
            }
            //查询店主信息
            $store_mod = model('store');
            $store_id = intval(input('post.store_id'));
            $operatetype = input('post.operatetype');
            $store_info = $store_mod->getStoreInfo(array('store_id' => $store_id));

            if (!is_array($store_info) || count($store_info) <= 0) {
                $this->error(lang('admin_storedeposit_userrecord_error'), 'Storedeposit/adjust');
            }
            $store_avaliable_deposit = floatval($store_info['store_avaliable_deposit']);
            $store_freeze_deposit = floatval($store_info['store_freeze_deposit']);
            if ($operatetype == 2 && $money > $store_avaliable_deposit) {
                $this->error(lang('admin_storedeposit_artificial_shortprice_error') . $store_avaliable_deposit, 'Storedeposit/adjust');
            }

            $storedepositlog_model = model('storedepositlog');
            #生成对应订单号
            $admininfo = $this->getAdminInfo();
            $data=array(
                'store_id'=>$store_info['store_id'],
                'store_name'=>$store_info['store_name'],
                'storedepositlog_type'=>Storedepositlog::TYPE_ADMIN,
                'storedepositlog_state'=>Storedepositlog::STATE_VALID,
                'storedepositlog_add_time'=>TIMESTAMP,
            );
            switch ($operatetype) {
                case 1:
                    $data['store_avaliable_deposit']=$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_deposit')."【".lang('admin_storedeposit_artificial_operatetype_add')."】，".lang('admin_storedeposit_price') . $money;
                    break;
                case 2:
                    $data['store_avaliable_deposit']=-$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_deposit')."【".lang('admin_storedeposit_artificial_operatetype_reduce')."】，".lang('admin_storedeposit_price') . $money;
                    break;
                default:
                    $this->error(lang('ds_common_op_fail'), 'Storedeposit/index');
                    break;
            }
            $data['storedepositlog_desc']=$log_msg;
            try {
                Db::startTrans();
                $storedepositlog_model->changeStoredeposit($data);
                Db::commit();
                $this->log($log_msg, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'Storedeposit/index');
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
        $obj_store = model('store');
        $store_info = $obj_store->getStoreInfo(array('seller_name' => $name));
        if (is_array($store_info) && count($store_info) > 0) {
            exit(json_encode(array('id' => $store_info['store_id'], 'name' => $store_info['seller_name'], 'store_avaliable_deposit' => $store_info['store_avaliable_deposit'], 'store_freeze_deposit' => $store_info['store_freeze_deposit'])));
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
                'text' => lang('admin_storedeposit_loglist'),
                'url' => url('Storedeposit/index')
            ),
            array(
                'name' => 'withdraw_list',
                'text' => lang('admin_storedeposit_cashmanage'),
                'url' => url('Storedeposit/withdraw_list')
            ),
            array(
                'name' => 'adjust',
                'text' => lang('admin_storedeposit_adjust'),
                'url' => "javascript:dsLayerOpen('".url('Storedeposit/adjust')."','".lang('admin_storedeposit_adjust')."')"
            ),
        );
        return $menu_array;
    }
}

?>
