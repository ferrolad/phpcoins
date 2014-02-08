<?php
class admin{
  function do_sign_in($captcha,$password){
    if(!captcha($captcha)){
      return ['msg'=>'验证码不对'];
    }elseif(config('admin_password') != $password){
      return ['msg'=>'密码不对'];
    }else{
      $_SESSION['admin'] = 1;
      return ['jump'=>url('index')];
    }
  }

  function do_transfer($id){
    transfer_edit(['id'=>$id,'status'=>1]);
    jump();
  }
  
  function do_withdraw($id){
    withdraw_edit(['id'=>$id,'status'=>1]);
    jump();
  }

  function help_del($id){
    help_del($id);
    jump();
  }
  
  function log_out(){
    unset($_SESSION['admin']);
    jump();
  }

  function help_write($title,$body,$sort){
    v_assert([!empty($title)?:'请填写标题',
	      !empty($body)?:'请填写内容']);
    help_add(['title'=>$title,'body'=>$body,'sort'=>_intval($sort)]);
    return ['msg'=>'操作完成','jump'=>url('index',['show'=>'help'])];
  }
  
  function setting($site_name,$text_logo,$smtp_host,$smtp_port,$smtp_user,$smtp_password,$btc_protocol,$btc_user,$btc_password,$btc_host,$btc_port){
    config('site_name',$site_name);
    config('text_logo',$text_logo);
    config('smtp_host',$smtp_host);
    config('smtp_port',$smtp_port);
    config('smtp_user',$smtp_user);
    config('smtp_password',$smtp_password);
    config('btc_protocol',$btc_protocol);
    config('btc_user',$btc_user);
    config('btc_password',$btc_password);
    config('btc_host',$btc_host);
    config('btc_port',$btc_port);
    return ['msg'=>'操作完成'];
  }

  function do_recharge($email,$amount){
    v_assert([!!user(['email'=>$email])?:'没有这个用户',
	      _rmbval($amount)>0?:'请填写有效金额']);
    if(db("update user set rmb=rmb+? where email=?",[$amount,$email],1) 
       && recharge_add(['user_id'=>user(['email'=>$email])['id'],'amount'=>$amount,'ctime'=>time()]))
      return ['msg'=>'操作完成','jump'=>url('index',['show'=>'recharge'])];
  }
}