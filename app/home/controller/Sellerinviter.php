<?php

/**
 * 卖家分销管理
 */

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
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
class  Sellerinviter extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerinviter.lang.php');
        if (intval(config('ds_config.inviter_open')) !== 1) {
            $this->error(lang('promotion_unavailable'), 'seller/index');
        }
    }

    public function order_list() {
        /* 设置买家当前菜单 */
        $this->setSellerCurMenu('sellerinviter_order');
        /* 设置买家当前栏目 */
        $this->setSellerCurItem('order_list');

        $condition = array(array('orderinviter_store_id' ,'=', session('store_id')));
        if (input('param.orderinviter_order_sn')) {
            $condition[] = array('orderinviter_order_sn','like', '%' . input('param.orderinviter_order_sn') . '%');
        }
        $orderinviter_list = Db::name('orderinviter')->where($condition)->order('orderinviter_id desc')->paginate(10);
        $order_list=$orderinviter_list->items();
        foreach($order_list as $key => $val){
            $order_list[$key]['orderinviter_valid_text']=lang('orderinviter_valid_array')[$val['orderinviter_valid']];
        }
        $page = $orderinviter_list->render();
        View::assign('show_page', $page);
        View::assign('orderinviter_list', $order_list);
        return View::fetch($this->template_dir . 'order_list');
    }

    public function goods_list() {
        $goods_model = model('goods');

        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        }

        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('inviter_open','=',1);
        if ((input('param.goods_name'))) {
            $condition[] = array('goods_name','like', '%' . input('param.goods_name') . '%');
        }

        $goods_list = $goods_model->getGoodsCommonList($condition, '*', 10);
        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());

        $storage_array = $goods_model->calculateStorage($goods_list);
        View::assign('storage_array', $storage_array);

        $this->setSellerCurMenu('sellerinviter_goods');
        $this->setSellerCurItem('goods_list');
        return View::fetch($this->template_dir . 'goods_list');
    }

    /**
     * 添加分销活动
     * */
    public function goods_add() {
        $goods_model = model('goods');
        if (!request()->isPost()) {
            //输出导航
            $this->setSellerCurMenu('sellerinviter_goods');
            $this->setSellerCurItem('goods_add');
            return View::fetch($this->template_dir . 'goods_add');
        } else {
            //验证输入
            $inviter_goods_commonid = intval(input('post.inviter_goods_commonid'));
            $inviter_ratio = floatval(input('post.inviter_ratio'));

            if (!($inviter_goods_commonid)) {
                ds_json_encode(10001, lang('inviter_goods_commonid_required'));
            }
            $goods_info = $goods_model->getGoodsCommonInfo('goods_commonid=' . $inviter_goods_commonid . ' AND store_id=' . session('store_id'));
            if (!$goods_info) {
                ds_json_encode(10001, lang('sellerinviter_goods_empty'));
            }
            if ($inviter_ratio <= 0) {
                ds_json_encode(10001, lang('inviter_ratio_min') . 0.01 . lang('ds_percent'));
            }
            if ($inviter_ratio > 100) {
                ds_json_encode(10001, lang('inviter_ratio_max') . 100 . lang('ds_percent'));
            }
            $result = $goods_model->editGoodsCommonById(array(
                'inviter_open' => 1,
                'inviter_ratio' => $inviter_ratio,
                    ), array($inviter_goods_commonid));
            if ($result) {
                $this->recordSellerlog('添加分销商品，商品编号：' . $inviter_goods_commonid);
                ds_json_encode(10000, lang('goods_add_success'));
            } else {
                ds_json_encode(10001, lang('goods_add_fail'));
            }
        }
    }

    /**
     * 编辑分销活动
     * */
    public function goods_edit() {
        $goods_model = model('goods');
        if (!request()->isPost()) {
            $goods_commonid = intval(input('param.goods_commonid'));
            $goods_info = $goods_model->getGoodsCommonInfo('goods_commonid=' . $goods_commonid . ' AND inviter_open=1 AND store_id=' . session('store_id'));
            if (!$goods_info) {
                $this->error(lang('sellerinviter_goods_empty'), 'Sellerinviter/goods_list');
            }
            View::assign('goods_info', $goods_info);
            //输出导航
            $this->setSellerCurMenu('sellerinviter_goods');
            $this->setSellerCurItem('goods_add');
            return View::fetch($this->template_dir . 'goods_add');
        } else {
            //验证输入
            $inviter_goods_commonid = intval(input('post.inviter_goods_commonid'));
            $inviter_ratio = floatval(input('post.inviter_ratio'));

            if (!($inviter_goods_commonid)) {
                ds_json_encode(10001, lang('inviter_goods_commonid_required'));
            }
            $goods_info = $goods_model->getGoodsCommonInfo('goods_commonid=' . $inviter_goods_commonid . ' AND inviter_open=1 AND store_id=' . session('store_id'));
            if (!$goods_info) {
                ds_json_encode(10001, lang('sellerinviter_goods_empty'));
            }
            if ($inviter_ratio <= 0) {
                ds_json_encode(10001, lang('inviter_ratio_min') . 0.01 . lang('ds_percent'));
            }
            if ($inviter_ratio > 100) {
                ds_json_encode(10001, lang('inviter_ratio_max') . 100 . lang('ds_percent'));
            }
            $result = $goods_model->editGoodsCommonById(array(
                'inviter_ratio' => $inviter_ratio,
                    ), array($inviter_goods_commonid));
            if ($result) {
                $this->recordSellerlog('编辑分销商品，商品编号：' . $inviter_goods_commonid);
                ds_json_encode(10000, lang('goods_edit_success'));
            } else {
                ds_json_encode(10001, lang('goods_edit_fail'));
            }
        }
    }

    public function goods_del() {
        $goods_model = model('goods');
        $goods_commonid = intval(input('param.goods_commonid'));
        $goods_info = $goods_model->getGoodsCommonInfo('goods_commonid=' . $goods_commonid . ' AND inviter_open=1 AND store_id=' . session('store_id'));
        if (!$goods_info) {
            ds_json_encode(10001, lang('sellerinviter_goods_empty'));
        }
        $result = $goods_model->editGoodsCommonById(array(
            'inviter_open' => 0,
                ), array($goods_commonid));
        if ($result) {
            $this->recordSellerlog('删除分销商品，商品编号：' . $goods_commonid);
            ds_json_encode(10000, lang('goods_del_success'));
        } else {
            ds_json_encode(10001, lang('goods_del_fail'));
        }
    }

    /**
     * 选择活动商品
     * */
    public function search_goods() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('goods_name','like','%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsCommonList($condition, '*', 8);
        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'search_goods');
        exit;
    }

    public function inviter_goods_info() {
        $goods_commonid = intval(input('param.goods_commonid'));

        $data = array();
        $data['result'] = true;




        //获取商品具体信息用于显示
        $goods_model = model('goods');
        $condition = array();
        $condition[]=array('goods_commonid','=',$goods_commonid);
        $goods_list = $goods_model->getGoodsOnlineList($condition);

        if (empty($goods_list)) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }


        $goods_info = $goods_list[0];
        $data['goods_id'] = $goods_info['goods_id'];
        $data['goods_commonid'] = $goods_info['goods_commonid'];
        $data['goods_name'] = $goods_info['goods_name'];
        $data['goods_price'] = $goods_info['goods_price'];
        $data['goods_image'] = goods_thumb($goods_info, 240);
        $data['goods_href'] = url('Goods/index', array('goods_id' => $goods_info['goods_id']));

        echo json_encode($data);
        die;
    }

    protected function getSellerItemList() {
        $menu_array = array();
        switch (request()->action()) {
            case 'goods_list':
                $menu_array[] = array(
                    'name' => 'goods_list', 'text' => lang('goods_list'),
                    'url' => url('Sellerinviter/goods_list')
                );
                break;
            case 'order_list':
                $menu_array[] = array(
                    'name' => 'order_list', 'text' => lang('order_list'), 'url' => url('Sellerinviter/order_list')
                );
                break;
        }
        return $menu_array;
    }

}
