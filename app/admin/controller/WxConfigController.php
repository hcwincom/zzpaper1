<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController;
 
use think\Db;

 
 
class WxConfigController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    
    /**
     *  微信配置
     * @adminMenu(
     *     'name'   => '微信配置',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '微信配置',
     *     'param'  => ''
     * )
     */
    public function index()
    {  
        return $this->fetch();
    }
    
    /**
     * 微信配置编辑1
     * @adminMenu(
     *     'name'   => '微信配置编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '微信配置编辑1',
     *     'param'  => ''
     * )
     */
    function editPost(){
       
        $data0= $this->request->param();
        $data=[
            'wx_appid'=>$data0['wx_appid'],
            'wx_appsecret'=>$data0['wx_appsecret'],
            'msg_send'=>$data0['msg_send'],
            'msg_back'=>$data0['msg_back'],
             
        ];
        
       
        $result=cmf_set_dynamic_config($data);
        if(empty($result)){
            $this->error('修改失败');
           
        }else{
            $data_action=[
                'aid'=>session('ADMIN_ID'),
                'time'=>time(),
                'type'=>'config',
                'ip'=>get_client_ip(),
                'action'=>'编辑微信配置',
            ];
            Db::name('action')->insert($data_action);
            $this->success('修改成功');
        }
        
    }
    /**
     * 清空用户微信绑定
     * @adminMenu(
     *     'name'   => '清空用户微信绑定',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '清空用户微信绑定',
     *     'param'  => ''
     * )
     */
    function clear(){
        
        $id=$this->request->param('id',0,'intval');
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'time'=>time(),
            'type'=>'config',
            'ip'=>get_client_ip(),
            'action'=>'清空用户微信绑定',
        ];
        $where=['user_type'=>2];
        if($id>0){
            $where['id']=$id;
            $data_action['action']='清空用户'.$id.'微信绑定';
        }
        $result=Db::name('user')->where($where)->update(['openid'=>'']);
        if(empty($result)){
            $this->error('未修改数据'); 
        }else{ 
            Db::name('action')->insert($data_action);
            $this->success('修改成功');
        }
        
    }
    /**
     * 更新access_token
     * @adminMenu(
     *     'name'   => '更新access_token',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '更新access_token',
     *     'param'  => ''
     * )
     */
    function token(){
         
        $appid = config('wx_appid');
        $appsecret = config('wx_appsecret');
        $token_time=config('token_time');
        //判断重复任务,手动更新最多1分钟1次
        if((time()-$token_time)<60){
           $this->error('手动更新最多1分钟1次');
        }
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'time'=>time(),
            'type'=>'config',
            'ip'=>get_client_ip(),
            'action'=>'更新access_token',
        ];
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
        $res=zz_curl($url);
        
        if(!empty($res['access_token'])){
            cmf_set_dynamic_config(['access_token'=>$res['access_token']]);
            cmf_set_dynamic_config(['token_time'=>time()]);
            $data_action['action'].='成功'; 
            Db::name('action')->insert($data_action);
            $this->success($data_action['action']);
        }else{
            $data_action['action'].='失败'; 
            $this->error($data_action['action']);
        }
      
    }
    
     
}
