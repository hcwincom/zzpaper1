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

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $where   = ['user_type'=>2];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|mobile']    = ['eq', $keyword];
        }
        $usersQuery = Db::name('user'); 
        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
       
        $page = $list->appends($request)->render();
       
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }
    /**
     * 本站用户删除
     * @adminMenu(
     *     'name'   => '本站用户删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        
        $id = $this->request->param('id', 0, 'intval');
        $count=Db::name('paper')->where('borrower_id',$id)->count();
        if($count>0){
            $this->error('该会员有借款借条未完成，不能删除');
        }
        $count=Db::name('paper')->where('lender_id',$id)->count();
        if($count>0){
            $this->error('该会员有出借借条未完成，不能删除');
        }
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->delete();
            if ($result) {
                $this->success("会员删除成功！");
            } else {
                $this->error('会员删除失败,会员不存在！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }
    
    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("会员拉黑成功！");
            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $this->success("会员启用成功！");
        } else {
            $this->error('数据传入失败！');
        }
    }
    
    /**
     * 用户详情
     * @adminMenu(
     *     'name'   => '用户详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '用户详情',
     *     'param'  => ''
     * )
     */
    public function info()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        $info=Db::name("user")->where(["id" => $id, "user_type" => 2])->find();
        if(empty($info)){
            $this->error('用户不存在');
        }
        
        $info['wx_more']=json_decode($info['more'],true);
        
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * 用户详情执行
     * @adminMenu(
     *     'name'   => '用户详情执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '用户详情执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
       $this->error('暂无此功能');
    }
}
