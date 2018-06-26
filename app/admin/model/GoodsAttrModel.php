<?php

namespace app\admin\model;

use think\Model;
use think\Db;
/*
 * 产品属性对应  */
class GoodsAttrModel extends Model
{
     
    
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
    
    /* 获取所有属性商品库存详情 */
    public function attr_store_all($gname='',$type='',$name=''){
        $sql='select gs.*,ga.attr,ga.unit,ga.sn,g.name as gname,g.cid,c.path,gs.num,gs.time as gstime,s.name as store_name
            from cmf_goods_store as gs  
            left join cmf_store as s on ga.store=s.id
            left join cmf_goods_attr as ga on ga.id=gs.goods
            left join cmf_goods as g on g.id=ga.gid
            left join cmf_cate as c on g.cid=c.id 
            order by gs.store asc,g.cid asc,ga.gid asc ';
         $where=' where 1 ';
         if(!empty($gname)){
             $where.=' and g.name like :gname ';
             $data['gname']='%'.$gname.'%';
         }
         //查询库存，按仓库，
         switch ($type){
             case 'store':$where.=' and gs.store_id= :name ';$data['name']=$name;break;
             case 'store':$where.=' and gs.store_id= :name ';$data['name']=$name;break;
             case 'store':$where.=' and gs.store_id= :name ';$data['name']=$name;break;
             case 'store':$where.=' and gs.store_id= :name ';$data['name']=$name;break;
         }
            $list=Db::query($sql);
         
        return $list;
    }
    //根据规格id得到产品名
    public function get_gname($id){
        $info=Db::name('goods_attr')->alias('ga')
        ->field('ga.*,g.name as gname')
        ->join('cmf_goods g','g.id=ga.gid')
        ->where('ga.id',$id)->find();
        return $info;
    }

}