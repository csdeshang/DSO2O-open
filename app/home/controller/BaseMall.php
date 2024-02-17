<?php

/**
 * 公共用户可以访问的类(不需要登录)
 */

namespace app\home\controller;
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
class  BaseMall extends BaseHome {

    public function initialize() {
        parent::initialize();
		if(request()->isMobile() && config('ds_config.h5_force_redirect')){
            $this->isHomeUrl();
        }
        $this->template_dir = 'default/mall/'.  strtolower(request()->controller()).'/';
    }
	
	/**
     * 手机端访问自动跳转
     */
    protected function isHomeUrl(){
        $controller = request()->controller();//取控制器名
        $action = request()->action();//取方法名
        $input = request()->param();//取参数
        $param = http_build_query($input);//将参数转换成链接形式

        if ($controller == 'Goods' && $action == 'index'){//商品详情
            header('Location:'.config('ds_config.h5_site_url').'/pages/home/goodsdetail/GoodsDetail?'.$param);
            exit;
        }else {
            header('Location:'.config('ds_config.h5_site_url'));exit;//其它页面跳转到首页
        }
    }
}

?>
