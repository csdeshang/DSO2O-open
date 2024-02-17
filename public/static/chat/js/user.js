var new_interval = 1;//消息提醒计时
var connect = 0;//连接状态
var new_msg = 0;//新消息数
var obj = {};
var chat_log = {};
var connect_list = {};
var connect_n = 0;
var web_info = new Array();//页面信息
var friend_list = new Array();//我的好友
var follow_list = new Array();//我关注的人
var recent_list = new Array();//最近联系人
var user_list = new Array();//所有会员信息
var msg_list = new Array();//收到消息
var goods_list = new Array();//所有商品信息
var store_goods = new Array();//店铺推荐的商品
var dialog_show = 0;//对话框是否打开
var user_show = 0;//当前选择的会员
var msg_max = 20;//消息数
var chat_audio = 1;//消息提醒声音开关
var audio_info = '<object width="1" height="1" style="position: absolute; left: -1px;" id="msg_audio" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">' +
        '<param value="' + CHAT_SITE_URL + '/audio.swf" name="movie"><param value="always" name="allowscriptaccess">' +
        '<embed width="1" height="1" allowscriptaccess="always" src="' + CHAT_SITE_URL + '/audio.swf" name="msg_audio"></object>';
$(function () {
    if (user['u_id'] != '') {
        web_info['chat_goods_html'] = '';
        web_info['html_title'] = $(document).attr('title');
        web_info['msg_dialog'] = '<div id="new_msg_dialog" class="msg-windows"><div class="user-tab-bar"><ul class="user-list" id="user_list"></ul></div>' +
                '<div class="msg-dialog"><div class="dialog-body">' +
                '<div id="msg_list" class="msg-contnet"><div id="user_msg_list"></div></div>' +
                '<div class="msg-input-box"><div class="msg-input-title"><div class="chat_tools"><i id="chat_show_smilies" class="iconfont">&#xe669;</i></div>' +
                '<span class="title">输入聊天信息</span><span class="chat-log-btn off" onclick="show_chat_log();">聊天记录<i></i></span></div>' +
                '<form id="msg_form"><textarea name="send_message" id="send_message" class="textarea" onkeyup="send_keyup(event);" onfocus="send_focus();" ></textarea>' +
                '<div class="msg-bottom"><div id="msg_count"></div><a href="JavaScript:void(0);" onclick="send_msg();" class="msg-button"><i></i>发送消息</a><div id="send_alert"></div></div></form></div></div>' +
                '<div id="dialog_chat_right" class="dialog-chat-right"></div><div id="dialog_chat_log" class="dialog_chat_log"></div></div>' +
                '<div id="dialog_clear" class="dialog_clear"></div></div>';
        var chat_user_list = '<div class="chat-box"><div class="chat-list"><div class="chat-list-top"><img class="avatar" src="' + user['avatar'] + '?' + Math.floor(Math.random() * 100) + '" /><h1>' + user['u_name'] + '</h1><span class="minimize-chat-list iconfont" onclick="chat_show_list();">&#xe6e6;</span></div>' +
                '<div id="chat_user_list" class="chat-list-content"><div><dl id="chat_user_friends"><dt onclick="chat_show_user_list(\'friends\');">' +
                '<span class="show"></span>我的好友</dt><dd id="chat_friends" style="display: none;"></dd></dl>' +
                '<dl id="chat_user_follow"><dt onclick="chat_show_user_list(\'follow\');"><span class="show"></span>我关注的人</dt><dd id="chat_follow" style="display: none;"></dd></dl>' +
                '<dl id="chat_user_recent"><dt onclick="chat_show_user_list(\'recent\');"><span class="show"></span>最近联系人</dt><dd id="chat_recent" style="display: none;"></dd></dl></div></div>' +
                '<div class="bottom-bar"><a href="' + HOMESITEURL + '/Membersnsfriend/index.html" target="_blank"><i class="iconfont">&#xe76d;</i></a></div>' +
                '</div></div>';
        var ajaxurl = HOMESITEURL + '/MemberInstantMessage/get_user_list?n=99&f_id=' + user['u_id'];
        $.ajax({
            type: "GET",
            url: ajaxurl,
            dataType: "json",
            async: true,
            success: function (res) {
                if (res.code != 10000) {
                    return false;
                }
                var u_list = res.result.user_list
                for (var i in u_list) {
                    var user_info = u_list[i];
                    var u_id = user_info['u_id']
                    connect_list[u_id] = 0;
                    connect_n++;
                    set_user_info(u_id, "u_name", user_info['u_name']);
                    set_user_info(u_id, "avatar", user_info['avatar']);
                    if (user_info['friend'] == 1)
                        friend_list[u_id] = user_info;
                    if (user_info['follow'] == 1)
                        follow_list[u_id] = user_info;
                    if (user_info['recent'] == 1)
                        recent_list[u_id] = user_info;
                }
                setTimeout("getconnect()", 1000);
                $("#web_chat_dialog").prepend(chat_user_list);
                $('#chat_user_list').perfectScrollbar();

                $("#chat_show_user").click(function () {
                    chat_show_list();
                });
            }
        });
    } else {
        var n = send_state();
        if (n > 0)
            setTimeout("getconnect()", 2000);
        $("#chat_show_user").click(function () {
            $('div[dstype="a-barLoginBox"]').trigger("click");
        });
    }
});
$("#web_chat_dialog").after(audio_info);

