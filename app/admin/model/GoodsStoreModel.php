<?php

namespace app\admin\model;

use think\Model;
use think\Db;
/*
 * 产品库存  */
class GoodsStoreModel extends Model
{
     
    
    
    /* 指定仓库的产品库存数 */
    public function store($store_id){
       
         $sql='select gs.*,ga.attr,ga.unit,ga.inprice,ga.outprice,g.name as gname,g.cid,c.path
            from cmf_goods_store as gs
            left join cmf_goods_attr as ga on ga.id=gs.goods
            left join cmf_goods as g on g.id=ga.gid
            left join cmf_cate as c on g.cid=c.id
            where gs.store=?
            order by g.cid asc,ga.gid asc ';
        
        $list=Db::query($sql,[$store_id]);
        return $list; 
    }
    
    
    
     

}