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
class  O2oErrandOrder extends AdminControl {

    const EXPORT_SIZE = 1000;

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/o2o_errand.lang.php');
    }

    public function index() {
        $order_model = model('o2o_errand_order');
        $condition=$this->_get_condition();
        $order_list = $order_model->getO2oErrandOrderList($condition,'*', 10);
        View::assign('show_page', $order_model->page_info->render());
        
        $logic_errand_order = model('errandorder','logic');
        
        foreach ($order_list as $order_id => $order_info) {
            $order_list[$order_id]['o2o_errand_order_state_text'] = $order_model->getO2oErrandOrderStateText($order_info['o2o_errand_order_state']);
            $btn_list = $logic_errand_order->getO2oErrandOrderBtn($order_info, 'admin');
            $order_list[$order_id] = array_merge($order_list[$order_id], $btn_list);
        }

        View::assign('order_list', $order_list);
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        $this->setAdminCurItem('add');
        return View::fetch('index');
    }

    /**
     * 平台订单状态操作
     *
     */
    public function change_state() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('miss_order_number'));
        }
        $order_model = model('o2o_errand_order');;

        //获取订单详细
        $condition = array();
        $condition[]=array('o2o_errand_order_id','=',$order_id);
        $order_info = $order_model->getO2oErrandOrderInfo($condition);

        $state_type = input('param.state_type');
        if ($state_type == 'cancel') {
            $result = $this->_order_cancel($order_info);
            if (!$result['code']) {
                $this->error($result['msg']);
            } else {
                ds_json_encode(10000, $result['msg']);
            }
        } elseif ($state_type == 'receive_pay') {
            $result = $this->_order_receive_pay($order_info, input('post.'));
            if (!$result['code']) {
                $this->error($result['msg']);
            } else {
                dsLayerOpenSuccess($result['msg'],'O2oErrandOrder/index');
            }
        }
    }

    /**
     * 系统取消订单
     */
    private function _order_cancel($order_info) {
        $order_id = $order_info['o2o_errand_order_id'];
        $logic_errand_order = model('errandorder','logic');
        $result=$logic_errand_order->cancelO2oErrandOrder(array('o2o_errand_order_id'=>$order_id),'admin');
        if(!$result['code']){
            ds_json_encode(10001, $result['msg']);
        }else{
            $this->log(lang('order_log_cancel') . ',' . lang('order_number') . ':' . $order_info['o2o_errand_order_sn'], 1);
        }
        return $result;
    }

    /**
     * 系统收到货款
     * @throws Exception
     */
    private function _order_receive_pay($order_info, $post) {
        $order_id = $order_info['o2o_errand_order_id'];
        $order_model = $order_model = model('o2o_errand_order');
        $logic_errand_order = model('errandorder','logic');
        $btn_list=$logic_errand_order->getO2oErrandOrderBtn($order_info,'admin');
        if (!$btn_list['if_pay']) {
            return ds_callback(false, '无权操作');
        }

        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            //显示支付接口列表
            $payment_list = model('payment')->getPaymentOpenList();
            //去掉预存款和货到付款
            foreach ($payment_list as $key => $value) {
                if ($value['payment_code'] == 'predeposit' || $value['payment_code'] == 'offline') {
                    unset($payment_list[$key]);
                }
            }
            View::assign('payment_list', $payment_list);
            echo View::fetch('receive_pay');
            exit;
        } else {
            Db::startTrans();
            try {
                $logic_errand_order->payO2oErrandOrder($order_info['o2o_errand_order_sn'], $post['payment_code'], $post['trade_no'], 'admin', $post['payment_time']);
                Db::commit();
                $this->log('将订单改为已收款状态,' . lang('order_number') . ':' . $order_info['o2o_errand_order_sn'], 1);
                return ds_callback(true, '修改成功');
            } catch (\Exception $e) {
                Db::rollback();
                return ds_callback(false, $e->getMessage());
            }
        }
    }

    /**
     * 查看订单
     *
     */
    public function show_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('miss_order_number'));
        }
        $order_model = $order_model = model('o2o_errand_order');;
        $order_info = $order_model->getO2oErrandOrderInfo(array('o2o_errand_order_id' => $order_id));
        $order_info['o2o_errand_order_state_text']= $order_model->getO2oErrandOrderStateText($order_info['o2o_errand_order_state']);
        View::assign('order_info', $order_info);
        return View::fetch('show_order');
    }

    public function map(){
        $order_type=input('param.order_type');
        if(!in_array($order_type,array('o2o_errand_order'))){
            $this->error(lang('param_error'));
        }
        
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        if($order_type=='o2o_errand_order'){
            $order_id=input('param.order_id');
            if(!$order_id){
                $this->error(lang('param_error'));
            }
            $order_model = $order_model = model('o2o_errand_order');;
            $temp = $order_model->getO2oErrandOrderInfo(array('o2o_errand_order_id' => $order_id));
            if(!$temp){
                $this->error(lang('param_error'));
            }
            $order_info=array(
                'order_type'=>$order_type,
                'order_id'=>$temp['o2o_errand_order_id'],
                'order_sn'=>$temp['o2o_errand_order_sn'],
                'reciver_name'=>$temp['o2o_errand_order_deliver_name'],
                'reciver_address'=>$temp['o2o_errand_order_deliver_address'],
                'reciver_phone'=>$temp['o2o_errand_order_deliver_phone'],
                'order_distance'=>$temp['o2o_errand_order_distance'],
                'order_lng'=>$temp['o2o_errand_order_deliver_lng'],
                'order_lat'=>$temp['o2o_errand_order_deliver_lat'],
            );
        }
        View::assign('order_info', $order_info);
        View::assign('store_longitude', $temp['o2o_errand_order_pickup_lng']);
        View::assign('store_latitude', $temp['o2o_errand_order_pickup_lat']);
        return View::fetch();
    }

    /*
     * 配送员列表
     */

    public function get_distributor_list() {
        $order_type=input('param.order_type');
        if(!in_array($order_type,array('o2o_errand_order'))){
            ds_json_encode(10001, lang('param_error'));
        }
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $order_model = $order_model = model('o2o_errand_order');;
        $order_info = $order_model->getO2oErrandOrderInfo(array('o2o_errand_order_id' => $order_id));
        if (empty($order_info)) {
            ds_json_encode(10001, lang('param_error'));
        }
        //配送员列表
        $o2o_distributor_model = model('o2o_distributor');
        $condition = array();
        //上线配送员
        $condition[] = array('o2o_distributor_state','=',1);
        $condition[] = array('store_id','=',0);
        $condition[] = array('o2o_distributor_lng','>',0);
        $condition[] = array('o2o_distributor_lat','>',0);
        
        $order = 'o2o_distributor_addtime asc';
        $field = '*';
        //如果传了位置经纬度，则按照经纬度的距离排序
        $lat = floatval(input('param.lat'));
        $lng = floatval(input('param.lng'));
        if($lat>0 && $lng>0){
            $field.= ',(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-o2o_distributor_lat)/360),2)+COS(PI()*' . $lat . '/180)* COS(o2o_distributor_lat * PI()/180)*POW(SIN(PI()*(' . $lng . '-o2o_distributor_lng)/360),2)))) as poi_distance';
            $order = 'poi_distance asc';
        }
        
        $o2o_distributor_list = $o2o_distributor_model->getO2oDistributorList($condition, $field, 10,$order);
        foreach ($o2o_distributor_list as $key => $val) {
            $o2o_distributor_list[$key]['o2o_distributor_avatar'] = get_o2o_distributor_file($val['o2o_distributor_avatar'],'avatar');
            $o2o_distributor_list[$key]['count_wait'] = $order_model->getO2oErrandOrderCount(array(array('o2o_errand_order_receipt_time','>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id' ,'=', $val['o2o_distributor_id']), array('o2o_errand_order_state','in', [ORDER_STATE_DELIVER, ORDER_STATE_SEND])));
            $o2o_distributor_list[$key]['count_complete'] = $order_model->getO2oErrandOrderCount(array(array('o2o_errand_order_receipt_time','>', strtotime(date('Y-m-d 0:0:0'))), array('o2o_distributor_id' ,'=', $val['o2o_distributor_id']), array('o2o_errand_order_state','in', [ORDER_STATE_SUCCESS])));
        }
        ds_json_encode(10000, '', $o2o_distributor_list);
    }

    
    public function deliver_order() {
        $order_id = intval(input('post.order_id'));
        $o2o_distributor_id = intval(input('post.o2o_distributor_id'));
        if (!$order_id || !$o2o_distributor_id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $o2o_distributor_model=model('o2o_distributor');
        $o2o_distributor_info=$o2o_distributor_model->getO2oDistributorInfo(array('o2o_distributor_id' => $o2o_distributor_id, 'o2o_distributor_state' => 1));
        if(!$o2o_distributor_info){
            ds_json_encode(10001, '配送员不存在');
        }
        $order_model = model('o2o_errand_order');
        Db::startTrans();
        try {
            $condition = array();
            $condition[] = array('o2o_errand_order_state','=',ORDER_STATE_PAY);
            $condition[] = array('o2o_errand_order_id','=',$order_id);
            if (!$order_model->editO2oErrandOrder(array('o2o_distributor_id' => $o2o_distributor_info['o2o_distributor_id'], 'o2o_distributor_name' => $o2o_distributor_info['o2o_distributor_name'], 'o2o_distributor_realname' => $o2o_distributor_info['o2o_distributor_realname'], 'o2o_distributor_phone' => $o2o_distributor_info['o2o_distributor_phone'], 'o2o_errand_order_state' => ORDER_STATE_DELIVER, 'o2o_errand_order_receipt_time' => TIMESTAMP), $condition)) {
                throw new \think\Exception('手慢无，被别人抢了', 10006);
            }

            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        ds_json_encode(10000, '接单成功');
    }
    
    
    
    /**
     * 导出
     *
     */
    public function export_step1() {

        $order_model = model('o2o_errand_order');
        $condition=$this->_get_condition();

        if (!is_numeric(input('param.page'))) {
            $count = $order_model->getO2oErrandOrderCount($condition);
            $export_list = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $export_list[$i] = $limit1 . ' ~ ' . $limit2;
                }
                View::assign('export_list', $export_list);
                return View::fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $order_model->getO2oErrandOrderList($condition, '*', '', 'o2o_errand_order_id desc', self::EXPORT_SIZE);
                $this->createExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.page') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $order_model->getO2oErrandOrderList($condition, '*', $limit2, 'o2o_errand_order_id desc');
            $this->createExcel($data);
        }
    }
    
    private function _get_condition(){
        $condition = array();

        $order_sn = input('param.order_sn');
        if ($order_sn) {
            $condition[]=array('o2o_errand_order_sn','=',$order_sn);
        }

        $order_state = input('param.order_state');
        if (in_array($order_state, array('0', '10', '20', '30', '40'))) {
            $condition[]=array('o2o_errand_order_state','=',$order_state);
        }

        $buyer_name = input('param.buyer_name');
        if ($buyer_name) {
            $condition[]=array('member_name','=',$buyer_name);
        }
        $query_start_time = input('param.query_start_time');
        $query_end_time = input('param.query_end_time');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_time);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_time);
        $start_unixtime = $if_start_time ? strtotime($query_start_time) : null;
        $end_unixtime = $if_end_time ? strtotime($query_end_time) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition[]=array('o2o_errand_order_add_time','between',array($start_unixtime, $end_unixtime));
        }
        return $condition;
    }

    /**
     * 生成excel
     *
     * @param array $data
     */
    private function createExcel($data = array()) {
        Lang::load(base_path() .'admin/lang/'.config('lang.default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('order_number'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('buyer_name'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('o2o_distributor'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '取货点名称');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '取货点电话');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '取货点地址');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '收货点名称');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '收货点电话');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '收货点地址');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('product_info'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '价值');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '重量');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('order_time'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '基础运费');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '重量附加费');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '特殊时段费');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => '小费');
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('order_total_price'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('order_state'));
        //data
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => 'DS' . $v['o2o_errand_order_sn']);
            $tmp[] = array('data' => $v['member_name']);
            $tmp[] = array('data' => $v['o2o_distributor_name']);
            $tmp[] = array('data' => $v['o2o_errand_order_pickup_name']);
            $tmp[] = array('data' => $v['o2o_errand_order_pickup_phone']);
            $tmp[] = array('data' => $v['o2o_errand_order_pickup_address']);
            $tmp[] = array('data' => $v['o2o_errand_order_deliver_name']);
            $tmp[] = array('data' => $v['o2o_errand_order_deliver_phone']);
            $tmp[] = array('data' => $v['o2o_errand_order_deliver_address']);
            $tmp[] = array('data' => $v['o2o_errand_order_detail']);
            $tmp[] = array('data' => '不超过'.lang('currency').$v['o2o_errand_order_goods_price']);
            $tmp[] = array('data' => $v['o2o_errand_order_weight'].'公斤');
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['o2o_errand_order_add_time']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['o2o_errand_order_distance_price']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['o2o_errand_order_weight_price']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['o2o_errand_order_time_price']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['o2o_errand_order_gratuity']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['o2o_errand_order_amount']));
            $tmp[] = array('data' => model('o2o_errand_order')->getO2oErrandOrderStateText($v['o2o_errand_order_state']));
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_od_order'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_od_order'), CHARSET) . input('param.page') . '-' . date('Y-m-d-H', TIMESTAMP));
    }
}
