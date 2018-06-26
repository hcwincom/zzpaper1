<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController;
 
use think\Db;

  
class ConfigController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    
    /**
     *  网站配置
     * @adminMenu(
     *     'name'   => '网站配置',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '网站配置',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        
        $info=[
            'zztitle'=>config('zztitle'),
            'rate'=>implode('-',config('rate')),
            'use'=>implode('-',config('use')),
            'tel'=>config('tel'),
            'rate_overdue'=>config('rate_overdue'),
            'company'=>config('company'),
            
        ];
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    
    /**
     * 网站配置编辑1
     * @adminMenu(
     *     'name'   => '网站配置编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站配置编辑1',
     *     'param'  => ''
     * )
     */
    function editPost(){
       
        $data= $this->request->param();
        $data['rate']=explode('-',$data['rate']);
        foreach($data['rate'] as $k=>$v){
            if($v<=0 && $v!=='0'){
                $this->error('利率只能为大于等于0的整数，用-分隔');
            }
        }
        $data['use']=explode('-',$data['use']);
        foreach($data['use'] as $k=>$v){
            if(empty($v)){
               unset($data[$k]);
            }
        }
       
        $result=cmf_set_dynamic_config($data);
        if(empty($result)){
            $this->error('修改失败');
           
        }else{
            $data_action=[
                'aid'=>session('ADMIN_ID'),
                'time'=>time(),
                'type'=>'config',
                'ip'=>get_client_ip(),
                'action'=>'编辑网站配置',
            ];
            Db::name('action')->insert($data_action);
            $this->success('修改成功',url('index'));
        }
        
    }
     
     
}
