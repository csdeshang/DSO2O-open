<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
class  Goods extends BaseGoods {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/goods.lang.php');
    }

    /**
     * 单个商品信息页
     */
    public function index() {
        $goods_id = intval(input('param.goods_id'));

        // 商品详细信息
        $goods_model = model('goods');
        $goods_detail = $goods_model->getGoodsDetail($goods_id);
        $goods_info = $goods_detail['goods_info'];

        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'),HOME_SITE_URL);
        }
        // 获取销量 BEGIN
        $rs = $goods_model->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']));
        $count = 0;
        foreach ($rs as $v) {
            $count += $v['goods_salenum'];
        }
        $goods_info['goods_salenum'] = $count;
        // 获取销量 END
        $this->getStoreInfo($goods_info['store_id']);

        // 看了又看（同分类本店随机商品）
        $goods_rand_list = model('goods')->getGoodsGcStoreRandList($goods_info['gc_id_1'], $goods_info['store_id'], $goods_info['goods_id'], 2);

        View::assign('goods_rand_list', $goods_rand_list);

        View::assign('spec_list', $goods_detail['spec_list']);
        View::assign('spec_image', $goods_detail['spec_image']);
        View::assign('goods_image', $goods_detail['goods_image']);
        View::assign('mansong_info', $goods_detail['mansong_info']);
        View::assign('gift_array', $goods_detail['gift_array']);

        $inform_switch = true;
        // 检测商品是否下架,检查是否为店主本人
        if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1 || $goods_info['store_id'] == session('store_id')) {
            $inform_switch = false;
        }
        View::assign('inform_switch', $inform_switch);


        $inviter_model=model('inviter');
        $goods_info['inviter_money']=0;
        if(config('ds_config.inviter_show') && config('ds_config.inviter_open') && $goods_info['inviter_open'] && session('member_id') && $inviter_model->getInviterInfo('i.inviter_id='.session('member_id').' AND i.inviter_state=1')){
            $inviter_money=round($goods_info['inviter_ratio'] / 100 * $goods_info['goods_price'] * floatval(config('ds_config.inviter_ratio_1')) / 100, 2);
            if($inviter_money>0){
                $goods_info['inviter_money']=$inviter_money;
            }
        }
       // halt($goods_info);
        View::assign('goods', $goods_info);



        $storeplate_model = model('storeplate');
        // 顶部关联版式
        if ($goods_info['plateid_top'] > 0) {
            $plate_top = $storeplate_model->getStoreplateInfoByID($goods_info['plateid_top']);
            View::assign('plate_top', $plate_top);
        }
        // 底部关联版式
        if ($goods_info['plateid_bottom'] > 0) {
            $plate_bottom = $storeplate_model->getStoreplateInfoByID($goods_info['plateid_bottom']);
            View::assign('plate_bottom', $plate_bottom);
        }
        View::assign('store_id', $goods_info['store_id']);

        //推荐商品 
        $goods_commend_list = $goods_model->getGoodsOnlineList(array(array('store_id' ,'=', $goods_info['store_id']), array('goods_commend' ,'=', 1)), 'goods_id,goods_name,goods_advword,goods_image,store_id,goods_price', 0, '', 5, 'goods_commonid');
        View::assign('goods_commend', $goods_commend_list);


        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name']);
        View::assign('nav_link_list', $nav_link_list);

        //评价信息
        $goods_evaluate_info = model('evaluategoods')->getEvaluategoodsInfoByGoodsID($goods_id);
        View::assign('goods_evaluate_info', $goods_evaluate_info);

        //SEO 设置
        $seo_param = array();
        $seo_param['name'] = $goods_info['goods_name'];
		$seo_param['key'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing(htmlspecialchars_decode($goods_info['goods_body']));
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        return View::fetch($this->template_dir . 'goods');
    }

    /*
     * 获取商品规格
     */
    public function getSpecList(){
        $goods_commonid = intval(input('param.goods_commonid'));
        if(!$goods_commonid){
            ds_json_encode(10001,lang('param_error'));
        }
        // 商品详细信息
        $goods_model = model('goods');
        $goods_common = $goods_model->getGoodsCommonInfoByID($goods_commonid);
        if (empty($goods_common)) {
            ds_json_encode(10001,lang('goods_index_goods_not_exists'));
        }
        $goods_detail=array(
            'spec_value'=> unserialize($goods_common['spec_value']),
            'spec_name'=> unserialize($goods_common['spec_name']),
//            'goods_spec'=> unserialize($goods_common['goods_spec']),
        );
        $spec_list=array();
        $spec_array=$goods_model->getGoodsSpecListByCommonId($goods_commonid);
        $spec_image = array();      // 各规格商品主图，规格颜色图片使用
        foreach ($spec_array as $key => $value) {
            $s_array = unserialize($value['goods_spec']);
            $tmp_array = array();
            if (!empty($s_array) && is_array($s_array)) {
                foreach ($s_array as $k => $v) {
                    $tmp_array[] = $k;
                }
            }
            sort($tmp_array);
            $spec_sign = implode('|', $tmp_array);
            $tpl_spec = $value;
            $tpl_spec['sign'] = $spec_sign;
            $spec_list[] = $tpl_spec;
            $spec_image[$value['color_id']] = goods_thumb($value, 240);
        }
        $goods_detail['spec_image']=$spec_image;
        
        ds_json_encode(10000,'',array('goods_detail'=>$goods_detail,'spec_list'=>$spec_list));
    }
    
    /**
     * 记录浏览历史
     */
    public function addbrowse() {
        $goods_id = intval(input('param.gid'));
        model('goodsbrowse')->addViewedGoods($goods_id, session('member_id'), session('store_id'));
        exit();
    }

    /**
     * 商品评论
     */
    public function comments() {
        $goods_id = intval(input('param.goods_id'));
        $type = input('param.type');
        $this->_get_comments($goods_id, $type, 10);
        echo View::fetch($this->template_dir . 'goods_comments');
    }

    /**
     * 商品评价详细页
     */
    public function comments_list() {
        $goods_id = intval(input('param.goods_id'));

        // 商品详细信息
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        // 验证商品是否存在
        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'));
        }
        View::assign('goods', $goods_info);

        $this->getStoreInfo($goods_info['store_id']);

        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name'], 'link' => url('Goods/index', ['goods_id' => $goods_id]));
        $nav_link_list[] = array('title' => lang('goods_index_evaluation'));
        View::assign('nav_link_list', $nav_link_list);

        //评价信息
        $goods_evaluate_info = model('evaluategoods')->getEvaluategoodsInfoByGoodsID($goods_id);
        View::assign('goods_evaluate_info', $goods_evaluate_info);

        //SEO 设置
        $seo_param = array();
        $seo_param['name'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing($goods_info['goods_name']);
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        $this->_get_comments($goods_id, input('param.type'), 20);

        return View::fetch($this->template_dir . 'comments_list');
    }

    private function _get_comments($goods_id, $type, $page) {
        $condition = array();
        $condition[]=array('geval_goodsid','=',$goods_id);
        switch ($type) {
            case '1':
                $condition[]=array('geval_scores','in', '5,4');
                View::assign('type', '1');
                break;
            case '2':
                $condition[]=array('geval_scores','in', '3,2');
                View::assign('type', '2');
                break;
            case '3':
                $condition[]=array('geval_scores','in', '1');
                View::assign('type', '3');
                break;
            default:
                View::assign('type','');
                break;
        }

        //查询商品评分信息
        $evaluategoods_model = model('evaluategoods');
        $goodsevallist = $evaluategoods_model->getEvaluategoodsList($condition, $page);

        View::assign('goodsevallist', $goodsevallist);
        View::assign('show_page', $evaluategoods_model->page_info->render());
    }

    /**
     * 销售记录
     */
    public function salelog() {
        $goods_id = intval(input('param.goods_id'));
        $order_model = model('order');
        $sales = $order_model->getOrderAndOrderGoodsSalesRecordList(array(array('order_goods.goods_id' ,'=', $goods_id)), 'order_goods.*, order.buyer_name, order.add_time', 10);

        View::assign('show_page', $order_model->page_info->render());
        View::assign('sales', $sales);
        View::assign('order_type', array(2 => lang('ds_xianshi_rob'), 3 => lang('ds_xianshi_flag'), '4' => lang('ds_xianshi_suit')));
        echo View::fetch($this->template_dir . 'goods_salelog');
    }

    /**
     * 产品咨询
     */
    public function consulting() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'), '', 'html', 'error');
        }

        //得到商品咨询信息
        $consult_model = model('consult');
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);

        $ctid = intval(input('param.ctid'));
        if ($ctid > 0) {
            $condition[] = array('consulttype_id','=',$ctid);
        }
        $consult_list = $consult_model->getConsultList($condition, '*', '10');
        View::assign('consult_list', $consult_list);

        // 咨询类型
        $consult_type = rkcache('consulttype', true);
        View::assign('consult_type', $consult_type);

        View::assign('consult_able', $this->checkConsultAble());
        echo View::fetch($this->template_dir . 'goods_consulting');
    }

    /**
     * 产品咨询
     */
    public function consulting_list() {

        View::assign('hidden_nctoolbar', 1);
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'));
        }

        // 商品详细信息
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        // 验证商品是否存在
        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'));
        }
        View::assign('goods', $goods_info);

        $this->getStoreInfo($goods_info['store_id']);

        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name'], 'link' => url('Goods/index', ['goods_id' => $goods_id]));
        $nav_link_list[] = array('title' => lang('goods_commodity_consulting'));
        View::assign('nav_link_list', $nav_link_list);

        //得到商品咨询信息
        $consult_model = model('consult');
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        if (intval(input('param.ctid')) > 0) {
            $condition[] = array('consulttype_id','=',intval(input('param.ctid')));
        }
        $consult_list = $consult_model->getConsultList($condition, '*');
        View::assign('consult_list', $consult_list);
        View::assign('show_page', $consult_model->page_info->render());

        // 咨询类型
        $consult_type = rkcache('consulttype', true);
        View::assign('consult_type', $consult_type);

        //SEO 设置
        $seo_param = array ();
        $seo_param['name'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing($goods_info['goods_name']);
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        View::assign('consult_able', $this->checkConsultAble($goods_info['store_id']));
        return View::fetch($this->template_dir . 'consulting_list');
    }

    private function checkConsultAble($store_id = 0) {
        //检查是否为店主本身
        $store_self = false;
        if (session('store_id')) {
            if (($store_id == 0 && intval(input('param.store_id')) == session('store_id')) || ($store_id != 0 && $store_id == session('store_id'))) {
                $store_self = true;
            }
        }
        //查询会员信息
        $member_info = array();
        $member_model = model('member');
        if (session('member_id'))
            $member_info = $member_model->getMemberInfoByID(session('member_id'));
        //检查是否可以评论
        $consult_able = true;
        if ((!config('ds_config.guest_comment') && !session('member_id') ) || $store_self == true || (session('member_id') > 0 && $member_info['is_allowtalk'] == 0)) {
            $consult_able = false;
        }
        return $consult_able;
    }

    /**
     * 商品咨询添加
     */
    public function save_consult() {
        //检查是否可以评论
        if (!config('ds_config.guest_comment') && !session('member_id')) {
            ds_json_encode(10001,lang('goods_index_goods_noallow'));
        }
        $goods_id = intval(input('post.goods_id'));
        if ($goods_id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }
        //咨询内容的非空验证
        if (trim(input('post.goods_content')) == "") {
            ds_json_encode(10001,lang('goods_index_input_consult'));
        }
        //表单验证
        $data = [
            'goods_content' => input('post.goods_content')
        ];
        $res=word_filter($data['goods_content']);
        if(!$res['code']){
            ds_json_encode(10001,$res['msg']);
        }
        $data['goods_content']=$res['data']['text'];
        $goods_validate = ds_validate('goods');
        if (!$goods_validate->scene('save_consult')->check($data)) {
            ds_json_encode(10001,$goods_validate->getError());
        }

        if (session('member_id')) {
            //查询会员信息
            $member_model = model('member');
            $member_info = $member_model->getMemberInfo(array('member_id' => session('member_id')));
            if (empty($member_info) || $member_info['is_allowtalk'] == 0) {
                ds_json_encode(10001,lang('goods_index_goods_noallow'));
            }
        }
        //判断商品编号的存在性和合法性
        $goods = model('goods');
        $goods_info = $goods->getGoodsInfoByID($goods_id);
        if (empty($goods_info)) {
            ds_json_encode(10001,lang('goods_index_goods_not_exists'));
        }
        //判断是否是店主本人
        if (session('store_id') && $goods_info['store_id'] == session('store_id')) {
            ds_json_encode(10001,lang('goods_index_consult_store_error'));
        }
        //检查店铺状态
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID($goods_info['store_id']);
        if ($store_info['store_state'] == '0' || intval($store_info['store_state']) == '2' || (intval($store_info['store_endtime']) != 0 && $store_info['store_endtime'] <= TIMESTAMP)) {
            ds_json_encode(10001,lang('goods_index_goods_store_closed'));
        }
        //接收数据并保存
        $input = array();
        $input['goods_id'] = $goods_id;
        $input['goods_name'] = $goods_info['goods_name'];
        $input['member_id'] = intval(session('member_id')) > 0 ? session('member_id') : 0;
        $input['member_name'] = session('member_name') ? session('member_name') : '';
        $input['store_id'] = $store_info['store_id'];
        $input['store_name'] = $store_info['store_name'];
        $input['consulttype_id'] = intval(input('post.consult_type_id',1));
        $input['consult_addtime'] = TIMESTAMP;
        $input['consult_content'] = $data['goods_content'];
        $input['consult_isanonymous'] = input('post.hide_name')=='hide'?1:0;
        $consult_model = model('consult');
        if ($consult_model->addConsult($input)) {
            ds_json_encode(10000,lang('goods_index_consult_success'));
        } else {
            ds_json_encode(10001,lang('goods_index_consult_fail'));
        }
    }



    public function json_area() {
        echo input('param.callback') . '(' . json_encode(model('area')->getAreaArrayForJson()) . ')';
    }

}

?>
