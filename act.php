<?php
class act{
  function trade_close($id){
    db("update trade set status=-1 where id=? and user_id=?",[$id,mid()]);
    jump(url('my',['show'=>'trade_record']));
  }

  function log_out(){
    unset($_SESSION['user_id']);
    jump();
  }

  function my(){
    html(['header','navbar','my_body','footer']);
    receive_btc();
  }

  function email_validate($key,$email){
    $u = user(['email'=>$email]);
    $key == md5($u['ctime']) && user_edit(['id'=>$u['id'],'email_validated'=>1]);
    msg('验证通过！');
  }

  function blank(){
    html(['header','navbar',$_GET['show'],'footer']);
  }

  function change_uid($uid){
    if(DEV){
      $_SESSION['user_id'] = $uid;
      header('location:/');
    }
  }

  function index(){
    html(['header','navbar','index_body','footer']);
  }

  function sign_up($email,$password,$pin,$confirm_password,$confirm_pin){
    v_assert([!empty($email)?:'请输入邮箱',
	      v_email($email)?:'邮箱格式不对',
	      strlen($password)>=6 ?:'登录密码长度不能小于6位',
	      strlen($pin)>=6 ?:'交易密码长度不能小于6位',
	      $confirm_password == $password ?:'两次输入的登录密码不同',
	      $confirm_pin == $pin ?:'两次输入的交易密码不同',
	      $password != $pin ?:'登录密码和交易密码不能相同']);
    if($_SESSION['user_id'] = user_add(['email'=>$email,
		 'password'=>md5($password),
		 'ctime'=>time(),
		 'secret'=>google_auth_create_secret(),
		 'address'=>btc()->getnewaddress($email),
		 'pin'=>md5($pin)])){
      return ['jump'=>'/'];
    }
  }

  function sign_in($email,$password){
    if($_SESSION['user_id'] = user(['email'=>$email,'password'=>md5($password)])['id']){
      return ['jump'=>'/'];
    }else{
      return ['msg'=>'邮箱或者密码错误'];
    }
  }

  function withdraw($amount,$name,$bank,$card,$auth){
    db_lock(['user','withdraw']);
    v_assert([!empty($amount)?:'请填写提现金额',
	      !empty($name)?:'请填写收款人姓名',
	      !empty($bank)?:'请填写收款银行',
	      !empty($card)?:'请填写收款银行卡号',
	      _rmbval($amount)!=0?:'请输入有效金额',
	      $amount <= floatval(user_info()['rmb']) ?:'提现金额不能大于可用人民币余额']);
    if(user_info()['auth_withdraw']){
      v_assert([google_auth_verify($auth)?:'认证码不对']);
    }
    if(!withdraw_add(['user_id'=>$_SESSION['user_id'],
		      'amount'=>$amount,
		      'name'=>$name,
		      'card'=>$card,
		      'bank'=>$bank,
		      'ctime'=>time()]) ||
       !db("update user set rmb=rmb-?,rmb_frozen=rmb_frozen+? where id=?",[$amount,$amount,mid()],1)){
      db_rollback();
      error();
    }
    db_unlock();
    return ['msg'=>'提现申请已提交','jump'=>'/?act=my'];
  }

  function transfer($address,$amount,$auth){
    db_lock(['user','transfer']);
    v_assert([!empty($address)?:'请填写比特币收款地址',
	      !empty($amount)?:'请填写比特币数量',
	      _btcval($amount)!=0?:'请填写有效数量',
	      $amount <= floatval(user_info()['btc'])?:'转出数量不能大于可用比特币数量']);
    if(user_info()['auth_withdraw']){
      v_assert([!empty($auth)?:'请填写认证码',
		google_auth_verify($auth)?:'认证码不对']);
    }
    if(!transfer_add(['user_id'=>mid(),
		      'address'=>$address,
		      'amount'=>$amount,
		      'ctime'=>time()])||
       !db("update user set btc=btc-?,btc_frozen=btc_frozen+? where id=?",
	   [$amount,$amount,mid()],1)){
	db_rollback();
	error();
    }
    db_unlock();
    return ['msg'=>'转出申请已提交','jump'=>'/'];
  }

  function buy_btc($price,$amount,$pin,$auth){
    db_lock(['user','trade']);
    v_assert([!empty(_rmbval($price))?:'请填写买入价',
	      !empty(_btcval($amount))?:'请填写买入数量',
	      md5($pin)==user_info()['pin']?:'交易密码不对',
	      $price*$amount<=floatval(user_info()['rmb'])?:'人民币余额不足']);
    if(user_info()['auth_trade']){
      v_assert([google_auth_verify($auth)?:'认证码不对']);
    }
    if(!db("update user set rmb=rmb-?,rmb_frozen=rmb_frozen+? where id=?",[$price*$amount,$price*$amount,$_SESSION['user_id']],1)||
       !trade_add(['amount'=>$amount,
		   'price'=>$price,
		   'ctime'=>time(),
		   'user_id'=>mid()])){
      db_rollback();
      error();
    }
    db_unlock();
    run_deal();
    return ['msg'=>'操作成功','jump'=>'/?act=my'];
  }

