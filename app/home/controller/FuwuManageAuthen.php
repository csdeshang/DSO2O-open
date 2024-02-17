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
class  FuwuManageAuthen extends BaseFuwu {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/fuwu_manage_authen.lang.php');
    }
    
    
    function quality() {
        View::assign('file_list',$this->get_info_file_list('quality'));
        /* 设置机构当前菜单 */
        $this->setFuwuCurMenu('fuwu_manage_authen_quality');
        /* 设置机构当前栏目 */
        $this->setFuwuCurItem('quality');
        return View::fetch($this->template_dir.'quality');
    }
    
    function scene() {
        View::assign('file_list',$this->get_info_file_list('scene'));
        /* 设置机构当前菜单 */
        $this->setFuwuCurMenu('fuwu_manage_authen_scene');
        /* 设置机构当前栏目 */
        $this->setFuwuCurItem('scene');
        return View::fetch($this->template_dir.'scene');
    }
    
    public function get_info_file_list($type){
        switch($type){
            case 'quality':
                $key=O2O_FUWU_UPLOAD_QUALIFY;
                break;
            case 'scene':
                $key=O2O_FUWU_UPLOAD_SCENE;
                break;
            default:
                ds_json_encode(10001, '类型错误');
        }
        $o2o_fuwu_upload_model=model('o2o_fuwu_upload');
        $file_list=$o2o_fuwu_upload_model->getO2oFuwuUploadList(array(
            'o2o_fuwu_organization_id'=>$this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],
            'o2o_fuwu_upload_type'=>$key,
        ));
        foreach($file_list as $k => $v){
            $file_list[$k]['o2o_fuwu_upload_url_url']= get_o2o_fuwu_file($this->o2o_fuwu_organization_info['o2o_fuwu_organization_id'],$v['o2o_fuwu_upload_url'],$type);
        }
        return $file_list;
    }

    /**
     *    栏目菜单
     */
    function getFuwuItemList() {
        if(request()->action()=='quality'){
        $item_list = array(
            array(
                'name' => 'quality',
                'text' => '资质证书',
                'url' => url('FuwuManageAuthen/quality'),
            ),
        );
        }else{
        $item_list = array(
            array(
                'name' => 'scene',
                'text' => '工作实景',
                'url' => url('FuwuManageAuthen/scene'),
            ),
        );
        }


        return $item_list;
    }
}

?>
