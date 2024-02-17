function initBmap(config) {
    this.api='//api.map.baidu.com'
    if (config) {
        this.BMAP_AK = config.BMAP_AK
    }
    this.getPosition = function (callback, start) {
        if (navigator.geolocation) {
            var tmid = window.setTimeout(function () {
                var res = {code: 10001, message: '定位超时', result: ''}
                callback(res)
            }, 1000 * 15)
            var func = start ? 'getCurrentPosition' : 'watchPosition'

            var temp = navigator.geolocation[func](function (position) {
                window.clearTimeout(tmid)
                var res = {code: 10000, message: '', result: {lng: position.coords.longitude, lat: position.coords.latitude}}
                callback(res)
            }, function (error) {
                window.clearTimeout(tmid)
                var message = ''
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = '定位请求拒绝'
                        break
                    case error.POSITION_UNAVAILABLE:
                        message = '定位获取失败'
                        break
                    case error.TIMEOUT:
                        message = '定位超时'
                        break
                    default:
                        message = '未知错误'
                        break
                }
                var res = {code: 10001, message: message, result: ''}
                callback(res)
            })
            if (!start) {
                this.navigator_id = temp
            }
        } else {
            var res = {code: 10001, message: '定位不支持', result: ''}
            callback(res)
        }
    }
    this.getPointByIp=function(callback){
        $.getJSON(this.api+'/location/ip',{coor:'bd09ll',ak:this.BMAP_AK},function(res){
            callback(res)
        })
    }
    this.getPointNearby=function(location,keyword,callback){
        $.getJSON(this.api+'/place/v2/search',{query: keyword?keyword:'宾馆$酒店$住宅$餐饮$生活娱乐$公司$商务$学校$大厦$公寓$写字楼',coord_type:'bd09ll',output: 'json',location: location,ak:this.BMAP_AK},function(res){
            callback(res)
        })
    }
}
