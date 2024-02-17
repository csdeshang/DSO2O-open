<?php

/*
 * 店铺规格值
 * 每个店铺都有对应分类下保存的规格值
 */

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
class  Sellerspec extends BaseSeller
{

    var $_store_id;

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerspec.lang.php');
        $this->_store_id = session('store_id');
    }

    /**
     * 选择分类
     */
    public function index()
    {
        // 获取商品分类
        $goodsclass_model = model('goodsclass');
        $gc_list = $goodsclass_model->getGoodsclass($this->_store_id);
        View::assign('gc_list', $gc_list);
        $this->setSellerCurItem('spec');
        $this->setSellerCurMenu('sellerspec');
        return View::fetch($this->template_dir.'index');
    }

    /**
     * 添加规格值
     */
    public function add_spec()
    {
        $sp_id = intval(input('param.spid'));
        $gc_id = intval(input('param.gcid'));
        // 验证参数
        if ($sp_id <= 0) {
            $this->error(lang('param_error'));
        }
        // 分类信息
        $gc_info = model('goodsclass')->getGoodsclassInfoById($gc_id);
        View::assign('gc_info', $gc_info);
        // 规格信息
        $spec_model = model('spec');
        $sp_info = $spec_model->getSpecInfo($sp_id, 'sp_id,sp_name');
        //halt($sp_info);
        View::assign('sp_info', $sp_info);
        // 规格值信息
        $sp_value_list = $spec_model->getSpecvalueList(array(
                                                           'store_id' => $this->_store_id, 'sp_id' => $sp_id,
                                                           'gc_id' => $gc_id
                                                       ));
        View::assign('sp_value_list', $sp_value_list);

        return View::fetch($this->template_dir.'spec_add');
    }

    /**
     * 保存规格值
     */
    public function save_spec()
    {
        $sp_id = intval(input('post.sp_id'));
        $gc_id = intval(input('post.gc_id'));
        if ($sp_id <= 0 || $gc_id <= 0 ) {
            ds_json_encode(10001,lang('param_error'));
        }

        $spec_model = model('spec');
        // 更新原规格值
        $sv_array = input('post.sv/a');
        if (!empty($sv_array['old']) && is_array($sv_array['old'])) {
            foreach ($sv_array['old'] as $key => $value) {
                if (empty($value['name'])) {
                    continue;
                }
                $where = array('spvalue_id' => $key);
                $update = array(
                    'spvalue_name' => $value['name'], 'sp_id' => $sp_id, 'gc_id' => $gc_id,
                    'store_id' => $this->_store_id, 
                    'spvalue_color' => isset($value['color'])?$value['color']:'',
                    'spvalue_sort' => intval($value['sort'])
                );
                $spec_model->editSpecvalue($update, $where);
            }
        }

        // 添加新规格值
        if (!empty($sv_array['new']) && is_array($sv_array['new'])) {
            $insert_array = array();
            foreach ($sv_array['new'] as $value) {
                if (empty($value['name'])) {
                    continue;
                }
                $tmp_insert = array(
                    'spvalue_name' => $value['name'], 'sp_id' => $sp_id, 'gc_id' => $gc_id,
                    'store_id' => $this->_store_id, 
                    'spvalue_color' => isset($value['color'])?$value['color']:'',
                    'spvalue_sort' => intval($value['sort'])
                );
                $insert_array[] = $tmp_insert;
            }
            $spec_model->addSpecvalueALL($insert_array);
        }

        ds_json_encode(10000,lang('ds_common_op_succ'));
    }

    /**
     * ajax删除规格值
     */
    public function ajax_delspec()
    {
        $spvalue_id = intval(input('param.id'));
        if ($spvalue_id <= 0) {
            echo 'false';
            exit();
        }
        $rs = model('spec')->delSpecvalue(array('spvalue_id' => $spvalue_id, 'store_id' => $this->_store_id));
        if ($rs) {
            echo 'true';
            exit();
        }
        else {
            echo 'false';
            exit();
        }
    }

    /**
     * AJAX获取商品分类
     */
    public function ajax_class()
    {
        $id = intval(input('param.id'));
        $deep = intval(input('param.deep'));
        if ($id <= 0 || $deep <= 0 || $deep >= 4) {
            echo 'false';
            exit();
        }
        $deep += 1;
        $goodsclass_model = model('goodsclass');

        // 验证分类是否存在
        $gc_info = $goodsclass_model->getGoodsclassInfoById($id);
        if (empty($gc_info)) {
            echo 'false';
            exit();
        }

        // 读取商品分类
        if ($deep != 4) {
            $gc_list = $goodsclass_model->getGoodsclass($this->_store_id, $id, $deep);
        }
        // 分类不为空输出分类信息
        if (!empty($gc_list)) {
            $data = array('type' => 'class', 'data' => $gc_list, 'deep' => $deep);
        }
        else {
            // 查询类型
            $type_model = model('type');
            $spec_list = $type_model->getSpecByType(array('type_id' => $gc_info['type_id']), 't.type_id, s.*');

            $data = array('type' => 'spec', 'data' => $spec_list, 'gcid' => $id, 'deep' => $deep);
        }
        echo json_encode($data);
        exit();
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array = array(
            array('name' => 'spec', 'text' => lang('edit_product_specifications'), 'url' => 'index')
        );
        return $menu_array;
    }
}

?>
