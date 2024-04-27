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
class  Sellerinfo extends BaseSeller
{
    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerinfo.lang.php');
    }
    /**
     * 店铺信息
     */
    public function index()
    {
        $store_model = model('store');
        $storebindclass_model = model('storebindclass');
        $storeclass_model = model('storeclass');
        $storegrade_model = model('storegrade');

        // 店铺信息
        $store_info = $store_model->getStoreInfoByID(session('store_id'));
        View::assign('store_info', $store_info);

        // 店铺分类信息
        $store_class_info = $storeclass_model->getStoreclassInfo(array('storeclass_id' => $store_info['storeclass_id']));
        View::assign('store_class_name', $store_class_info['storeclass_name']);

        // 店铺等级信息
        $store_grade_info = $storegrade_model->getOneStoregrade($store_info['grade_id']);
        View::assign('store_grade_name', $store_grade_info['storegrade_name']);

        $storejoinin_model = model('storejoinin');
        $joinin_detail = $storejoinin_model->getOneStorejoinin(array('member_id' => $store_info['member_id']));
        View::assign('joinin_detail', $joinin_detail);

        $store_bind_class_list = $storebindclass_model->getStorebindclassList(array(
                                                                                    array('store_id' ,'=', session('store_id')),
                                                                                    array('storebindclass_state','in', array(1, 2))
                                                                                ), null);
        $goods_class = model('goodsclass')->getGoodsclassIndexedListAll();
           for ($i = 0, $j = count($store_bind_class_list); $i < $j; $i++) {
               $store_bind_class_list[$i]['class_1_name'] = @$goods_class[$store_bind_class_list[$i]['class_1']]['gc_name'];
               $store_bind_class_list[$i]['class_2_name'] = @$goods_class[$store_bind_class_list[$i]['class_2']]['gc_name'];
               $store_bind_class_list[$i]['class_3_name'] = @$goods_class[$store_bind_class_list[$i]['class_3']]['gc_name'];
           }
        View::assign('store_bind_class_list', $store_bind_class_list);

        $this->setSellerCurMenu('sellerinfo');
        $this->setSellerCurItem('index');

        return View::fetch($this->template_dir.'index');
    }

    /**
     * 经营类目列表
     */
    public function bind_class()
    {

        $storebindclass_model = model('storebindclass');

        $store_bind_class_list = $storebindclass_model->getStorebindclassList(array('store_id' => session('store_id')), null);
        $goods_class = model('goodsclass')->getGoodsclassIndexedListAll();
        for ($i = 0, $j = count($store_bind_class_list); $i < $j; $i++) {
            $store_bind_class_list[$i]['class_1_name'] = @$goods_class[$store_bind_class_list[$i]['class_1']]['gc_name'];
            $store_bind_class_list[$i]['class_2_name'] = @$goods_class[$store_bind_class_list[$i]['class_2']]['gc_name'];
            $store_bind_class_list[$i]['class_3_name'] = @$goods_class[$store_bind_class_list[$i]['class_3']]['gc_name'];
        }
        View::assign('bind_list', $store_bind_class_list);

        $this->setSellerCurMenu('sellerinfo');
        $this->setSellerCurItem('bind_class');
        return View::fetch($this->template_dir.'bind_class_index');
    }

    /**
     * 申请新的经营类目
     */
    public function bind_class_add()
    {
        $goodsclass_model = model('goodsclass');
        $gc_list = $goodsclass_model->getGoodsclassListByParentId(0);
        View::assign('gc_list', $gc_list);

        $this->setSellerCurMenu('sellerinfo');
        $this->setSellerCurItem('bind_class');
        return View::fetch($this->template_dir.'bind_class_add');
    }

    /**
     * 申请新经营类目保存
     */
    public function bind_class_save()
    {
        if (!request()->isPost())
            exit();
        $goods_class_array = input('post.goods_class');#获取数组
        if (preg_match('/^[\d,]+$/', $goods_class_array)) {
            @list($class_1, $class_2, $class_3) = explode(',', trim($goods_class_array));
        }
        else {
            ds_json_encode(10001,lang('ds_common_save_fail'));
        }

        $storebindclass_model = model('storebindclass');

        $param = array();
        $param['store_id'] = session('store_id');
        $param['storebindclass_state'] = 0;
        $param['class_1'] = $class_1;
        $last_gc_id = $class_1;
        if (!empty($class_2)) {
            $param['class_2'] = $class_2;
            $last_gc_id = $class_2;
        }
        if (!empty($class_3)) {
            $param['class_3'] = $class_3;
            $last_gc_id = $class_3;
        }

        // 检查类目是否已经存在
        $store_bind_class_info = $storebindclass_model->getStorebindclassInfo($param);
        if (!empty($store_bind_class_info)) {
            ds_json_encode(10001,'该类目已经存在');
        }

        //取分佣比例
        $goods_class_info = model('goodsclass')->getGoodsclassInfoById($last_gc_id);
        $param['commis_rate'] = $goods_class_info['commis_rate'];
        $result = $storebindclass_model->addStorebindclass($param);

        if ($result) {
            ds_json_encode(10000,'申请成功，请等待系统审核');
        }
        else {
            ds_json_encode(10001,lang('ds_common_save_fail'));
        }
    }

    /**
     * 删除申请的经营类目
     */
    public function bind_class_del()
    {
        $condition = array();
        $condition[] = array('storebindclass_id','=',intval(input('param.bid')));
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('storebindclass_state','=',0);
        $del = model('storebindclass')->delStorebindclass($condition);
        if ($del) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        }
        else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 店铺续签
     */
    public function reopen()
    {
        $storereopen_model = model('storereopen');
        $reopen_list = $storereopen_model->getStorereopenList(array('storereopen_store_id' => session('store_id')));
        View::assign('reopen_list', $reopen_list);

        $store_info = $this->store_info;
        if (intval($store_info['store_endtime']) > 0) {
            $store_info['store_endtime_text'] = date('Y-m-d', $store_info['store_endtime']);
            $reopen_time = $store_info['store_endtime'] - 3600 * 24 + 1 - TIMESTAMP;
            if ($store_info['store_endtime'] - TIMESTAMP >= 0 && $reopen_time < 2592000) {
                //(<30天)
                $store_info['reopen'] = true;
            }
            $store_info['allow_applay_date'] = $store_info['store_endtime'] - 2592000;
        }

        if (!empty($reopen_list)) {
            $last = reset($reopen_list);
            $store_endtime = $store_info['store_endtime'];
            if ($store_endtime - TIMESTAMP < 2592000 && $store_endtime - TIMESTAMP >= 0) {
                //(<30天)
                $store_info['reopen'] = true;
            }
            else {
                $store_info['reopen'] = false;
            }
        }
        View::assign('store_info', $store_info);

        //店铺等级
        $grade_list = rkcache('storegrade', true);

        View::assign('grade_list', $grade_list);

        //默认选中当前级别
        View::assign('current_grade_id', session('grade_id'));

        //如果存在有未上传凭证或审核中的信息，则不能再申请续签
        $condition = array();
        $condition[] = array('storereopen_state','in',array(0, 1));
        $condition[] = array('storereopen_store_id','=',session('store_id'));
        $reopen_info = $storereopen_model->getStorereopenInfo($condition);
        if ($reopen_info) {
            if ($reopen_info['storereopen_state'] == '0') {
                View::assign('upload_cert', true);
                View::assign('reopen_info', $reopen_info);
            }
        }
        else {
            View::assign('applay_reopen', isset($store_info['reopen']) ? true : false);
        }

        $this->setSellerCurMenu('sellerinfo');
        $this->setSellerCurItem('reopen');

        return View::fetch($this->template_dir.'reopen_index');
    }

    /**
     * 申请续签
     */
    public function reopen_add() {
        if (request()->isPost()) {
            
            $storereopen_grade_id = intval(input('post.storereopen_grade_id'));
            $storereopen_year = intval(input('post.storereopen_year'));
            if ($storereopen_grade_id <= 0 || $storereopen_year <= 0)
                exit();

            // 店铺信息
            $store_info = $this->store_info;
            if (empty($store_info['store_endtime'])) {
                ds_json_encode(10001,'您的店铺使用期限无限制，无须续签');
            }

            $storereopen_model = model('storereopen');

            //如果存在有未上传凭证或审核中的信息，则不能再申请续签
            $condition = array();
            $condition[] = array('storereopen_state','in',array(0, 1));
            $condition[] = array('storereopen_store_id','=',session('store_id'));
            if ($storereopen_model->getStorereopenCount($condition)) {
                ds_json_encode(10001,'目前尚存在申请中的续签信息，不能重复申请');
            }

            $data = array();
            //取店铺等级信息
            $grade_list = rkcache('storegrade', true);
            if (empty($grade_list[$storereopen_grade_id])) {
                exit();
            }

            //取得店铺信息

            $data['storereopen_grade_id'] = $storereopen_grade_id;
            $data['storereopen_grade_name'] = $grade_list[$storereopen_grade_id]['storegrade_name'];
            $data['storereopen_grade_price'] = $grade_list[$storereopen_grade_id]['storegrade_price'];

            $data['storereopen_store_id'] = session('store_id');
            $data['storereopen_store_name'] = session('store_name');
            $data['storereopen_year'] = $storereopen_year;
            $data['storereopen_pay_amount'] = $data['storereopen_grade_price'] * $data['storereopen_year'];
            if ($data['storereopen_pay_amount'] == 0) {
                $data['storereopen_state'] = 1;
            }
            $insert = $storereopen_model->addStorereopen($data);
            if ($insert) {
                if ($data['storereopen_pay_amount'] == 0) {
                    ds_json_encode(10000,'您的申请已经提交，请等待管理员审核');
                } else {
                    ds_json_encode(10000,lang('ds_common_save_succ') . '，需付款金额' . ds_price_format($data['storereopen_pay_amount']) . '元，请尽快完成付款，付款完成后请上传付款凭证');
                }
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    //上传付款凭证
    public function reopen_upload()
    {
        if (!empty($_FILES['storereopen_pay_cert']['tmp_name'])) {
            $res = ds_upload_pic(ATTACH_PATH . DIRECTORY_SEPARATOR . 'store_joinin', 'storereopen_pay_cert');
            if ($res['code']) {
                $pic_name = $res['data']['file_name'];
            } else {
                $this->error($res['msg']);
            }
        }
        $data = array();
        $data['storereopen_pay_cert'] = $pic_name;
        $data['storereopen_pay_cert_explain'] = input('post.storereopen_pay_cert_explain');
        $data['storereopen_state'] = 1;
        $storereopen_model = model('storereopen');
        $update = $storereopen_model->editStorereopen($data, array('storereopen_id' => input('post.storereopen_id'), 'storereopen_state' => 0));
        if ($update) {
            $this->success('上传成功，请等待系统审核');
        }
        else {
            $this->error(lang('ds_common_save_fail'));
        }
    }

    /**
     * 删除未上传付款凭证的续签信息
     */
    public function reopen_del()
    {
        $storereopen_model = model('storereopen');
        $condition = array();
        $condition[] = array('storereopen_id','=',intval(input('param.storereopen_id')));
        $condition[] = array('storereopen_state','=',0);
        $condition[] = array('storereopen_store_id','=',session('store_id'));
        $del = $storereopen_model->delStorereopen($condition);
        if ($del) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        }
        else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $name 当前导航的name
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array = array();
        switch (request()->action()) {
            case 'index':
                $menu_array []= array(
                    'name' => 'bind_class', 'text' => lang('ds_member_path_bind_class'),
                    'url' => url('Sellerinfo/bind_class')
                );
                $menu_array[] = array(
                    'name' => 'index', 'text' => lang('ds_member_path_store_info'),
                    'url' => url('Sellerinfo/index')
                );
                $menu_array[] = array(
                    'name' => 'reopen', 'text' => lang('ds_member_path_store_reopen'),
                    'url' => url('Sellerinfo/reopen')
                );
                break;
            case 'bind_class':
                $menu_array []= array(
                        'name' => 'bind_class', 'text' => lang('ds_member_path_bind_class'),
                        'url' => url('Sellerinfo/bind_class')
                );
                    $menu_array[] = array(
                        'name' => 'index', 'text' => lang('ds_member_path_store_info'),
                        'url' => url('Sellerinfo/index')
                    );
                    $menu_array[] = array(
                        'name' => 'reopen', 'text' => lang('ds_member_path_store_reopen'),
                        'url' => url('Sellerinfo/reopen')
                    );
                break;
            case 'reopen':
            $menu_array = array(
                array(
                    'name' => 'index', 'text' => lang('ds_member_path_bind_class'),
                    'url' => url('Sellerinfo/bind_class')
                ), array(
                    'name' => 'index', 'text' => lang('ds_member_path_store_info'),
                    'url' => url('Sellerinfo/index')
                ),array(
                    'name' => 'reopen', 'text' => lang('ds_member_path_store_reopen'),
                    'url' => url('Sellerinfo/reopen')
                )
            );
                break;
        }
        if (!empty($array)) {
            $menu_array[] = $array;
        }
       return $menu_array;
    }
}