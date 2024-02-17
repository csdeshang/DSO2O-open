<?php

namespace app\common\logic;
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
 * 逻辑层模型
 */
class  Buy {

    /**
     * 会员信息
     * @var array
     */
    private $_member_info = array();

    /**
     * 店铺信息
     * @var array
     */
    private $_store_list = array();
    /**
     * 重量信息
     * @var array
     */
    private $_weight_list = array();

    /**
     * 下单数据
     * @var array
     */
    private $_order_data = array();

    /**
     * 表单数据
     * @var array
     */
    private $_post_data = array();

    /**
     * buy_1.logic 对象
     * @var obj
     */
    private $_logic_buy_1;

    public function __construct() {
        $this->_logic_buy_1 = model('buy_1', 'logic');
    }

    /**
     * 购买第一步
     * @param type $cart_id
     * @param type $ifcart
     * @param type $member_id
     * @param type $store_id
     * @return type
     */
    public function buyStep1($cart_id, $ifcart, $member_id, $store_id) {

        //得到购买商品信息
        if ($ifcart) {
            $result = $this->getCartList($cart_id, $member_id);
        } else {
            $result = $this->getGoodsList($cart_id, $member_id, $store_id);
        }

        if (!$result['code']) {
            return $result;
        }

        //得到页面所需要数据：收货地址、发票、代金券、预存款、商品列表等信息
        $result = $this->getBuyStep1Data($member_id, $result['data']);
        return $result;
    }

    /**
     * 第一步：处理购物车
     * @param type $cart_id 购物车
     * @param type $member_id 会员编号
     * @return type
     */
    public function getCartList($cart_id, $member_id) {
        $cart_model = model('cart');

        //取得POST ID和购买数量
        $buy_items = $this->_parseItems($cart_id);
        if (empty($buy_items)) {
            return ds_callback(false, '所购商品无效');
        }

        if (count($buy_items) > 50) {
            return ds_callback(false, '一次最多只可购买50种商品');
        }

        //购物车列表
        $condition = array(array('cart_id','in', array_keys($buy_items)), array('buyer_id' ,'=', $member_id));
        $cart_list = $cart_model->getCartList('db', $condition);

        //购物车列表 [得到最新商品属性及促销信息]
        $cart_list = $this->_logic_buy_1->getGoodsCartList($cart_list);

        //商品列表 [优惠套装子商品与普通商品同级罗列]
        $goods_list = $this->_getGoodsList($cart_list);

        //以店铺下标归类
        $ret = $this->_getStoreCartList($cart_list);
        if (!$ret['code']) {
            return $ret;
        }
        $store_cart_list = $ret['data'];
        return ds_callback(true, '', array('goods_list' => $goods_list, 'store_cart_list' => $store_cart_list));
    }

    /**
     * 第一步：处理立即购买
     * @param type $cart_id 购物车
     * @param type $member_id 会员编号
     * @param type $store_id 店铺编号
     * @return type
     */
    public function getGoodsList($cart_id, $member_id, $store_id) {

        //取得POST ID和购买数量
        $buy_items = $this->_parseItems($cart_id);
        if (empty($buy_items)) {
            return ds_callback(false, '所购商品无效');
        }

        $goods_id = key($buy_items);
        $quantity = current($buy_items);

        //商品信息[得到最新商品属性及促销信息]
        $goods_info = $this->_logic_buy_1->getGoodsOnlineInfo($goods_id, intval($quantity));
        if (empty($goods_info)) {
            return ds_callback(false, '商品已下架或不存在');
        }

        //不能购买自己店铺的商品
        if ($goods_info['store_id'] == $store_id) {
            return ds_callback(false, '不能购买自己店铺的商品');
        }

        //判断店铺是否在营业时间内，是否在配送距离内，是否超过接单门槛
        $ret = $this->_checkStore($goods_info['store_id']);
        if (!$ret['code']) {
            return $ret;
        }
        //进一步处理数组
        $store_cart_list = array();
        $goods_list = array();
        $goods_list[0] = $store_cart_list[$goods_info['store_id']][0] = $goods_info;

        return ds_callback(true, '', array('goods_list' => $goods_list, 'store_cart_list' => $store_cart_list));
    }

    /**
     * 购买第一步：返回商品、促销、地址、发票等信息，然后交前台抛出
     * @param unknown $member_id
     * @param unknown $data 商品信息
     * @return
     */
    public function getBuyStep1Data($member_id, $data) {
        //list($goods_list, $store_cart_list) = $data;
        $goods_list = $data['goods_list'];
        $store_cart_list = $data['store_cart_list'];

        //定义返回数组
        $result = array();
        $result['weight_list'] = array();
        $store_model = model('store');
        foreach ($store_cart_list as $store_id => $store_cart) {
            if ($store_model->isO2oSupport($this->_store_list[$store_id])) {
                $result['weight_list'][$store_id] = 0;
                foreach($store_cart as $goods){
                    $result['weight_list'][$store_id] += $goods['goods_weight']*$goods['goods_num'];
                }
                if ($this->_store_list[$store_id]['store_o2o_distribute_type'] == 1) {
                    $result['store_o2o_fee_list'][$store_id] = 0;
                } else {
                    if(intval(config('ds_config.dada_open'))==1){
                        $result['store_o2o_fee_list'][$store_id] = 0;
                    }else{
                        $result['store_o2o_fee_list'][$store_id] = 0;
                    }
                }
            } else {
                $current_store = current($store_cart);
                return ds_callback(false, '店铺[' . $current_store['store_name'] . ']不支持配送');
            }
        }

        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        list($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total) = $this->_logic_buy_1->calcCartList($store_cart_list);
        $result['store_cart_list'] = $store_cart_list;
        $result['store_goods_total'] = $store_goods_total;
        $result['store_goods_original_total'] = $store_goods_original_total;
        $result['store_goods_discount_total'] = $store_goods_discount_total;
        $ret = $this->_checkCost($store_goods_total);
        if (!$ret['code']) {
            return $ret;
        }
        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list, $store_mansong_rule_list) = $this->_logic_buy_1->getMansongruleCartListByTotal($store_goods_total);
        $result['store_premiums_list'] = $store_premiums_list;
        $result['store_mansong_rule_list'] = $store_mansong_rule_list;

