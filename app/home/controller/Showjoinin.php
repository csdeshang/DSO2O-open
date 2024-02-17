<?php

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
class  Showjoinin extends BaseMall {
    

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/showjoinin.lang.php');
    }
    /*
     * 入驻相关首页介绍
     */

    public function index() {
        $code_info = config('ds_config.store_joinin_pic');
        $info['pic'] = array();
        $info['show_txt'] = '';
        if (!empty($code_info)) {
            $info = unserialize($code_info);
        }
        $storejoinin_model = model('storejoinin');
        $joinin_detail = $storejoinin_model->getOneStorejoinin(array('member_id' => session('member_id')));
        View::assign('joinin_detail', $joinin_detail); //入驻信息
        View::assign('pic_list', $info['pic']); //首页图片
        View::assign('show_txt', $info['show_txt']); //贴心提示
        $help_model = model('help');
        $condition = array(); 
        $condition[] = array('helptype_id','=','1');//入驻指南
        $help_list = $help_model->getHelpList($condition, '', 4); //显示4个
        //获取第一文章分类的前三篇文章
        $index_articles=Db::name('article')->where('ac.ac_code','notice')->where('a.article_show',1)->alias('a')->field('a.article_id,a.article_url,a.article_title')->order('a.article_sort asc,a.article_time desc')->limit(5)->join('articleclass ac','a.ac_id=ac.ac_id')->select()->toArray();
        View::assign('index_articles', $index_articles);
        View::assign('help_list', $help_list);
        View::assign('article_list', ''); //底部不显示文章分类
        View::assign('show_sign', 'joinin');
        View::assign('html_title', config('ds_config.site_name') . ' - ' . lang('tenants'));
        return View::fetch($this->template_dir . 'index');
    }

}

?>
