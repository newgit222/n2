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
        var index = parent.layer.load(0, {shade: false}); //0代表加载的风格，支持0-2
        $(this).ajaxSubmit(options);
        parent.layer.close(index)
        return false;   //防止表单自动提交
    });
});

// function confirmimportances(url,param={}) {
//     layer.confirm('确定要执行操作吗？', {
//         btn: ['确定','取消'] //按钮
//     }, function(){
//         ajaxUrl(url,param)
//     }, function(){
//     });
// }


/**
 * 弹出输入框
 */
function inputAjax(url,title,key,obj={}) {
    parent.layer.prompt({title: title, formType: 0}, function(value, index){
        parent.layer.close(index);
        obj[key] =value
        ajaxUrl(url,obj)
    });
}


/**
 * 使用ajax请求
 */
function ajaxUrl(url,param={}) {

    //加载层
    var index = parent.layer.load(0, {shade: false}); //0代表加载的风格，支持0-2

    $.post(url,param,function(result){
        parent.layer.close(index)
        showResult(result)
    });
}

function showResult(result) {
    if (result.code == 1) {
        parent.layer.msg(result.msg, {icon: 6});
        setTimeout(function () {
            location.reload()
        }, 1000)
    } else {
        parent.layer.msg(result.msg, {icon: 5});
    }
}



function showResponse(responseText, statusText, xhr, $form) {
    if (responseText.code == 1) {
        parent.layer.msg(responseText.msg, {icon: 6});

    } else {
        parent.layer.msg(responseText.msg, {icon: 5});

    }

    if(responseText.url) {
        setTimeout(function () {
            window.location.href = responseText.url;
        }, 1000)
    }
}