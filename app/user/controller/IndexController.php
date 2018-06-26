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

class IndexController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    /**
     * 前台用户首页 ,待确认申请
     */
    public function index()
    {
        $uid=session('user.id');
        $user=Db::name('user')->where('id',$uid)->find();
        session('user',$user);
       $list_reply=Db::name('reply')
       ->alias('r')
       ->field('r.*,p.money as p_money,p.rate as p_rate,u1.user_nickname as bname,u1.avatar as bavatar,u2.user_nickname as lname,u2.avatar as lavatar')
       ->join('cmf_paper p','p.oid=r.oid')
       ->join('cmf_user u1','u1.id=p.borrower_id')
       ->join('cmf_user u2','u2.id=p.lender_id')
       ->where(['r.is_overtime'=>0,'r.status'=>0,'p.borrower_id|p.lender_id'=>$uid]) 
       ->order('id desc')
       ->select();
      $tmp=[];
       //判断申请人是借款人还是出借人,给前台要显示的值赋值
       foreach($list_reply as $k=>$v){ 
           $tmp[$k]=$v;
           if($v['is_borrower']==1){
               $tmp[$k]['name']=$v['bname'];
               $tmp[$k]['avatar']=$v['bavatar'];
           }else{
               $tmp[$k]['name']=$v['lname'];
               $tmp[$k]['avatar']=$v['lavatar'];
           }
       }
      
       $list_reply=$tmp;
       $this->assign('list_reply',$list_reply);
       $this->assign('reply_types',config('reply_types'));
       $this->assign('html_page','index'); 
       $this->assign('html_title','首页');
       return $this->fetch();

    }
    /**
     * 失信大厅
     */
    public function overdue()
    {
        
        $list_overdue=Db::name('paper') 
        ->where(['status'=>5])
        ->order('overdue_day asc,id desc')
        ->limit(0,config('mobile_page'))
        ->column('');
        
        $this->assign('list_overdue',$list_overdue); 
        $this->assign('html_page','overdue'); 
        $this->assign('html_title','失信大厅');
        return $this->fetch();
        
    }
    /**
     * 失信大厅ajax追加数据
     */
    public function ajax_overdue()
    {
        
        $page_list=config('mobile_page');
        $page=$this->request->param('page',1,'intval');
        $list_overdue=Db::name('paper')
        ->where(['status'=>5])
        ->order('overdue_day asc,id desc')
        ->limit(($page-1)*$page_list,$page_list)->column('');
        $this->success('返回成功','',$list_overdue);
         
        
    }
    /**
     * 逾期追回
     */
    public function overdue_old()
    {
        
        $list_overdue_old=Db::name('paper_old')
        ->where(['overdue_day'=>['gt',0]])
        ->order('overdue_day asc,id desc')
        ->limit(0,config('mobile_page'))
        ->column('');
        
        $this->assign('list_overdue_old',$list_overdue_old);
        $this->assign('html_page','overdue_old'); 
        $this->assign('html_title','逾期追回');
        return $this->fetch();
        
    }
    /**
     * 失信大厅ajax追加数据
     */
    public function ajax_overdue_old()
    {
        
        $page_list=config('mobile_page');
        $page=$this->request->param('page',1,'intval');
        $list_overdue_old=Db::name('paper_old')
        ->where(['overdue_day'=>['gt',0]])
        ->order('overdue_day asc,id desc')
        ->limit(($page-1)*$page_list,$page_list)->column('');
        $this->success('返回成功','',$list_overdue_old);
        
        
    }
    /**
     * 前台ajax 判断用户登录状态接口
     */
    function isLogin()
    {
        if (cmf_is_user_login()) {
            $this->success("用户已登录",null,['user'=>cmf_get_current_user()]);
        } else {
            $this->error("此用户未登录!");
        }
    }

    /**
     * 退出登录
    */
    public function logout()
    {
        session("user", null);//只有前台用户退出
        $this->redirect(url('user/login/login'));
    }

}
