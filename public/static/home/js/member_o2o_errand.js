var EARTH_RADIUS = 6378137.0 // 单位M
var PI = Math.PI

function getRad (d) {
  return d * PI / 180.0
}

function getDistance(lat1, lng1, lat2, lng2) {
    var f = getRad((lat1 + lat2) / 2)
    var g = getRad((lat1 - lat2) / 2)
    var l = getRad((lng1 - lng2) / 2)
    var sg = Math.sin(g)
    var sl = Math.sin(l)
    var sf = Math.sin(f)
    var s, c, w, r, d, h1, h2
    var a = EARTH_RADIUS
    var fl = 1 / 298.257
    sg = sg * sg
    sl = sl * sl
    sf = sf * sf
    s = sg * (1 - sl) + (1 - sf) * sl
    c = (1 - sg) * (1 - sl) + sf * sl
    w = Math.atan(Math.sqrt(s / c))
    r = Math.sqrt(s * c) / w
    d = 2 * w * a
    h1 = (3 * r - 1) / 2 / c
    h2 = (3 * r + 1) / 2 / s
    return d * (1 + fl * (h1 * sf * (1 - sg) - h2 * (1 - sf) * sg))
}
function getTotalAmount(config,deliver,receive,weight,time,gratuity) {
    
    // 基础运费
    var distance = getDistance(parseFloat(deliver.lat), parseFloat(deliver.lng), parseFloat(receive.lat), parseFloat(receive.lng))
    distance = (distance / 1000).toFixed(1)
    var distance_price_list = config.o2o_errand_distance_price
    var distance_price
    var start_distance_price = 0
    for (var i in distance_price_list) {
        if (distance_price_list[i].if_fixed==1) {
            start_distance_price = distance_price_list[i].price
        }
        if (distance_price_list[i].start_distance <= distance && distance_price_list[i].end_distance >= distance) {
            if (distance_price_list[i].if_fixed==1) {
                distance_price = distance_price_list[i].price
            } else {
                distance_price = parseFloat(start_distance_price) + parseInt((distance - distance_price_list[i].start_distance) / distance_price_list[i].interval_distance) * distance_price_list[i].price
            }
        }
    }
    if (isNaN(distance_price)) {
        var msg = '超过最大可配送距离' + distance_price_list[i].end_distance + '公里'
        return {done:false,msg:msg}
    }
    // 重量附加费
    var weight_price_list = config.o2o_errand_weight_price
    var weight_price
    var start_weight_price = 0
    for (var i in weight_price_list) {
        if (weight_price_list[i].if_fixed==1) {
            start_weight_price = weight_price_list[i].price
        }
        if (weight_price_list[i].start_weight <= weight && weight_price_list[i].end_weight >= weight) {
            if (weight_price_list[i].if_fixed==1) {
                weight_price = weight_price_list[i].price
            } else {
                weight_price = parseFloat(start_weight_price) + parseInt((weight - weight_price_list[i].start_weight) / weight_price_list[i].interval_weight) * weight_price_list[i].price
            }
        }
    }
    if (isNaN(weight_price)) {
        var msg = '超过最大可配送重量' + weight_price_list[i].end_weight + '公斤'
        return {done:false,msg:msg}
    }
    // 特殊时间段费用
    var time_price_list = config.o2o_errand_time_price
    var time_price = 0
    for (var i in time_price_list) {
        if (time_price_list[i].start_time <= time && time_price_list[i].end_time >= time) {
            time_price = time_price_list[i].price
        }
    }

    var total_price = parseFloat(distance_price) + parseFloat(weight_price) + parseFloat(time_price) + gratuity
    distance_price=distance_price.toFixed(2)
    weight_price=weight_price.toFixed(2)
    time_price=time_price.toFixed(2)
    total_price=total_price.toFixed(2)
    return {done:true,result:{total_price:total_price,distance_price:distance_price,weight_price:weight_price,time_price:time_price,gratuity:gratuity}}
}