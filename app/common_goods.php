<?php

/**
 * 取得商品缩略图的完整URL路径，接收商品信息数组，返回所需的商品缩略图的完整URL
 *
 * @param array $goods 商品信息数组
 * @param string $type 缩略图类型  值为240,480,1280
 * @return string
 */
function goods_thumb($goods = array(), $type = '') {
    $type_array = explode(',_', ltrim(GOODS_IMAGES_EXT, '_'));
    if ($type == 'default') {
        $type = '';
    } elseif (!in_array($type, $type_array)) {
        $type = '240';
    }

    if (empty($goods)) {
        return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
    }
    if (array_key_exists('apic_cover', $goods)) {
        $goods['goods_image'] = $goods['apic_cover'];
    }

    if (empty($goods['goods_image'])) {
        return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
    }
    $file = $goods['goods_image'];
    $fname = basename($file);

    //对象存储文件
    $upload_type = explode('_', $fname);
    if (in_array($upload_type['0'], array('alioss', 'cos'))) {
        $store_id = $upload_type['1'];
        $date= substr($upload_type['2'], 0, 8);
        $aliendpoint_type = config('ds_config.aliendpoint_type');
        if ($aliendpoint_type) {
            $image_url= HTTP_TYPE . config('ds_config.alioss_endpoint') . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file;
        } else {
            $image_url= 'https://' . config('ds_config.alioss_bucket') . '.' . config('ds_config.alioss_endpoint') . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file;
        }
        
        $param=array();
        if($type){
            $param[]='resize,m_pad,w_'.$type.',h_'.$type;
        }
        //是否有水印
        $storewatermark_model=model('storewatermark');
        $wm_arr=$storewatermark_model->getOneStorewatermarkByStoreId($store_id);
        if(!empty($wm_arr)){
            if($wm_arr['swm_image_name']){//有图片水印
                $temp=explode('_', $wm_arr['swm_image_name']);
                $pos_array=['se','nw','north','ne','west','center','east','sw','south','se'];
                if(in_array($temp['0'], array('alioss', 'cos'))){
                    $pos='se';
                    if(isset($pos_array[$wm_arr['swm_image_pos']])){
                        $pos=$pos_array[$wm_arr['swm_image_pos']];
                    }
                    $param[]='watermark,image_'.rtrim(strtr(base64_encode(ATTACH_WATERMARK.'/'.$wm_arr['swm_image_name']), '+/', '-_'), '=').',t_'.$wm_arr['swm_image_transition'].',g_'.$pos.'';
                }
            }
            if($wm_arr['swm_text']){//有文字水印
                $pos='se';
                if(isset($pos_array[$wm_arr['swm_text_pos']])){
                    $pos=$pos_array[$wm_arr['swm_text_pos']];
                }
                $param[]='watermark,size_'.$wm_arr['swm_text_size'].',text_'.rtrim(strtr(base64_encode($wm_arr['swm_text']), '+/', '-_'), '=').',color_'.trim($wm_arr['swm_text_color'],'#').',g_'.$pos.'';
            }
        }
        return $image_url.(!empty($param)?('?x-oss-process=image/'.implode('/', $param)):'');
    }
    //取店铺ID
    if (preg_match('/^(\d+_)/', $fname)) {
        $store_id = substr($fname, 0, strpos($fname, '_'));
    } else {
        $store_id = $goods['store_id'];
    }
    $date= substr($fname, strpos($fname, '_')+1, 8);

    $thumb_host = UPLOAD_SITE_URL . '/' . ATTACH_GOODS;
    if (!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . str_ireplace('.', '_' . $type . '.', $file))) {
        if (!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file)) {
            return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
        } else {
            return $thumb_host . '/' . $store_id . '/' . $date . '/' . $file;
        }
    }

    return $thumb_host . '/' . $store_id . '/' . $date . '/' . ($type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file));
}

/**
 * 取得商品缩略图的完整URL路径，接收图片名称与店铺ID
 *
 * @param string $file 图片名称
 * @param string $type 缩略图尺寸类型，值为240,480,1280
 * @param mixed $store_id 店铺ID 如果传入，则返回图片完整URL,如果为假，返回系统默认图
 * @return string
 */
