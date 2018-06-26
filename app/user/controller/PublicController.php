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

use cmf\controller\HomeBaseController;
use app\user\model\UserModel;
use think\Db;

class PublicController extends HomeBaseController
{
    //生成二维码
    public function qrcode(){
        $url=$this->request->param('url','','trim');
        import('phpqrcode',EXTEND_PATH); 
        $url = urldecode($url);
        \QRcode::png($url, false, QR_ECLEVEL_L,5, 2);
    }
     //借款协议
    public function protocol(){
        $name=$this->request->param('name');
        $protocol=Db::name('guide')->where('name',$name)->find();
        if($name=='borrower'){
            $paper['content']=$protocol['content'];
            $this->assign('info',$paper);
            $this->assign('html_title','借款协议');
            return $this->fetch('info/protocol');
        }
        
        $this->assign('info',$protocol);
        $this->assign('html_title',$protocol['title']);
        return $this->fetch('register/protocol');
        
        
    }
}
