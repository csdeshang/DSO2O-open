<?php

/*
 * 常规上传图片公共处理
 */

function ds_upload_pic($upload_path, $file_name, $save_name = '', $file_ext = ALLOW_IMG_EXT) {
    //判断是否上传图片
    if (empty($_FILES[$file_name]['name'])) {
        return ds_callback(false, '未获取到上传文件');
    }

    //验证上传文件格式
    $file_object = request()->file($file_name);
    if ($file_ext == 'mp4') {
        $fileSize = ALLOW_VIDEO_SIZE;
    } else {
        $fileSize = ALLOW_IMG_SIZE;
    }
    try {
        validate(['image' => 'fileSize:' . $fileSize . '|fileExt:' . $file_ext])
                ->check(['image' => $file_object]);
    } catch (\Exception $e) {
        return ds_callback(false, $e->getMessage());
    }

    // 未包含文件名
    if (!$save_name) {
        $save_name = date('YmdHis') . rand(10000, 99999);
    }
    //文件未带后缀,获取后缀名称
    if (!strpos($save_name, '.')) {
        $save_name .= '.' . pathinfo($_FILES[$file_name]['name'], PATHINFO_EXTENSION);
    }
    $upload_type = config('ds_config.upload_type');
    if ($upload_type == 'alioss') {//远程保存
        //阿里云 图片上传
        return upload_alioss($upload_path, $file_name, $save_name);
    } else {
        //本地存储
        $file_config = array(
            'disks' => array(
                'local' => array(
                    'root' => BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . $upload_path
                )
            )
        );
        config($file_config, 'filesystem');
        try {
            $file_name = \think\facade\Filesystem::putFileAs('', $file_object, $save_name);
            return ds_callback(true, '', array('file_name' => $file_name));
        } catch (\Exception $e) {
            return ds_callback(false, $e->getMessage());
        }
    }
}

/**
 * 删除单个文件
 * @param type $upload_path
 * @param type $file
 */
function ds_del_pic($upload_path,$file){
    
    $upload_type=explode('_', $file);
    if(in_array($upload_type['0'], array('alioss', 'cos'))){
        if ($upload_type['0'] == 'alioss') {
            //阿里云图片删除
            $alioss_object = array();
            $alioss_object[] = $upload_path . '/' . $file;
            return del_alioss($alioss_object);
        }
    }else{
        @unlink(BASE_UPLOAD_PATH.'/'.$upload_path . '/' . $file);
    }
}

/**
 * 取得图片的完整URL路径
 * 
 * @param string $file 视频名称
 * @return string
 */
function ds_get_pic($upload_path, $file){
    $fname = basename($file);
    $upload_type=explode('_', $fname);
    if(in_array($upload_type['0'], array('alioss', 'cos'))){//对象存储文件
        $aliendpoint_type = config('ds_config.aliendpoint_type');
        if($aliendpoint_type) {
            return HTTP_TYPE.config('ds_config.alioss_endpoint') . '/' . $upload_path . '/' . $file;
        }else{
            return 'https://'.config('ds_config.alioss_bucket').'.'.config('ds_config.alioss_endpoint') . '/' . $upload_path . '/' . $file;
        }
    }else{
        if ($file && file_exists(BASE_UPLOAD_PATH . '/' . $upload_path . '/' . $file)) {
            return UPLOAD_SITE_URL . '/' . $upload_path . '/' . $file;
        }else{
            return '';
        }
    }
    

}
/*
 * 公共生成缩略图
 * @param string $upload_path 上传文件路径
 * @param string $file_name 上传设置的文件名称
 * @param array $thumb_width 设置的图片宽度
 * @param array $thumb_height 设置的图片高度
 * @param array $thumb_ext 为空表示为不生成多余的图片，直接按照比例生成覆盖
 * @return string
 */

