<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class IndexController extends HomeBaseController
{
    private $token='zzpaper';
    public function index()
    {
        
        $redirect=session('login_http_referer');
        if(empty($redirect)){
            $redirect=$this->request->server('HTTP_REFERER');
            if(empty($redirect)){
                $redirect=url('user/index/index');
            }
            session('login_http_referer',$redirect);
        }
        
        //测试
        //$openid='oyHSG1Rq1YeiZ1o8OoqFyt4ri4yw'; 
        //检测网页授权
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']); 
        if( preg_match('/micromessenger/', $ua) && empty(session('user'))){
            // 公众号的id和secret
            $appid = config('wx_appid');
            $appsecret = config('wx_appsecret');
           
            $index=url('portal/index/index','',true,true);
            $index0= urlencode($index);
            
            if(empty($_GET["code"])){ 
               //开始只获取openid 
                $scope='snsapi_base';
                $url0='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.
                '&redirect_uri='.$index0.'&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
                session('wx',['scope'=>$scope,'url0'=>$url0]);
               
                header("Location: ".$url0);
                exit('正在获取微信授权openid');
            }
            $code = $_GET["code"];
            //openid
            $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid.
            "&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
            $res =zz_curl($url); 
            
             //获取到openid就查询用户信息，没有信息需要查询微信信息后注册，有信息到主页
            if(empty($res['openid'])){
                exit('微信信息获取失败，请退出重试');
            }else{
                session('wx.openid',$res['openid']);
                
                $user=Db::name('user')->where('openid',$res['openid'])->find();
                if(empty($user)){ 
                    //需要获取微信信息 
                    $access_token = config('access_token');
                    $openid = $res['openid'];
                    $get_user_info_url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
                    //获取到用户信息
                    $userinfo =zz_curl($get_user_info_url);
                    if(empty($userinfo['openid'])){
                        zz_log('user_info授权失败$$access_token'.$access_token);
                        session('wx',null);
                        session('redirect',null);
                        exit('微信授权信息获取失败，请退出重试');
                    }else{
                        session('wx',$userinfo); 
                        session('redirect',null);
                        $this->redirect(url('user/register/register'));
                    }
                }else{
                    session('user',$user);
                    $this->redirect($redirect);
                }
            }
            
        }
        if(empty(session('user'))){
            $this->redirect(url('user/login/login'));
        }else{
            $this->redirect($redirect);
        }
        
        exit;
    }
   /* 测试微信服务器token */
    public function checkSignature()
    {
        
        $echoStr = $_GET["echostr"]; 
        // you must define TOKEN by yourself
        if (empty($this->token)) {
            throw new \Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            echo $echoStr;
        }else{
            echo false;
        }
        exit();
    }
    
}
