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

layui.define(["table", "form","jquery"],
    function(e) {
        var t = layui.$,
            i = layui.table,
            u = layui.util,
            jq = layui.jquery,
            n = layui.form;

            //渠道
             i.render({
            elem: "#app-pay-channel-list",
            url: '/admin/pay/getChannelList',
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
                    field: "name",
                    width: 150,
                    title: "渠道名称"
                },
                {
                    field: "channel_fund",
                    width: 150,
                    title: "渠道余额"
                },
                {
                    field: "wirhdraw_charge",
                    width: 150,
                    title: "提现费用",
                },
                {
                    field: "create_time",
                    title: "创建时间",
                    width: 200,
                },
                {
                    field: "status",
                    title: "状态",
                    width: 100,
                    templet: "#buttonTpl",
                    align: "center"
                },
                {
                    title: "操作",
                    align: "center",
                    fixed: "right",
                    toolbar: "#table-pay-channel"
                }]],
            page: !0,
            limit: 10,
            limits: [10, 15, 20, 25, 30],
            text: "对不起，加载出现异常！"
        }),

                 i.render({
                     elem: "#app-pay-statistics-list",
                     url: '/admin/channel/getStatisticsList',
                     //自定义响应字段
                     response: {
                         statusName: 'code' //数据状态的字段名称
                         ,statusCode: 1 //数据状态一切正常的状态码
                         ,msgName: 'msg' //状态信息的字段名称
                         ,dataName: 'data' //数据详情的字段名称
                     },
                     cols: [[
                         // {
                         //     field: "id",
                         //     width: 100,
                         //     title: "ID",
                         //     sort: !0
                         // },
                         {
                             field: "name",
                             width: 130,
                             title: "渠道名称"
                         },
                         {
                             field: "success_mileage_sum",
                             width: 130,
                             title: "渠道跑量(昨日12点到现在)",
                             templet: function (d) {
                                 return "￥"+d.success_mileage_sum
                             },
                             // hide:

                         },
                         {
                             field: "name",
                             width: 130,
                             title: "15分钟成功率",
                             templet: function (d) {

                                 return "("+d.success_order_15+"/"+d.total_order_15+")"+d.success_rate_15 +'% '

                             }
                         },
                         {
                             field: "name",
                             width: 130,
                             title: "20分钟成功率",
                             templet: function (d) {
                                 return "("+d.success_order_20+"/"+d.total_order_20+")"+d.success_rate_20 +'% '
                             }
                         },
                         {
                             field: "name",
                             width: 130,
                             title: "30分钟成功率",
                             templet: function (d) {
                                 return "("+d.success_order_30+"/"+d.total_order_30+")"+d.success_rate_30 +'% '
                             }
                         },
                         {
                             field: "name",
                             width: 130,
                             title: "60分钟成功率",
                             templet: function (d) {
                                 return "("+d.success_order_60+"/"+d.total_order_60+")"+d.success_rate_60 +'% '
                             }
                         },

                        ]],
                     page: false,
                     // limit: 10,
                     // limits: [10, 15, 20, 25, 30],
                     text: "对不起，加载出现异常！",
                     done:function (res,curr,count) {

                         // console.log(res.data[0].is_channel_mileage_sum)
                         for ( var k in res.data) {
                             if(res.data[k].is_channel_mileage_sum =='0'){
                                 jq("[data-field='success_mileage_sum']").css('display','none'); //关键代码
                                 break;
                             }
                         }
                     }
                 }),

            i.render({
                elem: "#app-pay-channel-huishou-list",
                url: 'getChannelHsList',
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
                        field: "name",
                        width: 150,
                        title: "渠道名称"
                    },
                    {
                        field: "action",
                        width: 150,
                        title: "控制类名称"
                    },
                    {
                        field: "remarks",
                        title: "备注",
                    },
                    {
                        field: "create_time",
                        title: "创建时间",
                        width: 200,
                    },
                    // {
                    //     field: "status",
                    //     title: "状态",
                    //     width: 100,
                    //     templet: "#buttonTpl",
                    //     align: "center"
                    // },
                    {
                        title: "操作",
                        align: "center",
                        fixed: "right",
                        toolbar: "#table-pay-channel"
                    }]],
                page: !0,
                limit: 10,
                limits: [10, 15, 20, 25, 30],
                text: "对不起，加载出现异常！"
            }),
            i.on("tool(app-pay-channel-list)",
                function(e) {
                    var s = e;
                    if ("fund_details" === e.event) {
                        t(e.tr);
                        layer.open({
                            type: 2,
                            title: "账户明细",
                            content: "fundDetails.html?channel_id=" + e.data.id,
                            maxmin: !0,
                            area:  ['80%', '60%'],
                            yes: function(f, t) {},
                            success: function(e, t) {}
                        })
                    }else if("op_pay_channel" === e.event){  //渠道余额增减
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
                                                ,content: 'fundChange.html?channel_id=' + e.data.id
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
                                                            url:'fundChange.html?channel_id=' + e.data.id,
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
                     url: 'getFundDetails',
                     //添加请求字段
                     where: {
                         channel_id:  t("input[ name='channel_id' ] ").val()
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



            e("channel", {})
    });