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
            elem: "#app-card-list",
            url: 'getDepositeCardList',
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
                    width: 120,
                    title: "ID",
                    sort: !0
                },
                {
                    field: "name",
                    width: 100,
                    title: "银行卡"
                },


                {
                    field: "bank_account_username",
                    width: 200,
                    title: "银行账户名"
                },
                {
                    field: "bank_account_number",
                    width: 200,
                    title: "银行卡号"
                },
                {
                    field: "bank_account_address",
                    width: 150,
                    title: "支行名称"
                },
                {
                    field: "update_time",
                    width: 150,
                    title: "最后更新时间"
                },
                {
                    field: "status",
                    title: "状态",
                    templet: "#buttonTpl",
                    minWidth: 80,
                    align: "center"
                },
                {
                    title: "操作",
                    align: "center",
                    minWidth: 220,
                    // fixed: "right",
                    toolbar: "#table-system-card"
                }
                ]],
            page: !0,
            limit: 10,
            limits: [10, 15, 20, 25, 30],
            text: "对不起，加载出现异常！"
        }),
            i.on("tool(app-card-list)",
                function(e) {
                    var d = e.data;
                     if ("editCard" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "编辑充值银行卡",
                            content: "editDepositeCard.html?id="+ d.id,
                            maxmin: !0,
                            area: ['80%','60%'],
                            btn: ["确定", "取消"],
                            yes: function(f, t) {
                                var l = window["layui-layer-iframe" + f],
                                    r = "app-admin-card-submit",
                                    n = t.find("iframe").contents().find("#" + r);

                                l.layui.form.on("submit("+ r +")",
                                    function(t) {
                                        var l = t.field;
                                        layui.$.post("editDepositeCard",l,function (res) {
                                            if (res.code == 1){
                                                i.reload('app-card-list');
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
                    else if ("delCard" === e.event) {
                         t(e.tr);
                         layui.$.post("delDepositeCard", {id:e.data.id}, function (res) {
                             if (res.code == 1) {
                                 i.reload('app-card-list');
                                 layer.closeAll()
                             }
                             layer.msg(res.msg, {icon: res.code == 1 ? 1 : 2, time: 1500});
                         });
                     }
                }),
            i.render({
                elem: "#app-order-list",
                url: 'getOrderList',
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
                        width: 120,
                        title: "ID",
                        sort: !0
                    },
                    {
                        field: "uid",
                        width: 120,
                        title: "商户ID",
                        sort: !0
                    },
                    {
                        field: "username",
                        width: 120,
                        title: "充值商户",
                        sort: !0
                    },
                    {
                        field: "name",
                        width: 100,
                        title: "银行卡"
                    },
                    {
                        field: "bank_account_username",
                        width: 200,
                        title: "银行账户名"
                    },
                    {
                        field: "bank_account_number",
                        width: 200,
                        title: "银行卡号"
                    },
                    {
                        field: "bank_account_address",
                        width: 150,
                        title: "支行名称"
                    },
                    {
                        field: "trade_no",
                        width: 150,
                        title: "充值订单号"
                    },
                    {
                        field: "recharge_account",
                        width: 150,
                        title: "充值账号"
                    },
                    {
                        field: "amount",
                        width: 150,
                        title: "金额"
                    },

                    {
                        field: "update_time",
                        width: 150,
                        title: "最后更新时间"
                    },
                    {
                        field: "status",
                        title: "状态",
                        templet: "#buttonTpl",
                        minWidth: 80,
                        align: "center"
                    },
                    {
                        title: "操作",
                        align: "center",
                        minWidth: 220,
                        // fixed: "right",
                        toolbar: "#table-system-card"
                    }
                ]],
                page: !0,
                limit: 10,
                limits: [10, 15, 20, 25, 30],
                text: "对不起，加载出现异常！"
            }),
            i.on("tool(app-order-list)",
                function(e) {
                    var d = e.data;
                if("auditSuccess" === e.event){
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


                    }
                }),
            e("deposite", {})
    });