function ds_create_thumb($upload_path, $file_name, $thumb_width, $thumb_height, $thumb_ext = '') {
    if (!file_exists($upload_path . '/' . $file_name)) {
        return;
    }

    $thumb_width = explode(',', $thumb_width);
    $thumb_height = explode(',', $thumb_height);

    if (empty($thumb_ext)) {
        //为空则覆盖原有图片
        $image = \think\Image::open($upload_path . '/' . $file_name);
        $image->thumb($thumb_width[0], $thumb_height[0], \think\Image::THUMB_CENTER)->save($upload_path . '/' . $file_name);
    } else {
        $common_images_ext = explode(',', COMMON_IMAGES_EXT);
        $thumb_ext = explode(',', $thumb_ext);

        $ifthumb = FALSE;
        if ((count($thumb_width) == count($thumb_height)) && (count($thumb_width) == count($thumb_ext))) {
            $ifthumb = TRUE;
        }
        if ($ifthumb) {
            for ($i = 0; $i < count($thumb_width); $i++) {
                if (in_array($thumb_ext[$i], $common_images_ext)) {
                    $image = \think\Image::open($upload_path . '/' . $file_name);
                    $image->thumb($thumb_width[$i], $thumb_width[$i], \think\Image::THUMB_CENTER)->save($upload_path . '/' . str_ireplace('.', $thumb_ext[$i] . '.', $file_name));
                }
            }
        }
    }
}

/*
 * 公共删除图片
 */

function ds_unlink($upload_path, $file_name) {
    $common_images_ext = explode(',', COMMON_IMAGES_EXT);
    foreach ($common_images_ext as $ext) {
        $thumb_file = str_ireplace('.', $ext . '.', $file_name);
        @unlink($upload_path . DIRECTORY_SEPARATOR . $thumb_file);
    }
    @unlink($upload_path . DIRECTORY_SEPARATOR . $file_name);
}

/**
 * 只针对于相册图片上传的图片进行处理
 * upload_path  文件保存路径
 * file_name  上传文件的value值
 * save_name  文件保存名称
 */
function upload_albumpic($upload_path, $file_name = 'file', $save_name) {
    //判断是否上传图片
    if (empty($_FILES[$file_name]['name'])) {
        return ds_callback(false, '未获取到上传文件');
    }

    //验证上传文件格式
    $file_object = request()->file($file_name);
    try {
        validate(['image' => 'fileSize:' . ALLOW_IMG_SIZE . '|fileExt:' . ALLOW_IMG_EXT])
                ->check(['image' => $file_object]);
    } catch (\Exception $e) {
        return ds_callback(false, $e->getMessage());
    }

    //文件未带后缀,获取后缀名称
    if (!strpos($save_name, '.')) {
        $save_name .= '.' . pathinfo($_FILES[$file_name]['name'], PATHINFO_EXTENSION);
    }

    $upload_type = config('ds_config.upload_type');
    //远程保存
    if ($upload_type == 'alioss') {
        //阿里云 上传
        return upload_alioss($upload_path, $file_name, $save_name);
    } elseif ($upload_type == 'local') {
        //本地图片保存
        $upload_path = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . $upload_path;
        $file_config = array(
            'disks' => array(
                'local' => array(
                    'root' => $upload_path
                )
            )
        );
        config($file_config, 'filesystem');
        try {
            $file_name = \think\facade\Filesystem::putFileAs('', $file_object, $save_name);
            //本地上传文件 生成缩略图
            create_albumpic_thumb($upload_path, $file_name);
            return ds_callback(true, '', array('file_name' => $file_name));
        } catch (\Exception $e) {
            return ds_callback(false, $e->getMessage());
        }
    }
    //预留文件类型检测
}

/**
 * 阿里云OSS 上传
 * @param type $upload_path
 * @param type $file_name
 * @param string $save_name
 * @return type
 */
function upload_alioss($upload_path, $file_name, $save_name) {
    $accessId = config('ds_config.alioss_accessid');
    $accessSecret = config('ds_config.alioss_accesssecret');
    $bucket = config('ds_config.alioss_bucket');
    $endpoint = config('ds_config.alioss_endpoint');
    $aliendpoint_type = config('ds_config.aliendpoint_type') == '1' ? true : false;

    $object = $upload_path . '/' . 'alioss_' . $save_name;
    $filePath = $_FILES[$file_name]['tmp_name'];
    require_once root_path() . 'vendor/aliyuncs/oss-sdk-php/autoload.php';
    $OssClient = new \OSS\OssClient($accessId, $accessSecret, $endpoint, $aliendpoint_type);
    try {
        $fileinfo = $OssClient->uploadFile($bucket, $object, $filePath);
        // 根据图片路径 获取文件名
        $file_name = substr(strrchr($fileinfo['info']['url'], "/"), 1);
        return ds_callback(true,'',array('file_name' => $file_name));
    } catch (OssException $e) {
        return ds_callback(false,$e->getMessage());
    }
}


