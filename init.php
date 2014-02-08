<?php
$ONE_DAY_AGO = time() - 3600*24;
require_once LIB.'/fire/fb.php';

date_default_timezone_set('Asia/Shanghai');
db_curd();

if(!file_exists(ROOT.'/install.lock')){
  require ROOT.'/install.php';
}
$_REQUEST = array_trim($_REQUEST);
if(ADMIN && empty($_SESSION['admin']) && !in_array(ACT,['sign_in','do_sign_in'])){
  jump('/?act=sign_in&admin=1');
}
if(!ADMIN && empty(mid()) && !in_array(ACT,['blank','help','index','sign_in','sign_up','password_reset_send','password_reset'])){
  jump('/?act=blank&show=sign_in');
}
$act = ADMIN ? (new admin) : (new act);
if( method_exists($act,ACT)){
  output(call_user_func_hash([$act,ACT],$_REQUEST));
}else{
  html(ACT);
}



