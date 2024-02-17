<?php
namespace app\admin\controller;
use think\facade\Lang;
use think\facade\View;
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
class  Promotionmansong extends AdminControl
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/promotionmansong.lang.php');
    }


    /**
     * 活动列表
     **/
    public function index()
    {
        //自动开启满就送
        if (intval(input('param.promotion_allow')) === 1) {
            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_allow'] = 1;
            $config_model->editConfig($update_array);
        }
        $mansong_model = model('pmansong');

        $param = array();
        if (!empty(input('param.mansong_name'))) {
            $param[] = array('mansong_name','like', '%' . input('param.mansong_name') . '%');
        }
        if (!empty(input('param.store_name'))) {
            $param[] = array('store_name','like', '%' . input('param.store_name') . '%');
        }
        if (!empty(input('param.state'))) {
            $param[] = array('mansong_state','=',input('param.state'));
        }
        $mansong_list = $mansong_model->getMansongList($param, 10);
        View::assign('mansong_list', $mansong_list);
        View::assign('show_page', $mansong_model->page_info->render());
        View::assign('mansong_state_array', $mansong_model->getMansongStateArray());


        $this->setAdminCurItem('index');
        // 输出自营店铺IDS
        View::assign('flippedOwnShopIds', array_flip(model('store')->getOwnShopIds()));
        return View::fetch();
    }

    /**
     * 活动详细信息
     * temp
     **/
    public function mansong_detail()
    {
        $mansong_id = intval(input('param.mansong_id'));

        $mansong_model = model('pmansong');
        $mansongrule_model = model('pmansongrule');

        $mansong_info = $mansong_model->getMansongInfoByID($mansong_id);
        if (empty($mansong_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('mansong_info', $mansong_info);

        $param = array();
        $param['mansong_id'] = $mansong_id;
        $mansongrule_list = $mansongrule_model->getMansongruleListByID($mansong_id);
        View::assign('mansongrule_list', $mansongrule_list);
        $this->setAdminCurItem('mansong_detail');

        return View::fetch();
    }

    /**
     * 满即送活动取消
     **/
    public function mansong_cancel()
    {
        $mansong_id = intval(input('param.mansong_id'));
        
        if ($mansong_id<=0) {
            ds_json_encode(10001, lang('param_error'));
        }
        
        $mansong_model = model('pmansong');
        $result = $mansong_model->cancelMansong(array('mansong_id' => $mansong_id));
        if ($result) {
            $this->log('取消满即送活动，活动编号' . $mansong_id);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
        else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 满即送活动删除
     **/
    public function mansong_del() {
        $mansong_model = model('pmansong');
        $mansong_id = input('param.mansong_id');
        $mansong_id_array = ds_delete_param($mansong_id);
        if($mansong_id_array === FALSE){
            ds_json_encode(10001, lang('param_error'));
        }
        $condition = array(array('mansong_id','in', $mansong_id_array));
        $result =$mansong_model->delMansong($condition);
        if ($result) {
            $this->log('删除满即送活动，活动编号' . implode(',', $mansong_id_array));
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }
    

    /**
     * 套餐管理
     **/
    public function mansong_quota()
    {
        $mansongquota_model = model('pmansongquota');

        $param = array();
        if (!empty(input('param.store_name'))) {
            $param[] = array('store_name','like', '%' . input('param.store_name') . '%');
        }
        $mansongquota_list = $mansongquota_model->getMansongquotaList($param, 10, 'mansongquota_id desc');
        View::assign('mansongquota_list', $mansongquota_list);
        View::assign('show_page', $mansongquota_model->page_info->render());
        $this->setAdminCurItem('mansong_quota');

        return View::fetch();

    }

    /**
     * 设置
     **/
    public function mansong_setting()
    {
        if (!(request()->isPost())) {
            $setting = rkcache('config', true);
            View::assign('setting', $setting);
            $this->setAdminCurItem('mansong_setting');
            return View::fetch();
        } else {
            $promotion_mansong_price = intval(input('post.promotion_mansong_price'));
            if ($promotion_mansong_price < 0) {
                $this->error(lang('param_error'));
            }

            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_mansong_price'] = $promotion_mansong_price;

            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->log(lang('ds_config') . lang('ds_promotion_mansong') . lang('mansong_price'));
                dsLayerOpenSuccess(lang('setting_save_success'));
            } else {
                $this->error(lang('setting_save_fail'));
            }
        }
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
                'name' => 'index', 
                'text' => lang('mansong_list'), 
                'url' => url('Promotionmansong/index')
            ), array(
                'name' => 'mansong_quota',
                'text' => lang('mansong_quota'),
                'url' => url('Promotionmansong/mansong_quota')
            ), array(
                'name' => 'mansong_setting',
                'text' => lang('mansong_setting'),
                'url' => "javascript:dsLayerOpen('".url('Promotionmansong/mansong_setting')."','".lang('mansong_setting')."')"
            ),
        );
        if (request()->action() == 'mansong_detail') {
            $menu_array[] = array(
                'name' => 'mansong_detail', 'text' => lang('mansong_detail'),
                'url' => url('Promotionmansong/mansong_detail')
            );
        }
        return $menu_array;
    }
}