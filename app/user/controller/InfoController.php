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
use think\Validate;
use sms\Msg;
/* 个人中心 */
class InfoController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        
    }
    /**
     * 用户信息首页 
     */
    public function index()
    {
        
       $this->assign('html_title','个人中心'); 
       $user=Db::name('user')->where('id',session('user.id'))->find();
       session('user',$user);
       return $this->fetch();

    }
    /**
     * 用户手册
     */
    public function guide()
    {
        $list=Db::name('guide')->where('type',0)->order('sort asc,id asc')->column('');
        $this->assign('html_title','用户手册');
        $this->assign('list',$list);
        $this->assign('aa','cccc');
        return $this->fetch();
        
    }
    /* 借出记录 */
    public function lender(){
        $uid=session('user.id');
        $name=$this->request->param('name','');
        $where=[
            'p.lender_id'=>['eq',$uid], 
            'p.status'=>['in',[3,4,5]]
        ];
        if($name!=''){
            $where['p.borrower_name']=['like','%'.$name.'%'];
        }
        $m_paper=Db::name('paper');
 
        $list=$m_paper->alias('p')
        ->join('cmf_user u','u.id=p.borrower_id')
        ->where($where)
        ->order('p.status asc,p.expire_day asc,p.overdue_day asc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar'); 
        unset($where['p.status']);
        
        $list_old=Db::name('paper_old')->alias('p')
        ->join('cmf_user u','u.id=p.borrower_id')
        ->where($where)
        ->order('p.overdue_day asc,p.id desc') 
        ->column('p.*,u.user_nickname as name,u.avatar as avatar'); 
        $this->assign('list',$list);
        
        $this->assign('list_old',$list_old);
        $this->assign('name',$name);
        $this->assign('html_title','出借记录'); 
     
        return $this->fetch();
    }
    /* 借款记录 */
    public function borrower(){
        
        $uid=session('user.id');
        $name=$this->request->param('name','');
        $where=[
            'p.borrower_id'=>['eq',$uid],
            'p.status'=>['in',[3,4,5]]
        ];
        if($name!=''){
            $where['p.lender_name']=['like','%'.$name.'%'];
        }
        $m_paper=Db::name('paper');
        
        $list=$m_paper->alias('p')
        ->join('cmf_user u','u.id=p.lender_id')
        ->where($where)
        ->order('p.status asc,p.expire_day asc,p.overdue_day asc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar');
        unset($where['p.status']);
        
        $list_old=Db::name('paper_old')->alias('p')
        ->join('cmf_user u','u.id=p.lender_id')
        ->where($where)
        ->order('p.overdue_day asc,p.id desc')
        ->column('p.*,u.user_nickname as name,u.avatar as avatar');
        $this->assign('list',$list);
        
        $this->assign('list_old',$list_old);
        $this->assign('name',$name);
        $this->assign('html_title','借款记录');
        
        return $this->fetch();
        
    }
    /* 借款详情 */
    public function paper(){
        $oid=$this->request->param('oid');
        $where_paper=['oid'=>['eq',$oid]];
        $where_paper['status']=['in',[3,4,5]];
        $paper=Db::name('paper')->where($where_paper)->find();
        if(empty($paper)){
            $this->redirect(url('paper_old',['oid'=>$oid]));
        }
        $statuss=config('paper_status');
        $paper['status_name']=$statuss[$paper['status']];
        //未完成的计算最终还款金额
        $paper['final_money']=zz_get_money_overdue($paper['real_money'],$paper['money'],config('rate_overdue'),$paper['overdue_day']);
       
        $replys=Db::name('reply')->where('oid',$paper['oid'])->order('id desc')->column('');
        $uid=session('user.id');
        //如果是借款人操作则back==0 
        if($paper['lender_id']==$uid){
            $paper['back']=1;
            $tmp_uid=$paper['borrower_id'];
        }elseif($paper['borrower_id']==$uid){
            $paper['back']=0;
            $tmp_uid=$paper['lender_id'];
        }else{
            $this->error('数据错误');
        }
        $tmp_user=Db::name('user')->where('id='.$tmp_uid)->find();
        $paper['name']=$tmp_user['user_nickname'];
        $paper['avatar']=$tmp_user['avatar'];
        $this->assign('paper',$paper);
        $this->assign('replys',$replys);
        $this->assign('reply_status',config('reply_status'));
        $this->assign('reply_types',config('reply_types'));
        $this->assign('html_title','借条详情');
        return $this->fetch();
        
    }
    /* 借款详情 */
    public function paper_old(){
        $oid=$this->request->param('oid');
        $where_paper=['oid'=>['eq',$oid]];
    
        $paper=Db::name('paper_old')->where($where_paper)->find();
        if(empty($paper)){
            $this->error('借条错误，请刷新'); 
        }else{
            $paper['status_name']='已还款结束';
        }
        $replys=Db::name('reply')->where('oid',$paper['oid'])->order('id desc')->column('');
        $uid=session('user.id');
        
        //如果是借款人操作则back==0
        if($paper['lender_id']==$uid){
            $paper['back']=1;
            $tmp_uid=$paper['borrower_id'];
        }elseif($paper['borrower_id']==$uid){
            $paper['back']=0;
            $tmp_uid=$paper['lender_id'];
        }else{
            $this->error('数据错误');
        }
        $tmp_user=Db::name('user')->where('id='.$tmp_uid)->find();
        $paper['name']=$tmp_user['user_nickname'];
        $paper['avatar']=$tmp_user['bavatar'];
        $this->assign('paper',$paper);
        $this->assign('replys',$replys);
        $this->assign('reply_status',config('reply_status'));
        $this->assign('reply_types',config('reply_types'));
        $this->assign('html_title','借条详情');
        return $this->fetch();
        
    }
    
    /* 提交申请 */
    public function ajax_reply(){
        
        $data=$this->request->param('');
        $m_reply=Db::name('reply');
        $m_paper=Db::name('paper');
        
        $info_paper=$m_paper->where('oid',$data['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        $uid=session('user.id');
        switch ($data['type']){
            case 'delay':
                $first='申请延期';
               //处理延期期限，确保没问题,最少延期1天
                $data['day']=strtotime(date('Y-m-d',strtotime($data['day'])));
                if($data['day']<=($info_paper['end_time']+86399)){
                    $this->error('延期时间选择错误');
                }
                //延期默认为原利率
                $data['rate']=$info_paper['rate'];
                /* if(preg_match('/^\d{1,2}$/', $data['rate'])!=1){
                    $this->error('新利率错误');
                }
                if(!in_array($data['rate'], config('rate'))){
                    $this->error('利率不支持，请参考补借页面的利率');
                } */
                break;
            case 'back':
                $first='申请还款';
                $tmp=zz_get_money_overdue($info_paper['real_money'], $info_paper['money'], config('rate_overdue'),$info_paper['overdue_day']);
                if($tmp!=$data['final_money']){
                    $this->error('还款信息错误',url('user/info/index'));
                }
                break;
            default:
                $this->error('提交信息错误',url('user/info/index'));
        }
        
        
        //判断密码
       
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        unset($data['psw']);
         
        if($info_paper['lender_id']==$uid){
            $data['is_borrower']=0;
            $user1=$m_user->where('id',$info_paper['borrower_id'])->find();
        }elseif($info_paper['borrower_id']==$uid){
            $data['is_borrower']=1;
            $user1=$m_user->where('id',$info_paper['lender_id'])->find();
        }else{
            $this->error('借条信息错误',url('user/info/index'));
        }
        $first='你好，'.$user['user_nickname'].$first;
        //出借人主动点击已还款
        if($uid==$info_paper['lender_id'] && $data['type']=='back'){
            Db::startTrans();
            try {
                $m_paper->where('id',$info_paper['id'])->delete();
                $info_paper['final_money']=$data['final_money'];
                $info_paper['update_time']=time();
                unset($info_paper['id']);
                unset($info_paper['status']);
                unset($info_paper['expire_day']);
                Db::name('paper_old')->insert($info_paper);
                //确认还款后更新用户信息
                $user2=$user;
                $data_user1=['back'=>bcsub($user1['back'],$info_paper['money'],2)];
                $data_user2=['send'=>bcsub($user2['send'],$info_paper['money'],2)];
                //计算收益
                $rates=bcsub($info_paper['final_money'],$info_paper['money'],2);
                $data_user2['money']=bcadd($user2['money'],$rates,2);
                $data_user1['money']=bcsub($user1['money'],$rates,2);
                $m_user->where('id',$user1['id'])->update($data_user1);
                $m_user->where('id',$user2['id'])->update($data_user2);
               
            } catch (\Exception $e) {
                Db::rollBack();
                $this->error('操作失败！'.$e->getMessage());
            }
            
            Db::commit(); 
            $type='msg_send';
            $first='你好，'.$user2['user_nickname'].'确认了你的还款';
            $data=[
                $first,
                $info_paper['money'],
                date('Y-m-d',$info_paper['start_time']),
                date('Y-m-d',$info_paper['end_time']),
                '点击进入'
            ];
            
            $res=zz_wxmsg($user2['openid'], url('user/info/borrower','',true,true), $data, $type);
            if($res['errcode']!=0){
                zz_log($first.'-信息发送失败'.$res['errcode'].'-'.$res['errmsg'],'wx.log');
            } 
            $this->success('已确认还款',url('user/index/index'));
        }
        
        $data['insert_time']=time();
        $data['update_time']=$data['insert_time'];
        $id=$m_reply->insertGetId($data); 
        if($id>=1){
            //申请发送申请
            $type='msg_send'; 
            $data=[
                $first,
                $info_paper['money'],
                date('Y-m-d',$info_paper['start_time']),
                date('Y-m-d',$info_paper['end_time']),
                '点击进入'
            ];
            
            $res=zz_wxmsg($user1['openid'], url('user/index/index','',true,true), $data, $type);
            if($res['errcode']!=0){
                zz_log($first.'-信息发送失败'.$res['errcode'].'-'.$res['errmsg'],'wx.log');
            } 
            $this->success('申请提交成功,请尽快联系对方确认，否则该申请将在第三日凌晨过期！',url('user/info/paper',['id'=>$info_paper['id']]));
        }else{
            $this->error('申请提交失败');
        }
    }
    /* 借款协议 */
    public function protocol(){
        $oid=$this->request->param('oid');
        $where_paper=['oid'=>['eq',$oid]];
        $paper=Db::name('paper_old')->where($where_paper)->find();
        if(empty($paper)){
            $where_paper['status']=['in',[3,4,5]];
            $paper=Db::name('paper')->where($where_paper)->find();
            if(empty($paper)){
                $this->error('此借条不存在');
            }
            $statuss=config('paper_status');
            $paper['status_name']=$statuss[$paper['status']];
            //未完成的计算最终还款金额
            $paper['final_money']=zz_get_money_overdue($paper['real_money'],$paper['money'],config('rate_overdue'),$paper['overdue_day']);
            
        }else{
            $paper['status_name']='已还款结束';
        }
        $paper['borrower_idcard']='**************'.substr($paper['borrower_idcard'], -4);
        $paper['lender_idcard']='**************'.substr($paper['lender_idcard'], -4);
        $protocol=Db::name('guide')->where('name','borrower')->find();
        $paper['content']=$protocol['content'];
        $this->assign('info',$paper); 
        $this->assign('html_title','借款协议');
        return $this->fetch();
        
    }
    
    /*绑定信息*/
    public function bind(){
        $this->assign('html_title','绑定信息');
        return $this->fetch();
    }
    /* qq */
    public function qq(){
        $this->assign('html_title','修改QQ号');
        return $this->fetch();
    }
    
    /* 修改qq */
    public function ajax_qq(){
          
        $data=$this->request->param('');
        
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        } 
        Db::name('user')->where('id',$uid)->update(['qq'=>$data['qq']]);
        session('user.qq',$data['qq']);
        $this->error('修改成功',url('user/info/index'));
    }
    /* weixin */
    public function weixin(){
        $this->assign('html_title','修改微信号');
        return $this->fetch();
    }
    
    /* 修改weixin */
    public function ajax_weixin(){
        
        $data=$this->request->param('');
        
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        Db::name('user')->where('id',$uid)->update(['weixin'=>$data['weixin']]);
        session('user.weixin',$data['weixin']);
        $this->error('修改成功',url('user/info/index'));
    }
    /* 头像 */
    public function avatar(){
        $this->assign('html_title','换头像');
        return $this->fetch();
    }
    /* 头像修改 */
    public function ajax_avatar(){
         
        if(empty($_FILES['avatar1'])){
            $this->error('请选择图片');
        }
        $file=$_FILES['avatar1'];
       
        if($file['error']==0){
            if($file['size']>config('avatar_size')){
                $this->error('文件超出大小限制');
            }
            $avatar='avatar/'.md5(session('user.user_login')).'.jpg';
            $path=getcwd().'/upload/';
           
            $destination=$path.$avatar;
            if(move_uploaded_file($file['tmp_name'], $destination)){
                $avatar=zz_set_image($avatar,$avatar,100,100,6);
                if(is_file($path.$avatar)){ 
                    $this->success('上传成功',url('user/info/index'));
                }else{
                    $this->error('头像修改失败');
                }
            }else{
                $this->error('文件上传失败');
            }
        }else{
            $this->error('文件传输失败');
        }
    }
     
    /* 实名认证 */
    public function name(){
        
        $this->assign('html_title','实名认证');
        return $this->fetch();
    }
    
    /* 实名认证 */
    public function ajax_name(){
        $data=$this->request->param('');
        $rules = [
            'psw' => 'require|number|length:6', 
            'name'=>'require|chs|min:2',
        ]; 
        
        $validate = new Validate($rules);
        $validate->message([
            'psw.require' => '密码为6位数字', 
            'psw.length'     => '密码为6位数字', 
            'name.chs'=>'请填写真实姓名',
            'name.min'=>'请填写真实姓名',
            'name.require'=>'请填写真实姓名',
        ]);
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
         
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        //已认证
        if($user['is_name']==1){
            session('user',$user);
            $this->error('已认证',url('user/info/index'));
        }
         
        //
        import('idcard1',EXTEND_PATH);
        $idcard1= new \Idcard1();
        if(($idcard1->validation_filter_id_card($data['idcard']))!==true){
            $this->error('身份证号码非法!');
        }
        $tmp=$m_user->where(['user_login'=>['eq',$data['idcard']],'id'=>['neq',$uid]])->find();
        if(!empty($tmp)){
            $this->error('身份证号码已被占用');
        }
        $data_user=['user_login'=>$data['idcard'],'user_nickname'=>$data['name'],'is_name'=>1];
        try { 
            $m_user->where('id',$uid)->update($data_user);
        } catch (\Exception $e) {
            $this->error('认证失败，请检查身份信息');
        }
        $user=$m_user->where('id',$uid)->find();
        session('user',$user);
        $this->success('认证成功',url('user/info/index'));
        
    }
    /* 修改密码*/
    public function psw(){
        $this->assign('html_title','修改密码');
        return $this->fetch();
    }
    /* 修改密码*/
    public function ajax_psw(){
        $data=$this->request->param('');
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw0']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        //修改密码
        if(preg_match(config('reg_psw'), $data['psw'])==1){
            $m_user->where('id',$uid)->update(['user_pass'=>cmf_password($data['psw'])]);
            $this->success('修改成功',url('user/info/index'));
        }
        $this->error('修改失败');
        
    }
    /* 修改手机号*/
    public function mobile(){
        $this->assign('html_title','修改手机号');
        return $this->fetch();
    }
    /* 修改手机号*/
    public function ajax_mobile(){
        $data=$this->request->param('');
        $validate = new Validate([
             
            'code'  => 'require|number|length:6',
            'tel' => 'require|number|length:11',
            'psw' => 'require|number|length:6',
        ]);
        $validate->message([
            'tel.require'           => '手机号码错误',
            'tel.number'           => '手机号码错误',
            'tel.length'           => '手机号码错误', 
            'code.require'           => '短信验证码错误',
            'code.number'           => '短信验证码错误',
            'code.length'           => '短信验证码错误',
            'psw.require' => '密码为6位数字',
            'psw.number' => '密码为6位数字',
            'psw.length' => '密码为6位数字', 
        ]);
        
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        } 
       
        if (preg_match(config('reg_mobile'), $data['tel'])) {
            $uid=session('user.id');
            $m_user=Db::name('user');
            //判断手机号
            $tmp=$m_user->where('mobile',$data['tel'])->find();
            if(!empty($tmp)){
                $this->error("您的手机号已存在");
            }
            //判断密码
            $user=$m_user->where('id',$uid)->find(); 
            $result=zz_psw($user, $data['psw']);
            if(empty($result[0])){
                $this->error($result[1],$result[2]);
            }
            //短信验证码
            $msg=new Msg();
            $res=$msg->verify($data['tel'],$data['code']);
            if($res!=='success'){
                $this->error($res);
            } 
            $m_user->where('id',$uid)->update(['mobile'=>$data['tel']]);
            session('user.mobile',$data['tel']);
            $this->success('手机号更改成功',url('user/info/index'));
        } else {
            $this->error("您输入的手机号格式错误");
        }
         
    }
}
