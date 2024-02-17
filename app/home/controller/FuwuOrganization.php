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
class  FuwuOrganization extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/fuwu.lang.php');
    }

    public function index() {
        
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_state','=',1);
        $order = 'o2o_fuwu_organization_id asc';
        $key = trim(input('param.sort_key'));
        if ($key == 'credit') {
            $order = 'o2o_fuwu_organization_score desc';
        } else if ($key == 'sales') {//销量排行
            $order = 'o2o_fuwu_organization_serve_count desc';
        }
        $class_id = intval(input('param.class_id'));
        $o2o_fuwu_class_parent_name='';
        if ($class_id) {
            $class_info=model('o2o_fuwu_class')->getO2oFuwuClassInfo(array('o2o_fuwu_class_id'=>$class_id));
            if($class_info){
                $condition[] = array('o2o_fuwu_class_id_1|o2o_fuwu_class_id_2|o2o_fuwu_class_id_3','=',$class_info['o2o_fuwu_class_parent_id']?$class_info['o2o_fuwu_class_parent_id']:$class_id);
                if($class_info['o2o_fuwu_class_parent_id']){//当前分类为二级
                    $o2o_fuwu_class_parent_id=$class_info['o2o_fuwu_class_parent_id'];
                    $temp=model('o2o_fuwu_class')->getO2oFuwuClassInfo(array('o2o_fuwu_class_id'=>$o2o_fuwu_class_parent_id));
                    if($temp){
                        $o2o_fuwu_class_parent_name=$temp['o2o_fuwu_class_name'];
                    }
                }else{//当前分类为一级
                    $o2o_fuwu_class_parent_id=$class_info['o2o_fuwu_class_id'];
                    $o2o_fuwu_class_parent_name=$class_info['o2o_fuwu_class_name'];
                }
                
            }else{
                $o2o_fuwu_class_parent_id=0;
            }
            
        }else{
            $o2o_fuwu_class_parent_id=0;
        }
        $type=input('param.type');
        if(in_array($type,array('0','1'))){
            $condition[] = array('o2o_fuwu_organization_type','=',$type);
        }
        View::assign('o2o_fuwu_class_parent_id',$o2o_fuwu_class_parent_id);
        //查询同级的服务类别列表
        View::assign('o2o_fuwu_class_list',model('o2o_fuwu_class')->getO2oFuwuClassList(array('o2o_fuwu_class_parent_id'=>$o2o_fuwu_class_parent_id)));
        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $o2o_fuwu_organization_list = $o2o_fuwu_organization_model->getO2oFuwuOrganizationList($condition, '*', 20, $order);
        foreach ($o2o_fuwu_organization_list as $k => $v) {
            $o2o_fuwu_organization_list[$k]['fuwu_organization_year']=$v['o2o_fuwu_organization_birthday']?intval((TIMESTAMP-$v['o2o_fuwu_organization_birthday'])/(86400*365)):'';
            $o2o_fuwu_organization_list[$k]['o2o_fuwu_organization_avatar_url'] = get_o2o_fuwu_file($v['o2o_fuwu_organization_id'],$v['o2o_fuwu_organization_avatar'],'avatar');
        }
        
        View::assign('o2o_fuwu_organization_list',$o2o_fuwu_organization_list);
        View::assign('show_page', is_object($o2o_fuwu_organization_model->page_info)?$o2o_fuwu_organization_model->page_info->render():"");
        
        
        /* 引用搜索相关函数 */
        require_once(base_path() . '/home/common_search.php');
        
        // 当前位置导航
        $nav_link_list=array();
        $nav_link_list[] = array('title' => lang('homepage'), 'link' => url('home/Index/index'));
        $nav_link_list[] = array('title' => lang('fuwu_index'), 'link' => url('home/Fuwu/index'));
        if($o2o_fuwu_class_parent_name){
            $nav_link_list[] = array('title' => lang('fuwu_organization_list'), 'link' => dropParam(array('class_id')));
            $nav_link_list[] = array('title' => $o2o_fuwu_class_parent_name);
        }else{
            $nav_link_list[] = array('title' => lang('fuwu_organization_list'));
        }
        
        View::assign('nav_link_list', $nav_link_list);
        //SEO 设置
        $seo = model('seo')->type('index')->show();
        $this->_assign_seo($seo);
        return View::fetch($this->template_dir . 'index');
    }
    
    public function view(){
        //服务详情
        $organization_id = intval(input('param.organization_id'));

        if ($organization_id <= 0) {
            $this->error(lang('param_error'));
        }

        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_id','=',$organization_id);
        $o2o_fuwu_organization_info = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo($condition);

        if (empty($o2o_fuwu_organization_info)) {
            $this->error('服务机构不存在');
        }
        //营业时间
        $o2o_fuwu_organization_info['o2o_fuwu_organization_open_text']=str_pad(intval($o2o_fuwu_organization_info['o2o_fuwu_organization_open_start']/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($o2o_fuwu_organization_info['o2o_fuwu_organization_open_start']%60),2,'0',STR_PAD_LEFT).'~'.str_pad(intval($o2o_fuwu_organization_info['o2o_fuwu_organization_open_end']/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($o2o_fuwu_organization_info['o2o_fuwu_organization_open_end']%60),2,'0',STR_PAD_LEFT);
        View::assign('o2o_fuwu_organization_info',$o2o_fuwu_organization_info);
        
        //图片
        $o2o_fuwu_upload_model=model('o2o_fuwu_upload');
        $quality_list=$o2o_fuwu_upload_model->getO2oFuwuUploadList(array(
            'o2o_fuwu_organization_id'=>$organization_id,
            'o2o_fuwu_upload_type'=>O2O_FUWU_UPLOAD_QUALIFY,
        ));

        $scene_list=$o2o_fuwu_upload_model->getO2oFuwuUploadList(array(
            'o2o_fuwu_organization_id'=>$organization_id,
            'o2o_fuwu_upload_type'=>O2O_FUWU_UPLOAD_SCENE,
        ));

        View::assign('quality_list',$quality_list);
        View::assign('scene_list',$scene_list);
        View::assign('baidu_ak',config('ds_config.baidu_ak'));
        // 当前位置导航
        $nav_link_list=array();
        $nav_link_list[] = array('title' => lang('homepage'), 'link' => url('home/Index/index'));
        $nav_link_list[] = array('title' => lang('fuwu_index'), 'link' => url('home/Fuwu/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_list'), 'link' => url('home/FuwuOrganization/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_info'));
        View::assign('nav_link_list', $nav_link_list);
        //SEO 设置
        $seo = model('seo')->type('index')->show();
        $this->_assign_seo($seo);
        return View::fetch($this->template_dir . 'view');
    }
    
    
    public function goods(){
        //服务详情
        $organization_id = intval(input('param.organization_id'));

        if ($organization_id <= 0) {
            $this->error(lang('param_error'));
        }

        $o2o_fuwu_organization_model = model('o2o_fuwu_organization');
        $condition = array();
        $condition[] = array('o2o_fuwu_organization_id','=',$organization_id);
        $o2o_fuwu_organization_info = $o2o_fuwu_organization_model->getO2oFuwuOrganizationInfo($condition);

        if (empty($o2o_fuwu_organization_info)) {
            $this->error('服务机构不存在');
        }
        View::assign('o2o_fuwu_organization_info',$o2o_fuwu_organization_info);
        
        $o2o_fuwu_goods_model = model('o2o_fuwu_goods');
        $condition[] = array('o2o_fuwu_goods_state','=',1);
        $condition[] = array('o2o_fuwu_organization_id','=',$organization_id);
        $order='o2o_fuwu_goods_sort_order asc,o2o_fuwu_goods_id asc';
        $if_recommend=intval(input('param.if_recommend'));
        if($if_recommend){
            $order='o2o_fuwu_goods_recommend desc,o2o_fuwu_goods_sort_order asc';
        }
        $o2o_fuwu_goods_list = $o2o_fuwu_goods_model->getO2oFuwuGoodsList($condition,'*',20,$order);
        View::assign('o2o_fuwu_goods_list',$o2o_fuwu_goods_list);
        View::assign('show_page', is_object($o2o_fuwu_goods_model->page_info)?$o2o_fuwu_goods_model->page_info->render():"");
        
        // 当前位置导航
        $nav_link_list=array();
        $nav_link_list[] = array('title' => lang('homepage'), 'link' => url('home/Index/index'));
        $nav_link_list[] = array('title' => lang('fuwu_index'), 'link' => url('home/Fuwu/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_list'), 'link' => url('home/FuwuOrganization/index'));
        $nav_link_list[] = array('title' => lang('fuwu_organization_info'));
        View::assign('nav_link_list', $nav_link_list);
        //SEO 设置
        $seo = model('seo')->type('index')->show();
        $this->_assign_seo($seo);
        return View::fetch($this->template_dir . 'goods');
    }
    


}
