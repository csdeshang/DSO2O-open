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
class  Fuwu extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/fuwu.lang.php');
    }

    public function index() {
        //获取分类
        $o2o_fuwu_class_model = model('o2o_fuwu_class');
        $tmp_list = $o2o_fuwu_class_model->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id'=>0));
        
        $class_list = array();
        if (is_array($tmp_list)) {
            foreach ($tmp_list as $k => $v) {
                $v['o2o_fuwu_class_logo_url']=get_o2o_fuwu_class_logo($v['o2o_fuwu_class_logo']);
                $v['child']=$o2o_fuwu_class_model->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id'=>$v['o2o_fuwu_class_id']));
//                foreach($v['child'] as $key => $val){
//                    $v['child'][$key]['o2o_fuwu_class_logo_url']=get_o2o_fuwu_class_logo($val['o2o_fuwu_class_logo']);
//                }
                $class_list[] = $v;
            }
        }
        View::assign('o2o_fuwu_class_list', $class_list);
        //楼层广告
        $result=false;
        $condition=array();
        $condition[]=['ap_id','=',2];
        $condition[]=['adv_enabled','=',1];
        $condition[]=['adv_startdate','<',strtotime(date('Y-m-d H:00:00'))];
        $condition[]=['adv_enddate','>',strtotime(date('Y-m-d H:00:00'))];
        $adv_list=model('adv')->getAdvList($condition,'',10,'adv_sort asc,adv_id asc');
        if(!empty($adv_list)){
            $result=$adv_list;
        }
        View::assign('adv_fuwu_1', $result);
        // 当前位置导航
        $nav_link_list=array();
        $nav_link_list[] = array('title' => lang('homepage'), 'link' => url('home/Index/index'));
        $nav_link_list[] = array('title' => lang('fuwu_index'));
        View::assign('nav_link_list', $nav_link_list);
        //SEO 设置
        $seo = model('seo')->type('index')->show();
        $this->_assign_seo($seo);
        return View::fetch($this->template_dir . 'index');
    }
    

}
