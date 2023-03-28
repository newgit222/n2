$(function(){
    var options = {
        type: 'POST',
        success:showResponse,
        dataType: 'json',
        error : function(xhr, status, err) {
            alert("操作失败");
        }
    };

    $("#inputForm").submit(function(){
        lightyear.loading('show');
        $(this).ajaxSubmit(options);
        return false;   //防止表单自动提交
    });
});


// /**
//  * 弹出输入框
//  */
// function inputAjax(url,title,key,obj={}) {
//     parent.layer.prompt({title: title, formType: 0}, function(value, index){
//         parent.layer.close(index);
//         obj[key] =value
//         ajaxUrl(url,obj)
//     });
// }

// /**
//  * 需要安全码的操作
//  */
// function checkInputAjax(type,url,obj={},title='',key='') {
//     $.confirm({
//         title: '提示',
//         content: '' +
//             '<form action="" class="formName">' +
//             '<div class="form-group">' +
//             '<label>请输入'+title+'</label>' +
//             '<input type="text" placeholder="'+title+'" class="name form-control" required />' +
//             '</div>' +
//             '</form>',
//         buttons: {
//             formSubmit: {
//                 text: '提交',
//                 btnClass: 'btn-blue',
//                 action: function () {
//                     var name = this.$content.find('.name').val();
//                     if(!name){
//                         $.alert('请您输入'+title);
//                         return false;
//                     }
//                     obj[key] = name;
//                     ajaxUrl(url,obj)
//                 }
//             },
//             cancel: {
//                 text: '取消'
//             },
//         },
//         onContentReady: function () {
//             var jc = this;
//             this.$content.find('form').on('submit', function (e) {
//                 e.preventDefault();
//                 jc.$$formSubmit.trigger('click');
//             });
//         }
//     });
// }


/**
 * 弹出输入框
 */
function inputAjax(url,title,key,obj={}) {
    $.confirm({
        title: '提示',
        content: '' +
            '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<label>请输入'+title+'</label>' +
            '<input type="text" placeholder="'+title+'" class="name form-control" required />' +
            '</div>' +
            '</form>',
        buttons: {
            formSubmit: {
                text: '提交',
                btnClass: 'btn-blue',
                action: function () {
                    var name = this.$content.find('.name').val();
                    if(!name){
                        $.alert('请您输入'+title);
                        return false;
                    }
                    obj[key] = name;
                    ajaxUrl(url,obj)
                }
            },
            cancel: {
                text: '取消'
            },
        },
        onContentReady: function () {
            var jc = this;
            this.$content.find('form').on('submit', function (e) {
                e.preventDefault();
                jc.$$formSubmit.trigger('click');
            });
        }
    });
}



/**
 * 使用ajax请求
 */
function ajaxUrl(url,param={}) {
    //加载层
    lightyear.loading('show');
    $.post(url,param,function(result){
        lightyear.loading('hide');
        showResult(result)
    });
}

/**
 * 弹出框
 * @param url
 * @param param
 */
function confirmimportances(url,param={}) {


    $.confirm({
        title: '确认',
        content: '确定要执行操作吗？',
        buttons: {
            confirm: {
                text: '确认',
                btnClass: 'btn-blue',
                action: function(){
                    ajaxUrl(url,param)
                }
            },
            cancel: {
                text: '关闭',
                action: function(){
                    return;
                }
            },

        }
    });
}



function showResult(result) {

    if (result.code == 1) {
        lightyear.notify(result.msg, 'success', 3000);
        setTimeout(function () {
            location.reload()
        }, 1000)
    } else {
        lightyear.notify(result.msg, 'danger', 100);
    }
}



function showResponse(responseText, statusText, xhr, $form) {
    lightyear.loading('hide');
    if (responseText.code == 1) {
        lightyear.notify(responseText.msg, 'success', 3000);
    }else {
        lightyear.notify(responseText.msg, 'danger', 100);
    }


    if(responseText.url) {
        setTimeout(function () {
            window.location.href = responseText.url;
        }, 1000)
    }
}