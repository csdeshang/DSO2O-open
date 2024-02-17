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
class  Sellercost extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellercost.lang.php');
    }

    public function cost_list() {
        $storecost_model = model('storecost');
        $condition = array();
        $condition[]=array('storecost_store_id','=',session('store_id'));
        $storecost_remark = input('get.storecost_remark');
        if (!empty($storecost_remark)) {
            $condition[]=array('storecost_remark','like', '%' . $storecost_remark . '%');
        }
        $add_time_from = input('get.add_time_from');
        $add_time_to = input('get.add_time_to');
        if ((input('param.add_time_from')) != '') {
            $add_time_from = strtotime((input('param.add_time_from')));
            $condition[] = array('storecost_time', '>=', $add_time_from);
        }

        if ((input('param.add_time_to')) != '') {
            $add_time_to = strtotime((input('param.add_time_to')))+86399;
            $condition[] = array('storecost_time', '<=', $add_time_to);
        }
        $cost_list = $storecost_model->getStorecostList($condition, 10, 'storecost_id desc');

        View::assign('cost_list', $cost_list);
        View::assign('show_page', $storecost_model->page_info->render());

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellercost');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('cost_list');
        return View::fetch($this->template_dir.'cost_list');
    }


    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array = array(
            array(
                'name' => 'cost_list',
                'text' => '消费列表',
                'url' => url('Sellercost/cost_list')
            ),
        );
        return $menu_array;
    }
    
    
}
