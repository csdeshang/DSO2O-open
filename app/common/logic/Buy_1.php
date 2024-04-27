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
class Buy_1 {
    public $lock=false;//是否加锁
    /**
     * 取得商品最新的属性及促销[购物车]
     * @param unknown $cart_list
     */
    public function getGoodsCartList($cart_list) {

        $cart_list = $this->_getOnlineCartList($cart_list);

        //秒杀
        $this->getXianshiCartList($cart_list);
        //赠品
        $this->_getGiftCartList($cart_list);

        return $cart_list;
    }

    /**
     * 取得商品最新的属性及促销[立即购买]
     * @param type $goods_id
     * @param type $quantity
     * @return array
     */
    public function getGoodsOnlineInfo($goods_id, $quantity) {
        $goods_info = $this->_getGoodsOnlineInfo($goods_id, $quantity);
        //秒杀
        $this->getXianshiInfo($goods_info, $goods_info['goods_num']);
        //赠品
        $this->_getGoodsgiftList($goods_info);

        return $goods_info;
    }

    public function calcDistance($address_id, $weight_list) {

        if (empty($address_id) || empty($weight_list)) {
            $res = array('state' => false, 'msg' => lang('param_error'));
        } else {
            $address_model = model('address');
            $address_info = $address_model->getAddressInfo(array(array('address_id', '=', $address_id), array('member_id', '=', session('member_id'))));
            if (!$address_info) {
                $res = array('state' => false, 'msg' => '不存在该地址');
            } else {
                $res = array('state' => true);
                $data = array();
                $store_model = model('store');

                foreach ($weight_list as $store_id => $cargo_weight) {
                    $store_info = $store_model->getStoreInfoByID($store_id);

                    if ($store_model->isO2oSupport($store_info)) {
                        if ($store_info['store_o2o_distribute_type'] == 1) {
                            $data[$store_info['store_id']] = array('o2o_third' => '', 'distributor_type' => 1, 'o2o_fee' => 0);
                        } else {
                            $data[$store_info['store_id']] = array('o2o_third' => '', 'distributor_type' => 0, 'o2o_fee' => 0);
                        }
                        if (intval(config('ds_config.dada_open')) == 1 && $store_info['store_o2o_distribute_type'] == 0) {
                            include_once root_path() . 'extend/dada/index.php';
                            if (!$address_info['dada_lng_lat']) {
                                $result = convert_coordinate($address_info['address_longitude'], $address_info['address_latitude']);
                                $result = json_decode($result, true);
                                if ($result['status'] == '1') {
                                    $temp = explode(',', $result['locations']);
                                    $address_model->editAddress(array('dada_lng_lat' => $result['locations']), array(array('address_id', '=', $address_id), array('member_id', '=', session('member_id'))));
                                } else {
                                    $res = array('state' => false, 'msg' => 'convert coordinate fail');
                                    break;
                                }
                            } else {
                                $temp = explode(',', $address_info['dada_lng_lat']);
                            }
                            $body = array(
                                'shop_no' => $store_info['dada_shop_no'],
                                'origin_id' => 'temp' . rand(100000, 666666),
                                'city_code' => $store_info['dada_city_code'],
                                'cargo_price' => 0,
                                'is_prepay' => 0,
                                'receiver_name' => $address_info['address_realname'],
                                'receiver_address' => $address_info['address_detail'],
                                'receiver_phone' => $address_info['address_mob_phone'],
                                'receiver_tel' => $address_info['address_tel_phone'],
                                'callback' => API_SITE_URL . '/index/dada_callback',
                                'receiver_lat' => $temp[1],
                                'receiver_lng' => $temp[0],
                                'cargo_weight' => ($cargo_weight > 0.001) ? round($cargo_weight, 3) : 0.001,
                            );
                            $result = query_dada('/api/order/queryDeliverFee', json_encode($body));
                            if ($result->status == 'success') {
                                $data[$store_info['store_id']]['o2o_distance'] = floatval($result->result['distance']);
                                $data[$store_info['store_id']]['o2o_fee'] = floatval($result->result['deliverFee']);
                                $data[$store_info['store_id']]['o2o_third'] = 'dada';
                            } else {
                                $res = array('state' => false, 'msg' => $store_info['store_name'] . $result->msg);
                                break;
                            }
                        } else {
                            $dist = getDistance($store_info['store_latitude'], $store_info['store_longitude'], $address_info['address_latitude'], $address_info['address_longitude'], 2);
                            if ($store_info['store_o2o_distribute_type'] == 1) {
                                $distance_price_list = unserialize($store_info['store_o2o_distance_price'] ? $store_info['store_o2o_distance_price'] : config('ds_config.o2o_distance_price'));
                                $weight_price_list = unserialize($store_info['store_o2o_weight_price'] ? $store_info['store_o2o_weight_price'] : config('ds_config.o2o_weight_price'));
                                $time_price_list = unserialize($store_info['store_o2o_time_price'] ? $store_info['store_o2o_time_price'] : config('ds_config.o2o_time_price'));
                                $result = $this->calcO2oFee($distance_price_list, $weight_price_list, $time_price_list, $dist, $cargo_weight);
                            } else {
                                $distance_price_list = unserialize(config('ds_config.o2o_distance_price'));
                                $weight_price_list = unserialize(config('ds_config.o2o_weight_price'));
                                $time_price_list = unserialize(config('ds_config.o2o_time_price'));
                                $result = $this->calcO2oFee($distance_price_list, $weight_price_list, $time_price_list, $dist, $cargo_weight);
                            }
                            if (!$result['code']) {//是否超出配送距离
                                $res = array('state' => false, 'msg' => $store_info['store_name'] . '计算配送费出错：' . $result['msg']);
                                break;
                            }
                            $data[$store_info['store_id']]['o2o_fee'] = round($result['data']['distance_price'] + $result['data']['weight_price'] + $result['data']['time_price'], 2);

                            $data[$store_info['store_id']]['o2o_distance'] = $dist;
                        }
                        $data[$store_info['store_id']]['o2o_lng'] = $address_info['address_longitude'];
                        $data[$store_info['store_id']]['o2o_lat'] = $address_info['address_latitude'];
                    } else {
                        $res = array('state' => false, 'msg' => $store_info['store_name'] . '不支持配送');
                        break;
                    }
                }
                $res['data'] = $data;
            }
        }
        return $res;
    }

