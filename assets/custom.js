/**
{
 status, required,
 title,
 desc,
 url, required,
 urltext, required,
 secondurl,
 secondurltext
}
*/
function renderAjaxRespDiv(msg){
    $('#content').addClass('hide');
    res = '<div class="weui_msg" id="ajaxRespDiv">';
    if(msg.status){
        res += '<div class="weui_icon_area"><i class="weui_icon_success c_weui_icon_success weui_icon_msg"></i></div>';
        if(typeof msg.title == 'undefined'){
            msg.title = '操作成功';
        }
    }else{
        res += '<div class="weui_icon_area"><i class="weui_icon_warn weui_icon_msg"></i></div>';
        if(typeof msg.title == 'undefined'){
            msg.title = '操作失败';
        }
    }
    res += '<div class="weui_text_area">';
    res += '<h2 class="weui_msg_title">'+msg.title+'</h2>';
    if(typeof msg.desc != 'undefined'){
        res += '<p class="weui_msg_desc">'+msg.desc+'</p>';
    }
    res += '</div>';

    res += '<div class="weui_opr_area">';
    res += '<p class="weui_btn_area">';
    res += '<a href="'+msg.url+'" class="weui_btn weui_btn_primary">'+msg.urltext+'</a>';
    if(typeof msg.secondurl != 'undefined' && typeof msg.secondurltext != 'undefined'){
        res += '<a href="'+msg.secondurl+'" class="weui_btn weui_btn_default">'+msg.secondurltext+'</a>';
    }
    res += '</p></div>';
    res += '</div>';
    $('body').append(res);
}
