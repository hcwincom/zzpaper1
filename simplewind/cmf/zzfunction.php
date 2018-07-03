<?php
use think\Config;
use think\Db;
use think\Url;
use dir\Dir;
use think\Route;
use think\Loader;
use think\Request;
use cmf\lib\Storage;

// 应用公共文件

//设置插件入口路由
Route::any('plugin/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\PluginController@index");
Route::get('captcha/new', "\\cmf\\controller\\CaptchaController@index");

/* 记录日志 */
function zz_log($str,$filename='test.log'){
    error_log(date('Y-m-d H:i:s').$str."\r\n",3,'log/'.$filename);
}
/* 根据当前时间得到今天凌晨的时间 */
function zz_get_time0(){
    $day=date('Y-m-d',time());
    return strtotime($day);
}
/* 检测是否系统维护时间 */
function zz_check_time(){ 
    $time=time();
    $day=strtotime(date('Y-m-d',$time));
    $tmp=$time-$day;
    if($tmp<600 || $tmp>86390){
        return [1,'夜间0点到0点10分为系统维护时间，不能处理用户借条数据'];
    }else{
        return [0,'可以访问'];
    }
}
/* 利率计算 */
function zz_get_money($money,$rate,$days){
    $tmp1=bcmul($days*$rate,$money,2);
    $tmp2=bcdiv($tmp1,36000,2);
    return bcadd($money,$tmp2,2);
}
/* 得到最终还款金额 */
function zz_get_money_overdue($real_money,$money,$rate,$overdue_day){
    $tmp1=bcmul($overdue_day*$rate,$money,2);
    $tmp2=bcdiv($tmp1,36000,2); 
    return bcadd($real_money,$tmp2,2);
}
/* 密码输入 */
function zz_psw($user,$psw){
    if($user['user_pass']!=session('user.user_pass')){
        session('user',null);
        return [0,'密码已修改，请重新登录',url('user/login/login')];
    }  
    if(cmf_compare_password($psw, $user['user_pass'])){
        session('psw',0); 
        return [1];
    }else{
        $fail=session('psw');
        if(empty($fail)){
            session('psw',1);
        }elseif($fail==5){
            session('user',null);
            session('psw',0);
            return [0,'密码错误已达6次，请重新登录',url('user/login/login')];
        }else{
            session('psw',$fail+1);
        }
        return [0,'密码错误'.($fail+1).',累计六次将退出登录!',''];
    }
   
}
/* 发送微信信息 */
/*  cURL函数简单封装 */
function zz_curl($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return json_decode($output, true);
}
function zz_wxmsg($openid,$url0,$data,$type){
    if(empty($openid)){
        return ['errcode'=>1,'errmsg'=>'openid为空','msgid'=>0]; 
    }
    $token=config('access_token');
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;
    if($type=='msg_send'){
        $template_id=config($type);
        $json = '{
            "touser":"'.$openid.'",
            "template_id":"'.$template_id.'",
            "url":"'.$url0.'",
            "topcolor":"#FF0000", 
            "data":{
                "first": {
                "value":"'.$data[0].'",
                "color":"#173177"
                },
                "keyword1":{
                "value":"'.$data[1].'",
                "color":"#173177"
                },
                "keyword2":{
                "value":"'.$data[2].'",
                "color":"#173177"
                },
                "keyword3":{
                "value":"'.$data[3].'",
                "color":"#173177"
                },
              
                "remark":{
                "value":"'.$data[4].'",
                "color":"#173177"
                }
            }
        }';
    }elseif($type=='msg_back'){
        $template_id=config($type);
        $json = '{
            "touser":"'.$openid.'",
            "template_id":"'.$template_id.'",
             "url":"'.$url0.'",
            "topcolor":"#FF0000",
            "data":{
                "first": {
                "value":"'.$data[0].'",
                "color":"#173177"
                },
                "keyword1":{
                "value":"'.$data[1].'",
                "color":"#173177"
                },
                "keyword2":{
                "value":"'.$data[2].'",
                "color":"#173177"
                },
                "keyword3":{
                "value":"'.$data[3].'",
                "color":"#173177"
                },
                "keyword4":{
                "value":"'.$data[4].'",
                "color":"#173177"
                },
                "remark":{
                "value":"'.$data[5].'",
                "color":"#173177"
                }
            }
        }';
    }else{
        return ['errcode'=>1,'errmsg'=>'参数错误','msgid'=>0]; 
    } 
    $res=zz_curl($url,$json);
    return $res;
}
/* 过滤HTML得到纯文本 */
function zz_get_content($list,$len=100){
    //过滤富文本
    $tmp=[];
    foreach ($list as $k=>$v){
        
        $content_01 = $v["content"];//从数据库获取富文本content
        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        $con = mb_substr($contents, 0, $len,"utf-8");//返回字符串中的前100字符串长度的字符
        $v['content']=$con.'...';
        $tmp[]=$v;
    }
    return $tmp;
}


/*制作缩略图 
 * zz_set_image(原图名,新图名,新宽度,新高度,缩放类型)
 *  */
function zz_set_image($pic,$pic_new,$width,$height,$thump=6){
    /* 缩略图相关常量定义 */
//     const THUMB_SCALING   = 1; //常量，标识缩略图等比例缩放类型
//     const THUMB_FILLED    = 2; //常量，标识缩略图缩放后填充类型
//     const THUMB_CENTER    = 3; //常量，标识缩略图居中裁剪类型
//     const THUMB_NORTHWEST = 4; //常量，标识缩略图左上角裁剪类型
//     const THUMB_SOUTHEAST = 5; //常量，标识缩略图右下角裁剪类型
//     const THUMB_FIXED     = 6; //常量，标识缩略图固定尺寸缩放类型
    //         $water=INDEXIMG.'water.png';//水印图片
    //         $image->thumb(800, 800,1)->water($water,1,50)->save($imgSrc);//生成缩略图、删除原图以及添加水印
    // 1; //常量，标识缩略图等比例缩放类型
    //         6; //常量，标识缩略图固定尺寸缩放类型
    $path=getcwd().'/upload/';
    //判断文件来源，已上传和未上传
    $imgSrc=(is_file($pic))?$pic:($path.$pic);
    
    $imgSrc1=$path.$pic_new;
    if(is_file($imgSrc)){
        $image = \think\Image::open($imgSrc); 
        $size=$image->size(); 
        if($size!=[$width,$height] || !is_file($imgSrc1)){ 
            $image->thumb($width, $height,$thump)->save($imgSrc1);
        } 
    } 
    return $pic_new; 
}
 

/* 为网址补加http:// */
function zz_link($link){
    //处理网址，补加http://
    $exp='/^(http|ftp|https):\/\/([\w.]+\/?)\S*/';
    if(preg_match($exp, $link)==0){
        $link='http://'.$link;
    }
    return $link;
}