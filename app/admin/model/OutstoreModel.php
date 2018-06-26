<?php

namespace app\admin\model;

use think\Model;
use think\Db;
/*
 * 产品出库  */
class OutstoreModel extends Model
{
    /* 获取产品属性关联信息  goods_attr($id,$aname,$gname)*/
    public function update_info($oid){
        //先看状态，已提交的不能修改
        $info=Db::name('outstore_info')->where('oid',$oid)->find();
        if(empty($info) || $info['status']>0){
            return 0;
        }
        $list=Db::name('outstore')->where('oid',$oid)->select();
        $count=0;
        $num=0;
        $cprice=0;
        foreach($list as $v){
            $count++;
            $num+=$v['num'];
            $cprice=bcadd($cprice,$v['cprice'],2);
        }
       
        $data_m1=[
            'count'=>$count,
            'num'=>$num,
            'cprice'=>$cprice,
            'time'=>time(),
        ];
        Db::name('outstore_info')->where('oid',$oid)->update($data_m1);
        return $data_m1;
    }
    
    /* 获取属性详情 */
    public function attr($id){
        $sql='select ga.*,g.name as gname,c.name as cname,g.cid
            from cmf_goods_attr as ga
            left join cmf_goods as g on g.id=ga.gid
            left join cmf_cate as c on c.id=g.cid';
        
        $info=Db::query($sql.' where ga.id=? limit 1',[$id]); 
        return $info[0];
    }
    
    /* 获取所有属性详情 */
    public function attr_all(){
        $sql='select ga.*,g.name as gname,g.cid,c.path
            from cmf_goods_attr as ga
            left join cmf_goods as g on g.id=ga.gid
            left join cmf_cate as c on g.cid=c.id
            order by g.cid asc,ga.gid asc ';
        
        $list=Db::query($sql);
        return $list;
    }
    
    

}