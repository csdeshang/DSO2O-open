<?php

namespace app\common\model;
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
 * 店铺保证金
 */
class  Storedepositlog extends BaseModel {

    const TYPE_RECHARGE=1;
    const TYPE_WITHDRAW=2;
    const TYPE_ADMIN=3;
    const TYPE_VERIFY=4;
    const TYPE_PAY=5;
    const TYPE_VIEW=6;
    
    const STATE_VALID=1;
    const STATE_WAIT=2;
    const STATE_AGREE=3;
    const STATE_REJECT=4;
    const STATE_PAYED=5;
    const STATE_CANCEL=6;
    const STATE_PAYING=7;
    public $page_info;

    /**
     * 取提现单信息总数
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return int
     */
    public function getStoredepositlogWithdrawCount($condition = array()) {
        return Db::name('storedepositlog')->where(array('storedepositlog_type'=>self::TYPE_WITHDRAW))->where($condition)->count();
    }

    /**
     * 取得资金变更日志信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $fields 字段
     * @return array
     */
    public function getStoredepositlogInfo($condition = array(),$fields='') {

            $pdlog_list_paginate = Db::name('storedepositlog')->where($condition)->field($fields)->find();
            return $pdlog_list_paginate;
    }
    /**
     * 取得资金变更日志信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 字段
     * @return array
     */
    public function editStoredepositlog($condition = array(),$data=array()) {

            $pdlog_list_paginate = Db::name('storedepositlog')->where($condition)->update($data);
            return $pdlog_list_paginate;
    }
    /**
     * 取得资金变更日志列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 页面信息
     * @param type $fields 字段
     * @param type $order 排序
     * @param type $limit 限制
     * @return array
     */
    public function getStoredepositlogList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = 0) {
        if ($pagesize) {
            $pdlog_list_paginate = Db::name('storedepositlog')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $pdlog_list_paginate;
            return $pdlog_list_paginate->items();
        } else {
            $pdlog_list_paginate = Db::name('storedepositlog')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
            return $pdlog_list_paginate;
        }
    }


    /**
     * 变更资金
     * @access public
     * @author csdeshang
     * @param type $data
     * @return type
     */
    public function changeStoredeposit($data = array()) {
        if(!isset($data['store_id'])){
            throw new \think\Exception(lang('param_error'), 10006);
        }
        $store_info=Db::name('store')->where('store_id',$data['store_id'])->field('store_avaliable_deposit,store_freeze_deposit,store_payable_deposit,store_name')->lock(true)->find();
        if(!$store_info){
            throw new \think\Exception(lang('ds_store_is_not_exist'), 10006);
        }
        $data['store_name']=$store_info['store_name'];
        $store_data=array();
        if(isset($data['store_avaliable_deposit']) && $data['store_avaliable_deposit']!=0){
            if($data['store_avaliable_deposit']<0 && $store_info['store_avaliable_deposit']<abs($data['store_avaliable_deposit'])){//检查资金是否充足
                throw new \think\Exception(lang('ds_store_avaliable_deposit_is_not_enough'), 10006);
            }
            $store_data['store_avaliable_deposit']=bcadd($store_info['store_avaliable_deposit'],$data['store_avaliable_deposit'],2);
        }
        
        if(isset($data['store_freeze_deposit']) && $data['store_freeze_deposit']!=0){
            if($data['store_freeze_deposit']<0 && $store_info['store_freeze_deposit']<abs($data['store_freeze_deposit'])){//检查资金是否充足
                throw new \think\Exception(lang('ds_store_freeze_deposit_is_not_enough'), 10006);
            }
            $store_data['store_freeze_deposit']=bcadd($store_info['store_freeze_deposit'],$data['store_freeze_deposit'],2);
        }
        
        if(isset($data['store_payable_deposit']) && $data['store_payable_deposit']!=0){
            if($data['store_payable_deposit']<0 && $store_info['store_payable_deposit']<abs($data['store_payable_deposit'])){//检查资金是否充足
                throw new \think\Exception(lang('ds_store_payable_deposit_is_not_enough'), 10006);
            }
            $store_data['store_payable_deposit']=bcadd($store_info['store_payable_deposit'],$data['store_payable_deposit'],2);
        }

        if(!empty($store_data)){
            if(!Db::name('store')->where('store_id',$data['store_id'])->update($store_data)){
                throw new \think\Exception(lang('ds_store_deposit_adjust_fail'), 10006);
            }
        }
        $insert=Db::name('storedepositlog')->insertGetId($data);
        if(!$insert){
            throw new \think\Exception(lang('ds_store_deposit_log_insert_fail'), 10006);
        }
        return $insert;
    }



}
