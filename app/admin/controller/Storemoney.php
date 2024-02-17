<?php

namespace app\admin\controller;
use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
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
class  Storemoney extends AdminControl {

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
        if ($start_unixtime) {
            $condition[] = array('storemoneylog_add_time','>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('storemoneylog_add_time','<=', $end_unixtime);
        }
        $mname = input('get.mname');
        if (!empty($mname)) {
            $condition[]=array('store_name','like','%'.$mname.'%');
        }
        $storemoneylog_model = model('storemoneylog');
        $list_log = $storemoneylog_model->getStoremoneylogList($condition, 10, '*', 'storemoneylog_id desc');
        View::assign('show_page', $storemoneylog_model->page_info->render());
        View::assign('list_log', $list_log);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /*
     * 提现列表
     */
    public function withdraw_list() {
        $condition = array('storemoneylog_type'=>Storemoneylog::TYPE_WITHDRAW,);
        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[] = array('storedepositlog_state','=',intval($paystate_search));
        }

        $storemoneylog_model = model('storemoneylog');
        $withdraw_list = $storemoneylog_model->getStoremoneylogList($condition, 10, '*', 'storemoneylog_id desc');
        View::assign('show_page', $storemoneylog_model->page_info->render());
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
                $this->success(lang('ds_common_op_succ'), 'Storemoney/withdraw_set');
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
        $storemoneylog_model = model('storemoneylog');
        $condition = array();
        $condition[] = array('storemoneylog_id','=',$id);

        $info = $storemoneylog_model->getStoremoneylogInfo($condition);
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
                'store_id'=>$info['store_id'],
                'store_name'=>$info['store_name'],
                'storemoneylog_type'=>Storemoneylog::TYPE_VERIFY,
                'storemoneylog_state'=>Storemoneylog::STATE_VALID,
                'storemoneylog_add_time'=>TIMESTAMP,
            );
            if(input('param.verify_state')==1){//通过
                    $data['store_freeze_money']=-$info['store_freeze_money'];
                    $storemoneylog_state=Storemoneylog::STATE_AGREE;
            }else{
                $data['store_avaliable_money']=$info['store_freeze_money'];
                    $data['store_freeze_money']=-$info['store_freeze_money'];
                    $storemoneylog_state=Storemoneylog::STATE_REJECT;
            }
            $admininfo = $this->getAdminInfo();
            $data['storemoneylog_desc']=lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".((input('param.verify_state')==1)?lang('ds_pass'):lang('ds_refuse')).lang('seller_name')."【" . $info['store_name'] . "】".lang('admin_storemoney_log_stage_cash').'：'.input('param.verify_reason');
            try {
                Db::startTrans();
                $storemoneylog_model->changeStoremoney($data);
                //修提现状态
                if(!$storemoneylog_model->editStoremoneylog(array('storemoneylog_id'=>$id,'storemoneylog_state'=>Storemoneylog::STATE_WAIT),array('storemoneylog_state'=>$storemoneylog_state))){
                    throw new \think\Exception(lang('admin_storemoney_cash_edit_fail'), 10006);
                }
                Db::commit();
                $this->log($data['storemoneylog_desc'], 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($data['storemoneylog_desc'], 0);
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
                $this->error(lang('admin_storemoney_artificial_pricemin_error'));
            }
            //查询店主信息
            $store_mod = model('store');
            $store_id = intval(input('post.store_id'));
            $operatetype = input('post.operatetype');
            $store_info = $store_mod->getStoreInfo(array('store_id' => $store_id));

            if (!is_array($store_info) || count($store_info) <= 0) {
                $this->error(lang('admin_storemoney_userrecord_error'), 'Storemoney/adjust');
            }
            $store_avaliable_money = floatval($store_info['store_avaliable_money']);
            $store_freeze_money = floatval($store_info['store_freeze_money']);
            if ($operatetype == 2 && $money > $store_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortprice_error') . $store_avaliable_money, 'Storemoney/adjust');
            }
            if ($operatetype == 3 && $money > $store_avaliable_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $store_avaliable_money, 'Storemoney/adjust');
            }
            if ($operatetype == 4 && $money > $store_freeze_money) {
                $this->error(lang('admin_storemoney_artificial_shortfreezeprice_error') . $store_freeze_money, 'Storemoney/adjust');
            }
            $storemoneylog_model = model('storemoneylog');
            #生成对应订单号
            $admininfo = $this->getAdminInfo();
            $data=array(
                'store_id'=>$store_info['store_id'],
                'store_name'=>$store_info['store_name'],
                'storemoneylog_type'=>Storemoneylog::TYPE_ADMIN,
                'storemoneylog_state'=>Storemoneylog::STATE_VALID,
                'storemoneylog_add_time'=>TIMESTAMP,
            );
            switch ($operatetype) {
                case 1:
                    $data['store_avaliable_money']=$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_add')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 2:
                    $data['store_avaliable_money']=-$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_reduce')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 3:
                    $data['store_avaliable_money']=-$money;
                    $data['store_freeze_money']=$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_freeze')."】，".lang('admin_storemoney_price') . $money;
                    break;
                case 4:
                    $data['store_avaliable_money']=$money;
                    $data['store_freeze_money']=-$money;
                    $log_msg = lang('order_admin_operator')."【" . $admininfo['admin_name'] . "】".lang('ds_handle').lang('seller_name')."【" . $store_info['store_name'] . "】".lang('ds_store_money')."【".lang('admin_storemoney_artificial_operatetype_unfreeze')."】，".lang('admin_storemoney_price') . $money;
                    break;
                default:
                    $this->error(lang('ds_common_op_fail'), 'Storemoney/index');
                    break;
            }
            $data['storemoneylog_desc']=$log_msg;
            try {
                Db::startTrans();
                $storemoneylog_model->changeStoremoney($data);
                Db::commit();
                $this->log($log_msg, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'Storemoney/index');
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
            exit(json_encode(array('id' => $store_info['store_id'], 'name' => $store_info['seller_name'], 'store_avaliable_money' => $store_info['store_avaliable_money'], 'store_freeze_money' => $store_info['store_freeze_money'])));
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
                'url' => url('Storemoney/index')
            ),
            array(
                'name' => 'withdraw_list',
                'text' => lang('admin_storemoney_cashmanage'),
                'url' => url('Storemoney/withdraw_list')
            ),
            array(
                'name' => 'withdraw_set',
                'text' => lang('admin_storemoney_withdraw_set'),
                'url' => url('Storemoney/withdraw_set')
            ),
            array(
                'name' => 'adjust',
                'text' => lang('admin_storemoney_adjust'),
                'url' => "javascript:dsLayerOpen('".url('Storemoney/adjust')."','".lang('admin_storemoney_adjust')."')"
            ),
        );
        return $menu_array;
    }
}

?>