  function sell_btc($price,$amount,$pin,$auth){
    db_lock(['user','trade']);
    v_assert([_rmbval($price)!=0?:'请填写卖出价',
	      _btcval($amount)!=0?:'请填写卖出数量',
	      md5($pin)==user_info()['pin']?:'交易密码不对',
	      $amount<=floatval(user_info()['btc'])?:'比特币余额不足']);
    if(user_info()['auth_trade']){
      v_assert([google_auth_verify($auth)?:'wrong auth']);
    }
    if(!db("update user set btc=btc-?,btc_frozen=btc_frozen+? where id=?",[$amount,$amount,$_SESSION['user_id']])||
       !trade_add(['amount'=>$amount,
		   'price'=>$price,
		   'ctime'=>time(),
		   'user_id'=>mid(),
		   'type'=>1])){
	db_rollback();
	error();
    }
    db_unlock();
    run_deal();
    return ['msg'=>'操作成功','jump'=>'/?act=my'];
  }

  function change_password($old,$password,$confirm){
    v_assert([md5($old)==user_info()['password']?:'原密码不对',
	      strlen($password)>=6?:'密码长度不能少于6位',
	      $confirm==$password?:'两次填写的密码不同',
	      md5($password)!=user_info()['pin']?:'登录密码不能和交易密码相同']);
    if(user_edit(['id'=>mid(),
		  'password'=>md5($password)])){
      return ['msg'=>'操作成功','jump'=>'/?act=my'];
    }
  }

  function change_pin($old,$pin,$confirm){
    v_assert([md5($old)==user_info()['pin']?:'原交易密码不对',
	      strlen($pin)>=6?:'密码长度不能少于6位',
	      $confirm==$pin?:'两次填写的密码不同',
	      md5($pin)!=user_info()['password']?:'交易密码不能和登录密码相同']);
    if(user_edit(['id'=>mid(),
		  'pin'=>md5($pin)])){
      return ['msg'=>'操作成功','jump'=>'/?act=my'];
    }
  }

  function auth_config($auth,$code){
    $auth = $auth ?: [];
    v_assert([google_auth_verify($code)?:'认证码不对']);
    if(user_edit(['id'=>mid(),
		  'secret_installed'=>1,
		  'auth_trade'=>in_array('trade',$auth)?1:0,
		  'auth_withdraw'=>in_array('withdraw',$auth)?1:0])){
      return ['msg'=>'操作成功','jump'=>'/?act=my'];
    }
  }

  function change_profile($name){
    if(user_edit(['id'=>mid(),
		  'name'=>$name])){
      return ['msg'=>'操作成功'];
    }
  }

  function password_reset_send($email){
    v_assert([!!user(['email'=>$email])?:'邮箱不存在']);
    send_mail($email,'密码重置','<a href="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/?act=blank&show=password_reset&email='.$email.'&key='.md5(db("select * from user where email=?",[$email])->fetch()['password']).'">点击链接去重置密码</a>');
    return ['msg'=>'已发送'];
  }

  function password_reset($email,$password,$pin,$confirm_password,$confirm_pin,$key){
    v_assert([strlen($password)>=6?:'登录密码长度不能少于6位',
	      $password==$confirm_password?:'两次填写的登录密码不同',
	      strlen($pin)>=6?:'交易密码长度不能少于6位',
	      $pin==$confirm_pin?:'两次填写的交易密码不同',
	      $password != $pin ?:'登录密码和交易密码不能相同',
	      md5(user(['email'=>$email])['password'])==$key?:'wrong key']);
    if(user_edit(['id'=>user(['email'=>$email])['id'],
		  'secret_installed'=>0,
		  'auth_trade'=>0,
		  'auth_withdraw'=>0,
		  'password'=>md5($password),
		  'pin'=>md5($pin)])){
      $_SESSION['user_id']=user(['email'=>$email])['id'];
      return ['msg'=>'操作成功','jump'=>'/'];
    }
  }

  function email_validate_send(){
    send_mail(user_info()['email'],'验证邮箱','<a href="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/?act=email_validate&email='.user_info()['email'].'&key='.md5(user_info()['ctime']).'">点击链接验证邮箱</a>');
    return ['msg'=>'已发送'];
  }

  function help(){
    html(['header','navbar','help','footer']);
  }
}