function msg_dialog_close(id) {
    if (dialog_show == 1) {
        $("#" + id).hide("slide", {direction: 'right'}, 300);
    }
    dialog_show = 0;
    close_chat_log(user_show);
    if (connect === 1) {
        $("#web_chat_dialog").show();
    }
}
function msg_dialog_show(id) {
    if (dialog_show == 0) {
        $("#" + id).show("slide", {direction: 'right'}, 600,
                function () {
                    $("#send_message").focus();
                    var obj_msg = obj.find("div[select_user_msg='" + user_show + "']");
                    obj.find("#msg_list").scrollTop(obj_msg.height());
                });
    } else {
        $("#send_message").focus();
    }
    dialog_show = 1;
    if ($("#msg_count").html() == '') {
        $("#send_message").charCount({//输入字数控制
            allowed: 255,
            warning: 10,
            counterContainerID: 'msg_count',
            firstCounterText: '还可以输入',
            endCounterText: '字',
            errorCounterText: '已经超出'
        });
        $("#chat_show_smilies").smilies({smilies_id: "send_message"});
    }
}

function send_state() { //向服务器请求页面中的相关会员的在线状态
    var u_list = connect_list;
    var n = connect_n;
    switch (controller_act) {
        case "brand_list":
        case "search_index":
            $(".list_pic em[member_id]").each(function () {
                n++;
                var u_id = $(this).attr("member_id");
                if (u_id > 0 && u_id != user['u_id'])
                    u_list[u_id] = 0;
            });
            break;
        default:
            $("[member_id]").each(function () {
                n++;
                var u_id = $(this).attr("member_id");
                if (u_id > 0 && u_id != user['u_id'])
                    u_list[u_id] = 0;
            });
            break;
    }
    $('[dstype="mcard"]').each(function () {
        var data_str = $(this).attr('data-param');
        eval('var mcard_obj = ' + data_str);
        var u_id = mcard_obj["id"];
        if (u_id > 0 && u_id != user['u_id']) {
            n++;
            u_list[u_id] = 0;
        }
    });

    if (connect === 1) {
        if (n > 0) {
            var u_ids = Object.keys(u_list)
            ws.send('get_state:' + u_ids.join(','))
        } else {
            if (user['u_id'] == '') {
                ws.close();
            }
        }
    } else {
        return n;
    }
}
function get_state(list) {//返回会员的状态并在页面显示
    var u_list = list['u_state'];
    //店铺页面 唤起对话框
    if (layout == 'store' || controller_act == 'Goods_index') {
        var store_id = 0;
        var store_name = '';
        $("[member_id]").each(function () {
            var u_id = $(this).attr("member_id");
            if (store_id > 0) {
                set_user_info(u_id, "s_id", store_id);
                set_user_info(u_id, "s_name", store_name);
            }
            set_user_info(u_id, "online", u_list[u_id]);
            if ($(this).find(".chat").size() == 0) {
                $(this).prepend(get_chat(u_id, u_list[u_id]));
                if ($(this).attr("c_name")) {//店铺客服
                    var c_name = $(this).attr("c_name");
                    set_user_info(u_id, "c_name", c_name);
                    $(this).find(".chat").attr("href", "JavaScript:store_chat(" + u_id + ",'" + c_name + "');");
                }
            }
        });
    } else {
        switch (controller_act) {
            case "brand_list":
            case "search_index":
                $(".list_pic em[member_id]").each(function () {
                    var u_id = $(this).attr("member_id");
                    if ($(this).find(".chat").size() == 0) {
                        $(this).prepend(get_chat(u_id, u_list[u_id]));
                        $(this).after("<p>在线客服</p>");
                    }
                    set_user_info(u_id, "online", u_list[u_id]);
                });
                break;
            default:
                $("[member_id]").each(function () {
                    var u_id = $(this).attr("member_id");
                    if ($(this).find(".chat").size() == 0) {
                        $(this).prepend(get_chat(u_id, u_list[u_id]));
                    }
                    set_user_info(u_id, "online", u_list[u_id]);
                });
                break;
        }
    }
    if (user['u_id'] != '') {
        update_recent(u_list);
        update_friends(u_list);
        update_follow(u_list);
    } else {
        ws.close();
    }
}
function show_obj() {//弹出框
    if (user_show < 1) {
        chat_show_list();
        return false;
    }
    msg_dialog_show('new_msg_dialog');
}
function send_focus() {
    $("#send_alert").html('');
}
function send_keyup(event) {//回车发消息
    var t_msg = $.trim($("#send_message").val());
    if (event.keyCode == 13 && t_msg.length > 0) {
        send_msg();
    }
}
function send_msg(type=0,goods_id=0) {//发消息
    if (user_show < 1) {
        $("#send_alert").html('未选择聊天会员');
        return false;
    }
    var msg = {};
    msg['to_id'] = user_show;
    if(type==0){
        msg['message'] = $.trim($("#send_message").val());
        msg['message_type'] = 0
        if (msg['message'].length < 1) {
            $("#send_alert").html('发送内容不能为空');
            return false;
        }
        if (msg['message'].length > 255) {
            $("#send_alert").html('一次最多只能发送255字');
            return false;
        }
    }else{
		if(goods_id > 0){
			msg['message'] = goods_id
		}else{
			msg['message'] = chat_goods_id
		}
        msg['message_type'] = 1
    }
    if (connect < 1) {
        $("#send_alert").html('处于离线状态,稍后再试');
        return false;
    }
    $.ajax({
        type: "POST",
        url: HOMESITEURL + '/MemberInstantMessage/add',
        dataType: "json",
        data: msg,
        async: false,
        success: function (res) {
            if (res['code'] != 10000) {
                $("#send_alert").html('' + res['message']);
                return false;
            } else {
                var t_msg = res.result.instant_message_data
                if (connect === 1) {
                    $("#send_message").val('');
                    $("#send_message").focus();
                    $("#send_alert").html('');
                    show_t_msg(t_msg);
                    return true;
                } else {
                    $("#send_alert").html('由于网络原因未发送成功,稍后再试');
                    return false;
                }
            }
        }
    });
}
function get_msg(list) {//接收消息
    var msg = {};
    for (var k in list) {
        msg = list[k];
        var m_id = msg['instant_message_id'];
        var u_id = msg['instant_message_from_id'];
        set_user(u_id, msg['instant_message_from_name']);
        if (typeof msg_list[u_id][m_id] === "object") {//防止重复计数
            continue;
        }
        if (typeof msg['user'] === "object" && typeof msg['user']['avatar'] !== "undefined") {
            var user_info = msg['user'];
            var u_name = user_info['u_name'];
            set_user_info(u_id, "u_name", u_name);
            set_user_info(u_id, "s_id", user_info['s_id']);
            set_user_info(u_id, "s_name", user_info['s_name']);
            set_user_info(u_id, "avatar", user_info['avatar']);
            set_user_info(u_id, "s_avatar", user_info['s_avatar']);
            if (user_info['online'] > 0)
                set_user_info(u_id, "online", 1);
        }
        if (typeof user_list[u_id]['avatar'] === "undefined") {//当没获得会员信息时调用一次
            var ajaxurl = HOMESITEURL + '/MemberInstantMessage/get_info?t=member&u_id=' + u_id;
            $.ajax({
                type: "GET",
                url: ajaxurl,
                dataType: "json",
                async: false,
                success: function (res) {
                    if (res.code != 10000) {
                        return false
                    }
                    var member = res.result.user_info
                    var u_name = member['member_name'];
                    set_user_info(u_id, "s_id", member['store_id']);
                    set_user_info(u_id, "s_name", member['store_name']);
                    set_user_info(u_id, "avatar", member['member_avatar']);
                    set_user_info(u_id, "s_avatar", member['store_avatar']);
                }
            });
        }
        msg_list[u_id][m_id] = msg;
        if (dialog_show == 0 || obj.find("li[select_u_id='" + u_id + "']").size() == 0) {//没有打开对话窗口时计数
            user_list[u_id]['new_msg']++;
            new_msg++;
        } else {
            if (user_show == u_id) {
                show_msg(u_id);//当前对话的会员消息设为已读
                play_audio();
            } else {
                user_list[u_id]['new_msg']++;
                new_msg++;
            }
        }
        alert_user_msg(u_id);
    }
    alert_msg();
}
function get_chat_log(time_from) {
    var obj_chat_log = $("#dialog_chat_log");
    if (obj_chat_log.html() == '') {
        var chat_log_list = '<div class="chat-log-top"><h1><i class="iconfont">&#xe71b;</i>聊天记录</h1><span class="close-chat-log iconfont" onclick="show_chat_log();">&#xe696;</span></div>' +
                '<div id="chat_log_list" class="chat_log_list"><div id="chat_log_msg" class="chat-log-msg"></div></div><div class="chat-log-bottom"><div id="chat_time_from" class="chat_time_from">' +
                '<span time_id="7" onclick="get_chat_log(7);" class="current">7天</span><span time_id="15" onclick="get_chat_log(15);">15天</span><span time_id="30" onclick="get_chat_log(30);">30天</span></div>' +
                '<div class="chat_log_first"><p>已到第一页</p></div><div class="chat_log_last"><p>已到最后一页</p></div>' +
                '<div id="chat_log_page" class="chat_log_page"><span onclick="get_chat_previous();" class="previous iconfont" title="上一页">&#xe619;</span><span onclick="get_chat_next();" class="next iconfont" title="下一页">&#xe618;</span></div></div>';
        obj_chat_log.append(chat_log_list);
    }
    obj_chat_log.show();
    chat_log['u_id'] = user_show;
    chat_log['now_page'] = 0;
    chat_log['total_page'] = 0;
    chat_log['time_from'] = 7;
    chat_log['list'] = new Array();
    var time_id = obj_chat_log.find("span.current").attr("time_id");
    if (time_from != time_id) {
        obj_chat_log.find("span.current").removeClass("current");
        obj_chat_log.find("span[time_id='" + time_from + "']").addClass("current");
        chat_log['time_from'] = time_from;
    }
    get_chat_msg(false);
}
function get_chat_next() {
    var now_page = chat_log['now_page'] - 1;
    if (now_page >= 1) {
        show_chat_msg(now_page);
        chat_log['now_page'] = now_page;
    } else {
        $('.chat_log_last').show();
        setTimeout("$('.chat_log_last').hide()", 2000);
    }
}
function get_chat_previous() {
    var now_page = chat_log['now_page'] + 1;
    if (chat_log['total_page'] >= now_page) {
        if (typeof chat_log['list'][now_page] === "undefined") {
            get_chat_msg(false);
        } else {
            show_chat_msg(now_page);
            chat_log['now_page'] = now_page;
            if (chat_log['total_page'] > now_page && typeof chat_log['list'][now_page + 1] === "undefined")
                get_chat_msg(true);
        }
    } else {
        $('.chat_log_first').show();
        setTimeout("$('.chat_log_first').hide()", 2000);
    }
}
function get_chat_msg(t) {
    var ajaxurl = HOMESITEURL + '/MemberInstantMessage/get_chat_log.html?f_id=' + user['u_id'] + '&t_id=' + chat_log['u_id'] + '&t=' + chat_log['time_from'];
    if (chat_log['now_page'] > 0)
        ajaxurl += '&page=' + (chat_log['now_page'] + 1);
    $.ajax({
        type: "GET",
        url: ajaxurl,
        dataType: "json",
        async: t,
        success: function (res) {
            if (res.code != 10000) {
                return false
            }
            var chat_msg = res.result
            var now_page = chat_log['now_page'] + 1;
            chat_log['list'][now_page] = chat_msg['instant_message_list'];
            if (t == false) {
                chat_log['now_page'] = now_page;
                show_chat_msg(now_page);
            }
            chat_log['total_page'] = chat_msg['total_page'];
            if (chat_log['total_page'] > 1 && chat_log['total_page'] > now_page && t == false) {
                get_chat_msg(true);
            }
        }
    });
}

