
// 导航
$('.show_position').click(function(e){
    $('.show_posi').toggle();
        e.stopPropagation();
});

$(document).on('click',function(){
    closeposition();
})

function closeposition(){
    $('.show_posi').hide();
}
// 固定部分，灰色背景，且有导航

function hasnav(){
    var navHei=$('.nav-fixed').height();
    var navPadT=$('.nav-fixed').css('padding-top').split('px')[0];
    var navPadB=$('.nav-fixed').css('padding-top').split('px')[0];
    var winHei= $(window).height();
    var docHei= $(document).height();
    var perHeadHei=$('.person_head').height();
    var dkHei=$('.daihuai').height();
    var conHei=$('.content_nav').height();
    var confirm_item_head=$('.confirm_items-head').height();
    

    $('.content_div').height(winHei - navHei - perHeadHei - dkHei -conHei - navPadT - navPadB -confirm_item_head);
    $('.hasNav ').css('min-height',winHei - conHei - navPadT - navPadB +'px')
}

// 借入借出
function hasFixedTop(){
    var winHei = $(window).height();
    var perHeadHei = $('.person_head').height();
    var daiKuanHei = $('.daihuai').height();
    var seachDivHei = $('.search_div').height();
    var seachDivMT = $('.search_div').css('margin-top').split('px')[0];
    var seachDivMB = $('.search_div').css('margin-bottom').split('px')[0];
    var seachLi= $('.search_con>.search_con_ul_div').height();
   
    $('.search_con_ul').css('height',winHei - perHeadHei - daiKuanHei - seachDivHei - seachDivMT -seachDivMB - seachLi)
}


// 验证中文名称
function isChinaName(name) {
    var pattern = /^[\u4E00-\u9FA5]{1,6}$/;
    return pattern.test(name);
}

// 验证手机号
function isPhoneNo(phone) { 
    var pattern = /^1[34578]\d{9}$/; 
    return pattern.test(phone); 
}

// 验证身份证 
function isCardNo(card) { 
    var pattern = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/; 
    return pattern.test(card); 
} 

//6位数字密码
function isPassword(password){
    var pattern=/^\d{6}$/;
    return pattern.test(password)
}

//qq号
function isQq(qq){
    var pattern=/^\d{5,12}$/;
    return pattern.test(qq);
}

// 金额验证
function isMoney(money){
    var pattern=/^\d{1,12}$/;
    return  pattern.test(money);
}


  // 注册表单提交
function formValidate(form){
   
    if($.trim($('input[name="identity_name"]').val()) == '' || isChinaName($('input[name="identity_name"]').val()) == false){
            $('input[name="identity_name"]').focus();
            $('.error-tip').html('提示：请输入正确的用户名');
            return false;
         }else  if($.trim($('input[name="identity_num"]').val()) == '' || isCardNo($('input[name="identity_num"]').val()) == false){
            $('input[name="identity_num"]').focus();
            $('.error-tip').html('提示：请输入正确的身份证号码');
            return false;
        }else  if($.trim($('input[name="password"]').val()) == '' || isPassword($('input[name="password"]').val()) == false){
            $('input[name="password"]').focus();
            $('.error-tip').html('提示：请输入6位数字密码');
            return false;
        }else  if($.trim($('input[name="password_repeat"]').val()) == '' ||  $.trim($('input[name="password"]').val()) != $.trim($('input[name="password_repeat"]').val()) || isPassword($('input[name="password_repeat"]').val()) == false){
            $('input[name="password_repeat"]').focus();
            $('.error-tip').html('提示：两次密码不一致');
            return false;
        }else  if($.trim($('input[name="qq"]').val()) == '' || isQq($('input[name="qq"]').val()) == false){
            $('input[name="qq"]').focus();
            $('.error-tip').html('提示：请输入正确QQ号');
            return false;
        }else  if($.trim($('input[name="tel"]').val()) == '' || isPhoneNo($('input[name="tel"]').val()) == false){
            $('input[name="tel"]').focus();
            $('.error-tip').html('提示：请输入正确手机号');
            return false;
        }
}

    // 登录表单提交
function formLogin(){
    if($.trim($('input[name="tel"]').val()) == '' || isPhoneNo($('input[name="tel"]').val()) == false){
        $('input[name="tel"]').focus();
        $('.error-tip').html('提示：请输入正确手机号');
        return false;
    }else if($.trim($('input[name="password"]').val()) == '' || isPassword($('input[name="password"]').val()) == false){
        $('input[name="password"]').focus();
        $('.error-tip').html('提示：请输入正确6位数字');
        return false;
    }else if($.trim($('input[name="identifying_code"]').val()).length != 4 ){
        $('input[name="identifying_code"]').focus();
        $('.error-tip').html('提示：请输入正确验证码');
        return false;
    }
}

    // 失去焦点
$(function(){

        $('input[name="identity_name"]').blur(function(){
            if($.trim($('input[name="identity_name"]').val()) == '' || isChinaName($('input[name="identity_name"]').val()) == false){
      
                $('.error-tip').html('提示：请输入正确的用户名');
          
            }else{
                $('.error-tip').html('');
            }

        })
        $('input[name="identity_num"]').blur(function(){
            if($.trim($('input[name="identity_num"]').val()) == '' || isCardNo($('input[name="identity_num"]').val()) == false){
           
                $('.error-tip').html('提示：请输入正确的身份证号码');
      
            }else{
                $('.error-tip').html('');
            }
        })

        $('input[name="password"]').blur(function(){
            if($.trim($('input[name="password"]').val()) == '' || isPassword($('input[name="password"]').val()) == false){
           
                $('.error-tip').html('提示：请输入6位数字密码');
    
            }else{
                $('.error-tip').html('');
            }
        })

        $('input[name="password_repeat"]').blur(function(){
            if($.trim($('input[name="password_repeat"]').val()) == '' || isPassword($('input[name="password_repeat"]').val()) == false){
               
                $('.error-tip').html('提示：两次密码不一致');
      
            }else{
                $('.error-tip').html('');
            }
        })

        $('input[name="qq"]').blur(function(){
            if($.trim($('input[name="qq"]').val()) == '' || isQq($('input[name="qq"]').val()) == false){
         
                $('.error-tip').html('提示：请输入正确QQ号');
          
            }else{
                $('.error-tip').html('');
            }
        })

        $('input[name="tel"]').blur(function(){
            if($.trim($('input[name="tel"]').val()) == '' || isPhoneNo($('input[name="tel"]').val()) == false){
             
                $('.error-tip').html('提示：请输入正确手机号');
            
            }else{
                $('.error-tip').html('');
            }
        })

        $('input[name="borrowing_balance"]').blur(function(){
            if($.trim($('input[name="borrowing_balance"]').val())=='' || isMoney($.trim($('input[name="borrowing_balance"]').val()))=='false'){

                $('.error-tip ').html('提示：请输入正确金额');

            }else{

                $('.error-tip').html('');

            }
        })

        $('input[name="new_tel"]').blur(function(){
            if($.trim($('input[name="new_tel"]').val()) == '' ||   $.trim($('input[name="new_tel"]').val()) == $.trim($('input[name="tel"]').val())  ||  isPhoneNo($('input[name="new_tel"]').val()) == false){
       
                $('.error-tip').html('提示：请输入和原手机不同的手机号码');
                
                return false;
            }
        });
        $(".search_con_ulLi").click(function () {
            $(this).find(".ul_2nd").toggle();
        })

      
});





// document.querySelector('body').addEventListener('touchstart', function (ev) {
//     event.preventDefault();
// });

