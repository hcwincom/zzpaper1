<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\user\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'user_login' => 'require|unique:user,user_login', 
        'mobile' => 'require|unique:user,mobile',
    ];
    protected $message = [
        'user_login.require' => '身份证号不能为空',
        'user_login.unique'  => '身份证号已存在', 
        'mobile.require' => '手机号不能为空',
        'mobile.unique' => '手机号已存在',
         
    ];

     
}