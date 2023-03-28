//新订单提醒
setInterval(function(){
    // $.ajax({
    //     type:'post',
    //     url:"/agent/gema/agentLastOrder",
    //     dataType:'json',
    //     async:false,
    //     success:function(order_id){
    //         var loacal_order_id= localStorage.getItem('order_id');
    //         if(order_id !=loacal_order_id )
    //         {
    //             playVoiceAndOpenLayer($);
    //             lightyear.notify('有新的订单,请注意查收','info','5000','glyphicon glyphicon-warning-sign','bottom','right');
    //             localStorage.setItem('order_id',order_id);
    //         }
    //     }
    // });
},10000);


/*
    * 播报新订单以及以及弹出订单号
     *
     */
function playVoiceAndOpenLayer($,order_sn)
{
    var borswer = window.navigator.userAgent.toLowerCase();
    if(borswer.indexOf( "ie" ) >= 0)
    {
        //IE内核浏览器
        var strEmbed = '<embed name="embedPlay" src="/static/agent/media/order.mp3" autostart="true" hidden="true" loop="false"></embed>';
        if ($("body").find("embed").length <= 0){
            $("body").append(strEmbed);
        }
        var embed = document.embedPlay;
        //浏览器不支持 audio，则使用 embed 播放
        embed.volume = 100;
    }else{
        //非IE内核浏览器
        var strAudio = "<audio id='audioPlay' src='/static/agent/media/order.mp3' hidden='true'>";
        if($("body").find("audio").length <= 0){
            $("body").append(strAudio);
        }
        var audio = document.getElementById("audioPlay");
        //浏览器支持 audio
        audio.play(); //没有就播放

    }
}