function get_goods_list(s_id) {
    if (typeof store_goods[s_id] !== "undefined") {
        $("#chat_goods_list").html(store_goods[s_id]);
        $("#chat_goods_list").show();
        return;
    }
    var ajaxurl = HOMESITEURL + '/MemberInstantMessage/get_goods_list?s_id=' + s_id;
    $.ajax({
        type: "GET",
        url: ajaxurl,
        dataType: "json",
        async: true,
        success: function (res) {
            if (res.code != 10000) {
                return false
            }
            var list = res.result.goods_list
            var text_append = '<div class="title">店铺推荐</div><div class="content"><ul>';
            for (var k in list) {
                var goods = list[k];
                var text_goods = '<li>';
                text_goods += '<div class="goods-pic"><a href="' + goods['url'] + '" target="_blank">';
                text_goods += '<img title="' + goods['goods_name'] + '" alt="' + goods['goods_name'] + '" src="' + goods['pic'] + '"/></a></div>';
                text_goods += '<div class="goods-price">&yen;' + goods['goods_promotion_price'] + '</div>';
				text_goods += '<div class="goods-id" onclick="send_msg(1,' + goods['goods_id'] + ');">发给客服</div>';
                text_goods += '</li>';
                text_append += text_goods;
            }
            text_append += '</ul></div>';
            store_goods[s_id] = text_append;
            get_goods_list(s_id);
        }
    });
}
function update_msg(u_id) {//更新已读
    var u_name = user_list[u_id]['u_name'];
    user_list[u_id]['new_msg'] = 0;
    alert_user_msg(u_id);
    new_msg--;
    alert_msg();
}
//店铺客服对话窗口
function store_chat(u_id, c_name) {
    set_user_info(u_id, "c_name", c_name);//设置客服别名
    chat(u_id);
}
//打开对话窗口
function chat(u_id) {
    if (user['u_id'] == '') {//未登录时弹出登录窗口
        login_dialog();
        return;
    }
    if (u_id == user['u_id'])
        return;
    if (1 || typeof user_list[u_id] === "undefined" || typeof user_list[u_id]['avatar'] === "undefined") {
        var ajaxurl = HOMESITEURL + '/MemberInstantMessage/get_info.html?t=member&u_id=' + u_id;
        $.ajax({
            type: "GET",
            url: ajaxurl,
            dataType: "json",
            async: false,
            success: function (res) {
                if (res.code != 10000) {
                    return false
                }
                var member = res.result.user_info
                var u_name = member['member_name'];
                if (typeof u_name === "undefined" || u_name == '')
                    return false;
                set_user_info(u_id, "u_name", u_name);
                set_user_info(u_id, "s_id", member['store_id']);
                set_user_info(u_id, "s_name", member['store_name']);
                set_user_info(u_id, "avatar", member['member_avatar']);
                set_user_info(u_id, "s_avatar", member['store_avatar']);
            }
        });
    }
    update_user(u_id);
    show_msg(u_id);
    show_obj();
}
//显示窗口
function show_dialog() {
    update_dialog();
    show_obj();
}
//显示会员的对话
function update_dialog() {
    if (new_msg < 1)
        return true;
    var select_user = 0;
    for (var u_id in user_list) {
        if (user_list[u_id]['new_msg'] > 0) {
            update_user(u_id);
            obj.find("em[unread_id='" + u_id + "']").addClass("unread");
            obj.find("em[unread_id='" + u_id + "']").html(user_list[u_id]['new_msg']);
        }
    }
    select_user = obj.find(".unread").first().attr("unread_id");
    if (select_user > 0)
        show_msg(select_user);
}
function show_chat_log() {
    if (user_show < 1) {
        $("#send_alert").html('未选择聊天会员');
        return false;
    }
    if (typeof chat_log['u_id'] === "undefined" || chat_log['u_id'] != user_show) {
//	$("#web_chat_dialog").hide();
        obj.find(".chat-log-btn").removeClass("off");
        obj.find(".chat-log-btn").addClass("on");
        get_chat_log(7);
    } else {
        close_chat_log(user_show);
    }
}
//显示会员的消息
function show_msg(u_id) {
    var user_info = user_list[u_id];
    var u_name = user_info['u_name'];
    if (obj.find("div[select_user_msg='" + u_id + "']").size() == 0) {
        obj.find("#user_msg_list").prepend('<div class="msg_list" select_user_msg="' + u_id + '"></div>');
    }
    obj.find(".msg_list").hide();
    obj.find("div[select_user_msg='" + u_id + "']").show();
    obj.find("li[select_u_id]").removeClass("select_user");
    obj.find("li[select_u_id='" + u_id + "']").addClass("select_user");
    if (user_show != u_id) {
        close_chat_log(user_show);
        $("#chat_user_avatar").attr("src", user_info['avatar']);
        $("#chat_goods_list").hide();
        var add_html = '';
        var store_html = '';
        if (typeof user_info['c_name'] !== "undefined")
            add_html = '--' + user_info['c_name'];
        if (typeof user_info['s_name'] !== "undefined" && user_info['s_name'] !== "") {
            get_goods_list(user_info['s_id']);//异步调用店铺推荐的商品
            $("#chat_user_avatar").attr("src", user_info['s_avatar']);
            store_html = '<a target="_blank" href="' + HOMESITEURL + '/Store/index.html?store_id=' + user_info['s_id'] + '">' + user_info['s_name'] + '</a>';
            u_name = '客服：' + u_name;
        }
        var online_html = '<i class="offline" title="离线"></i>';
        if (user_info['online'] > 0) {
            online_html = '<i class="online" title="在线"></i>';
        }
        $("#chat_user_name").html(u_name + add_html + online_html);
        $("#chat_user_store").html(store_html);
        obj.find('#msg_list').perfectScrollbar('destroy');
        obj.find('#msg_list').perfectScrollbar();
    }
    user_show = u_id;
    var max_id = 0;
    for (var m_id in msg_list[u_id]) {
        if (obj.find("div[m_id='" + m_id + "']").size() == 0) {
            var msg = msg_list[u_id][m_id];
            show_f_msg(msg);
            update_msg(u_id);
            delete msg_list[u_id][m_id];//删除消息
            if (m_id > max_id)
                max_id = m_id;
        }
    }
    if(max_id){
        //将消息设置为已读
        $.getJSON(HOMESITEURL + '/MemberInstantMessage/set_message',{max_id:max_id,f_id:u_id})
    }
    var obj_msg = obj.find("div[select_user_msg='" + u_id + "']");
    obj.find("#msg_list").scrollTop(obj_msg.height());
    $("#send_message").focus();
}
//显示收到的消息
function show_f_msg(msg) {
    var u_id = msg['instant_message_from_id'];
    var user_info = user_list[u_id];
    var text_append = '';
    var obj_msg = obj.find("div[select_user_msg='" + u_id + "']");
    text_append += '<div class="from_msg" m_id="' + msg['instant_message_id'] + '">';
    text_append += '<span class="user-avatar"><img src="' + user_info['avatar'] + '"></span>';
    text_append += '<dl><dt class="from-msg-time">';
    text_append += timetrans(msg['instant_message_add_time']) + '</dt>';
    text_append += '<dd class="from-msg-text">';
    text_append += update_chat_msg(msg) + '</dd>';
    text_append += '</dl>';
    text_append += '</div>';
    obj_msg.append(text_append);
    var n = obj_msg.find("div[m_id]").size();
    if (n >= msg_max && n % msg_max == 1) {
        obj_msg.append('<div clear_id="' + msg['instant_message_id'] + '" onclick="clear_msg(' + u_id + ',' + msg['instant_message_id'] +
                ');" class="clear_msg"><a href="Javascript: void(0);">清除已上历史消息</a></div>');
    }
    obj.find("#msg_list").scrollTop(obj_msg.height());
}
function show_t_msg(msg) {//显示发出的消息
    var user_info = user;
    var u_id = msg['instant_message_to_id'];
    var text_append = '';
    var obj_msg = obj.find("div[select_user_msg='" + u_id + "']");
    text_append += '<div class="to_msg" m_id="' + msg['instant_message_id'] + '">';
    text_append += '<span class="user-avatar"><img src="' + user_info['avatar'] + '"></span>';
    text_append += '<dl><dt class="to-msg-time">';
    text_append += timetrans(msg['instant_message_add_time']) + '</dt>';
    text_append += '<dd class="to-msg-text">';
    text_append += update_chat_msg(msg) + '</dd>';
    text_append += '</dl>';
    text_append += '</div>';
    obj_msg.append(text_append);
    var n = obj_msg.find("div[m_id]").size();
    if (n >= msg_max && n % msg_max == 1) {
        obj_msg.append('<div clear_id="' + msg['instant_message_id'] + '" onclick="clear_msg(' + u_id + ',' + msg['instant_message_id'] +
                ');" class="clear_msg"><a href="Javascript: void(0);">清除已上历史消息</a></div>');
    }
    obj.find("#msg_list").scrollTop(obj_msg.height());
}
function show_chat_msg(now_page) {
    var log_list = chat_log['list'][now_page];
    $('#chat_log_msg').html('');
    for (var k in log_list) {
        var class_html = '';
        var text_append = '';
        var msg = log_list[k];
        msg['u_name'] = msg['instant_message_from_name'];
        if (msg['instant_message_from_id'] == user['u_id']) {
            msg['u_name'] = '我';
            class_html = 'chat_user';
        }
        text_append += '<div class="chat_msg ' + class_html + '" m_id="' + msg['instant_message_id'] + '">';
        text_append += '<p class="user-log"><span class="user-name">' + msg['u_name'] + '</span>';
        text_append += '<span class="user-time">' + timetrans(msg['instant_message_add_time']) + '</span></p>';
        text_append += '<p class="user-msg">' + update_chat_msg(msg) + '</p>';
        text_append += '</div>';
        $('#chat_log_msg').prepend(text_append);
    }
    $('#chat_log_list').perfectScrollbar('destroy');
    $('#chat_log_list').perfectScrollbar();
    $('#chat_log_list').scrollTop($('#chat_log_msg').height());
}

