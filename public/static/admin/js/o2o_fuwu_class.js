$(document).ready(function () {
    //列表下拉
    $('img[ds_type="flex"]').click(function () {
        var status = $(this).attr('status');
        if (status == 'open') {
            var pr = $(this).parent('td').parent('tr');
            var id = $(this).attr('fieldid');
            var obj = $(this);
            $(this).attr('status', 'none');
            //ajax
            $.ajax({
                url: ADMINSITEURL + '/o2o_fuwu_class/index?ajax=1&o2o_fuwu_class_parent_id=' + id,
                dataType: 'json',
                success: function (data) {
                    var src = '';
                    for (var i = 0; i < data.length; i++) {
                        var tmp_vertline = "<img class='preimg' src='templates/images/vertline.gif'/>";
                        src += "<tr id='ds_row_"+ data[i].o2o_fuwu_class_id +"' class='" + pr.attr('class') + " row" + id + "'>";
                        src += "<td class='w36'><input type='checkbox' name='check_o2o_fuwu_class_id[]' value='" + data[i].o2o_fuwu_class_id + "' class='checkitem'>";
                        if (data[i].have_child == 1) {
                            src += "<img fieldid='" + data[i].o2o_fuwu_class_id + "' status='open' ds_type='flex' src='" + ADMINSITEROOT + "/images/treetable/tv-expandable.gif' />";
                        } else {
                            src += "<img fieldid='" + data[i].o2o_fuwu_class_id + "' status='none' ds_type='flex' src='" + ADMINSITEROOT + "/images/treetable/tv-item.gif' />";
                        }
                        //图片
                        src += "</td><td class='w48 sort'>";
                        //排序
                        src += "<span title='可编辑' ajax_branch='o2o_fuwu_class_sort_order' datatype='number' fieldid='" + data[i].o2o_fuwu_class_id + "' fieldname='o2o_fuwu_class_sort_order' ds_type='inline_edit' class='editable'>" + data[i].o2o_fuwu_class_sort_order + "</span></td>";
                        //名称
                        src += "<td class='name'>";
                        for (var tmp_i = 1; tmp_i < (data[i].deep - 1); tmp_i++) {
                            src += tmp_vertline;
                        }
                        if (data[i].have_child == 1) {
                            src += " <img fieldid='" + data[i].o2o_fuwu_class_id + "' status='open' ds_type='flex' src='" + ADMINSITEROOT + "/images/treetable/tv-item1.gif' />";
                        } else {
                            src += " <img fieldid='" + data[i].o2o_fuwu_class_id + "' status='none' ds_type='flex' src='" + ADMINSITEROOT + "/images/treetable/tv-expandable1.gif' />";
                        }
                        src += " <span title='可编辑' required='1' fieldid='" + data[i].o2o_fuwu_class_id + "' ajax_branch='o2o_fuwu_class_name' fieldname='o2o_fuwu_class_name' ds_type='inline_edit' class='editable'>" + data[i].o2o_fuwu_class_name + "</span>";
                        //新增下级
                        if (data[i].deep < 2) {
                            src += "<a class='btn-add-nofloat marginleft' href=\"javascript:dsLayerOpen('" + ADMINSITEURL + "/o2o_fuwu_class/add/o2o_fuwu_class_parent_id/" + data[i].o2o_fuwu_class_id + "','新增下级')\"><span>新增下级</span></a></span>";
                        }
                        src += "</td>";

                        //操作
                        src += "<td class='w84'>";
                        src += "<span><a href=\"javascript:dsLayerOpen('" + ADMINSITEURL + "/o2o_fuwu_class/edit/o2o_fuwu_class_id/" + data[i].o2o_fuwu_class_id + "','编辑-"+data[i].o2o_fuwu_class_name+"')\" class='dsui-btn-edit'><i class='iconfont'></i>编辑</a>";
                        src += "<a href=\"javascript:dsLayerConfirm('" + ADMINSITEURL + "/o2o_fuwu_class/del/o2o_fuwu_class_id/" + data[i].o2o_fuwu_class_id + "','删除该分类将会同时删除该分类的所有下级分类，您确定要删除吗'," + data[i].o2o_fuwu_class_id + ");\" class='dsui-btn-del'><i class='iconfont'></i>删除</a>";
                        src += "</td>";
                        src += "</tr>";
                    }
                    //插入
                    pr.after(src);
                    obj.attr('status', 'close');
                    obj.attr('src', obj.attr('src').replace("tv-expandable", "tv-collapsable"));
                    $('img[ds_type="flex"]').unbind('click');
                    $('span[ds_type="inline_edit"]').unbind('click');
                    //重现初始化页面
                    $.getScript(ADMINSITEROOT + "/js/jquery.edit.js");
                    $.getScript(ADMINSITEROOT + "/js/o2o_fuwu_class.js");

                },
                error: function () {
                    alert('获取信息失败');
                }
            });
        }
        if (status == 'close') {
            $(".row" + $(this).attr('fieldid')).remove();
            $(this).attr('src', $(this).attr('src').replace("tv-collapsable", "tv-expandable"));
            $(this).attr('status', 'open');
        }
    })
});