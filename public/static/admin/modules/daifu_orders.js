/*
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

layui.define(["table", "form", "element"],
    function(e) {
        var t = layui.$,
            i = layui.table,
            u = layui.util;
        i.render({
            elem: "#app-order-list",
            url: 'getOrdersList',
            //自定义响应字段
            response: {
                statusName: 'code' //数据状态的字段名称
                ,statusCode: 1 //数据状态一切正常的状态码
                ,msgName: 'msg' //状态信息的字段名称
                ,dataName: 'data' //数据详情的字段名称
            },
            cols: [[{
                type: "checkbox",
                fixed: "left"
            },
                {
                    field: "id",
                    width: 80,
                    title: "ID",
                    sort: !0
                },
                {
                    field: "uid",
                    width: 80,
                    title: "商户编号"
                },
		    {
                    field: "amount",
                    width: 80,
                    title: "交易金额",
                    templet: function (item){
                       return parseInt(item.amount)
                    },
                    style:"color:red"
                },

                {
                    field: "bank_name",
                    width: 100,
                    title: "银行名称"
                },
                {
                    field: "bank_number",
                    width: 100,
                    title: "银行卡号"
                },
                {
                    field: "bank_owner",
                    width: 100,
                    title: "真实姓名"
                },
		      {
                    field: "service_rate",
                    width: 80,
                    title: "手续费",
                    templet: function (item){
                        return Number(item.service_charge) + Number(item.single_service_charge);
                    },
                    style:"color:red"
                },

                {
                    field: "username",
                    width: 100,
                    title: "码商"
                },
                {
                    field: "out_trade_no",
                    width: 50,
                    title: "平台订单号"
                },
                {
                    field: "trade_no",
                    width: 50,
                    title: "跑分平台订单号"

		}
                ,
                {
                    field: "create_time",
                    width: 50,
                    title: "创建时间",
                },
                {
                    field: "update_time",
                    width: 50,
                    title: "更新时间",
                },

                {
                    field: "status",
                    title: "订单状态",
                    templet: "#buttonTpl",
                    minWidth: 50,
                    align: "center"
                },
                {
                    field: "notify_result",
                    title: "回调状态",
                    templet: "#notifyButtonTpl",
                    minWidth: 50,
                    align: "center"
                },
                {
                    title: "操作",
                    align: "center",
                    minWidth: 320,
                    // fixed: "right",
                    toolbar: "#table-system-order"
                }
                ]],
            page: !0,
            limit: 10,
            limits: [10, 15, 20, 25, 30],
            text: "对不起，加载出现异常！"
        }),
            i.on("tool(app-order-list)",
                function(e) {
                    e.data;
                    if ("details" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "交易详情",
                            content: "details.html?id=" + e.data.id,
                            maxmin: !0,                             area: ['80%','60%'],
                            btn: ["确定", "取消"],
                            yes: function(e, t) {},
                            success: function(e, t) {}
                        })
                    }
                    else if ("add_notify" === e.event) {
                        //补发通知
                        t(e.tr);
                        t.ajax({
                            url: 'add_notify',
                            method:'POST',
                            data:{id:e.data.id},
                            success:function (res) {
                                if (res.code == 1){
                                    layer.msg(res.msg, {icon: 1,time: 1500},function () {
                                        layer.closeAll();
                                        i.reload('app-order-list');
                                    });

                                }else{
                                    layer.msg(res.msg, {icon: 2,time: 1500});
                                }
                            }
                        });
                    }
                    else if("auditSuccess" === e.event){
                        layer.prompt({
                            formType: 1,
                            title: "敏感操作，请验证口令"
                        }, function(d, f) {
                            // console.log(i);return false;
                            //检测口令
                            t.ajax({
                                url: '/admin/api/checkOpCommand?command='+ d,
                                method:'POST',
                                success:function (res) {
                                    if (res.code == 1){
                                        console.log(123)
                                        //口令正确
                                        // layer.close(f); //关闭弹层
                                        // t(e.tr);
                                        //正式补单操作
                                        t.ajax({
                                            url: 'auditSuccess',
                                            method:'POST',
                                            data:{id:e.data.id},
                                            success:function (res) {
                                                if (res.code == 1){
                                                    layer.closeAll();
                                                    i.reload('app-order-list');
                                                }else{
                                                    layer.msg(res.msg, {icon: 2,time: 1500});
                                                }
                                            }
                                        });
                                    }else{
                                        layer.msg(res.msg,{icon:2,time:1500});
                                        layer.close(d); //关闭弹层
                                    }
                                }
                            });
                        });

                    }else if("auditError" === e.event){
                        layer.prompt({
                            formType: 1,
                            title: "敏感操作，请验证口令"
                        }, function(d, f) {
                            // console.log(i);return false;
                            //检测口令
                            t.ajax({
                                url: '/admin/api/checkOpCommand?command='+ d,
                                method:'POST',
                                success:function (res) {
                                    if (res.code == 1){
                                        console.log(123)
                                        //口令正确
                                        // layer.close(f); //关闭弹层
                                        // t(e.tr);
                                        //正式补单操作
                                        t.ajax({
                                            url: 'auditError',
                                            method:'POST',
                                            data:{id:e.data.id},
                                            success:function (res) {
                                                if (res.code == 1){
                                                    layer.closeAll();
                                                    i.reload('app-order-list');
                                                }else{
                                                    layer.msg(res.msg, {icon: 2,time: 1500});
                                                }
                                            }
                                        });
                                    }else{
                                        layer.msg(res.msg,{icon:2,time:1500});
                                        layer.close(d); //关闭弹层
                                    }
                                }
                            });
                        });


                    }else if("appoint_ms" === e.event){
                        layer.open({
                            type: 2,
                            title: "指定码商",
                            content: "appoint_ms.html?id=" + e.data.id,
                            maxmin: !0,
                            area: ["600px", "400px"],
                            btn: ["确定", "取消"],
                            yes: function (d, f) {
                                var l = window["layui-layer-iframe" + d],
                                    o = "app-appoint-ms-submit",
                                    r = f.find("iframe").contents().find("#" + o);
                                l.layui.form.on("submit(" + o + ")",
                                    function (r) {
                                        var l = r.field;
                                        //提交修改
                                        t.post("appoint_ms", l, function (res) {
                                            if (res.code == 1) {
                                                layer.closeAll();
                                                i.reload('app-order-list');
                                            }
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1 : 2, time: 1500});
                                        });
                                    }),
                                    r.trigger("click")
                            },
                            success: function (e, t) {
                            }
                        })
                    }
                }),
            e("daifu_orders", {})
    });