function goods_cthumb($file, $type = '', $store_id = false) {
    $type_array = explode(',_', ltrim(GOODS_IMAGES_EXT, '_'));
    if ($type == 'default') {
        $type = '';
    } elseif (!in_array($type, $type_array)) {
        $type = '240';
    }
    if (empty($file)) {
        return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
    }
    $fname = basename($file);
    // 取店铺ID
    $upload_type = explode('_', $fname);
    //外网存储图片
    if (in_array($upload_type['0'], array('alioss', 'cos'))) {
        $store_id = $upload_type['1'];
        $date= substr($upload_type['2'], 0, 8);
        $aliendpoint_type = config('ds_config.aliendpoint_type');
        if ($aliendpoint_type) {
            $image_url= HTTP_TYPE . config('ds_config.alioss_endpoint') . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file;
        } else {
            $image_url= 'https://' . config('ds_config.alioss_bucket') . '.' . config('ds_config.alioss_endpoint') . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file;
        }
        $param=array();
        if($type){
            $param[]='resize,m_pad,w_'.$type.',h_'.$type;
        }
        //是否有水印
        $storewatermark_model=model('storewatermark');
        $wm_arr=$storewatermark_model->getOneStorewatermarkByStoreId($store_id);
        if(!empty($wm_arr)){
            if($wm_arr['swm_image_name']){//有图片水印
                $temp=explode('_', $wm_arr['swm_image_name']);
                $pos_array=['se','nw','north','ne','west','center','east','sw','south','se'];
                if(in_array($temp['0'], array('alioss', 'cos'))){
                    $pos='se';
                    if(isset($pos_array[$wm_arr['swm_image_pos']])){
                        $pos=$pos_array[$wm_arr['swm_image_pos']];
                    }
                    $param[]='watermark,image_'.rtrim(strtr(base64_encode(ATTACH_WATERMARK.'/'.$wm_arr['swm_image_name']), '+/', '-_'), '=').',t_'.$wm_arr['swm_image_transition'].',g_'.$pos.'';
                }
            }
            if($wm_arr['swm_text']){//有文字水印
                $pos='se';
                if(isset($pos_array[$wm_arr['swm_text_pos']])){
                    $pos=$pos_array[$wm_arr['swm_text_pos']];
                }
                $param[]='watermark,size_'.$wm_arr['swm_text_size'].',text_'.rtrim(strtr(base64_encode($wm_arr['swm_text']), '+/', '-_'), '=').',color_'.trim($wm_arr['swm_text_color'],'#').',g_'.$pos.'';
            }
        }
        return $image_url.(!empty($param)?('?x-oss-process=image/'.implode('/', $param)):'');
    }

    if ($store_id === false || !is_numeric($store_id)) {
        $store_id = substr($fname, 0, strpos($fname, '_'));
    }
    $date= substr($fname, strpos($fname, '_')+1, 8);
    
    // 本地存储时，增加判断文件是否存在，用默认图代替
    $thumb_host = UPLOAD_SITE_URL . '/' . ATTACH_GOODS;
    if (!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . ($type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file)))) {
        if (!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_GOODS . '/' . $store_id . '/' . $date . '/' . $file)) {
            return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
        } else {
            return $thumb_host . '/' . $store_id . '/' . $date . '/' . $file;
        }
        return ds_get_pic(ATTACH_COMMON,substr_replace(config('ds_config.default_goods_image'),"_".$type.".png",-4));
    }

    return $thumb_host . '/' . $store_id . '/' . $date . '/' . ($type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file));
}

/**
 * 商品二维码
 * @param array $goods_info
 * @return string
 */
function goods_qrcode($goods_info)
{
    return HOME_SITE_URL.'/qrcode?url='. urlencode(config('ds_config.h5_site_url').'/pages/home/goodsdetail/GoodsDetail?goods_id='.$goods_info['goods_id']);
}

/**
 * 商品二维码
 * @param array $goods_info
 * @return string
 */
function store_qrcode($store_id)
{
    return HOME_SITE_URL.'/qrcode?url='. urlencode(config('ds_config.h5_site_url').'/pages/home/storedetail/Storedetail?id='.$store_id);
}


/**
 * 取得买家缩略图的完整URL路径
 *
 * @param string $imgurl 商品名称
 * @param string $type 缩略图类型  值为240,1024
 * @return string
 */
function sns_thumb($image_name = '', $type = '')
{
    if (!in_array($type, array('240', '1024')))
        $type = '240';
    if (empty($image_name)) {
        return ds_get_pic(ATTACH_COMMON,config('ds_config.default_goods_image'));
    }

    $upload_type = explode('_', $image_name);
    if (in_array($upload_type['0'], array('alioss', 'cos'))) {
        $member_id=$upload_type['1'];
    }else{
        $member_id=$upload_type['0'];
    }
    $url=ds_get_pic(ATTACH_MALBUM . DIRECTORY_SEPARATOR . $member_id,$image_name);
    if (!$url) {
        return ds_get_pic(ATTACH_COMMON,config('ds_config.default_goods_image'));
    }
    return $url;
}

