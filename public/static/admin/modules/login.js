
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

layui.define("form",
    function(e) {
        var s = layui.$,
            t = (layui.layer, layui.laytpl, layui.setter, layui.view, layui.admin),
            i = layui.form,
            a = s("body");
        i.verify({
            nickname: function(e, s) {
                return new RegExp("^[a-zA-Z0-9_一-龥\\s·]+$").test(e) ? /(^\_)|(\__)|(\_+$)/.test(e) ? "用户名首尾不能出现下划线'_'": /^\d+\d+\d$/.test(e) ? "用户名不能全为数字": void 0 : "用户名不能有特殊字符"
            },
            password: [/^[\S]{6,12}$/, "密码必须6到12位，且不能出现空格"],
            vercode: function(value) {
                if(value.length < 2) {
                    return '验证码至少得2个字符';
                }
            }
        }),
            t.sendAuthCode({
                elem: "#app-user-getsmscode",
                elemPhone: "#app-user-login-cellphone",
                elemVercode: "#app-user-login-vercode",
                ajax: {
                    url: layui.setter.base + "json/user/sms.js"
                }
            }),
            a.on("click", "#app-user-get-vercode",
                function() {
                    s(this);
                    this.src = "/captcha?t=" + (new Date).getTime()
                }),
            e("login", {})
    });