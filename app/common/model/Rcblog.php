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
class  Rcblog extends BaseModel
{
    public $page_info;
    
    /**
     * 获取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getRechargecardBalanceLogList($condition, $pagesize, $order)
    {
        $res =Db::name('rcblog')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info=$res;
        return $res->items();
    }
}