<?php

namespace app\common\model;


use GatewayClient\Gateway;
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
class  InstantMessage extends BaseModel {

    public $page_info;
    /**
     * 获取店铺通知列表
     * @access public
     * @author csdeshang
     * @param type $condition
     * @param type $pagesize
     * @param type $order
     * @return type
     */
    public function getInstantMessageList($condition,$pagesize='',$order='instant_message_id desc'){
        if ($pagesize) {
            $result = Db::name('InstantMessage')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('InstantMessage')->where($condition)->order($order)->select()->toArray();
        }
    }
    /**
     * 取得店铺通知信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getInstantMessageInfo($condition = array(), $fields = '*') {
        return Db::name('InstantMessage')->where($condition)->field($fields)->order('instant_message_id desc')->find();
    }
    
    /**
     * 添加店铺通知信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addInstantMessage($data) {
        switch ($data['instant_message_to_type']) {
            case 0:
                $member_model = model('member');
                $member = $member_model->getMemberInfo(array('member_id' => $data['instant_message_to_id'], 'member_state' => 1));
                if (!$member) {
                    throw new \think\Exception(lang('user_not_exist'), 10006);
                }
                if($data['instant_message_from_type']==0 && $member['member_id']==$data['instant_message_from_id']){
                    throw new \think\Exception(lang('chat_self_error'), 10006);
                }
                $to_name = $member['member_name'];
                break;
            case 1:
                $store_model = model('store');
                $store = $store_model->getStoreOnlineInfoByID($data['instant_message_to_id']);
                if (!$store) {
                    throw new \think\Exception(lang('store_not_exist'), 10006);
                }
                if($data['instant_message_from_type']==0 && $store['member_id']==$data['instant_message_from_id']){
                    throw new \think\Exception(lang('chat_self_error'), 10006);
                }
                if($data['instant_message_from_type']==1 && $store['store_id']==$data['instant_message_from_id']){
                    throw new \think\Exception(lang('chat_self_error'), 10006);
                }
                $to_name = $store['store_name'];
                break;
            case 2:
                $live_apply_model = model('live_apply');
                $live_apply = $live_apply_model->getLiveApplyInfo(array(array('live_apply_id' ,'=', $data['instant_message_to_id']), array('live_apply_state' ,'=', 1), array('live_apply_end_time','>', TIMESTAMP)));
                if (!$live_apply) {
                    throw new \think\Exception(lang('live_not_exit'), 10006);
                }
                $to_name = $live_apply['live_apply_id'] . lang('live_room');
                break;
            default:
                throw new \think\Exception(lang('param_error'), 10006);
        }
        $data['instant_message_to_name']=$to_name;
        switch($data['instant_message_type']){
            case 1:
                $goods_id = $data['instant_message'];
                if(!$goods_id){
                    throw new \think\Exception(lang('param_error'), 10006);
                }
                $goods_model=model('goods');
                $goods = $goods_model->getGoodsInfoByID($goods_id);
                if (is_array($goods) && !empty($goods)) {
                    $data['instant_message']= json_encode(array(
                        'goods_id'=>$goods['goods_id'],
                        'goods_name'=>$goods['goods_name'],
                        'goods_price'=>$goods['goods_price'],
                        'goods_image'=>$goods['goods_image'],
                    ));
                }else{
                    throw new \think\Exception(lang('goods_not_exit'), 10006);
                }
                break;
        }
        $instant_message_id=Db::name('InstantMessage')->insertGetId($data);
        if (!$instant_message_id) {
            throw new \think\Exception(lang('send_fail'), 10006);
        }
        
        $data['instant_message_id']=$instant_message_id;
        $data=$this->formatInstantMessage($data);
        return $data;
    }
    
    public function formatInstantMessage($data){
        if($data['instant_message_type']==1){
            $data['instant_message']= json_decode($data['instant_message'],true);
            $data['instant_message']['goods_image_url']= goods_cthumb($data['instant_message']['goods_image']);
        }
        $member_model = model('member');
        $store_model = model('store');
        if($data['instant_message_to_type']==0){
            $member = $member_model->getMemberInfo(array('member_id' => $data['instant_message_to_id'], 'member_state' => 1));
            $data['instant_message_to_avatar']=get_member_avatar($member?$member['member_avatar']:'');
            $data['instant_message_to_info']=$member;
        }elseif($data['instant_message_to_type']==1){
            $store = $store_model->getStoreOnlineInfoByID($data['instant_message_to_id']);
            $data['instant_message_to_avatar']=get_store_logo($store?$store['store_avatar']:'');
            $data['instant_message_to_info']=$store;
        }
        
        if($data['instant_message_from_type']==0){
            $member = $member_model->getMemberInfo(array('member_id' => $data['instant_message_from_id'], 'member_state' => 1));
            $data['instant_message_from_avatar']=get_member_avatar($member?$member['member_avatar']:'');
            $data['instant_message_from_info']=$member;
        }elseif($data['instant_message_from_type']==1){
            $store = $store_model->getStoreOnlineInfoByID($data['instant_message_from_id']);
            $data['instant_message_from_avatar']=get_store_logo($store?$store['store_avatar']:'');
            $data['instant_message_from_info']=$store;
        }
        
        return $data;
    }
    
    /**
     * 编辑店铺通知信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editInstantMessage($data, $condition = array()) {
        return Db::name('InstantMessage')->where($condition)->update($data);
    }

    /**
     * 获取店铺通知数量
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return bool
     */
    public function getInstantMessageCount($condition = array()) {
        return Db::name('InstantMessage')->where($condition)->count();
    }

    
    public function sendInstantMessage($instant_message,$auto=false){
        
        if(!config('ds_config.instant_message_register_url')){
            return ds_callback(false,'未设置直播聊天gateway地址');
        }

        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
        try{
        Gateway::$registerAddress = config('ds_config.instant_message_register_url');
        if($instant_message['instant_message_to_type']==2){
            Gateway::sendToGroup('live_apply_'.$instant_message['instant_message_to_id'], json_encode($instant_message));
        }elseif($instant_message['instant_message_to_type']==0){
            Gateway::sendToUid('0:'.$instant_message['instant_message_to_id'], json_encode($instant_message));
        }
        }catch(\Exception $e){
          return ds_callback(false,$e->getMessage());
        }
        return ds_callback(true);
    }
}

?>
