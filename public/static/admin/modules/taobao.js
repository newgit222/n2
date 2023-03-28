
layui.define(["table", "form"],
    function(e) {
        var t = layui.$,
            i = layui.table,
            u = layui.util,
            n = layui.form;
        i.render({
            elem: "#app-user-taobao",
            url: "getList",
            //自定义响应字段
            response: {
                statusName: 'code' //数据状态的字段名称
                ,statusCode: 1 //数据状态一切正常的状态码
                ,msgName: 'msg' //状态信息的字段名称
                ,dataName: 'data' //数据详情的字段名称
            },
            cols: [[
                {
                    field: "id",
                    width: 100,
                    title: "ID",
                    sort: !0
                },
                {
                    field: "uid",
                    width: 100,
                    title: "商户UID",
                    sort: !0
                },
                {
                    field: "name",
                    width: 200,
                    title: "店铺名称"
                },
                {
                    field: "type",
                    title: "类型",
					templet: "#buttonTpl",
                    minWidth: 80,
                    align: "center"
                },
                {
                    field: "onlinedate",
                    width: 180,
                    title: "最后一次在线时间"
                },
                {
                    field: "status",
                    title: "状态",
					templet: "#buttonTpl1",
                    minWidth: 80,
                    align: "center"
                },
                {
                    title: "操作",
                    minWidth: 150,
                    align: "center",
                    fixed: "right",
                    toolbar: "#table-user-taobao"
                }]],
            page: !0,
            limit: 10,
            limits: [10, 15, 20, 25, 30],
            text: "对不起，加载出现异常！"
        }),
            i.on("tool(app-user-taobao)",
                function(e) {
                    if ("del" === e.event) layer.prompt({
                            formType: 1,
                            title: "敏感操作，请验证口令"
                        },
                        function(d, i) {
                            layer.close(i),
                                layer.confirm("真的删除此账户？", function(d) {
                                        t.ajax({
                                            url: 'del?id='+ e.data.id,
                                            method:'POST',
                                            success:function (res) {
                                                if (res.code == 1){
                                                    e.del()
                                                }
                                                layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                                layer.close(d); //关闭弹层
                                            }
                                        });
                                    })
                        });
                    else if ("edit" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "编辑店铺",
                            content: "edit.html?id=" + e.data.id,
                            maxmin: !0,
                            area:  ['80%', '60%'],
                            btn: ["确定", "取消"],
                            yes: function(f, t) {
                                var l = window["layui-layer-iframe" + f],
                                    r = "app-user-taobao-submit",
                                    n = t.find("iframe").contents().find("#" + r);
                                l.layui.form.on("submit(" + r + ")",
                                    function(t) {
                                        var l = t.field;
                                        layui.$.post("edit",l,function (res) {
                                            if (res.code == 1){
                                                //更新数据表
                                                e.update({
                                                    name: l.name,
                                                    type: l.type,
                                                    uid : l.uid ,
                                                    id: l.id
                                                }),
                                                i.render(),
                                                    layer.close(f)
                                            }
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                        });
                                    }),
                                    n.trigger("click")
                            },
                            success: function(e, t) {}
                        })
                    }
                }),
            e("taobao", {})
    });