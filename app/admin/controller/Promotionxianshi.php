<?php
/**
 * 秒杀
 */

namespace app\admin\controller;
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
class  Promotionxianshi extends AdminControl
{
    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/promotionxianshi.lang.php');
    }


    /**
     * 活动列表
     **/
    public function index()
    {
        //自动开启秒杀
        if (intval(input('param.promotion_allow')) === 1) {
            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_allow'] = 1;
            $config_model->editConfig($update_array);
        }

        $xianshi_model = model('pxianshi');
        $condition = array();
        if (!empty(input('param.xianshi_name'))) {
            $condition[]=array('xianshi_name','like', '%' . input('param.xianshi_name') . '%');
        }
        if (!empty(input('param.store_name'))) {
            $condition[]=array('store_name','like', '%' . input('param.store_name') . '%');
        }
        if (!empty(input('param.state'))) {
            $condition[]=array('xianshi_state','=',intval(input('param.state')));
        }
        $xianshi_list = $xianshi_model->getXianshiList($condition, 10, 'xianshi_state desc, xianshi_end_time desc');
        View::assign('xianshi_list', $xianshi_list);
        View::assign('show_page', $xianshi_model->page_info->render());
        View::assign('xianshi_state_array', $xianshi_model->getXianshiStateArray());

        $this->setAdminCurItem('xianshi_list');
        // 输出自营店铺IDS
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        return View::fetch();
    }

    /**
     * 秒杀活动取消
     **/
    public function xianshi_cancel()
    {
        $xianshi_id = intval(input('param.xianshi_id'));
        $xianshi_model = model('pxianshi');
        $result = $xianshi_model->cancelXianshi(array('xianshi_id' => $xianshi_id));
        if ($result) {
            $this->log('取消秒杀活动，活动编号' . $xianshi_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }
        else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 秒杀活动删除
     **/
    public function xianshi_del()
    {
        $xianshi_model = model('pxianshi');
        $xianshi_id = input('param.xianshi_id');
        $xianshi_id_array = ds_delete_param($xianshi_id);
        if($xianshi_id_array === FALSE){
            ds_json_encode(10001, lang('param_error'));
        }
        $condition = array(array('xianshi_id','in', $xianshi_id_array));
        $result = $xianshi_model->delXianshi($condition);
        if ($result) {
            $this->log('删除秒杀活动，活动编号' . $xianshi_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }
        else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 活动详细信息
     **/
    public function xianshi_detail()
    {
        $xianshi_id = intval(input('param.xianshi_id'));

        $xianshi_model = model('pxianshi');
        $xianshigoods_model = model('pxianshigoods');

        $xianshi_info = $xianshi_model->getXianshiInfoByID($xianshi_id);
        if (empty($xianshi_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('xianshi_info', $xianshi_info);

        //获取秒杀商品列表
        $condition = array();
        $condition[] = array('xianshi_id','=',$xianshi_id);
        $xianshigoods_list = $xianshigoods_model->getXianshigoodsExtendList($condition,5);
        View::assign('xianshigoods_list', $xianshigoods_list);
        View::assign('show_page',$xianshigoods_model->page_info->render());
        return View::fetch();
    }

    /**
     * 套餐管理
     **/
    public function xianshi_quota()
    {
        $xianshiquota_model = model('pxianshiquota');

        $condition = array();
        $condition[]=array('store_name','like', '%' . input('param.store_name') . '%');
        $xianshiquota_list = $xianshiquota_model->getXianshiquotaList($condition, 10, 'xianshiquota_endtime desc');
        View::assign('xianshiquota_list', $xianshiquota_list);
        View::assign('show_page', $xianshiquota_model->page_info->render());

        $this->setAdminCurItem('xianshi_quota');
        return View::fetch();

    }

    /**
     * 设置
     **/
   public function xianshi_setting() {
        if (!(request()->isPost())) {
            $setting = rkcache('config', true);
            View::assign('setting', $setting);
            return View::fetch();
        } else {
            $promotion_xianshi_price = intval(input('post.promotion_xianshi_price'));
            if ($promotion_xianshi_price < 0) {
                $this->error(lang('param_error'));
            }

            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_xianshi_price'] = $promotion_xianshi_price;

            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log('修改秒杀价格为' . $promotion_xianshi_price . '元');
                dsLayerOpenSuccess(lang('setting_save_success'));
            } else {
                $this->error(lang('setting_save_fail'));
            }
        }
    }

    /**
     * ajax修改信息
     */
    public function ajax()
    {
        $result = true;
        $update_array = array();
        $where = array();

        switch (input('param.branch')) {
            case 'recommend':
                $pxianshigoods_model = model('pxianshigoods');
                $update_array['xianshigoods_recommend'] = input('param.value');
                $where[]=array('xianshigoods_id','=',input('param.id'));
                $result = $pxianshigoods_model->editXianshigoods($update_array, $where);
                break;
        }

        if ($result) {
            echo 'true';
            exit;
        }
        else {
            echo 'false';
            exit;
        }

    }


    /*
     * 发送消息
     */
    private function send_message($member_id, $member_name, $message)
    {
        $param = array();
        $param['from_member_id'] = 0;
        $param['member_id'] = $member_id;
        $param['to_member_name'] = $member_name;
        $param['message_type'] = '1';//表示为系统消息
        $param['msg_content'] = $message;
        $message_model = model('message');
        return $message_model->addMessage($param);
    }

    /**
     * 页面内导航菜单
     *
     * @param string $menu_key 当前导航的menu_key
     * @param array $array 附加菜单
     * @return
     */
    protected function getAdminItemList()
    {
        $menu_array = array(
            array(
                'name' => 'xianshi_list', 'text' => lang('xianshi_list'), 'url' => url('Promotionxianshi/index')
            ), array(
                'name' => 'xianshi_quota', 'text' => lang('xianshi_quota'),
                'url' => url('Promotionxianshi/xianshi_quota')
            ), array(
                'name' => 'xianshi_setting',
                'text' => lang('xianshi_setting'),
                'url' => "javascript:dsLayerOpen('".url('Promotionxianshi/xianshi_setting')."','".lang('xianshi_setting')."')"
            ),
        );
        if (request()->action() == 'xianshi_detail')
            $menu_array[] = array(
                'name' => 'xianshi_detail', 'text' => lang('xianshi_detail'),
                'url' => url('Promotionxianshi/xianshi_detail')
            );
        return $menu_array;
    }
}