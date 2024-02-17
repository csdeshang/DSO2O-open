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
class Snsfriend extends BaseModel {

    public $page_info;

    /**
     * 好友添加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addSnsfriend($data) {
        $result = Db::name('snsfriend')->insertGetId($data);
        return $result;
    }

    /**
     * 好友列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $field 字段
     * @param type $pagesize 分页
     * @param type $type 类型
     * @return type
     */
    public function getSnsfriendList($condition, $field = '*', $pagesize = '', $type = 'simple') {
        //得到条件语句
        switch ($type) {
            case 'simple':
                $data = Db::name('snsfriend')->where($condition)->field($field);
             
                break;
            case 'detail':
                $data = Db::name('snsfriend')->alias('snsfriend')->where($condition)->field($field)->join('member member', 'snsfriend.friend_tomid=member.member_id');
             
                break;
            case 'fromdetail':
                $data = Db::name('snsfriend')->alias('snsfriend')->where($condition)->field($field)->join('member member', 'snsfriend.friend_frommid=member.member_id');
                
                break;
        }
        if($pagesize){
            $data= $data->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $data;
            $friend_list = $data->items();
        }else{
            $friend_list= $data->select()->toArray();
        }
        return $friend_list;
    }

    /**
     * 取得我的好友
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $limit 限制
     * @param type $member_list 会员列表
     * @return array
     */
    public function getFriendList($condition = array(), $limit = 50, $member_list = array())
    {
        $list = Db::name('snsfriend')->where($condition)->order('friend_addtime desc')->paginate(['list_rows'=>$limit,'query' => request()->param()],false);
        if ($list) {
            foreach ($list as $k => $v) {
                $member = array();
                $u_id = $v['friend_tomid'];
                $member['u_id'] = $u_id;
                $member['u_name'] = $v['friend_tomname'];
                $member['avatar'] = get_member_avatar_for_id($u_id);
                $member['friend'] = 1;
                $member_list[$u_id] = $member;
            }
        }
        $this->page_info=$list;
        return $member_list;
    }
    /**
     * 获取好友详细
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $field 字段
     * @return type
     */
    public function getOneSnsfriend($condition, $field = '*') {
        return Db::name('snsfriend')->where($condition)->field($field)->find();
    }

    /**
     * 好友总数
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSnsfriendCount($condition) {
        //得到条件语句
        $count = Db::name('snsfriend')->where($condition)->count();
        return $count;
    }

    /**
     * 更新好友信息
     * @access public
     * @author csdeshang
     * @param type $data 更新数据
     * @param type $condition 条件
     * @return boolean
     */
    public function editSnsfriend($data, $condition) {
        if (empty($data)) {
            return false;
        }
        //得到条件语句
        $result = Db::name('snsfriend')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除关注
     * @access public
     * @author csdeshang
     * @param type $condition
     * @return boolean
     */
    public function delSnsfriend($condition) {
        if (empty($condition)) {
            return false;
        }
        return Db::name('snsfriend')->where($condition)->delete();
    }

}