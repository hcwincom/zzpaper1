<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController;
use app\admin\model\CateModel;
use think\Db;

 
/**
 * Class CountController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '数据统计',
 *     'action' => 'default',
 *     'parent' => '',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   => '',
 *     'remark' => '数据统计'
 * )
 */
class CountController extends AdminBaseController
{
  
    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    /**
     * 12月统计 
     * @adminMenu(
     *     'name'   => '12月统计',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '12月统计',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        //新增未完成借条
        $where_new=['status'=>['in',[3,4,5]]];
        $where_user=['user_type'=>['eq',2]];
        $m1=Db::name('paper');
        $m2=Db::name('paper_old');
        $m_user=Db::name('user');
        $count1=[];
        $count2=[];
        $users=[];
        //计算月份
        $time=time();
        $date=getdate($time);
        $year=$date['year'];
        $mon=$date['mon'];
        
        
        $times[13]=$time;
        
        
        //计算前12个月每月的数据
        for($i=12;$i>0;$i--){
            
            $labels[$i]=$year.'-'.$mon;
            //stime用于datetime格式的数据库计算
            $stime=$labels[$i].'-01';
            if($i==12){
                $stime1=date('Y-m-d',$time);
            }else{
                $stime1=$labels[$i+1].'-01';
            }
            
            $times[$i]=strtotime($stime);
            $where_user['create_time']=['between',[$times[$i],$times[$i+1]]]; 
            $users[$i]=$m_user->where($where_user)->count();
             
            // 未还款借条借款
            $where_new['insert_time']=array('between',array($times[$i],$times[$i+1]));
            $count1['order'][$i]=$m1->where($where_new)->count();
            $count1['money'][$i]=$m1->where($where_new)->sum('money');
            if(empty($count1['money'][$i])){
                $count1['money'][$i]=0;
            }
            //已还款借条
            $where_new1=['insert_time'=>array('between',array($times[$i],$times[$i+1]))];
            $tmp1=$m2->where($where_new1)->count();
            $tmp2=$m2->where($where_new1)->sum('money');
            if(empty($tmp2)){
                $tmp2=0;
            }
            //已还款和未还款的借条相加
            $count1['order'][$i]+=$tmp1;
            $count1['money'][$i]+=$tmp2;
            //还款
            $where_old=['update_time'=>array('between',array($times[$i],$times[$i+1]))];
            $count2['order'][$i]=$m2->where($where_old)->count();
            $count2['money'][$i]=$m2->where($where_old)->sum('final_money');
            if(empty($count2['money'][$i])){
                $count2['money'][$i]=0;
            }
            
            $mon--;
            if($mon==0){
                $year--;
                $mon=12;
            }
        }
      
        //总订单，总用户
        $where_user['create_time']=['between',[$times[1],$times[13]]]; 
        $users[0]=$m_user->where($where_user)->count();
        
        $where_new['insert_time']=array('between',array($times[1],$times[13]));
        $count1['order'][0]=$m1->where($where_new)->count();
        $count1['money'][0]=$m1->where($where_new)->sum('money');
        $where_new1=['insert_time'=>array('between',array($times[1],$times[13]))];
        $tmp1=$m2->where($where_new1)->count();
        $tmp2=$m2->where($where_new1)->sum('money');
        if(empty($count1['money'][0])){
            $count1['money'][0]=0;
        }
        if(empty($tmp2)){
            $tmp2=0;
        }
        //已还款和未还款的借条相加
        $count1['order'][0]+=$tmp1;
        $count1['money'][0]+=$tmp2;
        
        $where_old=['update_time'=>array('between',array($times[1],$times[13]))];
        $count2['order'][0]=$m2->where($where_old)->count();
        $count2['money'][0]=$m2->where($where_old)->sum('final_money');
        if(empty($count2['money'][0])){
            $count2['money'][0]=0;
        }
         
        $this->assign('labels',$labels);
        $this->assign('count1',$count1);
        $this->assign('count2',$count2);
        $this->assign('users',$users);
        return $this->fetch();
    }
    /**
     * 统计查询
     * @adminMenu(
     *     'name'   => '统计查询',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '统计查询',
     *     'param'  => ''
     * )
     */
    public function search()
    {
      
        $data=$this->request->param();
        $where=[];
        if(empty($data['borrower_idcard'])){
            $data['borrower_idcard']='';
        }else{
            $where['borrower_idcard']=['eq',$data['borrower_idcard']];
        }
        if(empty($data['lender_idcard'])){
            $data['lender_idcard']='';
        }else{
            $where['lender_idcard']=['eq',$data['lender_idcard']];
        }
        if(empty($data['start_time'] )){
            $data['start_time']='';
        }else{
            $data['start_time']=$data['start_time'];
            $start_time0=strtotime($data['start_time']);
        }
        if(empty($data['end_time'] )){
            $data['end_time']='';
        }else{
            $data['end_time']=$data['end_time'];
            $end_time0=strtotime($data['end_time']);
        }
        
        if(isset($start_time0)){
            if(isset($end_time0)){
                if($start_time0>=$end_time0){
                    $this->error('起始时间不能大于等于结束时间',url('search'));
                }else{
                    $where['insert_time']=['between',[$start_time0,$end_time0]];
                }
            }else{
                $where['insert_time']=['egt',$start_time0];
            }
        }elseif(isset($end_time0)){
            $where['insert_time']=['elt',$end_time0];
        }
        $m1=Db::name('paper');
        $m2=Db::name('paper_old');
        $m_user=Db::name('user');
        $count=[];
        
        $where_user=['user_type'=>2];
        $where_back=$where;
        $where_old=$where;
        if(!empty($where['insert_time'])){
            $where_user['create_time']=$where['insert_time']; 
            $where_back['update_time']=$where['insert_time'];  
            unset($where_back['insert_time']);
        }
        
        $count['user']=$m_user->where($where_user)->count();
        //3正在出借,4今日到期,5逾期
        $where['status']=['eq',3]; 
        $count['intime_count']=$m1->where($where)->count();
        $count['intime_money']=$m1->where($where)->sum('money');
        $where['status']=['eq',4]; 
        $count['ontime_count']=$m1->where($where)->count();
        $count['ontime_money']=$m1->where($where)->sum('money'); 
        $where['status']=['eq',5];  
        $count['overdue1_count']=$m1->where($where)->count();
        $count['overdue1_money']=$m1->where($where)->sum('money'); 
        
        //已还款无逾期 
        $where_old['overdue_day']=['eq',0]; 
        $count['old0_count']=$m2->where($where_old)->count();
        $count['old0_money']=$m2->where($where_old)->sum('final_money'); 
        //逾期还款
        $where_old['overdue_day']=['gt',0]; 
        $count['old1_count']=$m2->where($where_old)->count();
        $count['old1_money']=$m2->where($where_old)->sum('final_money');
        
        //借款统计
        $count['send_count']=$count['intime_count']+$count['ontime_count']+ $count['overdue1_count']+ $count['old0_count']+$count['old1_count'];
        $count['send_money']=$count['intime_money']+$count['ontime_money']+ $count['overdue1_money']+ $count['old0_money']+$count['old1_money'];
         
        //还款 
        $where_back['overdue_day']=['eq',0];
        
        $count['back0_count']=$m2->where($where_back)->count();
        $count['back0_money']=$m2->where($where_back)->sum('final_money'); 
        $where_back['overdue_day']=['gt',0];
        $count['back1_count']=$m2->where($where_back)->count();
        $count['back1_money']=$m2->where($where_back)->sum('final_money'); 
        //还款统计
        $count['back_count']=$count['back0_count']+$count['back1_count'];
        $count['back_money']=$count['back0_money']+$count['back1_money'];
        
        $this->assign('data',$data);
        $this->assign('count',$count);
        
        return $this->fetch();
    }
    
    
    
}
