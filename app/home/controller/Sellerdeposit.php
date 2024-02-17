<?php

/**
 * 预存款管理
 */

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Lang;
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
class  Sellerdeposit extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerdeposit.lang.php');
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
            $condition[] = array('storedepositlog_add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('storedepositlog_add_time', '<=', $end_unixtime);
        }

        $storedepositlog_desc = input('param.storedepositlog_desc');
        if ($storedepositlog_desc) {
            $condition[]=array('storedepositlog_desc','like', '%' . $storedepositlog_desc . '%');
        }
        $storedepositlog_model = model('storedepositlog');
        $list_log = $storedepositlog_model->getStoredepositlogList($condition, 10, '*', 'storedepositlog_id desc');
        View::assign('show_page', $storedepositlog_model->page_info->render());
        View::assign('list_log', $list_log);
        /* 设置买家当前菜单 */
        $this->setSellerCurMenu('seller_deposit');
        /* 设置买家当前栏目 */
        $this->setSellerCurItem('index');
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_deposit,store_freeze_deposit,store_payable_deposit')->find();
        View::assign('store_info', $store_info);
        return View::fetch($this->template_dir . 'index');
    }
    
    public function recharge_add() {
        $storedepositlog_model = model('storedepositlog');
        if (request()->isPost()) {
            $money = abs(floatval(input('post.pdc_amount')));
            if (!$money) {
                ds_json_encode(10001,lang('param_error'));
            }
            try {
                Db::startTrans();

                $data = array(
                    'store_id' => $this->store_info['store_id'],
                    'store_name' => $this->store_info['store_name'],
                    'storedepositlog_type' => Storedepositlog::TYPE_PAY,
                    'storedepositlog_state' => Storedepositlog::STATE_VALID,
                    'storedepositlog_add_time' => TIMESTAMP,
                );
                $data['store_avaliable_deposit'] = $money;


                $data['storedepositlog_desc'] = lang('sellerdeposit_recharge_deposit');


                $storedepositlog_model->changeStoredeposit($data);
                //从店铺资金中扣除
                $storemoneylog_model = model('storemoneylog');
                $data2 = array(
                    'store_id' => $this->store_info['store_id'],
                    'store_name' => $this->store_info['store_name'],
                    'storemoneylog_type' => Storemoneylog::TYPE_DEPOSIT_IN,
                    'storemoneylog_state' => Storemoneylog::STATE_VALID,
                    'storemoneylog_add_time' => TIMESTAMP,
                    'store_avaliable_money' => -$money,
                    'storemoneylog_desc' => $data['storedepositlog_desc'],
                );
                $storemoneylog_model->changeStoremoney($data2);

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                ds_json_encode(10001,$e->getMessage());
            }
            $this->recordSellerlog(lang('sellerdeposit_recharge_deposit'));
            ds_json_encode(10000,lang('ds_common_op_succ'));
        } else {
            return View::fetch($this->template_dir . 'recharge_add');
        }
    }

    /**
     * 申请提现
     */
    public function withdraw_add() {
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_deposit,store_freeze_deposit,store_payable_deposit')->find();
        if (request()->isPost()) {
            $data=[
                'pdc_amount'=>floatval(input('post.pdc_amount')),
            ];
            $sellerdeposit_validate = ds_validate('sellerdeposit');
            if (!$sellerdeposit_validate->scene('withdraw_add')->check($data)) {
                ds_json_encode(10001,$sellerdeposit_validate->getError());
            }
            
            $pdc_amount = $data['pdc_amount'];
            $storedepositlog_model = model('storedepositlog');

            $data = array(
                'store_id' => $this->store_info['store_id'],
                'store_name' => $this->store_info['store_name'],
                'storedepositlog_type' => Storedepositlog::TYPE_WITHDRAW,
                'storedepositlog_state' => Storedepositlog::STATE_WAIT,
                'storedepositlog_add_time' => TIMESTAMP,
            );
            $data['store_avaliable_deposit'] = -$pdc_amount;
            $data['store_freeze_deposit'] = $pdc_amount;


            $data['storedepositlog_desc'] = lang('sellerdeposit_apply_withdraw').lang('sellerdeposit_avaliable_money');
            try {
                Db::startTrans();
                $storedepositlog_model->changeStoredeposit($data);
                Db::commit();
                $this->recordSellerlog(lang('sellerdeposit_apply_withdraw'));
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
        $condition = array(
            array('store_id' ,'=', session('store_id')),
            array('storedepositlog_type','in',[Storedepositlog::TYPE_WITHDRAW,Storedepositlog::TYPE_RECHARGE]),
        );


        $paystate_search = input('param.paystate_search');
        if (isset($paystate_search) && $paystate_search !== '') {
            $condition[] = array('storedepositlog_state','=',intval($paystate_search));
        }

        $storedepositlog_model = model('storedepositlog');
        $withdraw_list = $storedepositlog_model->getStoredepositlogList($condition, 10, '*', 'storedepositlog_id desc');
        View::assign('show_page', $storedepositlog_model->page_info->render());
        View::assign('withdraw_list', $withdraw_list);

        /* 设置买家当前菜单 */
        $this->setSellerCurMenu('seller_deposit');
        /* 设置买家当前栏目 */
        $this->setSellerCurItem('withdraw_list');
        $store_info = Db::name('store')->where(array('store_id' => session('store_id')))->field('store_avaliable_deposit,store_freeze_deposit,store_payable_deposit')->find();
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
                'text' => lang('sellerdeposit_log_list'),
                'url' => url('Sellerdeposit/index'),
            ),
            array(
                'name' => 'withdraw_list',
                'text' => lang('sellerdeposit_withdraw_list'),
                'url' => url('Sellerdeposit/withdraw_list'),
            ),
        );
        return $item_list;
    }

}
