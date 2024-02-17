<?php
namespace app\home\controller;

use think\facade\Db;
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
class  Membercard extends BaseMall {

    /**
     * AJAX for membership information
     */
    public function index() {
        $ownid = session('member_id');
        $uid = intval(input('get.uid'));

        $member_info = Db::name('member')->field('member_id, member_name, member_truename, member_sex, member_email, member_qq, member_ww, member_areainfo, member_birthday, member_privacy, member_exppoints')->find($uid);
        if (empty($member_info)) {
            echo 'false';
            exit;
        }
        if ($member_info['member_privacy'] != '') {
            $member_info['member_privacy'] = unserialize($member_info['member_privacy']);
        }

        //会员详情及会员级别处理
        if ($member_info) {
            $member_gradeinfo = model('member')->getOneMemberGrade(intval($member_info['member_exppoints']));
            $member_info = array_merge($member_info, $member_gradeinfo);
        }

        if ($ownid == $uid) {
            $followed = 2;
        } else {
            // Whether to pay attention to yourself(own)
            $followed = 0; // 0 stranger, 1 friend, 2 own
            $condition = array();
            $condition[] = array('friend_frommid','=',$uid);
            $condition[] = array('friend_tomid','=',$ownid);
            $friend_info = Db::name('snsfriend')->where($condition)->find();
            if (!empty($friend_info)) {
                $followed = 1;
            }
        }
        $data = array();
        $data['id'] = $member_info['member_id'];
        $data['name'] = $member_info['member_name'];
        $data['avatar'] = get_member_avatar_for_id($member_info['member_id']);
        $data['truename'] = ($followed >= intval($member_info['member_privacy']['nickname']) && !empty($member_info['member_nickname'])) ? $member_info['member_nickname'] : '';
        $data['sex'] = ($followed >= intval($member_info['member_privacy']['sex']) && !empty($member_info['member_sex'])) ? $member_info['member_sex'] : 3;
        $data['email'] = ($followed >= intval($member_info['member_privacy']['email']) && !empty($member_info['member_email'])) ? $member_info['member_email'] : lang('home_member_privary');
        $data['qq'] = ($followed >= intval($member_info['member_privacy']['qq']) && !empty($member_info['member_qq'])) ? $member_info['member_qq'] : '';
        $data['ww'] = ($followed >= intval($member_info['member_privacy']['ww']) && !empty($member_info['member_ww'])) ? $member_info['member_ww'] : '';
        $data['areainfo'] = ($followed >= intval($member_info['member_privacy']['area']) && !empty($member_info['member_areainfo'])) ? $member_info['member_areainfo'] : lang('home_member_privary');
        $data['birthday'] = ($followed >= intval($member_info['member_privacy']['birthday']) && !empty($member_info['member_birthday'])) ? $member_info['member_birthday'] : lang('home_member_privary');
        $data['level_name'] = $member_info['level_name'];

        switch (input('get.from')) {
            case 'shop':
                $data['url'] = HOME_SITE_URL;
                break;
            default:
                $data['url'] = '';
                break;
        }
        if ($ownid == $uid) {
            $data['follow'] = 2; // 0 stranger, 1 friend, 2 own
        } else {
            // Whether to pay attention to me
            $condition = array();
            $condition[] = array('friend_frommid','=',$ownid);
            $condition[] = array('friend_tomid','=',$uid);
            $friend_info = Db::name('snsfriend')->where($condition)->find();
            $data['follow'] = (!empty($friend_info)) ? 1 : 0;
        }
        // Pay attention to the number of
        $data['attention_count'] = Db::name('snsfriend')->where(array('friend_frommid' => $uid))->count();

        // Number of fans
        $data['fans_count'] = Db::name('snsfriend')->where(array('friend_tomid' => $uid))->count();
        echo $_GET['callback'] . '(' . json_encode($data) . ')';
    }

    public function mcard_info() {
        echo 'false';
        exit;
    }

}
