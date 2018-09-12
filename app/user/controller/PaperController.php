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

use cmf\controller\UserBaseController;
use think\Db;
use sms\Msg;
class PaperController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        
    }
    /**
     *查信用,搜索借条
     */
    public function search()
    { 
        $this->assign('html_title','查信用');
         $user=session('user');
         if(empty($user['is_name'])){ 
            $this->assign('error','没有实名认证，不能查信用');
            return $this->fetch();
        } 
        $idcard=$this->request->param('identity_num','','trim');
        if(empty($idcard)){
            $this->assign('error','');
            return $this->fetch();
        }
        $this->assign('idcard',$idcard); 
        // $codes=$this->request->param('sms','','trim');
        // $msg=new Msg(); 
        // $res=$msg->verify($user['mobile'],$codes);
        // if($res!='success'){
        //     $this->assign('error',$res);
        //     return $this->fetch();
        // }
        
        $info=Db::name('user')->where(['user_login'=>$idcard,'user_type'=>2])->find();
        if(empty($info)){
            $this->assign('error','没有此用户，请检查身份证号是否填写错误');
            return $this->fetch(); 
        }
       //未还借款
        $list1=Db::name('paper')->alias('p')
        ->join('cmf_user u','u.id=p.lender_id')
        ->where(['p.borrower_idcard'=>['eq',$idcard],'p.status'=>['in',[3,4,5]]])
        ->order('p.status asc,p.expire_day asc,p.overdue_day asc,p.id desc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar'); 
        //已还借款
        $list2=Db::name('paper_old')->alias('p')
        ->join('cmf_user u','u.id=p.lender_id')
        ->where(['p.borrower_idcard'=>$idcard])
        ->order('p.overdue_day asc,p.id desc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar'); 
        
        $this->assign('info',$info); 
        $this->assign('list1',$list1); 
        $this->assign('list2',$list2); 
        $this->assign('paper_status',config('paper_status')); 
        return $this->fetch('search_info');
      
    }
    /**
     *负债查询
     */
    public function search_paper()
    {
        $this->assign('html_title','负债查询');
         
        $oid=$this->request->param('oid','');
        $m_paper=Db::name('paper');
        $paper=$m_paper->where(['oid'=>$oid])->find();
        
        $uid=session('user.id');
        //只有相关人员能查询
        if(empty($paper) || ($paper['lender_id']!=$uid && $paper['borrower_id']!=$uid)){
           $this->error('非法访问');
        }
        $buid=$paper['borrower_id'];
        
        $info=Db::name('user')->where(['id'=>$buid,'user_type'=>2])->find();
        if(empty($info)){
            $this->assign('error','没有此用户');
            return $this->fetch();
        }
        //未还借款
        $list1=$m_paper->alias('p')
        ->join('cmf_user u','u.id=p.lender_id')
        ->where(['p.borrower_id'=>['eq',$buid],'p.status'=>['in',[3,4,5]]])
        ->order('p.status asc,p.expire_day asc,p.overdue_day asc,p.id desc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar');
         
        $this->assign('info',$info);
        $this->assign('list1',$list1);
        $this->assign('list2',[]);
        $this->assign('paper_status',config('paper_status'));
        return $this->fetch('search_info');
        
    }
    
     
    /**
     *补借条
     */
    public function send()
    {
        $this->assign('html_title','补借条'); 
        //0借款1出借
        $this->assign('send_type',$this->request->param('send_type',0,'intval')); 
        $error='';
        
        $user0=Db::name('user')->where('id',session('user.id'))->find();
        session('user',$user0);
        if(empty($user0['is_name'])){
            $error='没有实名认证，不能补借条';
        }
        if(empty($user0['is_paper'])){
            $error='有借条逾期超过3天，暂时不能补借条';
        }
        $this->assign('error',$error);
        return $this->fetch();
        
    }
    /**
     *补借条执行，废弃
     */
    public function sendPost()
    {
       $this->error('关闭');
         
    }
    /**
     *补借条,新
     */
    public function ajax_send()
    {  
        $tmp=zz_check_time();
        if($tmp[0]===1){
           $this->error($tmp[1]);
        }
        $user0=Db::name('user')->where('id',session('user.id'))->find();
        if(empty($user0['is_name'])){
            $this->error('没有实名认证，不能补借条');
        }
        if(empty($user0['is_paper'])){
            $this->error('有借条逾期超过3天，暂时不能补借条');
        }
        $data0=$this->request->param();
        
        $time=time();
        $today=date('Ymd',$time);
        //判断时间
        $data=[
            'end_time'=>strtotime($data0['end']),
            'start_time'=>strtotime($today),
            'insert_time'=>$time,
            'update_time'=>$time,
            'rate'=>$data0['rate'],
            'money'=>$data0['money'],
            'use'=>$data0['use'],
            
        ];
        //判断金钱格式
        if(preg_match(config('reg_psw'),$data0['psw'])!=1){
            $this->error('密码输入有误');
        }
        //判断金钱格式
        if(preg_match(config('reg_money'),$data['money'])!=1){
            $this->error('借款金额输入有误');
        }
        //计算到期天数
        $data['expire_day']=bcdiv(($data['end_time']-$data['start_time']),86400,0);
        if($data['expire_day']<1){
            $this->error('还款时间最早从明天开始');
        }
        
        //计算利息保存利率为百倍整数，所以360*100=36000
        $data['real_money']=zz_get_money($data['money'],$data['rate'],$data['expire_day']);
        
        //判断姓名格式
        $names=explode('-',$data0['name']);
        $where_user11=['user_type'=>2,'user_nickname'=>$names[0]];
        if(!empty($names[1])){
            if(preg_match(config('reg_mobile'),$names[1])!=1){
                $this->error('手机号输入有误');
            }
            $where_user11['mobile']=$names[1];
        } 
        //获取对方信息 
        $user11=Db::name('user')->where($where_user11)->select();
        if(empty($user11[0])){
            $this->error('对方姓名不存在');
        }
        if(!empty($user11[1])){
            $this->error('姓名有重复，请在姓名后加上手机号，用-分隔，如张三-15211112222');
        }  
        $user1=$user11[0];
        if(empty($user1['is_name'])){
            $this->error('对方未实名认证，不能补借条');
        }
        if($user1['id']==$user0['id']){
            $this->error('不能借给自己');
        }
        
        //比较密码
        $result=zz_psw($user0, $data0['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        $m_paper=Db::name('paper');
        
        $data['lender_id']=$user1['id'];
        $data['lender_name']=$user1['user_nickname'];
        $data['lender_idcard']=$user1['user_login'];
        $data['lender_mobile']=$user1['mobile'];
        //判断是借款还是出借，对方信息保存
        if(empty($data0['send_type'])){
            $count=$m_paper->where(['borrower_id'=>$user0['id'],'start_time'=>$data['start_time']])->count();
          
            $data_reply=[
                'insert_time'=>$time,
                'update_time'=>$time,
                'type'=>'send',
                'is_borrower'=>1,
                'oid'=>$today.'-'.$user0['id'].'-'.($count+1),
            ];
            $data['borrower_id']=$user0['id'];
            $data['borrower_name']=$user0['user_nickname'];
            $data['borrower_idcard']=$user0['user_login'];
            $data['borrower_mobile']=$user0['mobile'];
           
            $data['lender_id']=$user1['id'];
            $data['lender_name']=$user1['user_nickname'];
            $data['lender_idcard']=$user1['user_login'];
            $data['lender_mobile']=$user1['mobile'];
            
        }else{
            $count=$m_paper->where(['lender_id'=>$user0['id'],'start_time'=>$data['start_time']])->count();
            $data_reply=[
                'insert_time'=>$time,
                'update_time'=>$time,
                'type'=>'send',
                'is_borrower'=>0,
                'oid'=>$today.'-'.$user0['id'].'-'.($count+1),
            ];
            $data['borrower_id']=$user1['id'];
            $data['borrower_name']=$user1['user_nickname'];
            $data['borrower_idcard']=$user1['user_login'];
            $data['borrower_mobile']=$user1['mobile'];
            $data['lender_id']=$user0['id'];
            $data['lender_name']=$user0['user_nickname'];
            $data['lender_idcard']=$user0['user_login'];
            $data['lender_mobile']=$user0['mobile'];
        }
        $data['oid']=$data_reply['oid'];
        
        Db::startTrans();
        try {
            $m_paper->insert($data);
            $rid=Db::name('reply')->insertGetId($data_reply);
        } catch (\Exception $e) {
            Db::rollBack();
            $this->error('补借条失败，请重试!'.$e->getMessage());
        }
        
        Db::commit();
        //补借条发送申请
        $type='msg_send';
        $first='你好，'.$user0['user_nickname'].'发起补借条申请，请确认信息';
        $data=[
            $first,
            $data['money'],
            date('Y-m-d',$data['start_time']),
            date('Y-m-d',$data['end_time']),
            '点击进入'
        ];
       
        $res=zz_wxmsg($user1['openid'], url('user/index/index','',true,true), $data, $type); 
        if($res['errcode']!=0){ 
            zz_log($first.'-信息发送失败'.$res['errcode'].'-'.$res['errmsg'],'wx.log');
        } 
       
        $this->success('借条已经提交',url('user/paper/qrshow',['id'=>$rid]));
        
    }
    /* 中间页面，显示补借条后的二维码 */
    public function qrshow(){
        $id=$this->request->param('id',0,'intval');
        $reply=Db::name('reply')->where('id',$id)->find();
        if(empty($reply)){
            $this->error('申请信息已失效!');
        }
        $paper=Db::name('paper')->where('oid',$reply['oid'])->find();
        if(empty($paper)){
            $this->error('借条信息已失效!');
        }
        //判断是否显示同意
        $user=session('user');
        
        $send_type=0;
       //比较是否是借条发起者,发起者显示链接，其他人进入申请详情页
        if(($reply['is_borrower']==1 && $paper['borrower_id']==$user['id']) || ($reply['is_borrower']==0 && $paper['lender_id']==$user['id'])){
            $url=url('user/paper/qrshow',['id'=>$id],true,true);
            $this->assign('url',$url);
            $this->assign('html_title','借条链接');
            return $this->fetch();
        }else{
            $this->redirect(url('user/paper/confirm',['id'=>$id]));
        }
        
    }
    /* 申请详情 */
    public function confirm(){
        $id=$this->request->param('id',0,'intval');
        $info_reply=Db::name('reply')->where('id',$id)->find();
        if(empty($info_reply)){
            $this->error('该申请不存在');
        }
        $info_paper=Db::name('paper')->where('oid',$info_reply['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        //判断是否显示同意
        $user=session('user'); 
        $info_reply['send_type']=0;
        $send_type=0;
        
         
        //补借条时对方id
        if($info_paper['lender_id']==$user['id']){
          
            $tmp_uid=$info_paper['borrower_id'];
            if($info_reply['is_borrower']==1){
                $info_reply['send_type']=1;
            }
        }elseif($info_paper['borrower_id']==$user['id']){
            $tmp_uid=$info_paper['lender_id'];
            if($info_reply['is_borrower']==0){
                $info_reply['send_type']=1;
            }
        }else{
            $this->error('借条错误');
        }
        $tmp_user=Db::name('user')->where('id='.$tmp_uid)->find();
        
        $info_reply['name']=$tmp_user['user_nickname'];
        $info_reply['avatar']=$tmp_user['avatar'];
        $statuss=config('paper_status');
        $info_paper['status_name']=$statuss[$info_paper['status']];
        $this->assign('info_reply',$info_reply);
        $this->assign('info_paper',$info_paper);
        $this->assign('send_type',$send_type);
        $this->assign('html_title','申请详情');
        
        return $this->fetch();
    }
     /* 申请处理 */
    public function send_affirm(){
        $data=$this->request->param();
        $this->assign($data);
        return $this->fetch();
    }
    //  同意借条
    public function confirm_sure(){
        $data=$this->request->param();
        $this->assign($data);
        return $this->fetch();
    }
   

    /* 申请处理 */
    public function ajax_confirm(){
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $data=$this->request->param();
        $m_reply=Db::name('reply');
        $m_paper=Db::name('paper'); 
        $where_reply=['id'=>$data['id'],'status'=>0,'is_overtime'=>0];
        $info_reply=$m_reply->where($where_reply)->find();
        if(empty($info_reply)){
            $this->error('该申请不存在，或已被处理');
        }
        
        $info_paper=$m_paper->where('oid',$info_reply['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        //判断密码 
        $uid=session('user.id'); 
       
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        //微信通知的内容
        $first='你好，'.$user['user_nickname'];
       
       //判断是借款人发起，还是出借人发起
        if($info_reply['is_borrower']==1 && $info_paper['lender_id']==$user['id']){
           
            //处理人是出借人, 
            $user1=$m_user->where('id',$info_paper['borrower_id'])->find();
            $user2=$user;
            //微信通知的对象
            $openid=$user1['openid'];
            $url0=url('user/info/borrower','',true,true);
            $data_paper=[
                'update_time'=>time(),
                'lender_id'=>$user['id'],
                'lender_idcard'=>$user['user_login'],
                'lender_mobile'=>$user['mobile'],
            ];
        }elseif($info_reply['is_borrower']==0 && $info_paper['borrower_id']==$user['id']){
            //处理人是借款人, 
            $user2=$m_user->where('id',$info_paper['lender_id'])->find();
            $user1=$user; 
            //微信通知的对象
            $openid=$user2['openid'];
            $url0=url('user/info/lender','',true,true);
            $data_paper=[
                'update_time'=>time(),
                'borrower_id'=>$user['id'],
                'borrower_idcard'=>$user['user_login'],
                'borrower_mobile'=>$user['mobile'],
            ];
        }else{
            $this->error('无权操作此该借条');
        }
        
        //判断是否显示同意s
        $data_reply=['update_time'=>$data_paper['update_time']];  
        $reply_types=config('reply_types');
        //驳回
        if($data['op']==0){
            $first.='驳回了你的'.$reply_types[$info_reply['type']].'申请';
            $data_reply['status']=2; 
            if($info_reply['type']=='send'){
                $data_paper['status']=1; 
            }
        }else{
            //同意，处理借条
            $data_reply['status']=1; 
            $first.='同意了你的'.$reply_types[$info_reply['type']].'申请';
            switch($info_reply['type']){
                case 'send':
                    
                    //预期天数归0，计算到期天数
                    $data_paper['overdue_day']=0;
                    $data_paper['expire_day']=bcdiv(($info_paper['end_time']-strtotime(date('Y-m-d'))),86400,0);
                   
                    if($data_paper['expire_day']>=1){
                        $data_paper['status']=4;
                    }elseif($data_paper['expire_day']==0){
                        $data_paper['status']=3;
                    }else{
                        $this->error('借条信息错误或失效',url('user/index/index'));
                    }
                   
                    break;
                case 'delay':
                     
                    //根据延期日期和利率重新计算
                    $data_paper['rate']=$info_reply['rate'];
                    $data_paper['end_time']=$info_reply['day'];
                    if($data_paper['end_time']<=$info_paper['end_time']){
                        $this->error('延期日期无效');
                    }
                    //逾期先归0，再计算到期时间和状态
                    $data_paper['overdue_day']=0;
                   
                    $data_paper['expire_day']=bcdiv(($data_paper['end_time']-strtotime(date('Y-m-d'))),86400,0);
                    
                    if($data_paper['expire_day']>=1){
                        $data_paper['status']=4;
                    }elseif($data['expire_day']==0){
                        $data_paper['status']=3;
                    }else{
                        $data_paper['status']=5;
                        $data_paper['overdue_day']=0-$data_paper['expire_day'];
                    }
                    //计算新的到期利息
                    $days=bcdiv(($data_paper['end_time']-$info_paper['start_time']),86400,0);
                    $data_paper['real_money']=zz_get_money($info_paper['money'],$data_paper['rate'],$days);
                   
                     break;
                case 'back':
                    
                    //要删除paper，增加old,组装数据$info_paper
                    $info_paper['final_money']=$info_reply['final_money'];
                    $info_paper['update_time']=$data_reply['update_time'];
                   
            }
                 
        }
       
        Db::startTrans();
        try {
            //更新申请
            $m_reply->where($where_reply)->update($data_reply);
            //更新借条状态
            if(isset($data_paper)){
                $m_paper->where('id',$info_paper['id'])->update($data_paper);
            }
            //删除paper，增加old,组装数据$info_paper
            if($data['op']==1 ){
                if($info_reply['type']=='back'){
                    $m_paper->where('id',$info_paper['id'])->delete();
                    unset($info_paper['id']);
                    unset($info_paper['status']);
                    unset($info_paper['expire_day']);
                    Db::name('paper_old')->insert($info_paper);
                    //确认还款后更新用户信息
                    $data_user1=['back'=>bcsub($user1['back'],$info_paper['money'],2)];
                    $data_user2=['send'=>bcsub($user2['send'],$info_paper['money'],2)];
                    //计算收益
                    $rates=bcsub($info_paper['final_money'],$info_paper['money'],2);
                    $data_user2['money']=bcadd($user2['money'],$rates,2);
                    $data_user1['money']=bcsub($user1['money'],$rates,2);
                  /*   //判断user1是否有逾期3天
                    if($info_paper['overdue_day']>2){
                        $where_tmp=[
                            'borrower_id'>['eq',$info_paper['borrower_id']],
                            'overdue_day'=>['gt',2],
                        ];
                        $tmp_paper=$m_paper->where($where_tmp)->find();
                        //如果没有逾期超过3天的要回复借条权限
                        if(empty($tmp_paper)){
                            $data_user1['is_paper']=1;
                        }
                    } */

                    $m_user->where('id',$user1['id'])->update($data_user1);
                    $m_user->where('id',$user2['id'])->update($data_user2);
                }elseif($info_reply['type']=='send'){
                    //确认借款后更新用户信息
                    $data_user1=['back'=>bcadd($user1['back'],$info_paper['money'],2)];
                    $data_user2=['send'=>bcadd($user2['send'],$info_paper['money'],2)];
                    //更新接款人数
                    $m_borrowers=Db::name('borrowers');
                    $data_borrowers=['borrower_id'=>$user1['id'],'lender_id'=>$user2['id']];
                    $tmp=$m_borrowers->where($data_borrowers)->find();
                    if(empty($tmp)){
                        $m_borrowers->insert($data_borrowers);
                        $data_user1['borrow_man']=$user1['borrow_man']+1;
                    }
                    //累计借款笔数
                    $data_user1['borrow_num']=$user1['borrow_num']+1; 
                    //borrow_money累计借款
                    $data_user1['borrow_money']=bcadd($user1['borrow_money'],$info_paper['money'],2);
                    
                    //出借人信息
                     //累计出借
                    $data_user2['lender_money']=bcadd($user2['lender_money'],$info_paper['money'],2);
                    $m_user->where('id',$user1['id'])->update($data_user1);
                    $m_user->where('id',$user2['id'])->update($data_user2);
                }
                $user=$m_user->where('id',$uid)->find();
                session('user',$user);
               
            }
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollBack();
            $this->error('操作失败！'.$e->getMessage());
        }
        
        
        $type='msg_send'; 
        $data=[
            $first,
            $info_paper['money'], 
            date('Y-m-d',$info_paper['start_time']),
            date('Y-m-d',$info_paper['end_time']),
            '点击进入'
        ];
        
        $res=zz_wxmsg($openid, $url0, $data, $type);
        if($res['errcode']!=0){
            zz_log($first.'-信息发送失败'.$res['errcode'].'-'.$res['errmsg'],'wx.log');
        } 
        $this->success('数据已更新成功',url('user/index/index'));
    }
    /* 一键催款 */
    public function msg(){
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find(); 
        $date=date('Y-m-d');
       
        if($date==$user['msg_date']){
            $this->error('每天只能催款一次');
        }else{
            $m_user->where('id',$uid)->update(['msg_date'=>$date]); 
        }
        $where=[
            'lender_id'=>['eq',$uid],
            'status'=>['in',[3,5]],
        ];
        $list=Db::name('paper')->where($where)->column('');
        if(empty($list)){
            $this->error('没有到期和逾期的借款用户');
        }
        $ok=0;
        $fail='';
        $url0=url('user/info/borrower','',true,true);
        $type='msg_back';
        
        foreach($list as $k=>$v){
            $data=[
                '你的借款到期了',
                $v['lender_name'],
                $v['money'],
                date('Y-m-d',$v['start_time']),
                date('Y-m-d',$v['end_time']),
                '请尽快还款，点击进入'
            ]; 
            //获取openid
            $borrower=$m_user->where('id',$v['borrower_id'])->find(); 
            $res=zz_wxmsg($borrower['openid'], $url0, $data, $type);
            if($res['errcode']==0){
                $ok++;
            }else{
                $fail.=',用户'.$v['borrower_name'];
                zz_log('用户'.$v['borrower_name'].'催款信息发送失败'.$res['errcode'].'-'.$res['errmsg'],'wx.log');
            }
        }
        if($fail!=''){
            $fail.='催款信息发送失败';
        }
        $this->error('发送催款通知'.$ok.'条'.$fail);
    }
     
}
