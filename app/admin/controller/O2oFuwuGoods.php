<?php

/**
 * 商品管理
 */

namespace app\admin\controller;
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
class  O2oFuwuGoods extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/goods.lang.php');
    }

    /**
     * 商品管理
     */
    public function index() {
        $o2o_fuwu_goods_model = model('o2o_fuwu_goods');

        /**
         * 查询条件
         */
        $where = array();
        $search_o2o_fuwu_goods_name = trim(input('param.search_goods_name'));
        if ($search_o2o_fuwu_goods_name != '') {
            $where[]=array('o2o_fuwu_goods_name','like', '%' . $search_o2o_fuwu_goods_name . '%');
        }

        $search_store_name = trim(input('param.search_store_name'));
        if ($search_store_name != '') {
            $where[]=array('o2o_fuwu_organization_name','like', '%' .$search_store_name . '%');
        }

        $o2o_fuwu_goods_state = input('param.goods_state');
        if (in_array($o2o_fuwu_goods_state, array('0', '1', '10'))) {
            $where[]=array('o2o_fuwu_goods_state','=',$o2o_fuwu_goods_state);
        }
        $o2o_fuwu_goods_verify = input('param.goods_verify');
        if (in_array($o2o_fuwu_goods_verify, array('0', '1', '10'))) {
            $where[]=array('o2o_fuwu_goods_verify','=',$o2o_fuwu_goods_verify);
        }

        $type = input('param.type');
        switch ($type) {
            // 禁售
            case 'lockup':
                $where[]=array('o2o_fuwu_goods_state','=',10);
            // 全部商品
            default:
                $o2o_fuwu_goods_list = $o2o_fuwu_goods_model->getO2oFuwuGoodsList($where,'*',10,'o2o_fuwu_goods_recommend desc,o2o_fuwu_goods_sort_order asc,o2o_fuwu_goods_id desc');
                break;
        }
        View::assign('o2o_fuwu_goods_list', $o2o_fuwu_goods_list);
        View::assign('show_page', $o2o_fuwu_goods_model->page_info->render());



        View::assign('search', $where);

        View::assign('state', array('1' => '出售中', '0' => '仓库中', '10' => '违规下架'));

        View::assign('verify', array('1' => '通过', '0' => '未通过', '10' => '等待审核'));


        $type = input('param.type');
        if(!in_array($type, array('lockup','waitverify','allgoods'))){
            $type = 'allgoods';
        }
        
        View::assign('type', $type);
        $this->setAdminCurItem($type);
        return View::fetch();
    }



    /**
     * 违规下架
     */
    public function goods_lockup() {
//        if (request()->isPost()) {
            $o2o_fuwu_goods_id = input('param.o2o_fuwu_goods_id');
            $commonid_array = ds_delete_param($o2o_fuwu_goods_id);
            if ($commonid_array == FALSE) {
                ds_json_encode('10001',lang('ds_common_op_fail'));
            }
            $state=intval(input('param.state'));
            if(!in_array($state,array(1,10))){
                ds_json_encode('10001','状态错误');
            }
            $update = array();
            $update['o2o_fuwu_goods_state'] = $state;

            $where = array();
            $where[]=array('o2o_fuwu_goods_id','in', $commonid_array);

            model('o2o_fuwu_goods')->editO2oFuwuGoods($update, $where);
            ds_json_encode('10000',lang('ds_common_op_succ'));
//        } else {
//            View::assign('o2o_fuwu_goods_id', input('param.commonid'));
//            echo View::fetch('close_remark');
//        }
    }

    /**
     * 删除商品
     */
    public function goods_del() {
        $o2o_fuwu_goods_id = input('param.o2o_fuwu_goods_id');
        $o2o_fuwu_goods_id_array = ds_delete_param($o2o_fuwu_goods_id);
        if ($o2o_fuwu_goods_id_array == FALSE) {
            ds_json_encode('10001', lang('ds_common_op_fail'));
        }
        $condition = array();
        $condition[]=array('o2o_fuwu_goods_id','in',$o2o_fuwu_goods_id_array);
        model('o2o_fuwu_goods')->delO2oFuwuGoods($condition);
        ds_json_encode('10000', lang('ds_common_op_succ'));
    }

    /**
     * 审核商品
     */
    public function goods_verify() {
        if (request()->isPost()) {
            $o2o_fuwu_goods_id = input('param.o2o_fuwu_goods_id');
            $commonid_array = ds_delete_param($o2o_fuwu_goods_id);
            if ($commonid_array == FALSE) {
                $this->error(lang('ds_common_op_fail'));
            }

            $update2 = array();
            $update2['o2o_fuwu_goods_verify'] = intval(input('param.verify_state'));

            $update1 = array();
            $update1['o2o_fuwu_goods_verifyremark'] = trim(input('param.verify_reason'));
            $update1 = array_merge($update1, $update2);
            $where = array();
            $where[]=array('o2o_fuwu_goods_id','in', $commonid_array);

            $o2o_fuwu_goods_model = model('o2o_fuwu_goods');
            if (intval(input('param.verify_state')) == 0) {
                $o2o_fuwu_goods_model->editProducesVerifyFail($where, $update1, $update2);
            } else {
                $o2o_fuwu_goods_model->editProduces($where, $update1, $update2);
            }
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        } else {
            View::assign('o2o_fuwu_goods_id', input('param.commonid'));
            echo View::fetch('verify_remark');
        }
    }

    /**
     * ajax操作
     */
    public function ajax() {
        $o2o_fuwu_goods_model = model('o2o_fuwu_goods');
        switch (input('param.branch')) {

            case 'o2o_fuwu_goods_sort_order':
            case 'o2o_fuwu_goods_recommend':
                $o2o_fuwu_goods_model->editO2oFuwuGoods(array(input('param.column') => trim(input('param.value'))),array('o2o_fuwu_goods_id' => intval(input('param.id'))));
                echo 'true';
                exit;
                break;

        }
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'allgoods',
                'text' => '所有商品',
                'url' => url('O2oFuwuGoods/index')
            ),
            array(
                'name' => 'lockup',
                'text' => '下架商品',
                'url' => url('O2oFuwuGoods/index', ['type' => 'lockup'])
            ),
//            array(
//                'name' => 'waitverify',
//                'text' => '待审核',
//                'url' => url('O2oFuwuGoods/index', ['type' => 'waitverify'])
//            ),
        );
        return $menu_array;
    }

}

?>
