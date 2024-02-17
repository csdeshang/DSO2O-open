<?php

namespace app\common\model;
use think\facade\Db;


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
 * 数据层模型
 */
class Help extends BaseModel {

    public $page_info;
    
    /**
     * 增加帮助类型
     * @access public
     * @author csdeshang
     * @param type $type_array 类型数组
     * @return bool
     */
    public function addHelptype($type_array) {
        $type_id = Db::name('helptype')->insertGetId($type_array);
        return $type_id;
    }

    /**
     * 增加帮助
     * @access public
     * @author csdeshang
     * @param type $help_array 帮助内容
     * @param type $upload_ids 更新ID
     * @return type
     */
    public function addHelp($help_array, $upload_ids = array()) {
        $help_id = Db::name('help')->insertGetId($help_array);
        if ($help_id && !empty($upload_ids)) {
            $this->editHelpPic($help_id, $upload_ids); //更新帮助图片
        }
        return $help_id;
    }

    /**
     * 删除帮助类型记录
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return bool
     */
    public function delHelptype($condition) {
        if (empty($condition)) {
            return false;
        } else {
            $condition[]=array('helptype_code','=','auto'); //只有auto的可删除
            $result = Db::name('helptype')->where($condition)->delete();
            return $result;
        }
    }

    /**
     * 删除帮助记录
     * @access public
     * @author csdeshang
     * @param type $condition 检索条件
     * @param type $help_ids  帮助id数组
     * @return boolean
     */
    public function delHelp($condition, $help_ids = array()) {
        if (empty($condition)) {
            return false;
        } else {
            $result = Db::name('help')->where($condition)->delete();
            if ($result && !empty($help_ids)) {
                $condition = array();
                $condition[] = array('item_id','in', $help_ids);
                $this->delHelpPic($condition); //删除帮助中所用的图片
            }
            return $result;
        }
    }

    /**
     * 删除帮助图片
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return bool
     */
    public function delHelpPic($condition) {
        if (empty($condition)) {
            return false;
        } else {
            $upload_list = $this->getHelpPicList($condition);
            if (!empty($upload_list) && is_array($upload_list)) {
                foreach ($upload_list as $key => $value) {
                    @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR .'admin/storehelp'. DIRECTORY_SEPARATOR . $value['file_name']);
                }
            }
            $result = Db::name('upload')->where($condition)->delete();
            return $result;
        }
    }

    /**
     * 修改帮助类型记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 参数内容
     * @return boolean
     */
    public function editHelptype($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = Db::name('helptype')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 修改帮助记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return boolean
     */
    public function editHelp($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = Db::name('help')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 更新帮助图片
     * @access public
     * @author csdeshang
     * @param type $help_id 帮助ID
     * @param type $upload_ids 上传ID数组
     * @return boolean
     */
    public function editHelpPic($help_id, $upload_ids = array()) {
        if ($help_id && !empty($upload_ids)) {
            $condition = array();
            $data = array();
            $condition[] = array('upload_id','in', $upload_ids);
            $condition[] = array('upload_type','=','2');
            $condition[] = array('item_id','=','0');
            $data['item_id'] = $help_id;
            $result = Db::name('upload')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 帮助类型记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $fields 字段
     * @return type
     */
    public function getHelptypeList($condition = array(), $pagesize = '', $fields = '*') {
        if($pagesize){
            $result = Db::name('helptype')->field($fields)->where($condition)->order('helptype_sort asc,helptype_id desc')->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$result;
            $result=$result->items();
        }else{
            $result=Db::name('helptype')->field($fields)->where($condition)->order('helptype_sort asc,helptype_id desc')->select()->toArray();
        }
        return $result;
    }

    /**
     * 帮助记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $limit 限制
     * @param type $fields 字段
     * @return array
     */
    public function getHelpList($condition = array(), $pagesize = '', $limit = 0, $fields = '*') {
        if($pagesize) {
            $res=Db::name('help')->field($fields)->where($condition)->order('help_sort asc,help_id desc')->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            $result=$res->items();
        }else{
            $result = Db::name('help')->field($fields)->where($condition)->limit($limit)->order('help_sort asc,help_id desc')->select()->toArray();
        }
        return $result;
    }

    /**
     * 帮助图片记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件数组
     * @return type
     */
    public function getHelpPicList($condition = array()) {
        $condition[]=array('upload_type','=','2'); //帮助内容图片
        $result = Db::name('upload')->where($condition)->select()->toArray();
        return $result;
    }

    /**
     * 店铺页面帮助类型记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param type $pagesize 分页
     * @param type $limit 限制
     * @return type
     */
    public function getStoreHelptypeList($condition = array(), $pagesize = '', $limit = 0) { 
        $condition[]=array('page_show','=','1'); //页面类型:1为店铺,2为会员
        if($pagesize){
            $res = Db::name('helptype')->where($condition)->order('helptype_sort asc,helptype_id desc')->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            $result=$res->items();
        }else{
            $result = Db::name('helptype')->where($condition)->order('helptype_sort asc,helptype_id desc')->select()->toArray();
        }
        $result = ds_change_arraykey($result, 'helptype_id');
        
        return $result;
    }

    /**
     * 店铺页面帮助记录
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param type $pagesize
     * @return type
     */
    public function getStoreHelpList($condition = array(), $pagesize = '') {
        $condition[]=array('page_show','=','1'); //页面类型:1为店铺,2为会员
        $result = $this->getHelpList($condition, $pagesize);
        return $result;
    }

    /**
     * 前台商家帮助显示数据
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return array
     */
    public function getShowStoreHelpList($condition = array()) {
        $list = array();
        $help_list = array(); //帮助内容
        $condition[]=array('helptype_show','=','1'); //是否显示,0为否,1为是
        $list = $this->getStoreHelptypeList($condition); //帮助类型
        if (!empty($list) && is_array($list)) {
            $type_ids = array_keys($list); //类型编号数组
            $condition = array();
            $condition[] = array('helptype_id','in', $type_ids);
            $help_list = $this->getStoreHelpList($condition);
            if (!empty($help_list) && is_array($help_list)) {
                foreach ($help_list as $key => $value) {
                    $type_id = $value['helptype_id']; //类型编号
                    $help_id = $value['help_id']; //帮助编号
                    $list[$type_id]['help_list'][$help_id] = $value;
                }
            }
        }
        return $list;
    }

}