        //重新计算优惠后(满即送)的店铺实际商品总金额
        $store_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_goods_total, $store_mansong_rule_list, 'mansong');

        //返回店铺可用的代金券
        $store_voucher_list = $this->_logic_buy_1->getStoreAvailableVoucherList($store_goods_total, $member_id);
        $result['store_voucher_list'] = $store_voucher_list;

        //输出用户默认收货地址
        $result['address_info'] = model('address')->getDefaultAddressInfo(array(array('member_id' ,'=', $member_id),array('address_o2o_errand_type' ,'=', 0)));


        //发票 :只有所有商品都支持增值税发票才提供增值税发票
        $vat_deny = false;
        foreach ($goods_list as $goods) {
            if (!intval($goods['goods_vat'])) {
                $vat_deny = true;
                break;
            }
        }
        //不提供增值税发票时抛出true(模板使用)
        $result['vat_deny'] = $vat_deny;
        $result['vat_hash'] = $this->buyEncrypt($result['vat_deny'] ? 'deny_vat' : 'allow_vat', $member_id);

        //输出默认使用的发票信息
        $inv_info = model('invoice')->getDefaultInvoiceInfo(array('member_id' => $member_id));
        if ($inv_info['invoice_state'] == '2' && !$vat_deny) {
            $inv_info['content'] = '增值税发票 ' . $inv_info['invoice_company'] . ' ' . $inv_info['invoice_company_code'] . ' ' . $inv_info['invoice_reg_addr'];
        } elseif ($inv_info['invoice_state'] == '2' && $vat_deny) {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        } elseif (!empty($inv_info)) {
            $inv_info['content'] = '普通发票 ' . $inv_info['invoice_title'] . ' ' . $inv_info['invoice_code'] . ' ' . $inv_info['invoice_content'];
        } else {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        }
        $result['inv_info'] = $inv_info;

        $buyer_info = model('member')->getMemberInfoByID($member_id);
        if (floatval($buyer_info['available_predeposit']) > 0) {
            $result['available_predeposit'] = $buyer_info['available_predeposit'];
        }
        if (floatval($buyer_info['available_rc_balance']) > 0) {
            $result['available_rc_balance'] = $buyer_info['available_rc_balance'];
        }
        $result['member_paypwd'] = $buyer_info['member_paypwd'] ? true : false;

        return ds_callback(true, '', $result);
    }

    /**
     * 购买第二步
     * @param array $post
     * @param int $member_id
     * @param string $member_name
     * @param string $member_email
     * @return array
     */
    public function buyStep2($post, $member_id, $member_name, $member_email) {

        $this->_member_info['member_id'] = $member_id;
        $this->_member_info['member_name'] = $member_name;
        $this->_member_info['member_email'] = $member_email;
        $this->_post_data = $post;

        try {

            $order_model = model('order');
            Db::startTrans();
            $this->_logic_buy_1->lock=true;
            //第1步 表单验证
            $this->_createOrderStep1();

            //第2步 得到购买商品信息
            $this->_createOrderStep2();

            //第3步 得到购买相关金额计算等信息
            $this->_createOrderStep3();

            //第4步 生成订单
            $this->_createOrderStep4();

            //第6步 订单后续处理
            $this->_createOrderStep6();
            Db::commit();


            return ds_callback(true, '', $this->_order_data);
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
    }

    /**
     * 生成推广记录
     * @param array $order_list
     */
    public function addOrderInviter($order_list = array()) {
        if (!config('ds_config.inviter_open')) {
            return;
        }
        if (empty($order_list) || !is_array($order_list))
            return;
        $inviter_ratio_1 = config('ds_config.inviter_ratio_1');
        $inviter_ratio_2 = config('ds_config.inviter_ratio_2');
        $inviter_ratio_3 = config('ds_config.inviter_ratio_3');
        $orderinviter_model = model('orderinviter');
        foreach ($order_list as $order_id => $order) {
            //如果是线下支付因为不会生成结算单所以不生成推广记录
            if($order['payment_code']=='offline'){
                continue;
            }
            foreach ($order['order_goods'] as $goods) {
                //查询商品的分销信息
                $goods_common_info = Db::name('goodscommon')->alias('gc')->join('goods g', 'g.goods_commonid=gc.goods_commonid')->where('g.goods_id=' . $goods['goods_id'])->field('gc.goods_commonid,gc.inviter_open,gc.inviter_ratio')->find();
                if (!$goods_common_info['inviter_open']) {
                    continue;
                }
                $goods_amount = $goods['goods_pay_price']*$goods_common_info['inviter_ratio']/100;
                $inviter_ratios = array(
                    $inviter_ratio_1,
                    $inviter_ratio_2,
                    $inviter_ratio_3,
                );
                //判断买家是否是分销员
                if (config('ds_config.inviter_return')) {
                    if (Db::name('inviter')->where('inviter_state=1 AND inviter_id=' . $order['buyer_id'])->value('inviter_id')) {
                        if (isset($inviter_ratios[0]) && floatval($inviter_ratios[0]) > 0) {
                        $ratio=round($inviter_ratios[0]*$goods_common_info['inviter_ratio']/100,2);
                            $money_1 = round($inviter_ratios[0] / 100 * $goods_amount, 2);
                            if ($money_1 > 0) {

                                //生成推广记录
                                Db::name('orderinviter')->insert(array(
                                    'orderinviter_addtime' => TIMESTAMP,
                                    'orderinviter_store_name' => $order['store_name'],
                                    'orderinviter_goods_amount' => $goods['goods_pay_price'],
                                    'orderinviter_goods_quantity' => $goods['goods_num'],
                                    'orderinviter_order_type' => 0,
                                    'orderinviter_store_id' => $goods['store_id'],
                                    'orderinviter_goods_commonid' => $goods_common_info['goods_commonid'],
                                    'orderinviter_goods_id' => $goods['goods_id'],
                                    'orderinviter_level' => 1,
                                        'orderinviter_ratio' => $ratio,
                                    'orderinviter_goods_name' => $goods['goods_name'],
                                    'orderinviter_order_id' => $order_id,
                                    'orderinviter_order_sn' => $order['order_sn'],
                                    'orderinviter_member_id' => $order['buyer_id'],
                                    'orderinviter_member_name' => $order['buyer_name'],
                                    'orderinviter_money' => $money_1,
                                    'orderinviter_remark' => '获得分销员返佣，佣金比例' . $ratio . '%，订单号' . $order['order_sn'],
                                ));
                            }
                        }
                    }
                }
                //一级推荐人
                $inviter_1_id = Db::name('member')->where('member_id', $order['buyer_id'])->value('inviter_id');
                if (!$inviter_1_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id=' . $inviter_1_id)->value('inviter_id')) {
                    continue;
                }


                $inviter_1 = Db::name('member')->where('member_id', $inviter_1_id)->field('inviter_id,member_id,member_name')->find();
                if ($inviter_1 && isset($inviter_ratios[0]) && floatval($inviter_ratios[0]) > 0) {
                $ratio=round($inviter_ratios[0]*$goods_common_info['inviter_ratio']/100,2);
                    $money_1 = round($inviter_ratios[0] / 100 * $goods_amount, 2);
                    if ($money_1 > 0) {

                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount' => $goods['goods_pay_price'],
                            'orderinviter_goods_quantity' => $goods['goods_num'],
                            'orderinviter_order_type' => 0,
                            'orderinviter_store_id' => $goods['store_id'],
                            'orderinviter_goods_commonid' => $goods_common_info['goods_commonid'],
                            'orderinviter_goods_id' => $goods['goods_id'],
                            'orderinviter_level' => 1,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name' => $goods['goods_name'],
                            'orderinviter_order_id' => $order_id,
                            'orderinviter_order_sn' => $order['order_sn'],
                            'orderinviter_member_id' => $inviter_1['member_id'],
                            'orderinviter_member_name' => $inviter_1['member_name'],
                            'orderinviter_money' => $money_1,
                            'orderinviter_remark' => '获得一级推荐佣金，佣金比例' . $ratio . '%，推荐关系' . $inviter_1['member_name'] . '->' . $order['buyer_name'] . '，订单号' . $order['order_sn'],
                        ));
                    }
                }
                if (config('ds_config.inviter_level') <= 1) {
                    continue;
                }
                //二级推荐人
                $inviter_2_id = Db::name('member')->where('member_id', $inviter_1_id)->value('inviter_id');
                if (!$inviter_2_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id=' . $inviter_2_id)->value('inviter_id')) {
                    continue;
                }
                $inviter_2 = Db::name('member')->where('member_id', $inviter_2_id)->field('inviter_id,member_id,member_name')->find();
                if ($inviter_2 && isset($inviter_ratios[1]) && floatval($inviter_ratios[1]) > 0) {
                $ratio=round($inviter_ratios[1]*$goods_common_info['inviter_ratio']/100,2);
                    $money_2 = round($inviter_ratios[1] / 100 * $goods_amount, 2);
                    if ($money_2 > 0) {

                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount' => $goods['goods_pay_price'],
                            'orderinviter_goods_quantity' => $goods['goods_num'],
                            'orderinviter_order_type' => 0,
                            'orderinviter_store_id' => $goods['store_id'],
                            'orderinviter_goods_commonid' => $goods_common_info['goods_commonid'],
                            'orderinviter_goods_id' => $goods['goods_id'],
                            'orderinviter_level' => 2,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name' => $goods['goods_name'],
                            'orderinviter_order_id' => $order_id,
                            'orderinviter_order_sn' => $order['order_sn'],
                            'orderinviter_member_id' => $inviter_2['member_id'],
                            'orderinviter_member_name' => $inviter_2['member_name'],
                            'orderinviter_money' => $money_2,
                            'orderinviter_remark' => '获得二级推荐佣金，佣金比例' . $ratio . '%，推荐关系' . $inviter_2['member_name'] . '->' . $inviter_1['member_name'] . '->' . $order['buyer_name'] . '，订单号' . $order['order_sn'],
                        ));
                    }
                }
                if (config('ds_config.inviter_level') <= 2) {
                    continue;
                }
                //三级推荐人
                $inviter_3_id = Db::name('member')->where('member_id', $inviter_2_id)->value('inviter_id');
                if (!$inviter_3_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id=' . $inviter_3_id)->value('inviter_id')) {
                    continue;
                }
                $inviter_3 = Db::name('member')->where('member_id', $inviter_3_id)->field('inviter_id,member_id,member_name')->find();
                if ($inviter_3 && isset($inviter_ratios[2]) && floatval($inviter_ratios[2]) > 0) {
                $ratio=round($inviter_ratios[2]*$goods_common_info['inviter_ratio']/100,2);
                    $money_3 = round($inviter_ratios[2] / 100 * $goods_amount, 2);
                    if ($money_3 > 0) {

                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount' => $goods['goods_pay_price'],
                            'orderinviter_goods_quantity' => $goods['goods_num'],
                            'orderinviter_order_type' => 0,
                            'orderinviter_store_id' => $goods['store_id'],
                            'orderinviter_goods_commonid' => $goods_common_info['goods_commonid'],
                            'orderinviter_goods_id' => $goods['goods_id'],
                            'orderinviter_level' => 3,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name' => $goods['goods_name'],
                            'orderinviter_order_id' => $order_id,
                            'orderinviter_order_sn' => $order['order_sn'],
                            'orderinviter_member_id' => $inviter_3['member_id'],
                            'orderinviter_member_name' => $inviter_3['member_name'],
                            'orderinviter_money' => $money_3,
                            'orderinviter_remark' => '获得三级推荐佣金，佣金比例' . $ratio . '%，推荐关系' . $inviter_3['member_name'] . '->' . $inviter_2['member_name'] . '->' . $inviter_1['member_name'] . '->' . $order['buyer_name'] . '，订单号' . $order['order_sn'],
                        ));
                    }
                }
            }
        }
    }

    /**
     * 删除购物车商品
     * @param unknown $ifcart
     * @param unknown $cart_ids
     */
    public function delCart($ifcart, $member_id, $cart_ids) {
        if (!$ifcart || !is_array($cart_ids))
            return;
        $cart_id_str = implode(',', $cart_ids);
        if (preg_match('/^[\d,]+$/', $cart_id_str)) {
            $condition = array();
            $condition[] = array('buyer_id','=',$member_id);
            $condition[] = array('cart_id','in',$cart_ids);
            model('cart')->delCart('db', $condition,$member_id);
        }
    }

    /**
     * 订单生成前的表单验证与处理
     *
     */
    private function _createOrderStep1() {
        $post = $this->_post_data;

        //取得商品ID和购买数量
        $input_buy_items = $this->_parseItems($post['cart_id']);
        if (empty($input_buy_items)) {
            throw new \think\Exception('所购商品无效', 10006);
        }

        //验证收货地址
        $input_address_id = intval($post['address_id']);
        if ($input_address_id <= 0) {
            throw new \think\Exception('请选择收货地址', 10006);
        } else {
            $input_address_info = model('address')->getAddressInfo(array('address_id' => $input_address_id));
            if ($input_address_info['member_id'] != $this->_member_info['member_id']) {
                throw new \think\Exception('请选择收货地址', 10006);
            }
        }

        //收货地址城市编号
        $input_city_id = intval($input_address_info['city_id']);

        //是否开增值税发票
        $input_if_vat = $this->buyDecrypt($post['vat_hash'], $this->_member_info['member_id']);
        if (!in_array($input_if_vat, array('allow_vat', 'deny_vat'))) {
            throw new \think\Exception('订单保存出现异常[值税发票出现错误]，请重试', 10006);
        }
        $input_if_vat = ($input_if_vat == 'allow_vat') ? true : false;


        $input_pay_name = 'online';

        //验证发票信息
        $input_invoice_info = array();
        if (!empty($post['invoice_id'])) {
            $input_invoice_id = intval($post['invoice_id']);
            if ($input_invoice_id > 0) {
                $input_invoice_info = model('invoice')->getInvoiceInfo(array('invoice_id' => $input_invoice_id));
                if ($input_invoice_info['member_id'] != $this->_member_info['member_id']) {
                    throw new \think\Exception('请正确填写发票信息', 10006);
                }
            }
        }

        //验证代金券
        $input_voucher_list = array();
        if (!empty($post['voucher']) && is_array($post['voucher'])) {
            foreach ($post['voucher'] as $store_id => $voucher) {
                if (preg_match_all('/^(\d+)\|(\d+)\|([\d.]+)$/', $voucher, $matchs)) {
                    if (floatval($matchs[3][0]) > 0) {
                        $input_voucher_list[$store_id]['vouchertemplate_id'] = $matchs[1][0];
                        $input_voucher_list[$store_id]['voucher_price'] = $matchs[3][0];
                    }
                }
            }
        }

        //保存数据
        $this->_order_data['input_buy_items'] = $input_buy_items;
        $this->_order_data['input_city_id'] = $input_city_id;
        $this->_order_data['input_pay_name'] = $input_pay_name;
        $this->_order_data['input_pay_message'] = $post['pay_message'];
        $this->_order_data['input_address_info'] = $input_address_info;
        $this->_order_data['input_invoice_info'] = $input_invoice_info;
        $this->_order_data['input_voucher_list'] = $input_voucher_list;
        $this->_order_data['order_from'] = $post['order_from'] == 2 ? 2 : 1;
    }

    /**
     * 得到购买商品信息
     *
     */
    private function _createOrderStep2() {
        $post = $this->_post_data;
        $input_buy_items = $this->_order_data['input_buy_items'];

        if ($post['ifcart']) {
            //购物车列表
            $cart_model = model('cart');
            $condition = array(
                array('cart_id','in', array_keys($input_buy_items)), array('buyer_id' ,'=', $this->_member_info['member_id'])
            );
            $cart_list = $cart_model->getCartList('db', $condition);

            //购物车列表 [得到最新商品属性及促销信息]
            $cart_list = $this->_logic_buy_1->getGoodsCartList($cart_list);

            //商品列表 [优惠套装子商品与普通商品同级罗列]
            $goods_list = $this->_getGoodsList($cart_list);

            //以店铺下标归类
            $ret = $this->_getStoreCartList($cart_list);
            if (!$ret['code']) {
                throw new \think\Exception($ret['msg'], 10006);
            }
            $store_cart_list = $ret['data'];
        } else {

            //来源于直接购买
            $goods_id = key($input_buy_items);
            $quantity = current($input_buy_items);

            //商品信息[得到最新商品属性及促销信息]
            $goods_info = $this->_logic_buy_1->getGoodsOnlineInfo($goods_id, intval($quantity));
            if (empty($goods_info)) {
                throw new \think\Exception('商品已下架或不存在', 10006);
            }
            //判断店铺是否在营业时间内，是否在配送距离内，是否超过接单门槛
            $ret = $this->_checkStore($goods_info['store_id']);
            if (!$ret['code']) {
                throw new \think\Exception($ret['msg'], 10006);
            }
            //进一步处理数组
            $store_cart_list = array();
            $goods_list = array();
            $goods_list[0] = $store_cart_list[$goods_info['store_id']][0] = $goods_info;
            $this->_weight_list[$goods_info['store_id']]=$goods_info['goods_weight']*$quantity;
        }

        //保存数据
        $this->_order_data['goods_list'] = $goods_list;
        $this->_order_data['store_cart_list'] = $store_cart_list;
    }

    /**
     * 得到购买相关金额计算等信息
     *
     */
    private function _createOrderStep3() {
        $goods_list = $this->_order_data['goods_list'];
        $store_cart_list = $this->_order_data['store_cart_list'];
        $input_voucher_list = $this->_order_data['input_voucher_list'];
        $input_city_id = $this->_order_data['input_city_id'];

        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        list($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total) = $this->_logic_buy_1->calcCartList($store_cart_list);
        $ret = $this->_checkCost($store_goods_total);
        if (!$ret['code']) {
            throw new \think\Exception($ret['msg'], 10006);
        }
        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list, $store_mansong_rule_list) = $this->_logic_buy_1->getMansongruleCartListByTotal($store_goods_total);

        //重新计算店铺扣除满即送后商品实际支付金额
        $store_final_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_goods_total, $store_mansong_rule_list, 'mansong');

        //得到有效的代金券
        $input_voucher_list = $this->_logic_buy_1->reParseVoucherList($input_voucher_list, $store_goods_total, $this->_member_info['member_id']);

        //重新计算店铺扣除优惠券送商品实际支付金额
        $store_final_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_final_goods_total, $input_voucher_list, 'voucher');

        //计算每个店铺(所有店铺级优惠活动)总共优惠多少
        $store_promotion_total = $this->_logic_buy_1->getStorePromotionTotal($store_goods_total, $store_final_goods_total);


        //计算店铺最终订单实际支付金额(加上运费)
        $store_final_order_total = $store_final_goods_total;

        //计算店铺分类佣金[改由任务计划]
        $store_gc_id_commis_rate_list = model('storebindclass')->getStoreGcidCommisRateList($goods_list);

        //将赠品追加到购买列表(如果库存0，则不送赠品)
        $append_premiums_to_cart_list = $this->_logic_buy_1->appendPremiumsToCartList($store_cart_list, $store_premiums_list, $store_mansong_rule_list, $this->_member_info['member_id']);
        if ($append_premiums_to_cart_list === false) {
            throw new \think\Exception('抱歉，您购买的商品库存不足，请重购买', 10006);
        } else {
            list($store_cart_list, $goods_buy_quantity, $store_mansong_rule_list) = $append_premiums_to_cart_list;
        }

        //保存数据
        $this->_order_data['store_goods_total'] = $store_goods_total;
        $this->_order_data['store_final_order_total'] = $store_final_order_total;
        $this->_order_data['store_promotion_total'] = $store_promotion_total;

        $this->_order_data['store_gc_id_commis_rate_list'] = $store_gc_id_commis_rate_list;
        $this->_order_data['store_mansong_rule_list'] = $store_mansong_rule_list;
        $this->_order_data['store_cart_list'] = $store_cart_list;
        $this->_order_data['goods_buy_quantity'] = $goods_buy_quantity;
        $this->_order_data['input_voucher_list'] = $input_voucher_list;
    }

    /**
     * 生成订单
     * @param array $input
     * @throws Exception
     * @return array array(支付单sn,订单列表)
     */
    private function _createOrderStep4() {

        extract($this->_order_data);

        $member_id = $this->_member_info['member_id'];
        $member_name = $this->_member_info['member_name'];
        $member_email = $this->_member_info['member_email'];

        $order_model = model('order');

        //存储生成的订单数据
        $order_list = array();
        //存储通知信息
        $notice_list = array();


        $pay_sn = makePaySn($member_id);
        $order_pay = array();
        $order_pay['pay_sn'] = $pay_sn;
        $order_pay['buyer_id'] = $member_id;
        $order_pay_id = $order_model->addOrderpay($order_pay);
        if (!$order_pay_id) {
            throw new \think\Exception('订单保存失败[未生成支付单]', 10006);
        }

        //收货人信息
        list($reciver_info, $reciver_name) = $this->_logic_buy_1->getReciverAddr($input_address_info);
        $res = $this->_logic_buy_1->calcDistance($input_address_info['address_id'], $this->_weight_list);
        if (!$res['state']) {
            throw new \think\Exception($res['msg'], 10006);
        }
        foreach ($store_cart_list as $store_id => $goods_list) {

            //取得本店优惠额度(后面用来计算每件商品实际支付金额，结算需要)
            $promotion_total = !empty($store_promotion_total[$store_id]) ? $store_promotion_total[$store_id] : 0;
            //本店总的优惠比例,保留3位小数
            $should_goods_total = $store_final_order_total[$store_id] + $promotion_total;
            $promotion_rate = abs(number_format($promotion_total / $should_goods_total, 5, '.', ''));
            if ($promotion_rate <= 1) {
                $promotion_rate = floatval(substr($promotion_rate, 0, 5));
            } else {
                $promotion_rate = 0;
            }

            //每种商品的优惠金额累加保存入 $promotion_sum
            $promotion_sum = 0;

            $order = array();
            $order_common = array();
            $order_goods = array();

            $order['order_sn'] = $this->_logic_buy_1->makeOrderSn($order_pay_id);
            $order['pay_sn'] = $pay_sn;
            $order['store_id'] = $store_id;
            $order['store_name'] = $goods_list[0]['store_name'];
            $order['buyer_id'] = $member_id;
            $order['buyer_name'] = $member_name;
            $order['buyer_email'] = $member_email;
            $order['add_time'] = TIMESTAMP;
            $order['payment_code'] = 'online';
            $order['order_state'] = ORDER_STATE_NEW;
            $order['order_amount'] = $store_final_order_total[$store_id] + $res['data'][$store_id]['o2o_fee'];
            //配送信息
            $order['goods_weight'] = round($this->_weight_list[$store_id],3);
            $order['o2o_third'] = $res['data'][$store_id]['o2o_third'];
            $order['o2o_order_distributor_type'] = $res['data'][$store_id]['distributor_type'];
            $order['o2o_order_distance'] = $res['data'][$store_id]['o2o_distance'];
            $order['o2o_order_lng'] = $res['data'][$store_id]['o2o_lng'];
            $order['o2o_order_lat'] = $res['data'][$store_id]['o2o_lat'];
            $order['shipping_fee'] = $res['data'][$store_id]['o2o_fee'];
            $order['goods_amount'] = $order['order_amount'] - $order['shipping_fee'];
            $order['order_from'] = $order_from;
            //如果支持方式为空时，默认为货到付款 
            if ($order['payment_code'] == "") {
                $order['payment_code'] = "offline";
            }
            $order_id = $order_model->addOrder($order);
            if (!$order_id) {
                throw new \think\Exception('订单保存失败[未生成订单数据]', 10006);
            }
            $order['order_id'] = $order_id;
            $order_list[$order_id] = $order;

            $order_common['order_id'] = $order_id;
            $order_common['store_id'] = $store_id;
            $order_common['order_message'] = $input_pay_message[$store_id];

            //代金券
            if (isset($input_voucher_list[$store_id])) {
                $order_common['voucher_price'] = $input_voucher_list[$store_id]['voucher_price'];
                $order_common['voucher_code'] = $input_voucher_list[$store_id]['voucher_code'];
            }

            $order_common['reciver_info'] = $reciver_info;
            $order_common['reciver_name'] = $reciver_name;
            $order_common['reciver_city_id'] = $input_city_id;

            //发票信息
            $order_common['invoice_info'] = $this->_logic_buy_1->createInvoiceData($input_invoice_info);

            //保存促销信息
            if (isset($store_mansong_rule_list[$store_id])) {
                $order_common['promotion_info'] = addslashes($store_mansong_rule_list[$store_id]['desc']);
            }

            $order_id = $order_model->addOrdercommon($order_common);
            if (!$order_id) {
                throw new \think\Exception('订单保存失败[未生成订单扩展数据]', 10006);
            }
            $order_list[$order_id]['order_common']=$order_common;
            //生成order_goods订单商品数据
            $i = 0;
            
            $required_goods_id=Db::name('goods')->where(array(array('store_id','=',$order['store_id']),array('goods_verify','=',1),array('goods_state','=',1),array('goods_if_required','=',1)))->column('goods_id');
            if(empty($required_goods_id)){
                $required=true;
            }else{
                $required=false;
            }        
            foreach ($goods_list as $goods_info) {
                if (!$goods_info['state'] || !$goods_info['storage_state']) {
                    throw new \think\Exception('部分商品已经下架或库存不足，请重新选择', 10006);
                }
                if(!$required && !empty($required_goods_id) && in_array($goods_info['goods_id'],$required_goods_id)){
                    $required=true;
                }
                //如果不是优惠套装
                $order_goods[$i]['order_id'] = $order_id;
                $order_goods[$i]['goods_id'] = $goods_info['goods_id'];
                $order_goods[$i]['store_id'] = $store_id;
                $order_goods[$i]['goods_name'] = $goods_info['goods_name'];
                $order_goods[$i]['goods_price'] = $goods_info['goods_price'];
                $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
                $order_goods[$i]['goods_image'] = $goods_info['goods_image'];
                $order_goods[$i]['buyer_id'] = $member_id;
                if (isset($goods_info['ifxianshi'])) {
                    $order_goods[$i]['goods_type'] = 3;
                } elseif (isset($goods_info['ifzengpin'])) {
                    $order_goods[$i]['goods_type'] = 5;
                } else {
                    $order_goods[$i]['goods_type'] = 1;
                }
                $order_goods[$i]['promotions_id'] = isset($goods_info['promotions_id']) ? $goods_info['promotions_id'] : 0;

                $order_goods[$i]['commis_rate'] = floatval(@$store_gc_id_commis_rate_list[$store_id][$goods_info['gc_id']]);

                $order_goods[$i]['gc_id'] = $goods_info['gc_id'];
                //计算商品金额
                $goods_total = $goods_info['goods_price'] * $goods_info['goods_num'];
                //计算本件商品优惠金额
                $promotion_value = floor($goods_total * ($promotion_rate));
                $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value;
                $promotion_sum += $promotion_value;
                $i++;

                //存储库存报警数据
                if (isset($goods_info['goods_storage_alarm']) && $goods_info['goods_storage_alarm'] >= ($goods_info['goods_storage'] - $goods_info['goods_num'])) {
                    $param = array();
                    $param['common_id'] = $goods_info['goods_commonid'];
                    $param['sku_id'] = $goods_info['goods_id'];
                        $ten_param=array($param['common_id'],$param['sku_id']);
                    $weixin_param=array(
                            'url' => config('ds_config.h5_store_site_url').'/pages/seller/goods/GoodsForm2?commonid='.$goods_info['goods_commonid'].'&class_id='.$goods_info['gc_id'],
                            'data'=>array(
                                "keyword1" => array(
                                    "value" => $goods_info['goods_storage'] - $goods_info['goods_num'],
                                    "color" => "#333"
                                ),
                                "keyword2" => array(
                                    "value" => date('Y-m-d H:i'),
                                    "color" => "#333"
                                )
                            ),
                        );
                    $notice_list['goods_storage_alarm'][$goods_info['store_id']] = array('param'=>$param,'ali_param'=>$param,'ten_param'=>$ten_param,'weixin_param'=>$weixin_param);
                }
            }
            if(!$required){
                throw new \think\Exception('没有选择必点商品['.$order['store_name'].']', 10006);
            }
            //将因舍出小数部分出现的差值补到最后一个商品的实际成交价中(商品goods_price=0时不给补，可能是赠品)
            if ($promotion_total > $promotion_sum) {
                $i--;
                for ($i; $i >= 0; $i--) {
                    if (floatval($order_goods[$i]['goods_price']) > 0) {
                        $order_goods[$i]['goods_pay_price'] -= $promotion_total - $promotion_sum;
                        break;
                    }
                }
            }
            $insert = $order_model->addOrdergoods($order_goods);
            if (!$insert) {
                throw new \think\Exception('订单保存失败[未生成商品数据]', 10006);
            }
            $order_list[$order_id]['order_goods'] = $order_goods;
        }

        //保存数据
        $this->_order_data['pay_sn'] = $pay_sn;
        $this->_order_data['order_list'] = $order_list;
        $this->_order_data['notice_list'] = $notice_list;
    }


    /**
     * 订单后续其它处理
     */
    private function _createOrderStep6() {
        $ifcart = $this->_post_data['ifcart'];
        $goods_buy_quantity = $this->_order_data['goods_buy_quantity'];
        $input_voucher_list = $this->_order_data['input_voucher_list'];
        $store_cart_list = $this->_order_data['store_cart_list'];
        $input_buy_items = $this->_order_data['input_buy_items'];
        $order_list = $this->_order_data['order_list'];
        $input_address_info = $this->_order_data['input_address_info'];
        $notice_list = $this->_order_data['notice_list'];

        //变更库存和销量
        model('goods')->createOrderUpdateStorage($goods_buy_quantity);

        //更新使用的代金券状态
        if (!empty($input_voucher_list) && is_array($input_voucher_list)) {
            model('voucher')->editVoucherState($input_voucher_list);
        }


        //删除购物车中的商品
        $this->delCart($ifcart, $this->_member_info['member_id'], array_keys($input_buy_items));
        cookie('cart_goods_num', '', -3600);

        //生成推广记录
        $this->addOrderInviter($order_list);
        //发送提醒类信息
        if (!empty($notice_list)) {
            foreach ($notice_list as $code => $value) {
                $temp=current($value);
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize(array(
                    'code' => $code, 'store_id' => key($value), 'param' => $temp['param'], 'weixin_param' => $temp['weixin_param'], 'ali_param' => $temp['ali_param'], 'ten_param' => $temp['ten_param']))
                ));
            }
        }
    }

    /**
     * 加密
     * @param array /string $string
     * @param int $member_id
     * @return mixed arrray/string
     */
    public function buyEncrypt($string, $member_id) {
        $buy_key = sha1(md5($member_id . '&' . md5(config('ds_config.setup_date'))));
        if (is_array($string)) {
            $string = serialize($string);
        } else {
            $string = strval($string);
        }
        return ds_encrypt(base64_encode($string), $buy_key);
    }

    /**
     * 解密
     * @param string $string
     * @param int $member_id
     * @param number $ttl
     */
    public function buyDecrypt($string, $member_id, $ttl = 0) {
        $buy_key = sha1(md5($member_id . '&' . md5(config('ds_config.setup_date'))));
        if (empty($string))
            return;
        $string = base64_decode(ds_decrypt(strval($string), $buy_key, $ttl));
        return ($tmp = @unserialize($string)) !== false ? $tmp : $string;
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    if (intval($match[2][0]) > 0) {
                        $buy_items[$match[1][0]] = $match[2][0];
                    }
                }
            }
        }
        return $buy_items;
    }

    /**
     * 从购物车数组中得到商品列表
     * @param unknown $cart_list
     */
    private function _getGoodsList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list))
            return $cart_list;
        $goods_list = array();
        $i = 0;
        $this->_weight_list=array();
        foreach ($cart_list as $key => $cart) {
            if (!$cart['state'] || !$cart['storage_state'])
                continue;
            //购买数量
            $quantity = $cart['goods_num'];
            //如果是普通商品
            $goods_list[$i]['goods_num'] = $quantity;
            $goods_list[$i]['goods_id'] = $cart['goods_id'];
            $goods_list[$i]['store_id'] = $cart['store_id'];
            $goods_list[$i]['gc_id'] = $cart['gc_id'];
            $goods_list[$i]['goods_weight'] = $cart['goods_weight'];
            $goods_list[$i]['goods_name'] = $cart['goods_name'];
            $goods_list[$i]['goods_price'] = $cart['goods_price'];
            $goods_list[$i]['goods_original_price'] = $cart['goods_original_price'];
            $goods_list[$i]['store_name'] = $cart['store_name'];
            $goods_list[$i]['goods_image'] = $cart['goods_image'];
            $goods_list[$i]['goods_vat'] = $cart['goods_vat'];
            $i++;
            if(!isset($this->_weight_list[$cart['store_id']])){
                $this->_weight_list[$cart['store_id']]=$cart['goods_weight']*$quantity;
            }else{
                $this->_weight_list[$cart['store_id']]+=$cart['goods_weight']*$quantity;
            }
        }
        return $goods_list;
    }

    /**
     * 将下单商品列表转换为以店铺ID为下标的数组
     *
     * @param array $cart_list
     * @return array
     */
    private function _getStoreCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list))
            return ds_callback(false, '您已经下过单了');
        $new_array = array();
        foreach ($cart_list as $cart) {
            //判断店铺是否在营业时间内，是否在配送距离内，是否超过接单门槛
            $ret = $this->_checkStore($cart['store_id']);
            if (!$ret['code']) {
                return $ret;
            }
            $new_array[$cart['store_id']][] = $cart;
        }
        return ds_callback(true, '', $new_array);
    }

    /*
     * 判断o2o店铺营业时间
     */

    private function _checkStore($store_id) {
        $store_model = model('store');
        $store_info = $store_model->getOneStore(array('store_id' => $store_id), '*');

        if (!$store_info) {
            return ds_callback(false, '店铺[store_id:' . $store_id . ']不存在');
        }
        $ret = $store_model->getO2oState($store_info);
        if (!$ret['code']) {
            return $ret;
        }
        $this->_store_list[$store_id] = $store_info;

        return ds_callback(true);
    }

    /*
     * 	判断o2o店铺配送门槛
     */

    private function _checkCost($store_goods_total) {
        foreach ($store_goods_total as $store_id => $goods_total) {
            if ($this->_store_list[$store_id]['store_o2o_min_cost'] > $goods_total) {
                return ds_callback(false, '店铺[' . $this->_store_list[$store_id]['store_name'] . ']最低配送消费金额为' . $this->_store_list[$store_id]['store_o2o_min_cost'] . '元，请您调整购物车商品');
            }
        }
        return ds_callback(true);
    }

}