    public function calcO2oFee($distance_price_list, $weight_price_list, $time_price_list, $distance, $weight, $time = '') {
        if (!is_array($distance_price_list)) {
            return ds_callback(false, '基础运费配置错误');
        }
        $distance_price = false;
        $start_distance_price = 0;
        foreach ($distance_price_list as $item) {
            if ($item['if_fixed']) {
                $start_distance_price = $item['price'];
            }
            if ($item['start_distance'] <= $distance && $item['end_distance'] >= $distance) {
                if ($item['if_fixed']) {
                    $distance_price = $item['price'];
                } else {
                    $interval_distance = floatval($item['interval_distance']);
                    if ($interval_distance == 0) {
                        return ds_callback(false, '基础运费配置错误');
                    }
                    $distance_price = floatval($start_distance_price) + intval(($distance - $item['start_distance']) / $interval_distance) * $item['price'];
                }
            }
        }
        if ($distance_price === false) {
            return ds_callback(false, '超过最大可配送距离' . $item['end_distance'] . '公里');
        }
        //计算重量费用
        if (!is_array($weight_price_list)) {
            return ds_callback(false, '重量附加费配置错误');
        }
        $weight_price = false;
        $start_weight_price = 0;
        foreach ($weight_price_list as $item) {
            if ($item['if_fixed']) {
                $start_weight_price = $item['price'];
            }
            if ($item['start_weight'] <= $weight && $item['end_weight'] >= $weight) {
                if ($item['if_fixed']) {
                    $weight_price = $item['price'];
                } else {
                    $interval_weight = floatval($item['interval_weight']);
                    if ($interval_weight == 0) {
                        return ds_callback(false, '基础运费配置错误');
                    }
                    $weight_price = floatval($start_weight_price) + intval(($weight - $item['start_weight']) / $interval_weight) * $item['price'];
                }
            }
        }
        if ($weight_price === false) {
            return ds_callback(false, '超过最大可配送重量' . $item['end_weight'] . '公斤');
        }
        //计算时间费用
        if ($time) {
            $time = $time - strtotime(date('Y-m-d 0:0:0', $time));
        } else {
            $time = TIMESTAMP - strtotime(date('Y-m-d 0:0:0'));
        }

        $time = $time / 60;
        if (!is_array($time_price_list)) {
            return ds_callback(false, '特殊时段费配置错误');
        }
        $time_price = 0;

        foreach ($time_price_list as $item) {
            if ($item['start_time'] <= $time && $item['end_time'] >= $time) {
                $time_price = $item['price'];
            }
        }
        return ds_callback(true, '', array('distance_price' => $distance_price, 'weight_price' => $weight_price, 'time_price' => $time_price));
    }

    /**
     * 商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
     * @param unknown $store_cart_list 以店铺ID分组的购物车商品信息
     * @return array
     */
    public function calcCartList($store_cart_list) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array($store_cart_list, array(), 0);

        //存放每个店铺的商品总金额
        $store_goods_total = array();
        //存放本次下单所有店铺商品总金额
        $order_goods_total = 0;

