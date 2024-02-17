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
class  Sellergoodsclass extends BaseSeller {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
		Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellergoodsclass.lang.php');
    }

    /**
     * 卖家商品分类
     *
     * @param
     * @return
     */
    public function index() {
        $storegoodsclass_model = model('storegoodsclass');
        $goods_class = $storegoodsclass_model->getTreeClassList(array('store_id' => session('store_id')), 2);
        $str = '';
        if (is_array($goods_class) and count($goods_class) > 0) {
            foreach ($goods_class as $key => $val) {
                $row[$val['storegc_id']] = $key + 1;
                if ($val['storegc_parent_id'] != '0') {
                    $str .= intval($row[$val['storegc_parent_id']]) . ",";
                } else {
                    $str .= "0,";
                }
            }
            $str = substr($str, 0, -1);
        } else {
            $str = '0';
        }
        View::assign('map', $str);
        View::assign('class_num', count($goods_class) - 1);
        View::assign('goods_class', $goods_class);

        $this->setSellerCurMenu('sellergoodsclass');
        $this->setSellerCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }

    /* 分类添加 */

    public function goods_class_add() {
        $storegoodsclass_model = model('storegoodsclass');
        $goods_class = $storegoodsclass_model->getStoregoodsclassList(array(
            'store_id' => session('store_id'),
            'storegc_parent_id' => 0
        ));
        View::assign('goods_class', $goods_class);
        View::assign('class_info', array('storegc_parent_id' => input('top_class_id')));
        View::assign('type', 'add');
        return View::fetch($this->template_dir . 'class_add');
    }

    /* 分类编辑 */

    public function goods_class_edit() {
        $class_id = input('param.top_class_id');
        $storegoodsclass_model = model('storegoodsclass');
        $class_info = $storegoodsclass_model->getStoregoodsclassInfo(array('storegc_id' => intval($class_id)));
        $goods_class = $storegoodsclass_model->getStoregoodsclassList(array(
            'store_id' => session('store_id'),
            'storegc_parent_id' => 0
        ));
        View::assign('goods_class', $goods_class);
        View::assign('class_info', $class_info);
        View::assign('type', 'edit');
        return View::fetch($this->template_dir . 'class_add');
    }

    /**
     * 卖家商品分类保存
     *
     * @param
     * @return
     */
    public function goods_class_save() {
        $storegoodsclass_model = model('storegoodsclass');
        if (input('post.type') == 'edit') {

            $storegc_id = intval(input('post.storegc_id'));
            if ($storegc_id <= 0) {
                ds_json_encode(10001, lang('param_error'));
            }
            $class_array = array();
            if (input('post.storegc_name') != '') {
                $class_array['storegc_name'] = input('post.storegc_name');
            }
            if (input('post.storegc_parent_id') != '') {
                $class_array['storegc_parent_id'] = input('post.storegc_parent_id');
            }
            if (input('post.storegc_state') != '') {
                $class_array['storegc_state'] = input('post.storegc_state');
            }
            if (input('post.storegc_sort') != '') {
                $class_array['storegc_sort'] = input('post.storegc_sort');
            }
			if ($class_array['storegc_parent_id'] == $storegc_id) {
				ds_json_encode(10001,lang('storegc_parent_goods_class_equal_self_error'));
			}
			
            $condition = array();
            $condition[] = array('store_id','=',session('store_id'));
            $condition[] = array('storegc_id','=',intval(input('post.storegc_id')));
            $state = $storegoodsclass_model->editStoregoodsclass($class_array, $condition,session('store_id'));
            if ($state) {
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        } else {
            $class_array = array();
            $class_array['storegc_name'] = input('post.storegc_name');
            $class_array['storegc_parent_id'] = input('post.storegc_parent_id', 0);
            $class_array['storegc_state'] = input('post.storegc_state');
            $class_array['store_id'] = session('store_id');
            $class_array['storegc_sort'] = input('post.storegc_sort');
            $state = $storegoodsclass_model->addStoregoodsclass($class_array);
            if ($state) {
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 卖家商品分类删除
     *
     * @param
     * @return
     */
    public function drop_goods_class() {
        $storegoodsclass_model = model('storegoodsclass');
        $stcid_array = explode(',', input('param.class_id'));

        foreach ($stcid_array as $key => $val) {
            if (!is_numeric($val))
                unset($stcid_array[$key]);
        }

        $where = array();
        $where[]=array('storegc_id','in', $stcid_array);
        $where[]=array('store_id','=',session('store_id'));

        $drop_state = $storegoodsclass_model->delStoregoodsclass($where,session('store_id'));
        if ($drop_state) {
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $name 当前导航的name
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => '店铺分类', 'url' => url('Sellergoodsclass/index')
            )
        );
        return $menu_array;
    }

}
