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
class  O2oDistributorMoneylog extends BaseModel {

    
    public $page_info;

    /**
     * 取提现单信息总数
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return int
     */
    public function getO2oDistributorMoneylogWithdrawCount($condition = array()) {
        return Db::name('O2oDistributorMoneylog')->where(array('o2o_distributor_moneylog_type'=>self::TYPE_WITHDRAW))->where($condition)->count();
    }

    /**
     * 取得资金变更日志信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $fields 字段
     * @return array
     */
    public function getO2oDistributorMoneylogInfo($condition = array(),$fields='') {

            $pdlog_list_paginate = Db::name('O2oDistributorMoneylog')->where($condition)->field($fields)->find();
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
    public function editO2oDistributorMoneylog($condition = array(),$data=array()) {

            $pdlog_list_paginate = Db::name('O2oDistributorMoneylog')->where($condition)->update($data);
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
    public function getO2oDistributorMoneylogList($condition = array(), $pagesize = '', $fields = '*', $order = 'o2o_distributor_moneylog_add_time desc', $limit = 0) {
        if ($pagesize) {
            $pdlog_list_paginate = Db::name('O2oDistributorMoneylog')->where($condition)->field($fields)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $pdlog_list_paginate;
            $result = $pdlog_list_paginate->items();
        } else {
            $pdlog_list_paginate = Db::name('O2oDistributorMoneylog')->where($condition)->field($fields)->order($order)->limit($limit)->select()->toArray();
            $result = $pdlog_list_paginate;
        }
        
        foreach ($result as $key => $value) {
            $result[$key]['o2o_distributor_moneylog_add_time_desc'] = date('Y-m-d H:i',$value['o2o_distributor_moneylog_add_time']);
        }
        
        return $result;
        
    }


    /**
     * 变更资金
     * @access public
     * @author csdeshang
     * @param type $data
     * @return type
     */
    public function changeO2oDistributorMoney($change_type, $data = array()) {
        
        $data_log = array();
        $data_pd = array();
        
        $log_desc = isset($data['desc'])? $data['desc'] : '';
        
        $data_log['o2o_distributor_id'] = $data['o2o_distributor_id'];
        $data_log['o2o_distributor_moneylog_add_time'] = TIMESTAMP;
        $data_log['o2o_distributor_moneylog_type'] = $change_type;
        
        
        
        switch ($change_type) {
            case 'order_waimai':
                $data_log['o2o_distributor_moneylog_avaliable_money'] = $data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '确认收货，外卖配送费，订单号: ' . $data['order_sn'];
                
                $data_pd['o2o_distributor_avaliable_money'] = Db::raw('o2o_distributor_avaliable_money+'.$data['amount']);
                break;
            
            case 'order_errand':
                $data_log['o2o_distributor_moneylog_avaliable_money'] = $data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '确认收货，跑腿配送费，订单号: ' . $data['order_sn'].'。'.$log_desc;;
                
                $data_pd['o2o_distributor_avaliable_money'] = Db::raw('o2o_distributor_avaliable_money+'.$data['amount']);
                break;
            
            case 'order_complaint':
                $data_log['o2o_distributor_moneylog_avaliable_money'] = -$data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '用户投诉配送员' . $data['order_sn'];
                
                $data_pd['o2o_distributor_avaliable_money'] = Db::raw('o2o_distributor_avaliable_money+'.$data['amount']);
                break;
            case 'cash_apply':
                $data_log['o2o_distributor_moneylog_avaliable_money'] = -$data['amount'];
                $data_log['o2o_distributor_moneylog_freeze_money'] = $data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '申请提现，冻结预存款';
                //待审核状态,用于判断管理员的状态,在其他地方无用
                $data_log['o2o_distributor_moneylog_payment_state'] = '2';
                
                $data_pd['o2o_distributor_avaliable_money'] = Db::raw('o2o_distributor_avaliable_money-'.$data['amount']);
                $data_pd['o2o_distributor_freeze_money'] = Db::raw('o2o_distributor_freeze_money+'.$data['amount']);
                break;
            case 'cash_pay':
                $data_log['o2o_distributor_moneylog_freeze_money'] = -$data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '提现成功,'.$log_desc;
                
                $data_pd['o2o_distributor_freeze_money'] = Db::raw('o2o_distributor_freeze_money-'.$data['amount']);
                break;
            case 'cash_del':
                $data_log['o2o_distributor_moneylog_avaliable_money'] = $data['amount'];
                $data_log['o2o_distributor_moneylog_freeze_money'] = -$data['amount'];
                $data_log['o2o_distributor_moneylog_desc'] = '取消提现申请，解冻预存款。'.$log_desc;
                
                $data_pd['o2o_distributor_avaliable_money'] = Db::raw('o2o_distributor_avaliable_money+'.$data['amount']);
                $data_pd['o2o_distributor_freeze_money'] = Db::raw('o2o_distributor_freeze_money-'.$data['amount']);
                break;


            default:
                throw new \think\Exception('预存款更新参数错误', 10006);
                break;
        }
        
        $update = model('o2o_distributor')->editO2oDistributor($data_pd,array('o2o_distributor_id' => $data['o2o_distributor_id']));
        
        if (!$update) {
            throw new \think\Exception('配送员预存款更新异常', 10006);
        }
        $insert = Db::name('o2o_distributor_moneylog')->insertGetId($data_log);
        if (!$insert) {
            throw new \think\Exception('新增预存款记录异常', 10006);
        }
        
        
        return $insert;
    }



}