        foreach ($store_cart_list as $store_id => $store_cart) {
            $tmp_amount = 0;
            $tmp_amount2 = 0;
            $tmp_amount3 = 0;
            foreach ($store_cart as $key => $cart_info) {
                $store_cart[$key]['goods_original_total'] = ds_price_format($cart_info['goods_original_price'] * $cart_info['goods_num']); //商品原价
                $store_cart[$key]['goods_total'] = ds_price_format($cart_info['goods_price'] * $cart_info['goods_num']);
                $store_cart[$key]['goods_discount_total'] = ds_price_format($store_cart[$key]['goods_original_total'] - $store_cart[$key]['goods_total']); //优惠金额
                $store_cart[$key]['goods_image_url'] = goods_cthumb($store_cart[$key]['goods_image']);
                $tmp_amount += $store_cart[$key]['goods_total'];
                $tmp_amount2 += $store_cart[$key]['goods_original_total'];
                $tmp_amount3 += $store_cart[$key]['goods_discount_total'];
            }
            $store_cart_list[$store_id] = $store_cart;
            $store_goods_total[$store_id] = ds_price_format($tmp_amount);
            $store_goods_original_total[$store_id] = ds_price_format($tmp_amount2);
            $store_goods_discount_total[$store_id] = ds_price_format($tmp_amount3);
        }
        return array($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total);
    }

    /**
     * 取得店铺级优惠 - 跟据商品金额返回每个店铺当前符合的一条活动规则，如果有赠品，则自动追加到购买列表，价格为0
     * @param unknown $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array($premiums_list,$mansong_rule_list) 分别为赠品列表[下标自增]，店铺满送规则列表[店铺ID为下标]
     */
    public function getMansongruleCartListByTotal($store_goods_total) {
        if (!config('ds_config.promotion_allow') || empty($store_goods_total) || !is_array($store_goods_total))
            return array(array(), array());

        $pmansong_model = model('pmansong');

        //定义赠品数组，下标为店铺ID
        $premiums_list = array();
        //定义满送活动数组，下标为店铺ID
        $mansong_rule_list = array();

        foreach ($store_goods_total as $store_id => $goods_total) {
            $rule_info = $pmansong_model->getMansongruleByStoreID($store_id, $goods_total);
            if (is_array($rule_info) && !empty($rule_info)) {
                //即不减金额，也找不到促销商品时(已下架),此规则无效
                if (empty($rule_info['mansongrule_discount']) && empty($rule_info['mansong_goods_name'])) {
                    continue;
                }
                $rule_info['desc'] = $this->_parseMansongruleDesc($rule_info);
                $rule_info['discount'] = ds_price_format($rule_info['mansongrule_discount']);
                $mansong_rule_list[$store_id] = $rule_info;
                //如果赠品在售,有库存,则追加到购买列表
                if (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) {
                    $data = array();
                    $data['goods_id'] = $rule_info['goods_id'];
                    $data['goods_name'] = $rule_info['mansong_goods_name'];
                    $data['goods_num'] = 1;
                    $data['goods_price'] = 0.00;
                    $data['goods_image'] = $rule_info['goods_image'];
                    $data['goods_image_url'] = goods_cthumb($rule_info['goods_image']);
                    $data['goods_storage'] = $rule_info['goods_storage'];
                    $premiums_list[$store_id][] = $data;
                }
            }
        }
        return array($premiums_list, $mansong_rule_list);
    }

    /**
     * 重新计算每个店铺最终商品总金额(最初计算金额减去各种优惠)
     * @param array $store_goods_total 店铺商品总金额
     * @param array $preferential_array 店铺优惠活动内容
     * @param string $preferential_type 优惠类型，目前只有一个 'mansong'
     * @return array 返回扣除优惠后的店铺商品总金额
     */
    public function reCalcGoodsTotal($store_goods_total, $preferential_array, $preferential_type) {
        $deny = empty($store_goods_total) || !is_array($store_goods_total) || empty($preferential_array) || !is_array($preferential_array);
        if ($deny)
            return $store_goods_total;

        switch ($preferential_type) {
            case 'mansong':
                if (!config('ds_config.promotion_allow'))
                    return $store_goods_total;
                foreach ($preferential_array as $store_id => $rule_info) {
                    if (is_array($rule_info) && $rule_info['discount'] > 0) {
                        $store_goods_total[$store_id] -= $rule_info['discount'];
                    }
                }
                break;

            case 'voucher':
                if (!config('ds_config.voucher_allow'))
                    return $store_goods_total;
                foreach ($preferential_array as $store_id => $voucher_info) {
                    $store_goods_total[$store_id] -= $voucher_info['voucher_price'];
                }
                break;
        }
        return $store_goods_total;
    }

    /**
     * 取得店铺可用的代金券
     * @param array $store_goods_total array(店铺ID=>商品总金额)
     * @return array
     */
    public function getStoreAvailableVoucherList($store_goods_total, $member_id) {
        if (!config('ds_config.voucher_allow'))
            return array();
        $voucher_list = array();
        $voucher_model = model('voucher');
        foreach ($store_goods_total as $store_id => $goods_total) {
            $condition = array();
            $condition[] = array('voucher_store_id', '=', $store_id);
            $condition[] = array('voucher_owner_id', '=', $member_id);
            $voucher_list[$store_id] = $voucher_model->getCurrentAvailableVoucher($condition, $goods_total);
        }
        return $voucher_list;
    }

    /**
     * 验证传过来的代金券是否可用有效，如果无效，直接删除
     * @param array $input_voucher_list 代金券列表
     * @param array $store_goods_total (店铺ID=>商品总金额)
     * @return array
     */
    public function reParseVoucherList($input_voucher_list = array(), $store_goods_total = array(), $member_id) {
        if (empty($input_voucher_list) || !is_array($input_voucher_list))
            return array();
        $store_voucher_list = $this->getStoreAvailableVoucherList($store_goods_total, $member_id);

        foreach ($input_voucher_list as $store_id => $voucher) {
            $tmp = $store_voucher_list[$store_id];
            if (is_array($tmp) && isset($tmp[$voucher['vouchertemplate_id']])) {
                $input_voucher_list[$store_id]['voucher_price'] = $tmp[$voucher['vouchertemplate_id']]['voucher_price'];
                $input_voucher_list[$store_id]['voucher_id'] = $tmp[$voucher['vouchertemplate_id']]['voucher_id'];
                $input_voucher_list[$store_id]['voucher_code'] = $tmp[$voucher['vouchertemplate_id']]['voucher_code'];
                $input_voucher_list[$store_id]['voucher_owner_id'] = $tmp[$voucher['vouchertemplate_id']]['voucher_owner_id'];
            } else {
                unset($input_voucher_list[$store_id]);
            }
        }
        return $input_voucher_list;
    }

    /**
     * 判断商品是不是秒杀中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getXianshiInfo(& $goods_info, $quantity) {
        if (empty($quantity))
            $quantity = 1;
        if (!config('ds_config.promotion_allow') || empty($goods_info['xianshi_info']))
            return;
        $goods_info['xianshi_info']['down_price'] = ds_price_format($goods_info['goods_price'] - $goods_info['xianshi_info']['xianshigoods_price']);
        if ($quantity >= $goods_info['xianshi_info']['xianshigoods_lower_limit']) {
            $goods_info['goods_price'] = $goods_info['xianshi_info']['xianshigoods_price'];
            $goods_info['promotions_id'] = $goods_info['xianshi_info']['xianshi_id'];
            $goods_info['ifxianshi'] = true;
        }
    }


    /**
     * 计算每个店铺(所有店铺级优惠活动)总共优惠多少金额
     * @param array $store_goods_total 最初店铺商品总金额
     * @param array $store_final_goods_total 去除各种店铺级促销后，最终店铺商品总金额(不含运费)
     * @return array
     */
    public function getStorePromotionTotal($store_goods_total, $store_final_goods_total) {
        if (!is_array($store_goods_total) || !is_array($store_final_goods_total))
            return array();
        $store_promotion_total = array();
        foreach ($store_goods_total as $store_id => $goods_total) {
            $store_promotion_total[$store_id] = abs($goods_total - $store_final_goods_total[$store_id]);
        }
        return $store_promotion_total;
    }

    /**
     * 追加赠品到下单列表,并更新购买数量
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @param array $store_mansong_rule_list 满即送规则
     */
    public function appendPremiumsToCartList($store_cart_list, $store_premiums_list = array(), $store_mansong_rule_list = array(), $member_id) {
        if (empty($store_cart_list))
            return array();

        //处理商品级赠品
        foreach ($store_cart_list as $store_id => $cart_list) {
            foreach ($cart_list as $cart_info) {
                if (empty($cart_info['gift_list']))
                    continue;
                if (!is_array($store_premiums_list))
                    $store_premiums_list = array();
                if (!array_key_exists($store_id, $store_premiums_list))
                    $store_premiums_list[$store_id] = array();
                $zenpin_info = array();
                foreach ($cart_info['gift_list'] as $gift_info) {
                    $zenpin_info['goods_id'] = $gift_info['gift_goodsid'];
                    $zenpin_info['goods_name'] = $gift_info['gift_goodsname'];
                    $zenpin_info['goods_image'] = $gift_info['gift_goodsimage'];
                    $zenpin_info['goods_storage'] = $gift_info['goods_storage'];
                    $zenpin_info['goods_num'] = $cart_info['goods_num'] * $gift_info['gift_amount'];
                    $store_premiums_list[$store_id][] = $zenpin_info;
                }
            }
        }

        //取得每种商品的库存[含赠品]
        $goods_storage_quantity = $this->_getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list);

        //取得每种商品的购买量[不含赠品]
        $goods_buy_quantity = $this->_getEachGoodsBuyQuantity($store_cart_list);
        //halt($goods_buy_quantity);
        foreach ($goods_buy_quantity as $goods_id => $quantity) {
            $goods_storage_quantity[$goods_id] -= $quantity;
            if ($goods_storage_quantity[$goods_id] < 0) {
                //商品库存不足，请重购买
                return false;
            }
        }
        //将赠品追加到购买列表

        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $store_id => $goods_list) {
                $zp_list = array();
                $gift_desc = '';
                foreach ($goods_list as $goods_info) {
                    //如果没有库存了，则不再送赠品
                    if ($goods_storage_quantity[$goods_info['goods_id']] == 0) {
                        $gift_desc = '，赠品库存不足，未能全部送出 ';
                        continue;
                    }


                    $new_data = array();
                    $new_data['buyer_id'] = $member_id;
                    $new_data['store_id'] = $store_id;
                    $new_data['store_name'] = $store_cart_list[$store_id][0]['store_name'];
                    $new_data['goods_id'] = $goods_info['goods_id'];
                    $new_data['goods_name'] = $goods_info['goods_name'];
                    $new_data['goods_price'] = 0;
                    $new_data['goods_image'] = $goods_info['goods_image'];
                    $new_data['state'] = true;
                    $new_data['storage_state'] = true;
                    $new_data['gc_id'] = 0;
                    $new_data['goods_weight'] = 0;
                    $new_data['goods_vat'] = 0;
                    $new_data['goods_total'] = 0;
                    $new_data['ifzengpin'] = true;

                    //计算赠送数量，有就赠，赠完为止
                    if ($goods_storage_quantity[$goods_info['goods_id']] - $goods_info['goods_num'] >= 0) {
                        if (!isset($goods_buy_quantity[$goods_info['goods_id']])) {
                            $goods_buy_quantity[$goods_info['goods_id']] = $goods_info['goods_num'];
                        } else {
                            $goods_buy_quantity[$goods_info['goods_id']] += $goods_info['goods_num'];
                        }
                        $goods_storage_quantity[$goods_info['goods_id']] -= $goods_info['goods_num'];
                        $new_data['goods_num'] = $goods_info['goods_num'];
                    } else {
                        $new_data['goods_num'] = $goods_storage_quantity[$goods_info['goods_id']];
                        $goods_buy_quantity[$goods_info['goods_id']] += $goods_storage_quantity[$goods_info['goods_id']];
                        $goods_storage_quantity[$goods_info['goods_id']] = 0;
                    }
                    if (array_key_exists($goods_info['goods_id'], $zp_list)) {
                        $zp_list[$goods_info['goods_id']]['goods_num'] += $new_data['goods_num'];
                    } else {
                        $zp_list[$goods_info['goods_id']] = $new_data;
                    }
                }
                sort($zp_list);
                $store_cart_list[$store_id] = array_merge($store_cart_list[$store_id], $zp_list);

                @$store_mansong_rule_list[$store_id]['desc'] .= $gift_desc;
                @$store_mansong_rule_list[$store_id]['desc'] = trim($store_mansong_rule_list[$store_id]['desc'], '，');
            }
        }
        return array($store_cart_list, $goods_buy_quantity, $store_mansong_rule_list);
    }

    /**
     * 充值卡支付,依次循环每个订单
     * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function rcbPay($order_list, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

        $available_rcb_amount = floatval($buyer_info['available_rc_balance']);
        if ($available_rcb_amount <= 0)
            return;

        $order_model = model('order');
        $logic_order = model('order', 'logic');
        $predeposit_model = model('predeposit');
        $canPay=false;
        foreach ($order_list as $key => $order_info) {

            //货到付款的订单跳过
            if ($order_info['payment_code'] == 'offline')
                continue;
            if (!isset($order_info['rcb_amount'])) {
                $order_list[$key]['rcb_amount'] = $order_info['rcb_amount'] = 0;
            }

            if (!isset($order_info['pd_amount'])) {
                $order_list[$key]['pd_amount'] = $order_info['pd_amount'] = 0;
            }
            $order_amount = round($order_info['order_amount'] - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['order_sn'] = $order_info['order_sn'];

            //暂冻结充值卡,后面还需要 API彻底完成支付
            if ($available_rcb_amount > 0 && $order_amount>0) {
                if ($available_rcb_amount >= $order_amount) {
                    $available_rcb_amount -= $order_amount;
                    $data_pd['amount'] = $order_amount;
                    $canPay=true;
                } else {
                    $data_pd['amount'] = $available_rcb_amount;
                    $available_rcb_amount = 0;
                    $canPay=false;
                }

                $predeposit_model->changeRcb('order_freeze', $data_pd);
                //支付金额保存到订单
                $data_order = array();
                $order_list[$key]['rcb_amount'] = $data_order['rcb_amount'] = round($order_info['rcb_amount'] + $data_pd['amount'], 2);
                $result = $order_model->editOrder($data_order, array('order_id' => $order_info['order_id']));
                if (!$result) {
                    throw new \think\Exception('订单更新失败', 10006);
                }
               
            }
        }
        if ($canPay) {//收到货款
                    $logic_order->changeOrderReceivePay($order_list, 'buyer', $member_name,array('payment_code'=>'predeposit'));
                }
        return $order_list;
    }

    /**
     * 预存款支付,依次循环每个订单
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_list, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

//                 $payment_model = model('payment');
//                 $pd_payment_info = $payment_model->getPaymentOpenInfo(array('payment_code'=>'predeposit'));
//                 if (empty($pd_payment_info)) return;

        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0)
            return;

        $order_model = model('order');
        $logic_order = model('order', 'logic');
        $predeposit_model = model('predeposit');
        $canPay=false;
        foreach ($order_list as $key => $order_info) {

            //货到付款的订单、已经充值卡支付的订单跳过
            if ($order_info['payment_code'] == 'offline')
                continue;
            if ($order_info['order_state'] == ORDER_STATE_PAY)
                continue;
            if (!isset($order_info['rcb_amount'])) {
                $order_list[$key]['rcb_amount'] = $order_info['rcb_amount'] = 0;
            }

            if (!isset($order_info['pd_amount'])) {
                $order_list[$key]['pd_amount'] = $order_info['pd_amount'] = 0;
            }
            $order_amount = round($order_info['order_amount'] - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);

            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['order_sn'] = $order_info['order_sn'];
            //暂冻结预存款,后面还需要 API彻底完成支付
            if ($available_pd_amount > 0 && $order_amount>0) {
                if ($available_pd_amount >= $order_amount) {
                    $data_pd['amount'] = $order_amount;
                    $available_pd_amount -= $order_amount;
                    $canPay=true;
                } else {
                    $data_pd['amount'] = $available_pd_amount;
                    $available_pd_amount = 0;
                    $canPay=false;
                }
                $predeposit_model->changePd('order_freeze', $data_pd);
                //预存款支付金额保存到订单
                $data_order = array();
                $order_list[$key]['pd_amount'] = $data_order['pd_amount'] = round($order_info['pd_amount'] + $data_pd['amount'], 2);
                $result = $order_model->editOrder($data_order, array('order_id' => $order_info['order_id']));
                if (!$result) {
                    throw new \think\Exception('订单更新失败', 10006);
                }
           
            }
        }
        if ($canPay) {//收到货款
                    $logic_order->changeOrderReceivePay($order_list, 'buyer', $member_name,array('payment_code'=>'predeposit'));
                }
        return $order_list;
    }

    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num++;
        }
        return (date('y', TIMESTAMP) % 9 + 1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }

    /**
     * 更新库存与销量
     *
     * @param array $buy_items 商品ID => 购买数量
     */
    public function editGoodsNum($buy_items) {
        foreach ($buy_items as $goods_id => $buy_num) {
            $data = array(
                'goods_storage' => Db::raw('goods_storage-' . $buy_num),
                'goods_salenum' => Db::raw('goods_salenum+' . $buy_num)
            );
            $result = model('goods')->editGoods($data, array('goods_id' => $goods_id));
            if (!$result)
                throw new \think\Exception(lang('cart_step2_submit_fail'), 10006);
        }
    }

    /**
     * 取得店铺级活动 - 每个店铺可用的满即送活动规则列表
     * @param unknown $store_id_array 店铺ID数组
     */
    public function getMansongruleList($store_id_array) {
        if (!config('ds_config.promotion_allow') || empty($store_id_array) || !is_array($store_id_array))
            return array();
        $pmansong_model = model('pmansong');
        $mansong_rule_list = array();
        foreach ($store_id_array as $store_id) {
            $store_mansong_rule = $pmansong_model->getMansongInfoByStoreID($store_id);
            if (!empty($store_mansong_rule['rules']) && is_array($store_mansong_rule['rules'])) {
                foreach ($store_mansong_rule['rules'] as $rule_info) {
                    //如果减金额 或 有赠品(在售且有库存)
                    if (!empty($rule_info['mansongrule_discount']) || (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage']))) {
                        $mansong_rule_list[$store_id][] = $this->_parseMansongruleDesc($rule_info);
                    }
                }
            }
        }
        return $mansong_rule_list;
    }

    /**
     * 取得收货人地址信息
     * @param array $address_info
     * @return array
     */
    public function getReciverAddr($address_info = array()) {
        $reciver_info['phone'] = trim($address_info['address_mob_phone'] . ($address_info['address_tel_phone'] ? ',' . $address_info['address_tel_phone'] : null), ',');
        $reciver_info['mob_phone'] = $address_info['address_mob_phone'];
        $reciver_info['tel_phone'] = $address_info['address_tel_phone'];
        $reciver_info['address'] = $address_info['area_info'] . ' ' . $address_info['address_detail'];
        $reciver_info['area'] = $address_info['area_info'];
        $reciver_info['street'] = $address_info['address_detail'];
        $reciver_info['dada_lng_lat'] = $address_info['dada_lng_lat'];
		$reciver_info['house_number'] = $address_info['house_number'];
        $reciver_info = serialize($reciver_info);
        $reciver_name = $address_info['address_realname'];
        return array($reciver_info, $reciver_name);
    }

    /**
     * 整理发票信息
     * @param array $invoice_info 发票信息数组
     * @return string
     */
    public function createInvoiceData($invoice_info) {
        //发票信息
        $inv = array();
        if (isset($invoice_info['invoice_state']) && $invoice_info['invoice_state'] == 1) {
            $inv['类型'] = '普通发票 ';
            $inv['抬头'] = isset($invoice_info['invoice_title']) ? $invoice_info['invoice_title'] : '个人';
            $inv['内容'] = $invoice_info['invoice_content'];
            $inv['纳税人识别号'] = $invoice_info['invoice_code'];
        } elseif (!empty($invoice_info)) {
            $inv['单位名称'] = $invoice_info['invoice_company'];
            $inv['纳税人识别号'] = $invoice_info['invoice_company_code'];
            $inv['注册地址'] = $invoice_info['invoice_reg_addr'];
            $inv['注册电话'] = $invoice_info['invoice_reg_phone'];
            $inv['开户银行'] = $invoice_info['invoice_reg_bname'];
            $inv['银行账户'] = $invoice_info['invoice_reg_baccount'];
//            $inv['收票人姓名'] = $invoice_info['invoice_rec_name'];
//            $inv['收票人手机号'] = $invoice_info['invoice_rec_mobphone'];
//            $inv['收票人省份'] = $invoice_info['invoice_rec_province'];
//            $inv['送票地址'] = $invoice_info['invoice_goto_addr'];
        }
        return !empty($inv) ? serialize($inv) : serialize(array());
    }


    /**
     * 直接购买时返回最新的在售商品信息（需要在售）
     *
     * @param int $goods_id 所购商品ID
     * @param int $quantity 购买数量
     * @return array
     */
    private function _getGoodsOnlineInfo($goods_id, $quantity) {
        //取目前在售商品
        $goods_model=model('goods');
        $goods_model->lock=$this->lock;
        $goods_info = $goods_model->getGoodsOnlineInfoAndPromotionById($goods_id);
        if (empty($goods_info)) {
            return null;
        }
        $new_array = array();
        $new_array['goods_num'] = $quantity;
        $new_array['goods_id'] = $goods_id;
        $new_array['goods_commonid'] = $goods_info['goods_commonid'];
        $new_array['gc_id'] = $goods_info['gc_id'];
        $new_array['goods_weight'] = $goods_info['goods_weight'];
        $new_array['store_id'] = $goods_info['store_id'];
        $new_array['goods_name'] = $goods_info['goods_name'];
        $new_array['goods_price'] = $goods_info['goods_price'];
        $new_array['goods_original_price'] = $goods_info['goods_original_price'];
        $new_array['store_name'] = $goods_info['store_name'];
        $new_array['goods_image'] = $goods_info['goods_image'];
        $new_array['goods_vat'] = $goods_info['goods_vat'];
        $new_array['goods_storage'] = $goods_info['goods_storage'];
        $new_array['goods_storage_alarm'] = $goods_info['goods_storage_alarm'];
        $new_array['is_have_gift'] = $goods_info['is_have_gift'];
        $new_array['state'] = true;
        $new_array['storage_state'] = intval($goods_info['goods_storage']) < intval($quantity) ? false : true;
        $new_array['xianshi_info'] = $goods_info['xianshi_info'];

        //填充必要下标，方便后面统一使用购物车方法与模板
        //cart_id=goods_id,优惠套装目前只能进购物车,不能立即购买
        $new_array['cart_id'] = $goods_id;


        return $new_array;
    }

    /**
     * 取得某商品赠品列表信息
     * @param array $goods_info
     */
    private function _getGoodsgiftList(& $goods_info) {
        if (!isset($goods_info['is_have_gift']))
            return;
        $gift_list = model('goodsgift')->getGoodsgiftListByGoodsId($goods_info['goods_id']);
        //取得赠品当前信息，如果未在售踢除，如果在售取出库存
        if (empty($gift_list))
            return array();
        $goods_model = model('goods');
        foreach ($gift_list as $k => $v) {
            $goods_online_info = $goods_model->getGoodsOnlineInfoByID($v['gift_goodsid']);
            if (empty($goods_online_info)) {
                unset($gift_list[$k]);
            } else {
                $gift_list[$k]['goods_storage'] = $goods_online_info['goods_storage'];
            }
        }
        $goods_info['gift_list'] = $gift_list;
    }

    /**
     * 取商品最新的在售信息
     * @param unknown $cart_list
     * @return array
     */
    private function _getOnlineCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list))
            return $cart_list;
        //验证商品是否有效
        $goods_id_array = array();
        foreach ($cart_list as $key => $cart_info) {
            $goods_id_array[] = $cart_info['goods_id'];
        }
        $goods_model = model('goods');
        $goods_model->lock=$this->lock;
        $goods_online_list = $goods_model->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
        $goods_online_array = array();
        foreach ($goods_online_list as $goods) {
            $goods_online_array[$goods['goods_id']] = $goods;
        }

        foreach ((array) $cart_list as $key => $cart_info) {
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            if (in_array($cart_info['goods_id'], array_keys($goods_online_array))) {
                $goods_online_info = $goods_online_array[$cart_info['goods_id']];
                $cart_list[$key]['goods_commonid'] = $goods_online_info['goods_commonid'];
                $cart_list[$key]['goods_name'] = $goods_online_info['goods_name'];
                $cart_list[$key]['gc_id'] = $goods_online_info['gc_id'];
                $cart_list[$key]['goods_weight'] = $goods_online_info['goods_weight'];
                $cart_list[$key]['goods_image'] = $goods_online_info['goods_image'];
                $cart_list[$key]['goods_price'] = $goods_online_info['goods_price'];
                $cart_list[$key]['goods_original_price'] = $goods_online_info['goods_original_price'];
                $cart_list[$key]['goods_vat'] = $goods_online_info['goods_vat'];
                $cart_list[$key]['goods_storage'] = $goods_online_info['goods_storage'];
                $cart_list[$key]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                $cart_list[$key]['is_have_gift'] = $goods_online_info['is_have_gift'];
                if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                    $cart_list[$key]['storage_state'] = false;
                }
                $cart_list[$key]['xianshi_info'] = $goods_online_info['xianshi_info'];
            } else {
                //如果商品下架
                $cart_list[$key]['state'] = false;
                $cart_list[$key]['storage_state'] = false;
            }
        }

        return $cart_list;
    }

    /**
     * 批量判断购物车内的商品是不是秒杀中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * 并标识该商品为秒杀商品
     * @param array $cart_list
     */
    public function getXianshiCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        foreach ($cart_list as $key => $cart_info) {
            if (empty($cart_info['xianshi_info']))
                continue;
            $this->getXianshiInfo($cart_info, $cart_info['goods_num']);
            $cart_list[$key] = $cart_info;
        }
    }

    /**
     * 取得购物车商品的赠品列表[商品级赠品]
     *
     * @param array $cart_list
     */
    private function _getGiftCartList(& $cart_list) {
        foreach ($cart_list as $k => $cart_info) {
            $this->_getGoodsgiftList($cart_info);
            $cart_list[$k] = $cart_info;
        }
    }

    /**
     * 取得每种商品的库存
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @return array 商品ID=>库存
     */
    private function _getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list = array()) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array();
        $goods_storage_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                //正常商品
                $goods_storage_quangity[$cart_info['goods_id']] = $cart_info['goods_storage'];
            }
        }
        //取得赠品商品的库存
        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $store_id => $goods_list) {
                foreach ($goods_list as $goods_info) {
                    if (!isset($goods_storage_quangity[$goods_info['goods_id']])) {
                        $goods_storage_quangity[$goods_info['goods_id']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        return $goods_storage_quangity;
    }

    /**
     * 取得每种商品的购买量
     * @param array $store_cart_list 购买列表
     * @return array 商品ID=>购买数量
     */
    private function _getEachGoodsBuyQuantity($store_cart_list) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array();
        $goods_buy_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                //正常商品
                if (!isset($goods_buy_quangity[$cart_info['goods_id']])) {
                    $goods_buy_quangity[$cart_info['goods_id']] = $cart_info['goods_num'];
                } else {
                    $goods_buy_quangity[$cart_info['goods_id']] += $cart_info['goods_num'];
                }
            }
        }
        return $goods_buy_quangity;
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
                    $buy_items[$match[1][0]] = $match[2][0];
                }
            }
        }
        return $buy_items;
    }

    /**
     * 拼装单条满即送规则页面描述信息
     * @param array $rule_info 满即送单条规则信息
     * @return string
     */
    private function _parseMansongruleDesc($rule_info) {
        if (empty($rule_info) || !is_array($rule_info))
            return;
        $discount_desc = !empty($rule_info['mansongrule_discount']) ? '减' . $rule_info['mansongrule_discount'] : '';
        $goods_desc = (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) ? " 送<a href='" . url('home/Goods/index', ['goods_id' => $rule_info['goods_id']]) . "' title='{$rule_info['mansong_goods_name']}' target='_blank'>[赠品]</a>" : '';
        return sprintf('满%s%s%s', $rule_info['mansongrule_price'], $discount_desc, $goods_desc);
    }

}
