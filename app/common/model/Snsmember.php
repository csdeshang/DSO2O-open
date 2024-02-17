<?php

namespace app\common\model;
use think\facade\Db;


/**
 * 会与标签
 *//**
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
class  Snsmember extends BaseModel{
    
    public $page_info;
    
    /**
     * 选择删除标签记录
     * @param type $condition
     * @return type
     */
    public function delSnsmembertag($condition){
        return Db::name('snsmembertag')->where($condition)->delete();
    }
    
    /**
     * 获取会员标签列表
     * @param type $order
     * @param type $pagesize
     * @return type
     */
    public function getSnsmembertagList($order,$pagesize){
        if($pagesize){
            $tag_list = Db::name('snsmembertag')->order($order)->paginate(['list_rows'=>10,'query' => request()->param()],false);
            $this->page_info =$tag_list;
            return $tag_list->items();
        } else {
            return Db::name('snsmembertag')->order($order)->select()->toArray();
        }
    }
    
    /**
     *增加会员标签
     * @param type $data
     * @return type
     */
    public function addSnsmembertag($data){
        return Db::name('snsmembertag')->insert($data);;
    }
    
    /**
     * 编辑会员标签
     * @param type $update
     * @return type
     */
    public function editSnsmembertag($update){
        return $result = Db::name('snsmembertag')->update($update);;
    }
    
    /**
     * 获取单个会员标签
     * @param type $condition
     * @return type
     */
    public function getOneSnsmembertag($condition){
        return Db::name('snsmembertag')->find($condition);
    }
    
    
    /**
     * 获取会员标签数
     * @param type $condition
     * @return type
     */
    public function getSnstagmemberCount($condition){
        return Db::name('snsmtagmember')->where($condition)->count();;
    }
    
    /**
     * 获取所属标签会员列表
     * @param type $condition
     * @param type $field
     * @param type $pagesize
     * @param type $order
     * @param type $count
     * @return type
     */
    public function getSnsmtagmemberList($condition,$field,$pagesize,$order,$count){
        if($pagesize){
            $result = Db::name('snsmtagmember')->alias('s')->field($field)->join('member m','s.member_id=m.member_id','LEFT')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$result;
            return $result->items();
        } else {
            $result = Db::name('snsmtagmember')->alias('s')->field($field)->join('member m','s.member_id=m.member_id','LEFT')->where($condition)->order($order)->select()->toArray();
            return $result;
        }
        
    }
    
    /**
     * 删除所属标签会员
     * @param type $condition
     * @return type
     */
    public function delSnsmtagmember($condition){
        return Db::name('snsmtagmember')->where($condition)->delete();
    }
    
    public function editSnsmtagmember($condition,$update){
        return Db::name('snsmtagmember')->where($condition)->update($update);
    }
}