function chat_show_user_list(chat_show) {
    var obj_chat = $("#chat_user_" + chat_show);
    if (obj_chat.find("dt span").attr("class") == 'hide') {
        obj_chat.find("dd[u_id]").show();
        obj_chat.find("dt span").attr("class", "show");
    } else {
        obj_chat.find("dd[u_id]").hide();
        obj_chat.find("dt span").attr("class", "hide");
    }
}
function chat_show_list() {
    if (user['u_id'] == '') {
        return;
    }
    var obj_chat = $(".chat-list");
    if (new_msg > 0 || obj_chat.css("display") == 'none') {
        obj_chat.show("slide", {direction: 'right'}, 300);
    } else {
        obj_chat.hide("slide", {direction: 'right'}, 300);
    }
}
function del_msg(msg) {//已读消息处理
    var max_id = msg['max_id'];//最大的消息编号
    var u_id = msg['f_id'];//消息发送人
    for (var m_id in msg_list[u_id]) {
        if (max_id >= m_id) {
            delete msg_list[u_id][m_id];
            if (user_list[u_id]['new_msg'] > 0)
                user_list[u_id]['new_msg']--;
            if (new_msg > 0)
                new_msg--;
            alert_user_msg(u_id);
        }
    }
    alert_msg();
}
function alert_user_msg(u_id) {
    if (user_list[u_id]['new_msg'] > 0) {
        obj.find("em[unread_id='" + u_id + "']").addClass("unread");
        obj.find("em[unread_id='" + u_id + "']").html(user_list[u_id]['new_msg']);
        $("#chat_user_recent dd[u_id='" + u_id + "'] a").addClass("msg");
    } else {
        obj.find("em[unread_id='" + u_id + "']").html("");
        obj.find("em[unread_id='" + u_id + "']").removeClass("unread");
        $("#chat_user_recent dd[u_id='" + u_id + "'] a").removeClass("msg");
    }
}
function alert_msg() {
    var new_n = 0;
    clearInterval(new_interval);
    if (new_msg > 0) {//消息提醒
        new_interval = setInterval(function () {
            new_n++;
            if (connect === 1)
                $(document).attr('title', '新消息(' + new_msg + ')    ' + web_info['html_title']);
            if (new_n % 3 > 1)
                $(document).attr('title', web_info['html_title']);
        }, 500);
        $("#new_msg").show().html(new_msg);
        play_audio();
    } else {
        new_msg = 0;
        $("#new_msg").hide().html('');
    }
    $(document).attr('title', web_info['html_title']);
}
function get_chat(u_id, online) {//显示链接地址
    var add_html = '<a class="chat chat_online" title="在线联系" href="JavaScript:layer.msg(\'不能和自己聊天\')">在线</a>';
    if (u_id != user['u_id'] && u_id > 0) {
        var class_html = 'chat_offline';
        var text_html = '离线';
        if (online > 0) {
            class_html = 'chat_online';
            text_html = '在线';
        }
        add_html = '<a class="chat ' + class_html + '" title="在线联系" href="JavaScript:chat(' + u_id + ');">' + text_html + '</a>';
    }
    return add_html;
}
function clear_msg(u_id, m_id) {//清除消息处理
    var obj_msg = obj.find("div[select_user_msg='" + u_id + "']");
    obj_msg.find("div[clear_id='" + m_id + "']").prevAll().remove();
    obj_msg.find("div[clear_id='" + m_id + "']").remove();
}
function play_audio() {//提示声音
    if (chat_audio === 1) {
        var swf = document["msg_audio"];
        if (typeof swf.pplay === "function") {
            swf.load(CHAT_SITE_URL + '/msg.mp3');
            swf.pplay();
        }
    }
}

