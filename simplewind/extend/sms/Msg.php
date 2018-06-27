<?php
namespace sms;
/* 短信 */
class Msg{
    /* 短信验证码接口 */
    public function reg($phone){
        $msg=session('sms');
        $time=time();
        if(!empty($msg) && ($time-$msg['time'])<120){
            return '不要频繁发送';
        } 
        $content=rand(100000,999999);
        $nowapi_parm=[];
        $nowapi_parm['app']='sms.send';
        $nowapi_parm['tempid']=config('sms_tmpid');
        $nowapi_parm['param']=urlencode('code='.$content);
        $nowapi_parm['phone']=$phone;
        $nowapi_parm['appkey']=config('sms_appkey');
        $nowapi_parm['sign']=config('sms_sign');
        $nowapi_parm['format']='json';
        $result=$this->nowapi_call($nowapi_parm);
         if($result==='success'){
            session('sms',['mobile'=>$phone,'content'=>$content,'time'=>$time]);
        }else{
            $result='发送失败';
        }
        return $result;
    }
    /* 短信验证  */
    public function verify($phone,$content){
        $msg=session('sms'); 
        $time=time();
        if(empty($msg)){
            return '短信验证失效';
        }elseif(($msg['time']-$time)>600){
            return '短信验证码过期';
        }elseif($msg['content']!=$content){
            return '短信验证码错误';
        }else{ 
            session('sms',null);
            return 'success';
        }
    }
    function nowapi_call($a_parm){
        if(!is_array($a_parm)){
            return false;
        }
        //combinations
        $a_parm['format']=empty($a_parm['format'])?'json':$a_parm['format'];
        $apiurl=empty($a_parm['apiurl'])?'http://api.k780.com/?':$a_parm['apiurl'].'/?';
        unset($a_parm['apiurl']);
        foreach($a_parm as $k=>$v){
            $apiurl.=$k.'='.$v.'&';
        }
        $apiurl=substr($apiurl,0,-1);
        if(!$callapi=file_get_contents($apiurl)){
            return false;
        }
        //format
        if($a_parm['format']=='base64'){
            $a_cdata=unserialize(base64_decode($callapi));
        }elseif($a_parm['format']=='json'){
            if(!$a_cdata=json_decode($callapi,true)){
                return false;
            }
        }else{
            return false;
        }
        //array
        if($a_cdata['success']!='1'){
            return false;
        }
        if($a_cdata['result']['status']=='OK'){
            return 'success';
        }else{
            return false;
        }
    }
}