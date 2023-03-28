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

layui.define(["table", "form"],
    function(e) {
        var t = layui.$,
            i = layui.table,
            u = layui.util,
            n = layui.form;
        i.render({
            elem: "#app-balance-list",
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
                    field: "uid",
                    width: 100,
                    title: "商户UID",
                    sort: !0
                },
                {
                    field: "username",
                    width: 100,
                    title: "商户名称",
                    sort: !0
                },
                {
                    field: "enable",
                    width: 200,
                    title: "可用余额",
                    sort:true
                },
                {
                    field: "disable",
                    width: 180,
                    title: "冻结余额"
                },
                {
                    field: "status",
                    title: "状态",
                    templet: "#buttonTpl",
                    minWidth: 80,
                    align: "center"
                },
                {
                    field: "create_time",
                    title: "创建时间",
                    width: 180,
                    sort: !0,
                   // templet: function(d) {return u.toDateString(d.create_time*1000); }
                },
                // {
                //     field: "update_time",
                //     title: "更新时间",
                //     width: 180,
                //     sort: !0,
                //     //templet: function(d) {return u.toDateString(d.update_time*1000); }
                // },
                {
                    field: "last_online_time",
                    title: "最后在线时间",
                    width: 180,
                    sort: !0,
                    templet: function(d) {
                        if(d.last_online_time ) {
                            return u.toDateString(d.last_online_time * 1000);
                        }else{
                            return '无';
                        }
                    }
                },
                {
                    title: "操作",
                    minWidth: 220,
                    align: "center",
                    // fixed: "right",
                    toolbar: "#table-balance-list"
                }]],
            page: !0,
            limit: 10,
            limits: [10, 15, 20, 25, 30],
            text: "对不起，加载出现异常！"
        }),
            i.on("tool(app-balance-list)",
                function(e) {
                    if ("details" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "账户明细",
                            content: "details.html?id=" + e.data.uid,
                            maxmin: !0,
                            area:  ['80%', '60%'],
                            yes: function(f, t) {},
                            success: function(e, t) {}
                        })
                    }else if("op_balance" === e.event){  //增加用户资金余额
                        layer.prompt({
                                formType: 1,
                                title: "敏感操作，请验证口令",
                            },
                            function(d, f) {
                                // console.log(i);return false;
                                //检测口令
                                t.ajax({
                                    url: '/admin/api/checkOpCommand?command='+ d,
                                    method:'POST',
                                    success:function (res) {
                                        if (res.code == 1){
                                            //口令正确
                                            layer.close(d); //关闭弹层
                                            t(e.tr);
                                            layer.open({
                                                type: 2
                                                ,title: '增减余额'
                                                ,content: 'changeBalance.html?uid=' + e.data.uid
                                                ,maxmin: true
                                                ,area: ['80%','60%']
                                                ,btn: ['确定', '取消']
                                                ,yes: function(index, layero){
                                                    var iframeWindow = window['layui-layer-iframe'+ index]
                                                        ,submitID = 'app-user-manage-submit'
                                                        ,submit = layero.find('iframe').contents().find('#'+ submitID);

                                                    //监听提交
                                                    iframeWindow.layui.form.on('submit('+ submitID +')', function(obj){
                                                        var field = obj.field; //获取提交的字段

                                                        //提交 Ajax 成功后，静态更新表格中的数据
                                                        t.ajax({
                                                            url:'changeBalance.html?uid=' + e.data.uid,
                                                            method:'POST',
                                                            data:field,
                                                            success:function (res) {
                                                                if (res.code == 1){
                                                                    layer.closeAll();
                                                                    i.reload('app-balance-list');
                                                                }else{
                                                                    layer.msg(res.msg, {icon: 2,time: 1500});
                                                                }
                                                            }
                                                        });
                                                    });
                                                    submit.trigger('click');
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

            i.render({
                elem: "#app-balance-details-list",
                url: 'getDetails',
                //添加请求字段
                where: {
                    uid:  t("input[ name='uid' ] ").val()
                },
                //自定义响应字段
                response: {
                    statusName: 'code' //数据状态的字段名称
                    ,statusCode: 1 //数据状态一切正常的状态码
                    ,msgName: 'msg' //状态信息的字段名称
                    ,dataName: 'data' //数据详情的字段名称
                },
                cols: [[
                    {
                    field: "type",
                    templet: "#buttonType",
                    width: 100,
                    title: "资金类型"
                    },
                    {
                        field: "preinc",
                        width: 100,
                        title: "变动前金额",
                        style: "color:red"
                    },
                    {
                        field: "increase",
                        width: 100,
                        title: "增加金额",
                        style: "color:red"
                    },
                    {
                        field: "reduce",
                        width: 100,
                        title: "减少金额",
                        style: "color:red"
                    },
                    {
                        field: "suffixred",
                        width: 100,
                        title: "变动后金额",
                        style: "color:red"
                    },
                    {
                        field: "remarks",
                        title: "变动备注"
                    },
                    {
                        field: "update_time",
                        width: 200,
                        title: "更新时间",
                        //templet: function(d) {return u.toDateString(d.update_time*1000); }
                    }]],
                page: {
                    limit: 10,
                    limits: [10, 15, 20, 25, 30]
                },
                text: "对不起，加载出现异常！"
            }),
            i.render({
                elem: "#app-order-paid-list",
                url: 'paidList',
                width : 'auto', //宽度自动，100%父级宽度，超出宽度出现滚动
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
                        width: 50,
                        title: "ID",
                        sort: !0,
                         templet: function (d) {
                             if (d.mark_abnormal == 1 && d.status == 1) {
                                 return "<span style='color: #A897DB'>"+d.id+"</span>";
                             }
                            if(d.status !=3 ){
                                return d.id;
                            }
                            return "<span style='color: red'>"+d.id+"</span>";
                        }

                    },
                    {
                        field: "uid",
                        width: 80,
                        title: "交易商户",
                         templet: function (d) {
                             if (d.mark_abnormal == 1 && d.status == 1) {
                                 return "<span style='color: #A897DB'>"+d.uid+"</span>";
                             }
                            if (d.status != 3) {
                                return d.uid;
                            }
                            return "<span style='color: red'>"+d.uid+"</span>";
                        }

                    },
                    {
                        field: "cash_no",
                        width: 100,
                        title: "打款单号",
                        templet: function (d) {
                            if (d.mark_abnormal == 1 && d.status == 1) {
                                return "<span style='color: #A897DB'>"+d.cash_no+"</span>";
                            }

                            if (d.status != 3) {
                                return d.cash_no;
                            }
                            return "<span style='color: red'>"+d.cash_no+"</span>";
                        }

                    },
                    {
                        field: "amount",
                        width: 100,
                        title: "交易金额",
                        style: "color:red",
                        templet:function (d) {
                            if (d.mark_abnormal == 1 && d.status == 1) {
                                return "<span style='color: #A897DB'>"+Math.floor(d.amount)+"</span>";
                            }
                            return Math.floor(d.amount)
                        }
                    },
                    {
                        field: "commission",
                        width: 100,
                        title: "交易手续费",
                        style: "color:red",
                        templet:function (d) {
                            if (d.mark_abnormal == 1 && d.status == 1) {
                                return "<span style='color: #A897DB'>"+ d.commission+"</span>";
                            }
                            return d.commission;
                        }
                    },
                    {
                        field: "type",
                        width: 70,
                        title: "提款方式",
                        templet:function (d) {
                            if (d.mark_abnormal == 1 && d.status == 1) {
                                if(d.type)
                                {
                                    return "<span style='color: #A897DB'>USDT</span>";
                                }
                                else
                                {
                                    return "<span style='color: #A897DB'>银行卡</span>";
                                }
                            }

                             if(d.type)
                                {
                                     return "<span style='color: red'>USDT</span>";
                                }
                                else
                                 {
                                return "<span style='color: red'>银行卡</span>";
                                }

                      //      return d.type?'USDT':'银行卡';
                        }
                    },

                    {
                        field: "account",
                        width: 350,
                        title: "提款详情",
                        templet:function (d) {
                            if (d.mark_abnormal == 1 && d.status == 1) {
                                if(d.type) {
                                    return "<span style='color: #A897DB'>"+d.withdraw_usdt_address+" "+d.account_name+" "+d.account+"</span>";
                                } else {
                                    return "<span style='color: #A897DB'>"+d.method+" "+d.account_name+" "+d.account+"</span>";
                                }
                            }

                            if(d.status !=3) {
                                return d.type?d.withdraw_usdt_address:d.method+" "+d.account_name+" "+d.account;
                            }

                            if(d.type) {
                                return "<span style='color: red'>"+d.withdraw_usdt_address+" "+d.account_name+" "+d.account+"</span>";
                            } else {
                                return "<span style='color: red'>"+d.method+" "+d.account_name+" "+d.account+"</span>";
                            }
                        }

                    },
                    //
                    // {
                    //     field: "method",
                    //     width: 120,
                    //     title: "交易方式"
                    // },
                    // {
                    //     field: "account_name",
                    //     width: 200,
                    //     title: "收款姓名"
                    // },
                    // {
                    //     field: "account",
                    //     width: 200,
                    //     title: "收款账号"
                    // },
                    {
                        field: "status",
                        title: "订单状态",
                        templet: "#buttonTpl",
                        minWidth: 100,
                        align: "center"
                    },
                    {
                        title: "操作",
                        minWidth: 300,
                        align: "center",
                        // fixed: "right",
                        toolbar: "#table-balance-tool"
                    },
                    {
                        field: "create_time",
                        width: 200,
                        title: "创建时间",
                        color: "red",
                    },
                    {
                        field: "update_time",
                        width: 200,
                        title: "更新时间",
                    },

                    {
                        field: "channel_name",
                        title: "渠道",
                        minWidth: 100,
                        align: "center"
                    },
                    {
                        field: "voucher",
                        title: "转账凭证",
                        templet: function(d){
                            if(!d.voucher){
                                return '无';
                            }
                            return '<div lay-event="showImg" ><img src="'+d.voucher+'" alt="" width="50px" height="50px"></a></div>';
                        },
                        minWidth: 100,
                        align: "center"
                    },
                    {
                        field: "voucher_time",
                        title: "凭证上传时间",
                        minWidth: 200,
                        templet:function(d){
                            if(!d.voucher_time){
                                return '无';
                            }
                          return layui.util.toDateString(d.voucher_time*1000, 'yyyy-MM-dd HH:mm:ss');
                        },
                        align: "center"
                    },

                    ]],
                page: !0,
                limit: 10,
                limits: [10, 15, 20, 25, 30],
                text: "对不起，加载出现异常！",
                done: function() {
                    //加载完成
                }
            }),
            i.render({
                elem: "#app-order-change-list",
                url: 'changeList',
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
                        // width: 100,
                        title: "ID",
                        sort: !0
                    },
                    {
                        field: "uid",
                        // width: 100,
                        title: "交易商户"
                    },
                    {
                        field: "type",
                        // width: 200,
                        title: "资金类型",
                        templet: "#typebuttonTpl",
                    },
                    {
                        field: "preinc",
                        // width: 100,
                        title: "变动前金额",
                        style: "color:red"
                    },
                    {
                        field: "increase",
                        // width: 100,
                        title: "增加金额",
                        style: "color:green"
                    },
                    {
                        field: "reduce",
                        // width: 100,
                        title: "减少金额",
                        style: "color:red"
                    },
                    {
                        field: "suffixred",
                        // width: 100,
                        title: "变动后金额",
                        style: "color:red"
                    },
                    {
                        field: "remarks",
                        // width: 120,
                        title: "资金变动说明"
                    },
                    {
                        field: "create_time",
                        // width: 200,
                        title: "创建时间"
                    }]],
                page: !0,
                limit: 10,
                limits: [10, 15, 20, 25, 30],
                text: "对不起，加载出现异常！",
                done: function() {
                    //加载完成
                }
            }),
            i.on("tool(app-order-paid-list)",
                function(e) {
                    console.log(e);
                    if ("deal" === e.event) {
                        layer.prompt({
                                formType: 1,
                                title: "敏感操作，请验证口令"
                            },
                            function (d, f) {
                                layer.close(f),
                                    layer.confirm("确定审核吗？", function (d) {
                                        //读取配置
                                        t.post("getAuditSwitch", function (res) {

                                            if(e.data.is_paid_select_channel) {
                                                layer.close(d)
                                                layer.open({
                                                    type: 2
                                                    , area: ['500px', '300px']
                                                    , title: '请选择渠道'
                                                    , content: 'select_channel.html'
                                                    , maxmin: true
                                                    , btn: ['确认选择', '关闭']
                                                    , yes: function (index, layero) {
                                                        var row = window["layui-layer-iframe" + index].callbackdata();
                                                        // layer.alert("get:" + row);
                                                        var channel_id = row.channel_id;

                                                        if (res.data == '1') {
                                                            layer.prompt({
                                                                    formType: 2,
                                                                    title: "请输入审核备注"
                                                                },
                                                                function (audit_remarks, h) {
                                                                    layer.close(h)
                                                                    t.post("deal", {
                                                                        cash_id: e.data.id,
                                                                        audit_remarks: audit_remarks,
                                                                        channel_id: channel_id
                                                                    }, function (res) {
                                                                        layer.msg(res.msg, {
                                                                            icon: res.code == 1 ? 1 : 2,
                                                                            time: 1500
                                                                        });
                                                                        layer.close(d); //关闭弹层
                                                                        i.reload('app-order-paid-list')
                                                                    });
                                                                });
                                                        } else {
                                                            t.post("deal", {
                                                                cash_id: e.data.id,
                                                                channel_id: channel_id
                                                            }, function (res) {
                                                                layer.msg(res.msg, {
                                                                    icon: res.code == 1 ? 1 : 2,
                                                                    time: 1500
                                                                });
                                                                layer.close(d); //关闭弹层
                                                                i.reload('app-order-paid-list')
                                                            });
                                                        }


                                                        //可以使用ajax请求对数据进行进一步处理
                                                        layer.close(index);
                                                    }, btn2: function (index, layero) {
                                                        layer.close(index);
                                                    }
                                                });

                                                // layer.prompt({
                                                //         formType: 0,
                                                //         title: "请输入渠道id(选填)"
                                                //     },
                                                //     function (channel_id, h) {
                                                //         layer.close(h)
                                                //
                                                //         if (res.data == '1') {
                                                //             layer.prompt({
                                                //                     formType: 2,
                                                //                     title: "请输入审核备注"
                                                //                 },
                                                //                 function (audit_remarks, h) {
                                                //                     layer.close(h)
                                                //                     t.post("deal", {
                                                //                         cash_id: e.data.id,
                                                //                         audit_remarks: audit_remarks,
                                                //                         channel_id: channel_id
                                                //                     }, function (res) {
                                                //                         layer.msg(res.msg, {
                                                //                             icon: res.code == 1 ? 1 : 2,
                                                //                             time: 1500
                                                //                         });
                                                //                         layer.close(d); //关闭弹层
                                                //                         i.reload('app-order-paid-list')
                                                //                     });
                                                //                 });
                                                //         } else {
                                                //             t.post("deal", {
                                                //                 cash_id: e.data.id,
                                                //                 channel_id: channel_id
                                                //             }, function (res) {
                                                //                 layer.msg(res.msg, {
                                                //                     icon: res.code == 1 ? 1 : 2,
                                                //                     time: 1500
                                                //                 });
                                                //                 layer.close(d); //关闭弹层
                                                //                 i.reload('app-order-paid-list')
                                                //             });
                                                //         }
                                                //
                                                //
                                                //     });
                                            }else{
                                                if (res.data == '1') {
                                                    layer.prompt({
                                                            formType: 2,
                                                            title: "请输入审核备注"
                                                        },
                                                        function (audit_remarks, h) {
                                                            layer.close(h)
                                                            t.post("deal", {
                                                                cash_id: e.data.id,
                                                                audit_remarks: audit_remarks,
                                                            }, function (res) {
                                                                layer.msg(res.msg, {
                                                                    icon: res.code == 1 ? 1 : 2,
                                                                    time: 1500
                                                                });
                                                                layer.close(d); //关闭弹层
                                                                i.reload('app-order-paid-list')
                                                            });
                                                        });
                                                } else {
                                                    t.post("deal", {
                                                        cash_id: e.data.id,
                                                    }, function (res) {
                                                        layer.msg(res.msg, {
                                                            icon: res.code == 1 ? 1 : 2,
                                                            time: 1500
                                                        });
                                                        layer.close(d); //关闭弹层
                                                        i.reload('app-order-paid-list')
                                                    });
                                                }
                                            }

                                        });
                                    })
                            });
                    }
                    else if ("successOrder" === e.event) {
                        layer.prompt({
                                formType: 1,
                                title: "敏感操作，请验证口令"
                            },
                            function (d, f) {
                                layer.close(f),
                                    layer.confirm("确定审核吗？", function (d) {
                                        //读取配置
                                        t.post("getAuditSwitch", function (res) {
                                            if (res.data == '1') {
                                                layer.prompt({
                                                        formType: 2,
                                                        title: "请输入审核备注"
                                                    },
                                                    function (audit_remarks, h) {
                                                        layer.close(h)
                                                        t.post("successOrder", {
                                                            cash_id: e.data.id,
                                                            audit_remarks: audit_remarks
                                                        }, function (res) {
                                                            layer.msg(res.msg, {
                                                                icon: res.code == 1 ? 1 : 2,
                                                                time: 1500
                                                            });
                                                            layer.close(d); //关闭弹层
                                                            i.reload('app-order-paid-list')
                                                        });
                                                    });
                                            } else {
                                                t.post("successOrder", {cash_id: e.data.id}, function (res) {
                                                    layer.msg(res.msg, {icon: res.code == 1 ? 1 : 2, time: 1500});
                                                    layer.close(d); //关闭弹层
                                                    i.reload('app-order-paid-list')
                                                });
                                            }
                                        });
                                    })
                            });
                    }
                    else if("transpond" === e.event){
                        //转发跑分

                        //选择跑分商户
                        layer.open({
                            type: 2
                            , area: ['500px', '300px']
                            , title: '请选择商户'
                            , content: 'select_admin.html'
                            , maxmin: true
                            , btn: ['确认选择', '关闭']
                            , yes: function (index, layero) {
                                var row = window["layui-layer-iframe" + index].callbackdata();
                                var admin_id = row.id;
                                if(admin_id<1){
                                    layer.msg('请选择账号')
                                    return false;
                                }

                                //读取配置
                                t.post("getAuditSwitch",function (res) {
                                    if(res.data =='1' ){
                                        layer.prompt({
                                                formType: 2,
                                                title: "请输入审核备注"
                                            },
                                            function(audit_remarks, h) {
                                                layer.close(h)
                                                t.post("transpond",{cash_id:e.data.id,audit_remarks:audit_remarks,admin_id:admin_id},function (res) {
                                                    layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                                    i.reload('app-order-paid-list')
                                                });
                                            });
                                    }else{
                                        t.post("transpond",{cash_id:e.data.id,admin_id:admin_id},function (res) {
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                            i.reload('app-order-paid-list')
                                        });
                                    }
                                });


                                //可以使用ajax请求对数据进行进一步处理
                                layer.close(index);
                            }, btn2: function (index, layero) {
                                layer.close(index);
                            }
                        });




                    }
                    else if("revocation" === e.event){
                        //撤回

                        t.post("revocation",{cash_id:e.data.id},function (res) {
                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                            i.reload('app-order-paid-list')
                        });
                    }

                    else if ("details_tixian" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "交易详情",
                            content: "details_tixian.html?id=" + e.data.id,
                            maxmin: !0,                             area: ['80%','60%'],
                            btn: ["确定", "取消"],
                            yes: function(e, t) {},
                            success: function(e, t) {}
                        })
                    }

                    else if("transpond_channel" === e.event){
                        //转发渠道代付

                        //转发渠道代付
                        layer.open({
                            type: 2
                            , area: ['500px', '300px']
                            , title: '请选择渠道'
                            , content: 'select_channelv2?id='+e.data.id
                            , maxmin: true
                            , btn: ['确认选择', '关闭']
                            , yes: function (index, layero) {
                                var row = window["layui-layer-iframe" + index].callbackdata();
                                var cnl_id = row.channel_id;
                                if(cnl_id<1){
                                    layer.msg('请选择渠道')
                                    return false;
                                }

                                console.log({cash_id:e.data.id,cnl_id:cnl_id})
                                //读取配置
                                t.post("getAuditSwitch",function (res) {
                                    if(res.data =='1' ){
                                        layer.prompt({
                                                formType: 2,
                                                title: "请输入审核备注"
                                            },
                                            function(audit_remarks, h) {
                                                layer.close(h)
                                                t.post("transpond_channel",{cash_id:e.data.id,audit_remarks:audit_remarks,cnl_id:cnl_id},function (res) {
                                                    layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                                    i.reload('app-order-paid-list')
                                                });
                                            });
                                    }else{
                                        t.post("transpond_channel",{cash_id:e.data.id,cnl_id:cnl_id},function (res) {
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                            i.reload('app-order-paid-list')
                                        });
                                    }
                                });


                                //可以使用ajax请求对数据进行进一步处理
                                layer.close(index);
                            }, btn2: function (index, layero) {
                                layer.close(index);
                            }
                        });




                    }


                    else if ("rebut" === e.event) {

                        //读取配置
                        t.post("getAuditSwitch",function (res) {
                            if(res.data =='1' ){
                                layer.prompt({
                                        formType: 2,
                                        title: "请输入审核备注"
                                    },
                                    function(audit_remarks, h) {
                                        layer.close(h)
                                        t.post("rebut",{cash_id:e.data.id,audit_remarks:audit_remarks},function (res) {
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                            i.reload('app-order-paid-list')
                                        });
                                    });
                            }else{
                                t.post("rebut",{cash_id:e.data.id},function (res) {
                                    layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                    i.reload('app-order-paid-list')
                                });
                            }
                        });
                    }
                   
                   else if ("handle" === e.event) {

                        //读取配置
                        t.post("getAuditSwitch",function (res) {
                            if(0 ){
                                layer.prompt({
                                        formType: 2,
                                        title: "请输入审核备注"
                                    },
                                    function(audit_remarks, h) {
                                        layer.close(h)
                                        t.post("rebut",{cash_id:e.data.id,audit_remarks:audit_remarks},function (res) {
                                            layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 1500});
                                            i.reload('app-order-paid-list')
                                        });
                                    });
                            }else{
                                t.post("handle",{cash_id:e.data.id},function (res) {
                                    layer.msg(res.msg, {icon: res.code == 1 ? 1: 2,time: 5000});
                                    i.reload('app-order-paid-list')
                                });
                            }
                        });
                    }




                    else if("showImg" === e.event ){
                        //页面层
                        layer.open({
                            type: 1,
                            skin: 'layui-layer-rim', //加上边框
                            area: ['80%', '80%'], //宽高
                            shadeClose: true, //开启遮罩关闭
                            end: function (index, layero) {
                                return false;
                            },
                            content: '<div style="text-align:center"><img src="' +e.data.voucher + '" /></div>'
                        });
                    }
                }),
            i.render({
                elem: "#app-balance-everyday-list",
                url: 'everyList',
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
                        width: 100,
                        title: "ID",
                        sort: !0
                    },
                    {
                        field: "uid",
                        width: 100,
                        title: "商户ID"
                    },
                    {
                        field: "username",
                        width: 200,
                        title: "商户名"
                    },
                    // {
                    //     field: "amount",
                    //     width: 100,
                    //     title: "交易金额",
                    //     style: "color:red",
                    //     templet:function (d) {
                    //         return Math.floor(d.amount)
                    //     }
                    // },
                    {
                        field: "enable",
                        width: 100,
                        title: "余额",
                        style: "color:red"
                    },
                    {
                        field: "disable",
                        width: 120,
                        title: "冻结余额"
                    },

                    {
                        field: "createtime",
                        width: 200,
                        title: "时间",
                        color: "red",
                        templet: function(d) {return u.toDateString(d.createtime*1000); }
                    },
                    ]],
                page: !0,
                limit: 10,
                limits: [10, 15, 20, 25, 30],
                text: "对不起，加载出现异常！",
                done: function() {
                    //加载完成
                }
            }),

            e("balance", {})
    });
