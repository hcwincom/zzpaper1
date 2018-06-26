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
namespace app\admin\model;

 
use think\Model;
use tree\Tree;

class CateModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    /**
     * 生成分类 select树形结构
     * @param int $selectId 需要选中的分类 id
     * @param int $currentCid 需要隐藏的分类 id
     * @return string
     */
    public function adminCateTree($selectId = 0, $currentCid = 0)
    {
        $where = [];
        if (!empty($currentCid)) {
            $where['id'] = ['neq', $currentCid];
        }
        $cates = $this->order("sort asc")->where($where)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        $newCate = [];
        foreach ($cates as $item) {
            $item['selected'] = $selectId == $item['id'] ? "selected" : "";

            array_push($newCate, $item);
        }

        $tree->init($newCate);
        $str     = '<option value=\"{$id}\" {$selected}>{$spacer}{$name}</option>';
        $treeStr = $tree->getTree(0, $str);

        return $treeStr;
    }

    /**
     * @param int|array $currentIds
     * @param string $tpl
     * @return string
     */
    public function adminCateTableTree($currentIds = 0, $tpl = '')
    {
        $where = [];
//        if (!empty($currentCid)) {
//            $where['id'] = ['neq', $currentCid];
//        }
        $cates = $this->order("sort ASC")->where($where)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCate = [];
        foreach ($cates as $item) {
            $item['insert_time']=date('Y-m-d H:i:s',$item['insert_time']); 
            $item['time']=date('Y-m-d H:i:s',$item['time']); 
            $item['checked'] = in_array($item['id'], $currentIds) ? "checked" : "";
       
            $item['str_action'] = '<a href="' . url("Cate/add", ["parent" => $item['id']]) . '">添加子分类</a>  <a href="' . url("Cate/edit", ["id" => $item['id']]) . '">' . lang('EDIT') . '</a>  <a class="js-ajax-delete" href="' . url("Cate/delete", ["id" => $item['id']]) . '">' . lang('DELETE') . '</a> ';
            array_push($newCate, $item);
        }

        $tree->init($newCate);

        if (empty($tpl)) {
            $tpl = "<tr>
                        <td><input name='sorts[\$id]' type='text' size='3' value='\$sort' class='input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer \$name </td>
                        <td>\$dsc</td>
                        <td>\$insert_time</td>
                        <td>\$time</td>
                        <td>\$str_action</td>
                    </tr>";
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

    /**
     * 添加产品分类
     * @param $data
     * @return bool
     */
    public function addCate($data)
    {
        $result = true; 
        self::startTrans();
        try {
            //在本地添加OK，但是外网就失败，因为path没有默认值
            $this->allowField(true)->save($data);
            $id = $this->id;
            if (empty($data['parent_id'])) {

                $this->where( ['id' => $id])->update(['path' => '0-' . $id]);
            } else {
                $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
                $this->where( ['id' => $id])->update(['path' => "$parentPath-$id"]);

            }
            self::commit();
           
        } catch (\Exception $e) {
            self::rollback(); 
            $result = false; 
        }
        
        return $result;
    }

    public function editCate($data)
    {
        $result = true;

        $id          = intval($data['id']);
        $parentId    = intval($data['parent_id']);
        $oldCate = $this->where('id', $id)->find();

        if (empty($parentId)) {
            $newPath = '0-' . $id;
        } else {
            $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
            if ($parentPath === false) {
                $newPath = false;
            } else {
                $newPath = "$parentPath-$id";
            }
        }

        if (empty($oldCate) || empty($newPath)) {
            $result = false;
        } else {


            $data['path'] = $newPath;
            
            $this->isUpdate(true)->allowField(true)->save($data, ['id' => $id]);

            $children = $this->field('id,path')->where('path', 'like', "%-$id-%")->select();

            if (!empty($children)) {
                foreach ($children as $child) {
                    $childPath = str_replace($oldCate['path'] . '-', $newPath . '-', $child['path']);
                    $this->isUpdate(true)->save(['path' => $childPath], ['id' => $child['id']]);
                }
            } 
        } 
        return $result;
    }


}