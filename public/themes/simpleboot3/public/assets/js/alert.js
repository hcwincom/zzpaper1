var simpleAlert = function (opts){
    var opt = {
        'closeAll':fasle,
        "imgshow":0,
        "content":[]
    }

    var option=$.extend(opt,opts);

    var dialog={};
    var $simpleAlert = $('<div class="drop_down">');
    var $shelter = $('<div class="drop_down_head>');
    var $simpleAlertBody = $('<div class="drop_down_list">');
    var $simpleAlertselP = $('<p class="fixed_li">100%</p>');
    var $simpleAlertUl = $('<ul>');
    var $simpleAlertBodyLi = $('<li>'+ content  +'</li>');
    var $simpleEnd=$('</ul>'+'</div>'+'</div>'+'</div>')
}


dialog.init=function(){
    $simpleAlert.append(shelter).append($simpleAlertBody).append($simpleAlertselP).append($simpleAlertUl).append($simpleAlertBodyLi).append($simpleEnd);
    $('body').append($simpleAlert);
    $simpleAlertBody.show().animate({"marginTop":"-128px","opacity":"1"},300);
    dialog.init();
    return dialog;
}