/**
 * 取得积分商品缩略图的完整URL路径
 *
 * @param string $imgurl 商品名称
 * @param string $type 缩略图类型  值为small
 * @return string
 */
function pointprod_thumb($image_name = '', $type = '')
{

    if (empty($image_name)) {
        return ds_get_pic(ATTACH_COMMON,config('ds_config.default_goods_image'));
    }
    $url=ds_get_pic(ATTACH_POINTPROD,$image_name);
    if (!$url) {
        return ds_get_pic(ATTACH_COMMON,config('ds_config.default_goods_image'));
    }
    return $url;
}

/**
 * 取得品牌图片
 *
 * @param string $image_name
 * @return string
 */
function brand_image($image_name = '')
{
    if ($image_name != '') {
        return ds_get_pic( ATTACH_BRAND , $image_name);
    }
    return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/default_brand_image.gif';
}

/**
 * 取得分类图片
 *
 * @param string $image_name
 * @return string
 */
function goodsclass_image($gc_image)
{
	if (empty($gc_image)) {
	    return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/default_goodsclass_image.jpg';
	}
	$url=ds_get_pic(ATTACH_COMMON,$gc_image);
	if (!$url) {
	    return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/default_goodsclass_image.jpg';
	}
	return $url;
}

/**
 * 取得订单状态文字输出形式
 *
 * @param array $order_info 订单数组
 * @return string $order_state 描述输出
 */
function get_order_state($order_info)
{
    switch ($order_info['order_state']) {
        case ORDER_STATE_CANCEL:
            $order_state = lang('order_state_cancel');
            break;
        case ORDER_STATE_NEW:
            $order_state = lang('order_state_new');
            break;
        case ORDER_STATE_PAY:
            $order_state = lang('order_state_pay');
            break;
	case ORDER_STATE_RECEIPT:
            $order_state = lang('order_state_receipt');
            break;
        case ORDER_STATE_DELIVER:
            $order_state = lang('order_state_deliver');
            break;		
        case ORDER_STATE_SEND:
            $order_state = lang('order_state_send');
            break;
        case ORDER_STATE_SUCCESS:
            $order_state = lang('order_state_success');
            if(isset($order_info['refund_state']) && $order_info['refund_state'] == 2 && $order_info['order_refund_lock_state'] ==0){
                //全部退款
                $order_state .= '(退款成功)';
            }elseif(isset($order_info['refund_state']) && $order_info['refund_state'] == 1 && $order_info['order_refund_lock_state'] ==0){
                //部分退款
                $order_state .= '(有商品退款)';
            }
            break;
    }
    if($order_info['order_refund_lock_state']>0){
        $order_state .= '(退款待处理)';
    }
    return $order_state;
}

/**
 * 取得退款文字输出形式,针对订单表字段
 *
 * @param array $refund_state
 * @return string 描述输出
 */
function get_order_refund_state($refund_state) {
    return str_replace(array('0', '1', '2'), array('', '部分退款', '全部退款'), $refund_state);
}

/**
 * 取得订单支付类型文字输出形式
 *
 * @param array $payment_code
 * @return string
 */
function get_order_payment_name($payment_code)
{
    return str_replace(array('offline', 'online', 'alipay', 'alipay_h5', 'alipay_app', 'wxpay_native', 'wxpay_jsapi', 'wxpay_h5', 'wxpay_app', 'wxpay_minipro', 'predeposit'), array('货到付款', '在线付款', '支付宝PC支付', '支付宝手机支付', '支付宝APP支付', '微信扫码支付', '微信公众号支付', '微信H5支付', '微信APP支付', '小程序支付', '站内余额支付'), $payment_code);
}

/**
 * 取得订单商品销售类型文字输出形式
 *
 * @param array $goods_type
 * @return string 描述输出
 */
function get_order_goodstype($goods_type)
{
    return str_replace(array('1', '3', '4', '5'), array('', '秒杀', '优惠套装', '赠品'), $goods_type);
}

//实物订单退款退货文字输出
function get_refundreturn_seller_state($refundreturn_seller_state){
    return str_replace(array('1', '2', '3'), array('待审核', '同意', '不同意'), $refundreturn_seller_state);
}

//实物订单退款退货文字输出
function get_refundreturn_admin_state($refundreturn_admin_state){
    return str_replace(array('1', '2', '3', '4'), array('商家处理中', '待平台处理', '已完成', '不同意'), $refundreturn_admin_state);
}

/**
 * 取得广告图片
 *
 * @param string $image_name
 * @return string
 */
function adv_image($image_name = '')
{
    if ($image_name != '') {
        return ds_get_pic( ATTACH_ADV , $image_name);
    }
}

?>
