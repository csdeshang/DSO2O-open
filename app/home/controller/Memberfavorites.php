<?php

namespace app\home\controller;
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
class  Memberfavorites extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/memberfavorites.lang.php');
    }

    /**
     * 增加商品收藏
     */
    public function favoritesgoods() {
        $fav_id = intval(input('param.fid'));
        if ($fav_id <= 0) {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_collect_fail')));
            die;
        }
        $favorites_model = model('favorites');
        //判断是否已经收藏
        $favorites_info = $favorites_model->getOneFavorites(array(
            'fav_id' => "$fav_id", 'fav_type' => 'goods',
            'member_id' => session('member_id')
        ));
        if (!empty($favorites_info)) {
            echo json_encode(array(
                'done' => false, 'msg' => lang('favorite_already_favorite_goods')
            ));
            die;
        }
        //判断商品是否为当前会员所有
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($fav_id);
        if ($goods_info['store_id'] == session('store_id')) {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_no_my_product')));
            die;
        }
        //添加收藏
        $insert_arr = array();
        $insert_arr['member_id'] = session('member_id');
        $insert_arr['member_name'] = session('member_name');
        $insert_arr['fav_id'] = $fav_id;
        $insert_arr['fav_type'] = 'goods';
        $insert_arr['fav_time'] = TIMESTAMP;
        $result = $favorites_model->addFavorites($insert_arr);
        if ($result) {
            //增加收藏数量
            $goods_model->editGoodsById(array('goods_collect' => Db::raw('goods_collect+1')), $fav_id);
            echo json_encode(array('done' => true, 'msg' => lang('favorite_collect_success')));
            die;
        } else {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_collect_fail')));
            die;
        }
    }

    /**
     * 增加店铺收藏
     */
    public function favoritesstore() {
        $fav_id = intval(input('param.fid'));
        if ($fav_id <= 0) {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_collect_fail')));
            die;
        }
        $favorites_model = model('favorites');
        //判断是否已经收藏
        $favorites_info = $favorites_model->getOneFavorites(array(
            'fav_id' => "$fav_id", 'fav_type' => 'store',
            'member_id' => session('member_id')
        ));
        if (!empty($favorites_info)) {
            echo json_encode(array(
                'done' => false, 'msg' => lang('favorite_already_favorite_store')
            ));
            die;
        }
        //判断店铺是否为当前会员所有
        if ($fav_id == session('store_id')) {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_no_my_store')));
            die;
        }
        //添加收藏
        $insert_arr = array();
        $insert_arr['member_id'] = session('member_id');
        $insert_arr['member_name'] = session('member_name');
        $insert_arr['fav_id'] = $fav_id;
        $insert_arr['fav_type'] = 'store';
        $insert_arr['fav_time'] = TIMESTAMP;
        $result = $favorites_model->addFavorites($insert_arr);
        if ($result) {
            //增加收藏数量
            $store_model = model('store');
            $store_model->editStore(array('store_collect' => Db::raw('store_collect+1')), array('store_id' => $fav_id));
            echo json_encode(array('done' => true, 'msg' => lang('favorite_collect_success')));
            die;
        } else {
            echo json_encode(array('done' => false, 'msg' => lang('favorite_collect_fail')));
            die;
        }
    }

    /**
     * 商品收藏列表
     *
     * @param
     * @return
     */
    public function fglist() {
        $favorites_model = model('favorites');
        $show_type = 'favorites_goods_picshowlist'; //默认为图片横向显示
        $show = input('param.show');
        $store_array = array(
            'list' => 'favorites_goods_index', 'pic' => 'favorites_goods_picshowlist',
            'store' => 'favorites_goods_shoplist'
        );
        if (array_key_exists($show, $store_array))
            $show_type = $store_array[$show];

        $favorites_list = $favorites_model->getGoodsFavoritesList(array(array('member_id' ,'=', session('member_id'))), '*', 20);
        View::assign('show_page', $favorites_model->page_info->render());
        $store_favorites = array(); //店铺收藏信息
        $store_goods_list = array(); //店铺为分组的商品
        if (!empty($favorites_list) && is_array($favorites_list)) {
            $favorites_id = array(); //收藏的商品编号
            foreach ($favorites_list as $key => $favorites) {
                $fav_id = $favorites['fav_id'];
                $favorites_id[] = $favorites['fav_id'];
                $favorites_key[$fav_id] = $key;
            }
            $goods_model = model('goods');
            $field = 'goods.goods_id,goods.goods_name,goods.store_id,goods.goods_image,goods.goods_price,goods.evaluation_count,goods.goods_salenum,goods.goods_collect,store.store_name,store.member_id,store.member_name,store.store_qq,store.store_ww';
            $goods_list = $goods_model->getGoodsStoreList(array(array('goods_id','in', $favorites_id)), $field);
            $store_array = array(); //店铺编号
            if (!empty($goods_list) && is_array($goods_list)) {
                foreach ($goods_list as $key => $fav) {
                    $fav_id = $fav['goods_id'];
                    $fav['goods_member_id'] = $fav['member_id'];
                    $key = $favorites_key[$fav_id];
                    $favorites_list[$key]['goods'] = $fav;
                    $store_id = $fav['store_id'];
                    if (!in_array($store_id, $store_array))
                        $store_array[] = $store_id;
                    $store_goods_list[$store_id][] = $favorites_list[$key];
                }
            }

            if (!empty($store_array) && is_array($store_array)) {
                $store_list = $favorites_model->getStoreFavoritesList(array(
                    array('member_id' ,'=', session('member_id')),
                    array('fav_id','in', $store_array)
                ));
                if (!empty($store_list) && is_array($store_list)) {
                    foreach ($store_list as $key => $val) {
                        $store_id = $val['fav_id'];
                        $store_favorites[] = $store_id;
                    }
                }
            }
        }
        $this->setMemberCurMenu('member_favorites');
        $this->setMemberCurItem('fav_goods');

        View::assign('favorites_list', $favorites_list);
        View::assign('store_favorites', $store_favorites);
        View::assign('store_goods_list', $store_goods_list);
        return View::fetch($this->template_dir . $show_type);
    }

    /**
     * 店铺收藏列表
     *
     * @param
     * @return
     */
    public function fslist() {
        $favorites_model = model('favorites');
        $favorites_list = $favorites_model->getStoreFavoritesList(array(array('member_id' ,'=', session('member_id'))), '*', 10);
        if (!empty($favorites_list) && is_array($favorites_list)) {
            $favorites_id = array(); //收藏的店铺编号
            foreach ($favorites_list as $key => $favorites) {
                $fav_id = $favorites['fav_id'];
                $favorites_id[] = $favorites['fav_id'];
                $favorites_key[$fav_id] = $key;
            }
            $store_model = model('store');
            $store_list = $store_model->getStoreList(array(array('store_id','in', $favorites_id)));
            if (!empty($store_list) && is_array($store_list)) {
                foreach ($store_list as $key => $fav) {
                    $fav_id = $fav['store_id'];
                    $key = $favorites_key[$fav_id];
                    $favorites_list[$key]['store'] = $fav;
                }
            }
        }
        $this->setMemberCurMenu('membergavorites');
        $this->setMemberCurItem('fav_store');

        View::assign('favorites_list', $favorites_list);
        View::assign('show_page', $favorites_model->page_info->render());
        return View::fetch($this->template_dir . "favorites_store_index");
    }

    /**
     * 删除收藏
     *
     * @param
     * @return
     */
    public function delfavorites() {
        if (!input('param.fav_id') || !input('param.type')) {
            ds_json_encode(10001,lang('favorite_del_fail'));
        }
        if (!preg_match_all('/^[0-9,]+$/', input('param.fav_id'), $matches)) {
            ds_json_encode(10001,lang('param_error'));
        }
        $fav_id = trim(input('param.fav_id'), ',');
        if (!in_array(input('param.type'), array('goods', 'store'))) {
            ds_json_encode(10001,lang('param_error'));
        }
        $type = input('param.type');
        $favorites_model = model('favorites');
        $fav_arr = explode(',', $fav_id);
        if (!empty($fav_arr) && is_array($fav_arr)) {
            $favorites_list = $favorites_model->getFavoritesList(array(array('fav_id','in', $fav_arr),array('fav_type' ,'=', "$type"),array('member_id' ,'=', session('member_id'))));
            if (!empty($favorites_list) && is_array($favorites_list)) {
                $fav_arr = array();
                foreach ($favorites_list as $k => $v) {
                    $fav_arr[] = $v['fav_id'];
                }
                $result = $favorites_model->delFavorites(array(
                    array('fav_id','in', $fav_arr), array('fav_type' ,'=', "$type"),
                    array('member_id' ,'=', session('member_id'))
                ));
                if (!empty($fav_arr) && $result) {
                    //更新商品收藏数量
                    if ($type == 'store'){
                        $store_model = model('store');
                        $condition = array();
                        $condition[] = array('store_id','=',$fav_id);
                        $condition[] = array('store_collect','>',0);
                        $store_model->editStore(array('store_collect' => Db::raw('store_collect-1'),), $condition);
                    }else{
                        $goods_model = model('goods');
                        $goods_model->editGoodsById(array('goods_collect' => Db::raw('goods_collect-1')), $fav_arr);
                    }
                    ds_json_encode(10000,lang('favorite_del_success'));
                }
            } else {
                ds_json_encode(10001,lang('favorite_del_fail'));
            }
        } else {
            ds_json_encode(10001,lang('favorite_del_fail'));
        }
    }

    /**
     * 店铺新上架的商品列表
     *
     * @param
     * @return
     */
    public function store_goods() {
        $store_id = intval(input('param.store_id'));
        if ($store_id > 0) {
            $condition = array();
            $add_time_from = TIMESTAMP - 60 * 60 * 24 * 30; //30天
            $condition[]=array('store_id','=',$store_id);
            $condition[] = array('goods_addtime','between', $add_time_from . ',' . TIMESTAMP);
            $goods_model = model('goods');
            $goods_list = $goods_model->getGoodsOnlineList($condition, 'goods_id,goods_name,store_id,goods_image,goods_price', 0, 'goods_id desc', 50);
            View::assign('goods_list', $goods_list);
            echo View::fetch($this->template_dir . 'favorites_store_goods');
        }
    }


    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'fav_goods', 'text' => lang('ds_member_path_collect_list'),
                'url' => url('Memberfavorites/fglist')
            ), array(
                'name' => 'fav_store', 'text' => lang('ds_member_path_collect_store'),
                'url' => url('Memberfavorites/fslist')
            )
        );
        return $menu_array;
    }

}
