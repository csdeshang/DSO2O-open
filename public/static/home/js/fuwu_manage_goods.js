$(function(){
    // 取消回车提交表单 
    $('input').keypress(function(e){
        var key = window.event ? e.keyCode : e.which;
        if (key.toString() == "13") {
         return false;
        }
    });

    /* 商品图片ajax上传 */
    $('#goods_image').fileupload({
        dataType: 'json',
        url: HOMESITEURL + '/fuwu_manage_info/upload_info_file.html?upload_type=uploadedfile',
        formData: {type:'goods_image',item_id:GOODS_ID},
        add: function (e,data) {
        	$('img[dstype="goods_image"]').attr('src', HOMESITEROOT + '/images/loading.gif');
            data.submit();
        },
        done: function (e,data) {
            var param = data.result;
            if (param.code!=10000) {
                alert(param.message);
                $('img[dstype="goods_image"]').attr('src',DEFAULT_GOODS_IMAGE);
            } else {
                $('input[dstype="goods_image"]').val(param.result.path);
                $('img[dstype="goods_image"]').attr('src',param.result.url);
            }
        }
    });
    /* ajax打开图片空间 */
    // 商品主图使用
    $('a[dstype="show_image"]').unbind().ajaxContent({
        event:'click', //mouseover
        loaderType:"img",
        loadingMsg:HOMESITEROOT+"/images/loading.gif",
        target:'[dstype_pic="goods_image"]'
    }).click(function(){
        $(this).hide();
        $('a[dstype="del_goods_demo"]').show();
    });
    $('a[dstype="del_goods_demo"]').unbind().click(function(){
        $('[dstype_pic="goods_image"]').html('');
        $(this).hide();
        $('a[dstype="show_image"]').show();
    });

    $('#add_album').fileupload({
        dataType: 'json',
        url: HOMESITEURL+'/fuwu_manage_info/upload_info_file.html?upload_type=uploadedfile',
        formData: {type:'goods_body',item_id:GOODS_ID},
        add: function (e,data) {
             $('i[dstype="add_album_i"]').html("&#xe717;").addClass('rotate').attr('data_type', parseInt($('i[dstype="add_album_i"]').attr('data_type'))+1);
            data.submit();
        },
        done: function (e,data) {
            var _counter = parseInt($('i[dstype="add_album_i"]').attr('data_type'));
            _counter -= 1;
            if (_counter == 0) {
                 $('i[dstype="add_album_i"]').removeClass('rotate').html("&#xe733;");
                $('a[dstype="mb_add_img"]').click();
            }
            $('i[dstype="add_album_i"]').attr('data_type', _counter);
        }
    });
    /* ajax打开图片空间 end */


    /* 手机端 商品描述 */
    // 显示隐藏控制面板
    $('div[dstype="mobile_pannel"]').on('click', '.module', function(){
        mbPannelInit();
        $(this).siblings().removeClass('current').end().addClass('current');
    });
    // 上移
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="mp_up"]', function(){
        var _parents = $(this).parents('.module:first');
        _rs = mDataMove(_parents.index(), 0);
        if (!_rs) {
            return false;
        }
        _parents.prev().before(_parents.clone());
        _parents.remove();
        mbPannelInit();
    });
    // 下移
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="mp_down"]', function(){
        var _parents = $(this).parents('.module:first');
        _rs = mDataMove(_parents.index(), 1);
        if (!_rs) {
            return false;
        }
        _parents.next().after(_parents.clone());
        _parents.remove();
        mbPannelInit();
    });
    // 删除
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="mp_del"]', function(){
        var _parents = $(this).parents('.module:first');
        mDataRemove(_parents.index());
        _parents.remove();
        mbPannelInit();
    });
    // 编辑
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="mp_edit"]', function(){
        $('a[dstype="meat_cancel"]').click();
        var _parents = $(this).parents('.module:first');
        var _val = _parents.find('.text-div').html();
        $(this).parents('.module:first').html('')
            .append('<div class="content"></div>').find('.content')
            .append('<div class="dssc-mea-text" dstype="mea_txt"></div>')
            .find('div[dstype="mea_txt"]')
            .append('<p id="meat_content_count" class="text-tip">')
            .append('<textarea class="textarea valid" data-old="' + _val + '" dstype="meat_content">' + _val + '</textarea>')
            .append('<div class="button"><a class="dssc-btn dssc-btn-blue" dstype="meat_edit_submit" href="javascript:void(0);">确认</a><a class="dssc-btn ml10" dstype="meat_edit_cancel" href="javascript:void(0);">取消</a></div>')
            .append('<a class="text-close" dstype="meat_edit_cancel" href="javascript:void(0);">X</a>')
            .find('#meat_content_count').html('').end()
            .find('textarea[dstype="meat_content"]').unbind().charCount({
                allowed: 500,
                warning: 50,
                counterContainerID: 'meat_content_count',
                firstCounterText:   '还可以输入',
                endCounterText:     '字',
                errorCounterText:   '已经超出'
            });
    });
    // 编辑提交
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="meat_edit_submit"]', function(){
        var _parents = $(this).parents('.module:first');
        var _c = toTxt(_parents.find('textarea[dstype="meat_content"]').val().replace(/[\r\n]/g,''));
        var _cl = _c.length;
        if (_cl == 0 || _cl > 500) {
            return false;
        }
        _data = new Object;
        _data.type = 'text';
        _data.value = _c;
        _rs = mDataReplace(_parents.index(), _data);
        if (!_rs) {
            return false;
        }
        _parents.html('').append('<div class="tools"><a dstype="mp_up" href="javascript:void(0);">上移</a><a dstype="mp_down" href="javascript:void(0);">下移</a><a dstype="mp_edit" href="javascript:void(0);">编辑</a><a dstype="mp_del" href="javascript:void(0);">删除</a></div>')
            .append('<div class="content"><div class="text-div">' + _c + '</div></div>')
            .append('<div class="cover"></div>');

    });
    // 编辑关闭
    $('div[dstype="mobile_pannel"]').on('click', '[dstype="meat_edit_cancel"]', function(){
        var _parents = $(this).parents('.module:first');
        var _c = _parents.find('textarea[dstype="meat_content"]').attr('data-old');
        _parents.html('').append('<div class="tools"><a dstype="mp_up" href="javascript:void(0);">上移</a><a dstype="mp_down" href="javascript:void(0);">下移</a><a dstype="mp_edit" href="javascript:void(0);">编辑</a><a dstype="mp_del" href="javascript:void(0);">删除</a></div>')
        .append('<div class="content"><div class="text-div">' + _c + '</div></div>')
        .append('<div class="cover"></div>');
    });
    // 初始化控制面板
    mbPannelInit = function(){
        $('div[dstype="mobile_pannel"]')
            .find('a[dstype^="mp_"]').show().end()
            .find('.module')
            .first().find('a[dstype="mp_up"]').hide().end().end()
            .last().find('a[dstype="mp_down"]').hide();
    }
    // 添加文字按钮，显示文字输入框
    $('a[dstype="mb_add_txt"]').click(function(){
        $('div[dstype="mea_txt"]').show();
        $('a[dstype="meai_cancel"]').click();
    
    $('div[dstype="mobile_editor_area"]').find('textarea[dstype="meat_content"]').unbind().charCount({
        allowed: 500,
        warning: 50,
        counterContainerID: 'meat_content_count',
        firstCounterText:   '还可以输入',
        endCounterText:     '字',
        errorCounterText:   '已经超出'
    })});
    // 关闭 文字输入框按钮
    $('a[dstype="meat_cancel"]').click(function(){
        $(this).parents('div[dstype="mea_txt"]').find('textarea[dstype="meat_content"]').val('').end().hide();
    });
    // 提交 文字输入框按钮
    $('a[dstype="meat_submit"]').click(function(){
        var _c = toTxt($('textarea[dstype="meat_content"]').val().replace(/[\r\n]/g,''));
        var _cl = _c.length;
        if (_cl == 0 || _cl > 500) {
            return false;
        }
        _data = new Object;
        _data.type = 'text';
        _data.value = _c;
        _rs = mDataInsert(_data);
        if (!_rs) {
            return false;
        }
        $('<div class="module m-text"></div>')
            .append('<div class="tools"><a dstype="mp_up" href="javascript:void(0);">上移</a><a dstype="mp_down" href="javascript:void(0);">下移</a><a dstype="mp_edit" href="javascript:void(0);">编辑</a><a dstype="mp_del" href="javascript:void(0);">删除</a></div>')
            .append('<div class="content"><div class="text-div">' + _c + '</div></div>')
            .append('<div class="cover"></div>').appendTo('div[dstype="mobile_pannel"]');
        
        $('a[dstype="meat_cancel"]').click();
    });
    // 添加图片按钮，显示图片空间文字
    $('a[dstype="mb_add_img"]').click(function(){
        $('a[dstype="meat_cancel"]').click();
        $('div[dstype_pic="goods_body"]').show().load(HOMESITEURL+'/fuwu_manage_goods/pic_list.html?item=goods_body');
    });
    // 关闭 图片选择
    $('div[dstype="mobile_editor_area"]').on('click', 'a[dstype="meai_cancel"]', function(){
        $('div[dstype_pic="goods_body"]').html('');
    });
    // 插图图片
    insert_mobile_img = function(data){
        _data = new Object;
        _data.type = 'image';
        _data.value = data;
        _rs = mDataInsert(_data);
        if (!_rs) {
            return false;
        }
        $('<div class="module m-image"></div>')
            .append('<div class="tools"><a dstype="mp_up" href="javascript:void(0);">上移</a><a dstype="mp_down" href="javascript:void(0);">下移</a><a dstype="mp_rpl" href="javascript:void(0);">替换</a><a dstype="mp_del" href="javascript:void(0);">删除</a></div>')
            .append('<div class="content"><div class="image-div"><img src="' + data + '"></div></div>')
            .append('<div class="cover"></div>').appendTo('div[dstype="mobile_pannel"]');
        
    }
    // 替换图片
    $('div[dstype="mobile_pannel"]').on('click', 'a[dstype="mp_rpl"]', function(){
        $('a[dstype="meat_cancel"]').click();
        $('div[dstype_pic="goods_body"]').show().load(HOMESITEURL+'/fuwu_manage_goods/pic_list.html?item=goods_body&type=replace');
    });
    // 插图图片
    replace_mobile_img = function(data){
        var _parents = $('div.m-image.current');
        _parents.find('img').attr('src', data);
        _data = new Object;
        _data.type = 'image';
        _data.value = data;
        mDataReplace(_parents.index(), _data);
    }
    // 插入数据
    mDataInsert = function(data){
        _m_data = mDataGet();
        _m_data.push(data);
        return mDataSet(_m_data);
    }
    // 数据移动 
    // type 0上移  1下移
    mDataMove = function(index, type) {
        _m_data = mDataGet();
        _data = _m_data.splice(index, 1);
        if (type) {
            index += 1;
        } else {
            index -= 1;
        }
        _m_data.splice(index, 0, _data[0]);
        return mDataSet(_m_data);
    }
    // 数据移除
    mDataRemove = function(index){
        _m_data = mDataGet();
        _m_data.splice(index, 1);     // 删除数据
        return mDataSet(_m_data);
    }
    // 替换数据
    mDataReplace = function(index, data){
        _m_data = mDataGet();
        _m_data.splice(index, 1, data);
        return mDataSet(_m_data);
    }
    // 获取数据
    mDataGet = function(){
        _m_body = $('input[name="goods_body"]').val();
        if (_m_body == '' || _m_body == 'false') {
            var _m_data = new Array;
        } else {
            eval('var _m_data = ' + _m_body);
        }
        return _m_data;
    }
    // 设置数据
    mDataSet = function(data){
        var _i_c = 0;
        var _i_c_m = 20;
        var _t_c = 0;
        var _t_c_m = 5000;
        var _sign = true;
        $.each(data, function(i, n){
            if (n.type == 'image') {
                _i_c += 1;
                if (_i_c > _i_c_m) {
                    alert('只能选择'+_i_c_m+'张图片');
                    _sign = false;
                    return false;
                }
            } else if (n.type == 'text') {
                _t_c += n.value.length;
                if (_t_c > _t_c_m) {
                    alert('只能输入'+_t_c_m+'个字符');
                    _sign = false;
                    return false;
                }
            }
        });
        if (!_sign) {
            return false;
        }
        $('span[dstype="img_count_tip"]').html('还可以选择图片<em>' + (_i_c_m - _i_c) + '</em>张');
        $('span[dstype="txt_count_tip"]').html('还可以输入<em>' + (_t_c_m - _t_c) + '</em>字');
        _data = JSON.stringify(data);
        $('input[name="goods_body"]').val(_data);
        return true;
    }
    // 转码
    toTxt = function(str) {
        var RexStr = /\<|\>|\"|\'|\&|\\/g
        str = str.replace(RexStr, function(MatchStr) {
            switch (MatchStr) {
            case "<":
                return "";
                break;
            case ">":
                return "";
                break;
            case "\"":
                return "";
                break;
            case "'":
                return "";
                break;
            case "&":
                return "";
                break;
            case "\\":
                return "";
                break;
            default:
                break;
            }
        })
        return str;
    }
});

/* 插入商品图片 */
function insert_img(name, src) {
    $('input[dstype="goods_image"]').val(name);
    $('img[dstype="goods_image"]').attr('src',src);
}
