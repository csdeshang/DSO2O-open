<?php

/**
 * ============================================================================
 * 通用文件
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 数据层模型
 */

namespace app\common\model;

use think\Model;
use think\Response;
use think\exception\HttpResponseException;

class BaseModel {
    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;
    
    public function __construct() {
        
    }
    
    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        
        //抛出异常
//        return $v->failException(true)->check($data);
        if (!$v->check($data)){
            $this->validateMsg($v->getError());
        }
        
        
    }
    
    //根据来源返回不同错误展示信息
    protected function validateMsg($message) {
        $module = app('http')->getName();
        $isAjax = request()->isAjax();
        if ($module == 'api' || $isAjax) {
            ds_json_encode(10001, $message);
        } elseif ($module == 'admin' || $module == 'home') {
            $type = 'view';
            $header = [];
            $result = [
                'code' => 0,
                'msg' => $message,
                'data' => '',
                'url' => null,
                'wait' => 3,
            ];

            $response = Response::create(app()->config->get('jump.dispatch_error_tmpl'), $type)->assign($result)->header($header);
            throw new HttpResponseException($response);
        } else {
            ds_json_encode(10001, '控制器验证器返回错误');
        }
    }

    //model 层验证器 返回【废弃 使用validate进行验证】
    protected function validateErrorMsg($message) {

        $module = app('http')->getName();
        $isAjax = request()->isAjax();
        
        if ($module == 'api' || $isAjax) {
            //api 目录 以及 home和admin目录下的ajax请求返回
            ds_json_encode(10001, $message);
        } elseif ($module == 'admin' || $module == 'home') {
            $type = 'view';
            $header = [];
            $result = [
                'code' => 0,
                'msg' => $message,
                'data' => '',
                'url' => null,
                'wait' => 3,
            ];

            $response = Response::create(app()->config->get('jump.dispatch_error_tmpl'), $type)->assign($result)->header($header);
            throw new HttpResponseException($response);
        } else {
            ds_json_encode(10001, 'model层验证器返回错误');
        }
    }
}
