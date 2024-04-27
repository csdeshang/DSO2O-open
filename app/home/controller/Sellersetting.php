<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
use think\Image;
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
class Sellersetting extends BaseSeller {


    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellersetting.lang.php');
    }

    /*
     * 店铺设置
     */

    public function setting() {
        /**
         * 实例化模型
         */
        $store_model = model('store');

        $store_id = session('store_id'); //当前店铺ID
        /**
         * 获取店铺信息
         */
        $store_info = $store_model->getStoreInfoByID($store_id);

        $if_miniprocode=$this->getMiniProCode(1);
        View::assign('miniprogram_code',$if_miniprocode?(UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png'):'');
        /**
         * 保存店铺设置
         */
        if (request()->isPost()) {
            /**
             * 更新入库
             */
            $param = array(
                'store_qq' => input('post.store_qq'),
                'store_ww' => input('post.store_ww'),
                'store_phone' => input('post.store_phone'),
                'store_mainbusiness' => input('post.store_mainbusiness'),
                'store_keywords' => input('post.seo_keywords'),
                'store_description' => input('post.seo_description')
            );



            if (!empty(input('post.store_name'))) {
                $store = $store_model->getStoreInfo(array('store_name' => input('param.store_name')));
                //店铺名存在,则提示错误
                if (!empty($store) && ($store_id != $store['store_id'])) {
                    ds_json_encode(10001, lang('please_change_another_name'));
                }
                $param['store_name'] = input('post.store_name');
            }
            //店铺名称修改处理
            if (input('param.store_name') != $store_info['store_name'] && !empty(input('post.store_name'))) {
                $condition = array();
                $condition[] = array('store_id','=',$store_id);
                $update = array();
                $update['store_name'] = input('param.store_name');
                Db::name('goodscommon')->where($condition)->update($update);
                Db::name('goods')->where($condition)->update($update);
            }
            
            $store_validate = ds_validate('store');
            if (!$store_validate->scene('seller_setting')->check($param)) {
                ds_json_encode(10001, $store_validate->getError());
            }

            $this->getMiniProCode(1);
            $store_model->editStore($param, array('store_id' => $store_id));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
        /**
         * 实例化店铺等级模型
         */
        // 从基类中读取店铺等级信息
        $store_grade = $this->store_grade;

        //编辑器多媒体功能
        $editor_multimedia = false;
        $sg_fun = @explode('|', $store_grade['storegrade_function']);
        if (!empty($sg_fun) && is_array($sg_fun)) {
            foreach ($sg_fun as $fun) {
                if ($fun == 'editor_multimedia') {
                    $editor_multimedia = true;
                }
            }
        }
        View::assign('editor_multimedia', $editor_multimedia);

        /**
         * 输出店铺信息
         */
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_setting');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('store_setting');
        View::assign('store_info', $store_info);
        View::assign('store_grade', $store_grade);
        /**
         * 页面输出
         */
        return View::fetch($this->template_dir . 'setting');
    }

    public function getMiniProCode($force=0){
        if($force || !file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png')){
            model('wechat')->getOneWxconfig();
            $a=model('wechat')->getMiniProCode(session('store_id'), 'pages/home/storedetail/Storedetail');
            if(@imagecreatefromstring($a)==false){
                $a= json_decode($a);
                //View::assign('errmsg',$a->errmsg);
            }else{
                if (is_dir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id')) || (!is_dir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id')) && mkdir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id'), 0755, true))) {
                    file_put_contents(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png', $a);
                    return true;
                } else {
                    //View::assign('errmsg','没有权限生成目录');
                }
                
            }
            
        }else{
            return true;
        }
        return false;
    }
    public function store_image_upload() {
        $store_id = session('store_id');
        $upload_file = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_id;
        $file_name = session('store_id') . '_' . date('YmdHis') . rand(10000, 99999).'.png';
        $store_image_name = input('param.id');

        if (!in_array($store_image_name, array('store_logo', 'store_banner','mb_title_img', 'store_avatar'))) {
            exit;
        }

        if (!empty($_FILES[$store_image_name]['name'])) {
            $res = ds_upload_pic(ATTACH_STORE . DIRECTORY_SEPARATOR . $store_id, $store_image_name, $file_name);
            if ($res['code']) {
                $file_name = $res['data']['file_name'];
                if(file_exists($upload_file . DIRECTORY_SEPARATOR . $file_name)){
                /* 处理图片 */
                $image = Image::open($upload_file . DIRECTORY_SEPARATOR . $file_name);
                switch ($store_image_name) {
                    case 'store_logo':
                        $image->thumb(200, 60, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
                    case 'store_banner':
                        $image->thumb(1920, 150, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
					case 'mb_title_img':
					    $image->thumb(414, 162, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
					    break;
                    case 'store_avatar':
                        $image->thumb(100, 100, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
                    default:
                        break;
                }
                }
            } else {
                json_encode(array('error' => $res['msg']));
                exit;
            }
        }
        $store_model = model('store');
        //删除原图
        $store_info = $store_model->getStoreInfoByID($store_id);
        @unlink($upload_file . DIRECTORY_SEPARATOR . $store_info[$store_image_name]);
        @unlink($upload_file . DIRECTORY_SEPARATOR . 'm_'.$store_info[$store_image_name]);
        $result = $store_model->editStore(array($store_image_name => $file_name), array('store_id' => $store_id));
        if ($result) {
            $data = array();
            $data['file_name'] = $file_name;
            $data['file_path'] = ds_get_pic( ATTACH_STORE . '/' . $store_id , $file_name);
            /**
             * 整理为json格式
             */
            $output = json_encode($data);
            echo $output;
            exit;
        }
    }

    /**
     * 店铺幻灯片
     */
    public function store_slide() {
        /**
         * 模型实例化
         */
        $store_model = model('store');
        $upload_model = model('upload');
        /**
         * 保存店铺信息
         */
        if (request()->isPost()) {
            // 更新店铺信息
            $update = array();
            $update['store_slide'] = implode(',', input('post.image_path/a'));
            $update['store_slide_url'] = implode(',', input('post.image_url/a'));
            $store_model->editStore($update, array('store_id' => session('store_id')));

            // 删除upload表中数据
            $upload_model->delUpload(array('upload_type' => 3, 'item_id' => session('store_id')));
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            // 删除upload中的无用数据
            $upload_info = $upload_model->getUploadList(array('upload_type' => 3, 'item_id' => session('store_id')), 'file_name');
            if (is_array($upload_info) && !empty($upload_info)) {
                foreach ($upload_info as $val) {
                    @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_SLIDE . DIRECTORY_SEPARATOR . $val['file_name']);
                }
            }
            $upload_model->delUpload(array('upload_type' => 3, 'item_id' => session('store_id')));

            $store_info = $store_model->getStoreInfoByID(session('store_id'));
            if ($store_info['store_slide'] != '' && $store_info['store_slide'] != ',,,,') {
                View::assign('store_slide', explode(',', $store_info['store_slide']));
                View::assign('store_slide_url', explode(',', $store_info['store_slide_url']));
            }
            $this->setSellerCurMenu('seller_setting');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('store_slide');
            return View::fetch($this->template_dir . 'slide');
        }
    }

    /**
     * 店铺幻灯片ajax上传
     */
    public function silde_image_upload() {
        $file_id = intval(input('param.file_id'));
        $id = input('param.id');
        if($file_id<0 || empty($id)){
            return;
        }
        
        $file_name = session('store_id') . '_' . $file_id . '.png';
        $res = ds_upload_pic(ATTACH_SLIDE, $id, $file_name);
        if ($res['code']) {
            $file_name = $res['data']['file_name'];
            $img_path = $file_name;
            $output['file_id'] = $file_id;
            $output['id'] = $id;
            $output['file_name'] = $img_path;
            $output['file_url'] = ds_get_pic(ATTACH_SLIDE, $img_path);
            echo json_encode($output);
            exit;
        } else {
            json_encode(array('error' => $res['msg']));
            exit;
        }
    }

    /**
     * ajax删除幻灯片图片
     */
    public function dorp_img() {
        $file_id = intval(input('param.file_id'));
        $img_src = input('param.img_src');
        if($file_id<0 || empty($img_src)){
            return;
        }
        $ext =  strrchr($img_src, '.');
        $file_name = session('store_id') . '_' . $file_id .$ext;
        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_SLIDE . DIRECTORY_SEPARATOR . $file_name);
        echo json_encode(array('succeed' => lang('ds_common_save_succ')));
        die;
    }

    /**
     * 卖家店铺主题设置
     *
     * @param string
     * @param string
     * @return
     */
    public function theme() {
        /**
         * 店铺信息
         */
        $store_class = model('store');
        $store_info = $store_class->getStoreInfoByID(session('store_id'));
        /**
         * 主题配置信息
         */
        $style_data = array();
        $style_configurl = PUBLIC_PATH . '/static/home/default/store/styles/' . "styleconfig.php";

        if (file_exists($style_configurl)) {
            include_once($style_configurl);
        }
        /**
         * 当前店铺主题
         */
        $curr_store_theme = !empty($store_info['store_theme']) ? $store_info['store_theme'] : 'default';
        /**
         * 当前店铺预览图片
         */
        $curr_image = BASE_SITE_ROOT . '/static/home/default/store/styles/' . $curr_store_theme . '/images/preview.jpg';

        $curr_theme = array(
            'curr_name' => $curr_store_theme,
            'curr_truename' => $style_data[$curr_store_theme]['truename'],
            'curr_image' => $curr_image
        );

            /**
             * 店铺等级
             */
            $grade_class = model('storegrade');
            $grade = $grade_class->getOneStoregrade($store_info['grade_id']);

            /**
             * 可用主题
             */
            $themes = explode('|', $grade['storegrade_template']);
        $theme_list = array();
        /**
         * 可用主题预览图片
         */
        foreach ($style_data as $key => $val) {
            if (in_array($key, $themes)) {
                $theme_list[$key] = array(
                    'name' => $key, 'truename' => $val['truename'],
                    'image' => BASE_SITE_ROOT . '/static/home/default/store/styles/' . $key . '/images/preview.jpg'
                );
            }
        }
        /**
         * 页面输出
         */
        $this->setSellerCurMenu('seller_setting');
        $this->setSellerCurItem('store_theme');

        View::assign('store_info', $store_info);
        View::assign('curr_theme', $curr_theme);
        View::assign('theme_list', $theme_list);
        return View::fetch($this->template_dir . 'theme');
    }

    /**
     * 卖家店铺主题设置
     *
     * @param string
     * @param string
     * @return
     */
    public function set_theme() {
        //读取语言包
        $style = input('param.style_name');
        $style = isset($style) ? trim($style) : null;
        if (!empty($style) && file_exists(PUBLIC_PATH . '/static/home/default/store/styles/theme/' . $style . '/images/preview.jpg')) {
            $store_class = model('store');
            $rs = $store_class->editStore(array('store_theme' => $style), array('store_id' => session('store_id')));
            ds_json_encode(10000,lang('store_theme_congfig_success'));
        } else {
            ds_json_encode(10001,lang('store_theme_congfig_fail'));
        }
    }




    public function map() {
        $this->setSellerCurMenu('seller_setting');
        $this->setSellerCurItem('store_map');
        /**
         * 实例化模型
         */
        $store_model = model('store');

        $store_id = session('store_id'); //当前店铺ID
        /**
         * 获取店铺信息
         */
        $store_info = $store_model->getStoreInfoByID($store_id);

        /**
         * 保存店铺设置
         */
        if (request()->isPost()) {
            model('store')->editStore(array(
                'store_address' => input('post.company_address_detail'),
                'region_id' => input('post.district_id') ? input('post.district_id') : (input('post.city_id') ? input('post.city_id') : (input('post.province_id') ? input('post.province_id') : 0)),
                'area_info' => input('post.company_address'),
                'store_longitude' => input('post.longitude'),
                'store_latitude' => input('post.latitude')
                    ), array(
                'store_id' => session('store_id'),
            ));
            ds_json_encode(10000,lang('save_success'));
        }
        View::assign('store_info', $store_info);
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        return View::fetch($this->template_dir . 'map');
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
                'name' => 'store_setting', 'text' => lang('ds_member_path_store_config'),
                'url' => url('Sellersetting/setting')
            ),
            array(
                'name' => 'store_map', 'text' => lang('ds_member_path_store_map'),
                'url' => url('Sellersetting/map')
            ),
            array(
                'name' => 'store_slide', 'text' => lang('ds_member_path_store_slide'),
                'url' => url('Sellersetting/store_slide')
            ),
            array(
                'name' => 'store_theme', 'text' => lang('store_theme'), 'url' => url('Sellersetting/theme')
            ),
        );
        return $menu_array;
    }

}

?>
