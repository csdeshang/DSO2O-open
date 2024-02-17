<?php

/**
 * 预存款管理
 */

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Lang;
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
class  Sellermoney extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellermoney.lang.php');
    }

    /**
     * 预存款变更日志
     */
    public function index() {
        $condition = array(array('store_id' ,'=', session('store_id')));


        $query_start_date = input('param.query_start_date');
        $query_end_date = input('param.query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? (strtotime($query_end_date) + 86399) : null;
        if ($start_unixtime) {
            $condition[] = array('storemoneylog_add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('storemoneylog_add_time', '<=', $end_unixtime);
        }

        $storemoneylog_desc = input('param.storemoneylog_desc');
        if ($storemoneylog_desc) {
            $condition[]=array('storemoneylog_desc','like', '%' . $storemoneylog_desc . '%');
        }
        $storemoneylog_model = model('storemoneylog');
        $list_log = $storemoneylog_model->getStoremoneylogList($condition, 10, '*', 'storemoneylog_id desc');
        View::assign('show_page', $storemoneylog_model->page_info->render());
        View::assign('list_log', $list_log);
        /* 设置买家当前菜单 */
        $this->setSellerCurMenu('seller_money');
        /* 设置买家当前栏目 */
        $this->setSellerCurItem('index');
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_money,store_freeze_money')->find();
        View::assign('store_info', $store_info);
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 申请提现
     */
    public function withdraw_add() {
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_money,store_freeze_money')->find();
        if (request()->isPost()) {
            $data=[
                'pdc_amount'=>floatval(input('post.pdc_amount')),
            ];
            $sellermoney_validate = ds_validate('sellermoney');
            if (!$sellermoney_validate->scene('withdraw_add')->check($data)) {
                ds_json_encode(10001,$sellermoney_validate->getError());
            }
            
            $pdc_amount = $data['pdc_amount'];
            $storemoneylog_model = model('storemoneylog');
            //是否超过提现周期
            $last_withdraw=$storemoneylog_model->getStoremoneylogInfo(array(array('store_id','=',$this->store_info['store_id']),array('storemoneylog_state','in',[Storemoneylog::STATE_WAIT,Storemoneylog::STATE_AGREE]),array('storemoneylog_type','=',Storemoneylog::TYPE_WITHDRAW),array('storemoneylog_add_time','>',TIMESTAMP-intval(config('ds_config.store_withdraw_cycle'))*86400)),'storemoneylog_add_time');
            if($last_withdraw){
                ds_json_encode(10001,lang('sellermoney_last_withdraw_time_error').date('Y-m-d',$last_withdraw['storemoneylog_add_time']));
            }
            //是否不小于最低提现金额
            if($pdc_amount<floatval(config('ds_config.store_withdraw_min'))){
                ds_json_encode(10001,lang('sellermoney_withdraw_min').config('ds_config.store_withdraw_min').lang('currency_zh'));
            }
            //是否不超过最高提现金额
            if($pdc_amount>floatval(config('ds_config.store_withdraw_max'))){
                ds_json_encode(10001,lang('sellermoney_withdraw_max').config('ds_config.store_withdraw_max').lang('currency_zh'));
            }
            $data = array(
                'store_id' => $this->store_info['store_id'],
                'store_name' => $this->store_info['store_name'],
                'storemoneylog_type' => Storemoneylog::TYPE_WITHDRAW,
                'storemoneylog_state' => Storemoneylog::STATE_WAIT,
                'storemoneylog_add_time' => TIMESTAMP,
            );
            $data['store_avaliable_money'] = -$pdc_amount;
            $data['store_freeze_money'] = $pdc_amount;

            $storejoinin_info = Db::name('storejoinin')->where(array('member_id' => $this->store_info['member_id']))->field('settlement_bank_account_name,settlement_bank_account_number,settlement_bank_name,settlement_bank_address')->find();

            $joinin_detail = model('storejoinin')->getOneStorejoinin(array('member_id' => $this->store_info['member_id']));
            if ($joinin_detail['business_licence_address'] != '') {
                $sml_desc = lang('sellermoney_bank_user').'：' . $storejoinin_info['settlement_bank_account_name'] . '，'.lang('sellermoney_bank_number').'：' . $storejoinin_info['settlement_bank_account_number'] . '，'.lang('sellermoney_bank_sub_name').'：' . $storejoinin_info['settlement_bank_name'] . '，'.lang('sellermoney_bank_name').'：' . $storejoinin_info['settlement_bank_address'];
            } else {
                $sml_desc = lang('sellermoney_alipay_name').'：' . $storejoinin_info['settlement_bank_account_name'] . '，'.lang('sellermoney_alipay_number').'：' . $storejoinin_info['settlement_bank_account_number'];
            }

            $data['storemoneylog_desc'] = $sml_desc;
            try {
                Db::startTrans();
                $storemoneylog_model->changeStoremoney($data);
                Db::commit();
                $this->recordSellerlog(lang('sellermoney_apply_withdraw'));
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } catch (\Exception $e) {
                Db::rollback();
                ds_json_encode(10001,$e->getMessage());
            }
        } else {
            View::assign('store_withdraw_cycle', config('ds_config.store_withdraw_cycle'));
            View::assign('store_withdraw_min', config('ds_config.store_withdraw_min'));
            View::assign('store_withdraw_max', config('ds_config.store_withdraw_max'));
            View::assign('store_info', $store_info);
            return View::fetch($this->template_dir . 'withdraw_add');
        }
    }

    /**
     * 提现列表
     */
    public function withdraw_list() {
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('storemoneylog_type','=',Storemoneylog::TYPE_WITHDRAW);


        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[] = array('storemoneylog_state','=',intval($paystate_search));
        }

        $storemoneylog_model = model('storemoneylog');
        $withdraw_list = $storemoneylog_model->getStoremoneylogList($condition, 10, '*', 'storemoneylog_id desc');
        View::assign('show_page', $storemoneylog_model->page_info->render());
        View::assign('withdraw_list', $withdraw_list);

        /* 设置买家当前菜单 */
        $this->setSellerCurMenu('seller_money');
        ;
        /* 设置买家当前栏目 */
        $this->setSellerCurItem('withdraw_list');
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_money,store_freeze_money')->find();
        View::assign('store_info', $store_info);
        return View::fetch($this->template_dir . 'withdraw_list');
    }

    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $item_list = array(
            array(
                'name' => 'index',
                'text' => lang('sellermoney_log_list'),
                'url' => url('Sellermoney/index'),
            ),
            array(
                'name' => 'withdraw_list',
                'text' => lang('sellermoney_withdraw_list'),
                'url' => url('Sellermoney/withdraw_list'),
            ),
        );

        return $item_list;
    }

}
