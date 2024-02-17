<?php

/**
 * 商品管理
 */

namespace app\admin\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Lang;
use GatewayClient\Gateway;
/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class InstantMessage extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/instant_message.lang.php');
    }

    /**
     * 商品管理
     */
    public function index() {
        $instant_message_model = model('instant_message');
        $f_name = trim(input('param.f_name'));
        $t_name = trim(input('param.t_name'));
        $time_add_from = input('param.add_time_from')?strtotime(input('param.add_time_from')):'';
        $time_add_to = input('param.add_time_to')?strtotime(input('param.add_time_to')):'';
        /**
         * 查询条件
         */
        $condition = array();
        if($f_name){
            $condition[]=array('instant_message_from_name','like','%'.$f_name.'%');
        }
        if($t_name){
            $condition[]=array('instant_message_to_name','like','%'.$t_name.'%');
        }
        if($time_add_from){
            $condition[]=array('instant_message_add_time','>=',$time_add_from);
        }
        if($time_add_to){
            $condition[]=array('instant_message_add_time','>=',$time_add_to);
        }
        $instant_message_open = input('param.instant_message_open');
        if (in_array($instant_message_open, array('0', '1', '2'))) {
            $condition[]=array('instant_message_open','=',$instant_message_open);
        }

        $instant_message_list = $instant_message_model->getInstantMessageList($condition, 10);
        foreach($instant_message_list as $key => $val){
            $instant_message_list[$key]=$instant_message_model->formatInstantMessage($val);
        }
        View::assign('instant_message_list', $instant_message_list);
        View::assign('show_page', $instant_message_model->page_info->render());



        View::assign('search', $condition);

        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /**
     * 删除商品
     */
    public function del() {
        $instant_message_id = input('param.instant_message_id');
        $instant_message_id_array = ds_delete_param($instant_message_id);
        if ($instant_message_id_array == FALSE) {
            ds_json_encode('10001', lang('ds_common_op_fail'));
        }
        $condition = array();
        $condition[] = array('instant_message_id','in', $instant_message_id_array);
        model('instant_message')->delInstantMessage($condition);
        $this->log(lang('ds_del') . lang('instant_message') . ' ID:' . implode('、', $instant_message_id_array), 1);
        ds_json_encode('10000', lang('ds_common_op_succ'));
    }

    /*
     * 直播设置
     */
    public function setting() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            View::assign('list_config', $list_config);
            $this->setAdminCurItem('setting');
            return View::fetch();
        } else {
            $update_array=array();
            $update_array['instant_message_gateway_url'] = input('param.instant_message_gateway_url');
            $update_array['instant_message_register_url'] = input('param.instant_message_register_url');
            $update_array['instant_message_open'] = input('param.instant_message_open');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                dkcache('config');
                $this->log(lang('ds_setting') . lang('instant_message'), 1);
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->log(lang('ds_setting') . lang('instant_message'), 0);
            }
        }
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_list'),
                'url' => url('InstantMessage/index')
            ),
            array(
                'name' => 'setting',
                'text' => lang('ds_setting'),
                'url' => url('InstantMessage/setting')
            ),
        );
        return $menu_array;
    }

}

?>
