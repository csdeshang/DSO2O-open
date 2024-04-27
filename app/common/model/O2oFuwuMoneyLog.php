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
 * 数据层模型
 */
class  O2oFuwuMoneyLog extends BaseModel {

    
    const TYPE_BILL=1;
    //提现
    const TYPE_WITHDRAW=2;
    //管理员调整
    const TYPE_ADMIN=3;
    //审核,用于提现,不管审核失败还是通过
    const TYPE_VERIFY=4;
    const TYPE_DEPOSIT_OUT=5;
    const TYPE_DEPOSIT_IN=6;
    
    //有效
    const STATE_VALID=1;
    //待审核
    const STATE_WAIT=2;
    //同意
    const STATE_AGREE=3;
    //拒绝
    const STATE_REJECT=4;
    
    public $page_info;

    /**
     * 取提现单信息总数
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return int
     */
    public function getO2oFuwuMoneyLogWithdrawCount($condition = array()) {
        return Db::name('o2o_fuwu_money_log')->where(array('o2o_fuwu_money_log_type'=>self::TYPE_WITHDRAW))->where($condition)->count();
    }

    /**
     * 取得资金变更日志信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $fields 字段
     * @return array
     */
    public function getO2oFuwuMoneyLogInfo($condition = array(),$fields='') {

            $pdlog_list_paginate = Db::name('o2o_fuwu_money_log')->where($condition)->field($fields)->find();
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
    public function editO2oFuwuMoneyLog($condition = array(),$data=array()) {

            $pdlog_list_paginate = Db::name('o2o_fuwu_money_log')->where($condition)->update($data);
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
    public function getO2oFuwuMoneyLogList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = 0) {
        if ($pagesize) {
            $pdlog_list_paginate = Db::name('o2o_fuwu_money_log')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $pdlog_list_paginate;
            return $pdlog_list_paginate->items();
        } else {
            $pdlog_list_paginate = Db::name('o2o_fuwu_money_log')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
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
    public function changeO2oFuwuMoney($data = array()) {
        if(!isset($data['o2o_fuwu_organization_id'])){
            throw new \think\Exception(lang('param_error'), 10006);
        }
        $o2o_fuwu_organization_info=Db::name('o2o_fuwu_organization')->where('o2o_fuwu_organization_id',$data['o2o_fuwu_organization_id'])->field('o2o_fuwu_organization_avaliable_money,o2o_fuwu_organization_freeze_money,o2o_fuwu_organization_name')->lock(true)->find();
        if(!$o2o_fuwu_organization_info){
            throw new \think\Exception('服务机构不存在', 10006);
        }
        $data['o2o_fuwu_organization_name']=$o2o_fuwu_organization_info['o2o_fuwu_organization_name'];
        $o2o_fuwu_organization_data=array();
        if(isset($data['o2o_fuwu_organization_avaliable_money']) && $data['o2o_fuwu_organization_avaliable_money']!=0){
            if($data['o2o_fuwu_organization_avaliable_money']<0 && $o2o_fuwu_organization_info['o2o_fuwu_organization_avaliable_money']<abs($data['o2o_fuwu_organization_avaliable_money'])){//检查资金是否充足
                throw new \think\Exception('可用资金不足', 10006);
            }
            $o2o_fuwu_organization_data['o2o_fuwu_organization_avaliable_money']=bcadd($o2o_fuwu_organization_info['o2o_fuwu_organization_avaliable_money'],$data['o2o_fuwu_organization_avaliable_money'],2);
        }
        if(isset($data['o2o_fuwu_organization_freeze_money']) && $data['o2o_fuwu_organization_freeze_money']!=0){
            if($data['o2o_fuwu_organization_freeze_money']<0 && $o2o_fuwu_organization_info['o2o_fuwu_organization_freeze_money']<abs($data['o2o_fuwu_organization_freeze_money'])){//检查资金是否充足
                throw new \think\Exception('冻结资金不足', 10006);
            }
            $o2o_fuwu_organization_data['o2o_fuwu_organization_freeze_money']=bcadd($o2o_fuwu_organization_info['o2o_fuwu_organization_freeze_money'],$data['o2o_fuwu_organization_freeze_money'],2);
        }
        if(!empty($o2o_fuwu_organization_data)){
            if(!Db::name('o2o_fuwu_organization')->where('o2o_fuwu_organization_id',$data['o2o_fuwu_organization_id'])->update($o2o_fuwu_organization_data)){
                throw new \think\Exception('资金更新失败', 10006);
            }
        }
        $insert=Db::name('o2o_fuwu_money_log')->insertGetId($data);
        if(!$insert){
            throw new \think\Exception('资金记录新增失败', 10006);
        }
        return $insert;
    }



}
