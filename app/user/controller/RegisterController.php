<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use think\Validate;
use think\Db;
 
use sms\Msg;

class RegisterController extends HomeBaseController
{

    /**
     * 前台用户注册
     */
    public function index()
    {
         
        if (cmf_is_user_login()) {
            $this->redirect(url('user/index/index'));
        } else {
           // return $this->fetch(":register");
           $this->redirect(url('register'));
        }
    }
    /**
     * 前台用户注册页面
     */
    public function register()
    {
      
        if(empty(session('wx.openid'))){
            
            $this->error('请通过微信公众号注册',url('portal/index/index'));
        }
        
        $this->assign('html_title','注册');
       return $this->fetch();
         
    }
    /**
     * 发送验证码
     */
    public function sendmsg()
    { 
        $phone=$this->request->param('tel',0);
        $type=$this->request->param('type','reg');
        $tmp=Db::name('user')->where('mobile',$phone)->find();
        
        switch ($type){
            //注册
            case 'reg':
                if(!empty($tmp)){
                    $this->error('该手机号已被使用');
                }
                break;
            //找回密码
            case 'find':
                if(empty($tmp)){
                    $this->error('该手机号不存在');
                }
                break;
            //换手机号
            case 'mobile':
                if(!empty($tmp)){
                    $this->error('该手机号已被使用');
                }
                //判断密码
                $psw=$this->request->param('psw',0);
                $user=Db::name('user')->where('id',session('user.id'))->find();
                $result=zz_psw($user, $psw);
                if(empty($result[0])){
                    $this->error($result[1],$result[2]);
                }
                break;
            default:
                 $this->error('未知操作');
                 
        }
        $msg=new Msg();
         
        $this->error($msg->reg($phone));
    }
    /**
     * 发送验证码，要验证图片验证码
     */
    public function sendmsg1()
    {
        $pic=$this->request->param('pic',0);
        $phone=$this->request->param('tel',0);
        $type=$this->request->param('type','reg');
        if (!cmf_captcha_check($pic)) {
            $this->error('图片验证码错误');
        }
        $tmp=Db::name('user')->where('mobile',$phone)->find();
        if($type=='reg'){
            if(!empty($tmp)){
                $this->error('该手机号已被使用');
            }
        }elseif($type=='find'){
            if(empty($tmp)){
                $this->error('该手机号不存在');
            }
        } 
        $msg=new Msg(); 
        $this->error($msg->reg($phone));
    }
    
    /**
     * 前台用户注册提交
     */
    public function ajaxRegister()
    {
         
            $rules = [ 
                'user_pass' => 'require|number|length:6', 
                'mobile'=>'require|number|length:11', 
                'user_nickname'=>'require|chs|min:2', 
            ]; 
            $redirect                = url('user/index/index');
            $validate = new Validate($rules);
            $validate->message([ 
                'user_pass.require' => '密码不能为空', 
                'user_pass.length'     => '密码为6位数字',
                'mobile.require' => '手机号码不能为空',
                'mobile.length'     => '手机号码格式错误',
                'user_nickname.chs'=>'请填写真实姓名',
                'user_nickname.require'=>'请填写真实姓名',
                'user_nickname.min'=>'请填写真实姓名',
            ]);
            
            $data1 = $this->request->post();
            $data=[
                'user_login'=>$data1['idcard'],
                'user_nickname'=>$data1['username'],
                'user_pass'=>$data1['password'],
                'mobile'=>$data1['tel'],
                'qq'=>$data1['qq'],
                'weixin'=>$data1['weixin'],
                'last_login_ip'   => get_client_ip(0, true),
                'create_time'     => time(),
                'last_login_time' => time(),
                'user_status'     => 1,  
                "user_type"       => 2,//会员 
                'is_name'=>1,   //默认实名
            ];
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            //验证码
            $msg=new Msg();
            $sms=$msg->verify($data['mobile'],$data1['sms']);
            if($sms!='success'){
                $this->error($sms);
            }
            import('idcard1',EXTEND_PATH);
            $idcard1= new \Idcard1();
            if(($idcard1->validation_filter_id_card($data['user_login']))!==true){
                $this->error('身份证号码非法!');
            }
            if(preg_match(config('reg_mobile'), $data['mobile'])!=1){
                $this->error('手机号码错误');
            }
            
            $data['user_pass'] = cmf_password($data['user_pass']);
            $result = $this->validate($data, 'User');
            if ($result !== true) {
                $this->error($result);
            } else {
               //保存微信头像为本地
                $wx=session('wx');
                //定义头像名,有微信头像就获取，没有就指定默认
                $data['avatar']='avatar/'.md5($data['user_login']).'.jpg';
                //$imgSrc='http://wx.qlogo.cn/mmopen/vi_32/NtItl7iciafpn9B8zHC4Zhy0hsvYCvibbSeTlQpkDH44Il4RRZ4kwQ36l1PZ2DkMiaU0xibD3OeJxOLS6IY8u1pNTrQ/132';
                if(empty($wx['headimgurl'])){ 
                    zz_set_image('self.jpg', $data['avatar'],100,100,6); 
                }else{
                    $this->download($wx['headimgurl'],$data['avatar']);
                }
                //用户性别
                $data['sex']=$wx['sex'];
                $data['openid']=$wx['openid'];
                $sexs=[0=>'未知',1=>'男',2=>'女'];
                $wx0=[
                    'openid'=>$wx['openid'],
                    '性别'=>$sexs[$wx['sex']],
                    '使用语言'=>$wx['language'],
                    '所在地'=>$wx['country'].'-'.$wx['province'].'-'.$wx['city'],
                    '头像'=>$wx['headimgurl']
                ];
                
                $data['more']=json_encode($wx0);
                $result             = Db::name('user')->insertGetId($data);
                if ($result !== false) {
                    $data   = Db::name("user")->where('id', $result)->find();
                    cmf_update_current_user($data);
                    session('wx',null);
                    $this->success("注册成功！");
                } else {
                    $this->error("注册失败！");
                }
            }
             
       
    }
    /* 下载网络文件到本地 */
    function download($url, $path = '/avatar/1.jpg')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        //$filename = pathinfo($url, PATHINFO_BASENAME);
        $path=getcwd().'/upload/'.$path;
        $resource = fopen($path, 'a');
        fwrite($resource, $file);
        fclose($resource);
    }
}