/**
 * 删除阿里云OSS文件
 * @param type $object  单个文件完整路径或数组
 */
function del_alioss($object) {
    if(empty($object)){
        return ds_callback(true, '');
    }
    //外网存储图片删除
    $accessId = config('ds_config.alioss_accessid');
    $accessSecret = config('ds_config.alioss_accesssecret');
    $bucket = config('ds_config.alioss_bucket');
    $endpoint = config('ds_config.alioss_endpoint');
    $aliendpoint_type = config('ds_config.aliendpoint_type') == '1' ? true : false;
    require_once root_path() . 'vendor/aliyuncs/oss-sdk-php/autoload.php';
    $OssClient = new \OSS\OssClient($accessId, $accessSecret, $endpoint, $aliendpoint_type);
    try {
        //deleteObjects  批量删除  deleteObject 单个文件删除
        $OssClient->deleteObjects($bucket, $object);
        return ds_callback(true, '');
    } catch (OssException $e) {
        return ds_callback(false, $e->getMessage());
    }
}

/*
 * 生成相册图片的缩略图
 * upload_path  文件路径
 * file_name  文件名称
 */

function create_albumpic_thumb($upload_path, $file_name) {
    if (!file_exists($upload_path . '/' . $file_name)) {
        return;
    }
    $ifthumb = FALSE;
    if (defined('GOODS_IMAGES_WIDTH') && defined('GOODS_IMAGES_HEIGHT') && defined('GOODS_IMAGES_EXT')) {
        $thumb_width = explode(',', GOODS_IMAGES_WIDTH);
        $thumb_height = explode(',', GOODS_IMAGES_HEIGHT);
        $thumb_ext = explode(',', GOODS_IMAGES_EXT);
        if (count($thumb_width) == count($thumb_height) && count($thumb_width) == count($thumb_ext)) {
            $ifthumb = TRUE;
        }
    }
    if ($ifthumb) {
        for ($i = 0; $i < count($thumb_width); $i++) {
            $image = \think\Image::open($upload_path . '/' . $file_name);
            $image->thumb($thumb_width[$i], $thumb_height[$i], \think\Image::THUMB_CENTER)->save($upload_path . '/' . str_ireplace('.', $thumb_ext[$i] . '.', $file_name));
        }
    }
}

/* * 删除商品图文件
 * pic_list  要删除的文件
 * */

function del_albumpic($pic_list) {
    if (!empty($pic_list) && is_array($pic_list)) {
        
        $image_ext = explode(',', GOODS_IMAGES_EXT);
        
        $alioss_object = array();
        foreach ($pic_list as $v) {
            $upload_type = explode('_', $v['apic_cover']);
            //外网存储图片
            if (in_array($upload_type['0'], array('alioss', 'cos'))) {
                if ($upload_type['0'] == 'alioss') {
                    $alioss_object[] = ATTACH_GOODS . '/' . $v['store_id'] . '/' . date('Ymd', $v['apic_uploadtime']) . '/' . $v['apic_cover'];
                }
            } else {
                $upload_path = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_GOODS . DIRECTORY_SEPARATOR . $v['store_id'] . DIRECTORY_SEPARATOR . date('Ymd',$v['apic_uploadtime']);
                foreach ($image_ext as $ext) {
                    $file = str_ireplace('.', $ext . '.', $v['apic_cover']);
                    @unlink($upload_path . DIRECTORY_SEPARATOR . $file);
                }
                @unlink($upload_path . DIRECTORY_SEPARATOR . $v['apic_cover']);
            }
        }
        $upload_type = config('ds_config.upload_type');
        if ($upload_type == 'alioss') {
            //阿里云 图片删除
            return del_alioss($alioss_object);
        }
    }
}