function set_user(u_id, u_name) {//初始化会员信息
    var user_info = new Array();
    user_info['u_id'] = u_id;
    user_info['u_name'] = u_name;
    user_info['new_msg'] = 0;
    user_info['online'] = 0;
    if (typeof user_list[u_id] === "undefined") {
        user_list[u_id] = user_info;
    }
    if (typeof msg_list[u_id] === "undefined") {
        msg_list[u_id] = new Array();
    }
}
function set_user_info(u_id, k, v) {//设置会员信息
    if (typeof user_list[u_id] === "undefined") {
        set_user(u_id, '');
    }
    user_list[u_id][k] = v;
}
function close_chat_log(u_id) {
    if (user_show == 0 || chat_log['u_id'] == u_id) {
        chat_log = {};
        $("#dialog_chat_log").hide();
        $('#chat_log_msg').html('');
        obj.find(".chat-log-btn").removeClass("on");
        obj.find(".chat-log-btn").addClass("off");
        if (connect === 1)
            $("#web_chat_dialog").show();
    }
}
function close_dialog(u_id) {
    obj.find("li[select_u_id='" + u_id + "']").remove();
    obj.find("div[select_user_msg='" + u_id + "']").hide();
    if (obj.find("li[select_u_id]").size() == 0) {
        msg_dialog_close('new_msg_dialog');
    } else {
        if (user_show == u_id)
            obj.find("li[select_u_id]").first().trigger("click");
    }
    if (user_show == u_id) {
        user_show = 0;
        close_chat_log(u_id);
    }
    if (obj.find("li[select_u_id]").size() < 2) {
        obj.find(".user-tab-bar").hide();
        $("#new_msg_dialog").css('width', '742px')
    }
}
function update_chat_msg(res) {
    if (res['instant_message_type'] == 1) {
        var chat_goods = res['instant_message']
        var msg = '<div class="dstouch-chat-product"> <a href="' + HOMESITEURL + '/goods/index?goods_id=' + chat_goods.goods_id + '" target="_blank"><div class="goods-pic"><img src="' + chat_goods.goods_image_url + '" alt=""/></div><div class="goods-info"><div class="goods-name">' + chat_goods.goods_name + '</div><div class="goods-price">￥' + chat_goods.goods_price + "</div></div></a> </div>";

    } else {
        var msg=res['instant_message']
        if (typeof smilies_array !== "undefined") {
            msg = '' + msg;
            for (var i in smilies_array[1]) {
                var s = smilies_array[1][i];
                var re = new RegExp("" + s[1], "g");
                var smilieimg = '<img width="28" height="28" title="' + s[6] + '" alt="' + s[6] + '" src="' + BASESITEROOT + '/static/plugins/js/smilies/images/' + s[2] + '">';
                msg = msg.replace(re, smilieimg);
            }
        }
    }

    return msg;
}
function update_friends(u_list) {
    var obj_friend = $("#chat_friends");
    for (var u_id in friend_list) {
        set_user_info(u_id, "online", u_list[u_id]);
        if (obj_friend.parent().find("dd[u_id='" + u_id + "']").size() == 0) {
            if (user_list[u_id]['online'] > 0) {
                obj_friend.before('<dd u_id="' + u_id + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-1"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="online"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            } else {
                obj_friend.after('<dd u_id="' + u_id + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-0"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="offline"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            }
        }
    }
    obj_friend.remove();
    chat_show_user_list('friends');
}
function update_follow(u_list) {
    var obj_follow = $("#chat_follow");
    for (var u_id in follow_list) {
        set_user_info(u_id, "online", u_list[u_id]);
        if (obj_follow.parent().find("dd[u_id='" + u_id + "']").size() == 0) {
            if (user_list[u_id]['online'] > 0) {
                obj_follow.before('<dd u_id="' + u_id + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-1"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="online"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            } else {
                obj_follow.after('<dd u_id="' + u_id + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-0"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="offline"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            }
        }
    }
    obj_follow.remove();
    chat_show_user_list('follow');
}
function update_recent(u_list) {
    var obj_recent = $("#chat_recent");
    for (var u_id in recent_list) {
        set_user_info(u_id, "online", u_list[u_id]);
        if (obj_recent.parent().find("dd[u_id='" + u_id + "']").size() == 0) {
            if (user_list[u_id]['online'] > 0) {
                obj_recent.before('<dd u_id="' + u_id + '" title="最后对话:' + recent_list[u_id]['time'] + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-1"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="online"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            } else {
                obj_recent.after('<dd u_id="' + u_id + '" title="最后对话:' + recent_list[u_id]['time'] + '" onclick="chat(' + u_id + ');"><span class="user-avatar avatar-0"><img alt="' + user_list[u_id]['u_name']
                        + '" src="' + user_list[u_id]['avatar'] + '?' + Math.floor(Math.random() * 100) + '"><i class="offline"></i></span><h5>' + user_list[u_id]['u_name'] + '</h5><a class="iconfont" href="javascript:void(0)">&#xe71b;</a></dd>');
            }
        }
    }
    obj_recent.remove();
}
function update_user(u_id) {
    if ($("#dialog_chat_right").html() == '') {
        var text_append = '<span class="dialog-close iconfont" onclick="msg_dialog_close(\'new_msg_dialog\');">&#xe6e6;&nbsp;</span>';
        text_append += '<div class="user-info">';
        text_append += '<div class="user-avatar">';
        text_append += '<img id="chat_user_avatar" src=""></div>';
        text_append += '<div id="chat_user_store" class="store-name"></div>';
        text_append += '<div id="chat_user_name" class="user-name"></div>';
        text_append += '</div>';
        text_append += '<div id="chat_goods_list" class="goods-list"></div>';
        $("#dialog_chat_right").append(text_append + web_info['chat_goods_html']);
    }
    if (obj.find("li[select_u_id='" + u_id + "']").size() == 0) {
        var user_info = user_list[u_id];
        var u_name = user_info['u_name'];
        var text_append = '';
        var class_html = 'offline';
        if (user_info['online'] > 0)
            class_html = 'online';
        text_append += '<li class="user" select_u_id="' + u_id + '" onclick="show_msg(' + u_id + ');">';
        text_append += '<i class="' + class_html + '"></i>';
        text_append += '<span class="user-avatar avatar-' + user_info['online'] + '" title="' + u_name + '"><img alt="' + u_name + '" src="' + user_info['avatar'] + '"></span>';
        text_append += '<span class="user-name" title="' + u_name + '">';
        text_append += u_name + '<em></em></span>';
        text_append += '<em unread_id="' + u_id + '" class=""></em>';
        text_append += '<a class="ac-ico iconfont">&#xe64b;</a>';
        text_append += '</li>';
        obj.find("#user_list").append(text_append);
        obj.find("#user_list").sortable({items: 'li'});
        obj.find("li[select_u_id='" + u_id + "'] .ac-ico").bind("click", function () {
            close_dialog(u_id);
            return false;
        });
        if (obj.find("li[select_u_id]").size() > 1) {
            obj.find(".user-tab-bar").show();
            $("#new_msg_dialog").css('width', '942px')
        }
    }
    obj.find(".user-tab-bar").perfectScrollbar();
}
var ws
var lockReconnect = false
var timeOut = false
function getconnect() {
    try {
        ws = new WebSocket(connect_url)
        init()
    } catch (e) {
        reconnect()
    }
}
function init() {
    ws.onopen = wsOpen
    ws.onmessage = wsMessage
    ws.onclose = wsClose
    ws.onerror = wsError
}
function reconnect() {
    if (lockReconnect) {
        return
    }
    lockReconnect = true
    // 没连接上会一直重连，设置延迟避免请求过多
    timeOut && clearTimeout(timeOut)
    timeOut = setTimeout(function () {
        getconnect()
        lockReconnect = false
    }, 4000)
}
function wsOpen() {
    connect = 1
    send_state();
    if (user['u_id'] == '')
        return false;//未登录时不取消息

    $("#web_chat_dialog").show();
    if ($("#new_msg_dialog").size() == 0)
    {
        $("#web_chat_dialog").after(web_info['msg_dialog']);
        $("#new_msg_dialog").draggable({containment: "body"});
    }
    obj = $("#new_msg_dialog");
    // 心跳检测重置
    heartCheck.start()
}
function wsMessage(res) {
    var message = JSON.parse(res.data)
    if (!message) {
        console.log(res)
        return
    }
    var type = message.type || ''
    switch (type) {
        // Events.php中返回的init类型的消息，将client_id发给后台进行uid绑定
        case 'init':
            var clientId = message.client_id
            //获取未读消息
            $.getJSON(HOMESITEURL + '/MemberInstantMessage/join',{client_id:clientId})
            break
        case 'leave':
            break
        case 'get_state'://用户状态
            get_state(message)
            break
        case 'get_msg'://未读消息
            get_msg(message.msg_list)
            break
        default:
            get_msg([message])

    }
    heartCheck.start()
}
function wsClose(res) {
    connect = 0
    console.log(res)
    $("#web_chat_dialog").hide();
    reconnect()
}
function wsError(res) {
    console.log(res)
    reconnect()
}
// 心跳检测
var heartCheck = {
    timeout: 3000,
    timeoutObj: null,
    start: function () {
        var self = this
        this.timeoutObj && clearTimeout(this.timeoutObj)
        this.timeoutObj = setInterval(function () {
            // 这里发送一个心跳，后端收到后，返回一个心跳消息，
            ws.send('123456789')
        }, this.timeout)
    }
}


function timetrans(date) {
    var date = new Date(date * 1000);//如果date为13位不需要乘1000
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
    var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
    var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
    var s = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
    return Y + M + D + h + m + s;
}