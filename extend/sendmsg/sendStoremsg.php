<?php
namespace sendmsg;
use think\facade\Db;
class sendStoremsg {
    private $code = '';
    private $store_id = 0;

    /**
     * 设置
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key,$value){
        $this->$key = $value;
    }

    public function send($param = array(),$weixin_param = array(),$ali_param = array(),$ten_param = array()) {
        $msg_tpl = rkcache('storemsgtpl', true);
        if (!isset($msg_tpl[$this->code]) || $this->store_id <= 0) {
            return false;
        }

        $tpl_info = $msg_tpl[$this->code];

        $setting_info = model('storemsgsetting')->getStoremsgsettingInfo(array('storemt_code' => $this->code, 'store_id' => $this->store_id));
        // 发送站内信
        if ($tpl_info['storemt_message_switch'] && ($tpl_info['storemt_message_forced'] || $setting_info['storems_message_switch'])) {
            $message = ds_replace_text($tpl_info['storemt_message_content'],$param);
            $this->sendMessage($message);
        }
        // 发送短消息
        if ($tpl_info['storemt_short_switch'] && $setting_info['storems_short_number'] != '' && ($tpl_info['smt_short_forced'] || $setting_info['storems_short_switch'])) {
            $message = ds_replace_text($tpl_info['storemt_short_content'],$param);
            $smslog_param=array(
                    'ali_template_code'=>$tpl_info['ali_template_code'],
                    'ali_template_param'=>$ali_param,
                    'ten_template_code'=>$tpl_info['ten_template_code'],
                    'ten_template_param'=>$ten_param,
                    'message'=>$message,
                );
            $this->sendShort($setting_info['storems_short_number'], $smslog_param);
        }
        // 发送邮件
        if ($tpl_info['storemt_mail_switch'] && $setting_info['storems_mail_number'] != '' && ($tpl_info['storemt_mail_forced'] || $setting_info['storems_mail_switch'])) {
            $param['site_name'] = config('ds_config.site_name');
            $param['mail_send_time'] = date('Y-m-d H:i:s');
            $subject = ds_replace_text($tpl_info['storemt_mail_subject'],$param);
            $message = htmlspecialchars_decode(ds_replace_text($tpl_info['storemt_mail_content'],$param));
            $this->sendMail($setting_info['storems_mail_number'], $subject, $message);
        }
        // 发送微信模板消息
        if(!empty($weixin_param) && $tpl_info['storemt_weixin_switch'] && $tpl_info['storemt_weixin_code'] && ($tpl_info['storemt_weixin_forced'] || $setting_info['storems_weixin_switch'])){
            $param['site_name'] = config('ds_config.site_name');
            $member_id=Db::name('store')->where(array('store_id'=>$this->store_id))->value('member_id');
            if($member_id){
                $openid=Db::name('member')->where(array('member_id'=>$member_id))->value('member_h5_wxopenid');
                if($openid){
                    $tm_data = array(
                        "first" => array(
                            "value" => $tpl_info['storemt_name'],
                            "color" => "#ff7007"
                        ),
                        "remark" => array(
                            "value" => ds_replace_text($tpl_info['storemt_short_content'],$param),
                            "color" => "#333"
                        )
                    );
                    $wechat_model=model('wechat');
                    $wechat_model->getOneWxconfig();
                    $wechat_model->sendMessageTemplate($openid, $tpl_info['storemt_weixin_code'], $weixin_param['url'], array_merge($tm_data,$weixin_param['data']));
                }
            }    
        }        
    }

    /**
     * 发送站内信
     * @param unknown $message
     */
    private function sendMessage($message) {
        $insert = array();
        $insert['storemt_code'] = $this->code;
        $insert['store_id'] = $this->store_id;
        $insert['storemsg_content'] = $message;
        model('storemsg')->addStoremsg($insert);
    }

    /**
     * 发送短消息
     * @param unknown $number
     * @param unknown $message
     */
    private function sendShort($number, $message) {
        model('smslog')->sendSms($number, $message,'','','0','',true);
    }

    /**
     * 发送邮件
     * @param unknown $number
     * @param unknown $subject
     * @param unknown $message
     */
    private function sendMail($number, $subject, $message) {
        // 计划任务代码
        $insert = array();
        $insert['mailcron_address'] = $number;
        $insert['mailcron_subject'] = $subject;
        $insert['mailcron_contnet'] = $message;
        model('mailcron')->addMailCron($insert);
    }
}
