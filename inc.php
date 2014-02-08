<?php

function captcha($code=null){
  if( 0 == func_num_args()){
    require LIB.'/captcha/simple-php-captcha.php';
    $_SESSION['captcha'] = simple_php_captcha();  
    return $_SESSION['captcha']['image_src'];
  }else{
    return strtolower($code) == strtolower($_SESSION['captcha']['code']);
  }
}

function remove_dupchar($str,$char,$time=2){
  return preg_replace_callback('#(['.preg_quote($char).']{'.$time.',})#',
			       function($m){
				 return $m[1]{1};
			       }
			       ,$str);
}

function multi_require($files,$ext='php'){
  $work_dir = empty(dirname(debug_backtrace()[0]['file'])) ? '' : dirname(debug_backtrace()[0]['file']).'/';
  array_map(function($file)use($ext,$work_dir){
      require $work_dir.$file.($ext? '.'.$ext : '');
    },$files);
}

function html($file_name){
  foreach((array)$file_name as $f){
    include HTML.'/'.$f.'.html';
  }
}

function array_pick($keys,$array){
  foreach($keys as $k)
    $pick[$k] = $array[$k];
  return $pick;
}

function config($key='',$value=''){
  $config = read_array(ROOT.'/config.php');
  switch (func_num_args()){
  case 0:
    return $config;
    break;
  case 1:
    return @$config[$key];
    break;
  case 2:
    $config[$key] = $value;
    array_save(ROOT.'/config.php',$config);
    break;
  }
}

function array_save($file_name,$array=[]){
  $code = output_capture(function()use($array){
      var_export($array);
    });
    file_put_contents($file_name,<<<PHP
<?php
		      return $code;
PHP
		      );
  
}

function read_array($file_name){
  $array = include $file_name;
  $array === 1 && $array = [];
  return (array)$array;
}

function output_capture($output_code){
  ob_start();
  $output_code();
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}

function pdo(){
  static $db = null;
  !$db
    && ($db = new PDO('mysql:dbname='.config()['db_name'].';host='.config()['db_host'],config()['db_user'],config()['db_password']))
    && $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION)
    && $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  return $db;
}

function error($msg=''){
  $msg = 'File: '.debug_backtrace()[0]['file'].' Line: '.debug_backtrace()[0]['line'];
  echo is_ajax() ? json_encode(['status'=>0,'msg'=>$msg]) : $msg;
}

function db($prepare='',$params=[],$bool=0){
  if(!is_array($params)){
    $params = [$params];
  }
  if(!$prepare)
    return pdo();
  $q = db()->prepare($prepare);
  $status = $q->execute($params);
  if($bool == 0){
    return $q; 
  }elseif($bool == 1){
    return $status;
  }elseif($bool == 2){
    return pdo();
  }
}

function is_ajax(){
  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function db_clear(){
  array_map(function($table_name){
	db("delete from $table_name");
    },array_column(db("show tables")->fetchAll(),'Tables_in_'.config('db_name')));
}

function times($time,$func){
  for($i=0;$i<$time;$i++){
    $func();
  }
}

function bool2int($data){
  if($data === true){
    return 1;
  }elseif($data === false){
    return 0;
  }elseif(is_array($data)){
    foreach($data as $k=>$v){
      if($v === true){
	$data[$k] = 1;
      }elseif($v === false){
	$data[$k] = 0;
      }elseif(is_array($v)){
	$data[$k] = bool2int($v);
      }
    }
  }
  return $data;
}

function call_user_func_hash($function_name,$hash){
  $arg = [];
  foreach(is_array($function_name)
	  ? (new ReflectionClass($function_name[0]))->getMethod($function_name[1])->getParameters()
	  :(new ReflectionFunction($function_name))->getParameters() as $param){
    $arg[] = @$hash[$param->name];
  }
  return call_user_func_array($function_name,$arg);
}

function google_auth(){
  require_once LIB.'/auth/PHPGangsta/GoogleAuthenticator.php';
  return new PHPGangsta_GoogleAuthenticator();
}

function google_auth_create_secret(){
  return google_auth()->createSecret();
}

function google_auth_qr_url(){
  return preg_replace('#^https#','http',google_auth()->getQRCodeGoogleUrl(config('text_logo'),google_auth_current_user_secret()));
}

function google_auth_code(){
  return google_auth()->getCode(google_auth_current_user_secret());
}

function google_auth_verify($code){
  return google_auth()->verifyCode(google_auth_current_user_secret(),$code,2);
}

function google_auth_current_user_secret(){
  return db("select * from user where id=?",[$_SESSION['user_id']])->fetch()['secret'];
}

function btc(){
  static $btc = null;
  require_once LIB.'/btc/bitcoin.inc';
  $btc = $btc ?: new BitcoinClient(config('btc_protocol'),config('btc_user'),config('btc_password'),config('btc_host'),config('btc_port'),'',0);
  return $btc;
}

function user_info($id=null){
  if(!$id && empty($_SESSION['user_id']))
    return;
  $id = $id ?: $_SESSION['user_id'];
  return array_hash_only(db("select * from user where id=?",[$id])->fetch());
}

function array_hash_only($array){
  foreach($array as $k=>$v){
    if(is_numeric($k))
      unset($array[$k]);
  }
  return $array;
}

function receive_btc(){
  db()->exec('lock tables user write');
  if($received = btc()->getreceivedbyaddress(user_info()['address'],0) - user_info()['received']){
    db("update user set btc=btc+?,received=received+? where id=?",[$received,$received,$_SESSION['user_id']],1);
  }
  db()->exec('unlock tables');
}


function db_lock($tables){
  $tables = is_array($tables) ? $tables : [$tables];
  $tables = join(',', array_map(function($t){
      return $t.' write';
      },$tables));
  db()->exec('set autocommit=0');
  db()->exec('lock tables '.$tables);
}

function db_unlock(){
  db()->exec('commit');
  db()->exec('unlock tables');
}

function db_rollback(){
  db()->exec('rollback');
}

function pager($count,$per_page=20){
  $cur_page = empty($_GET['p']) ? 1 : $_GET['p'];
  $total_page = ceil($count/$per_page);
  if($total_page <= 1)
    return '';
  $page_url = function($p){
    $url = parse_url($_SERVER['REQUEST_URI']);
    $query=[];
    parse_str(@$url['query'],$query);
    $query = array_merge($query,['p'=>$p]);
    return $url['path'].'?'.http_build_query($query);
  };
  $pre_url = $cur_page > 1 ? $page_url($cur_page-1) : '';
  $next_url = $cur_page < $total_page ? $page_url($cur_page+1) : '';
  $ul_start = '<ul class="pager">';
  $ul_end = '</ul>';
  $html = $ul_start
    .($pre_url ? '<li><a href="'.$pre_url.'">上一页</a></li>' : '<li class="disabled"><a href="#">上一页</a></li>')
    .($next_url ? '<li><a href="'.$next_url.'">下一页</a></li>' : '<li class="disabled"><a href="#">下一页</a></li>')
    .$ul_end;
  return $html;
}

function run_deal(){
  db_lock(['user','deal','trade']);
  for(;;){
    $buy = db("select * from trade where type=0 and status=0 order by price desc")->fetch();
    $sell = db("select * from trade where type=1 and status=0 order by price asc")->fetch();
    if($buy && $sell && floatval($buy['price']) - floatval($sell['price']) >= 0){
      $amount =  min($buy['amount']-$buy['deal'],$sell['amount']-$sell['deal']);
      if(!(db("update trade set deal=deal+? where id in (?,?)",[$amount,$buy['id'],$sell['id']],1) &&
	   db("update user set rmb_frozen=rmb_frozen-?,btc=btc+? where id=?",[$amount*$buy['price'],$amount,$buy['user_id']],1) &&
	   db("update user set btc_frozen=btc_frozen-?,rmb=rmb+? where id=?",[$amount,$amount*$sell['price'],$sell['user_id']],1) &&
	   db("insert into deal(buy_trade,sell_trade,ctime,amount)values(?,?,?,?)",[$buy['id'],$sell['id'],time(),$amount],1) &&
	   db("update trade set status=1 where amount=deal",[],1))){
	   db_rollback();
      }
    }else{
      break;
    }
  }
  db_unlock();
}

function btcval($number){
  return floatval(number_format(abs($number),config('btc_decimal'),'.',''));
}

function _btcval(&$number){
  $number = btcval($number);
  return $number;
}

function rmbval($number){
  return floatval(number_format(abs($number),2,'.',''));
}

function _rmbval(&$number){
  $number = rmbval($number);
  return $number;
}

function ohlc(){
  $ohlc = [];
  $o = db("select price,floor(deal.ctime/60) min from deal inner join trade on deal.buy_trade=trade.id inner join (select min(id) id from deal group by floor(ctime/60)) b on deal.id=b.id")->fetchAll();
  $h = db("select max(price) price,floor(deal.ctime/60) min from deal inner join trade on deal.buy_trade=trade.id group by floor(deal.ctime/60)")->fetchAll();
  $l = db("select min(price) price,floor(deal.ctime/60) min from deal inner join trade on deal.buy_trade=trade.id  group by floor(deal.ctime/60)")->fetchAll();
  $c = db("select price,floor(deal.ctime/60) min from deal inner join trade on deal.buy_trade=trade.id  inner join (select max(id) id from deal group by floor(ctime/60)) b on deal.id=b.id")->fetchAll();
  $d = db("select sum(amount) amount,ctime,floor(ctime/60) min from deal group by floor(ctime/60)")->fetchAll();
 
  $o = array_pk($o,'min');
  $h = array_pk($h,'min');
  $l = array_pk($l,'min');
  $c = array_pk($c,'min');
  $d = array_pk($d,'min');
  foreach($o as $v){
    $ohlc[] = [$d[$v['min']]['ctime']*1000,
	       $d[$v['min']]['amount'],
	       $o[$v['min']]['price'],
	       $h[$v['min']]['price'],
	       $l[$v['min']]['price'],
	       $c[$v['min']]['price']];
  }
  return $ohlc;
}

function array_pk($array,$pk){
  $n_array = [];
  foreach($array as $v){
    $n_array[$v[$pk]] = $v;
  }
  return $n_array;
}

function send_mail($email,$title,$body){
  require_once LIB.'/mail/PHPMailerAutoload.php';
  $mail = new PHPMailer();
  $mail->isSMTP();
  $mail->SMTPDebug = 0;
  $mail->Debugoutput = 'html';
  $mail->Host = config('smtp_host');
  $mail->Port = config('smtp_port');
  $mail->SMTPSecure = 'tls';
  $mail->SMTPAuth = true;
  $mail->Username = config('smtp_user');
  $mail->Password = config('smtp_password');
  $mail->setFrom(config('smtp_user'),config('text_logo'));
  $mail->addAddress($email,$email);
  $mail->Subject = $title;
  $mail->msgHTML($body);
  return $mail->send();
}

function array_remove_keys($array,$keys){
  $keys = is_array($keys) ? $keys : [$keys];
  foreach($array as $k=>$v){
    if(in_array($k,$keys))
      unset($array[$k]);
  }
  return $array;
}

function array_remove_number_keys($array){
  foreach($array as $k=>$v){
    if(preg_match('#^[0-9]+$#',$k))
      unset($array[$k]);
  }
  return $array;
}

function array_trim($array){
  foreach($array as $k=>$v){
    $array[$k] = is_array($v) ? array_trim($v) : trim($v);
  }
  return $array;
}

function get_external_ip(){
  return file_get_contents('http://ipecho.net/plain');
}

function date_full($timestamp){
  return date('Ymd H:i:s',$timestamp);
}

function array_map_suf($arr,$suf){
  return array_map(function($v)use($suf){
      return $v.$suf;
    },$arr);
}

function array_map_pre($arr,$pre){
  return array_map(function($v)use($pre){
      return $pre.$v;
    },$arr);
}

function db_curd(){
  array_map(function($t){
      $read = <<<PHP
	   function $t(\$arr){
	if(!is_array(\$arr)){
	  \$arr = [table_pk('$t')=>\$arr];
	}
	return db("select * from $t where ".implode(' and ',array_map_suf(array_keys(\$arr),'=?')),array_values(\$arr))->fetch();
      }
PHP
  ;
    eval($read);
    $add = <<<PHP
      function {$t}_add(\$arr){
      if(db("insert into $t (".implode(',',array_keys(\$arr)).")values(".implode(',',array_fill(0,count(\$arr),'?')).")",array_values(\$arr),1)){
      return db()->lastInsertId();
      }
    }
PHP
      ;
    eval($add);
    $edit = <<<PHP
      function {$t}_edit(\$arr){
      \$sql = "update $t set ".
		rtrim(
		      implode(
			      '',array_map_suf(
					       array_map_pre(
							     array_keys(
									array_remove_keys(
											  \$arr,table_pk('$t')
											  )
									)
							     ,' '),'=?,'
					       )
			      ),','
		      )
	." where ".table_pk('$t')."=?";
      return db(\$sql,
		array_merge(
			    array_values(
					 array_remove_keys(
							   \$arr,table_pk('$t'))),
			    [\$arr[table_pk('$t')]]),1);
    }
PHP
  ;
eval($edit);
$del = <<<PHP
  function {$t}_del(\$pk){
  return db("delete from $t where ".
	    table_pk('$t').
	    '=?',[\$pk],1);
}
PHP
  ;
eval($del);
    },array_column(db("show tables")->fetchAll(),'Tables_in_'.config('db_name')));
}

function table_pk($table_name){
  foreach(db("show columns from $table_name")->fetchAll() as $v){
    if($v['Key'] == 'PRI')
      return $v['Field'];
  }
}

function mid(){
  return intval(@$_SESSION['user_id']);
}

function msg($msg=''){
  $_SESSION['msg'] = $msg;
  jump(url('msg'));
}

function jump($url=''){
  $url = $url ?: $_SERVER['HTTP_REFERER'];
  header('location:'.$url);
  exit;
}

function db_page($sql,$arg=[],$per=20){
  $GLOBALS['pager'] = pager(db(preg_replace('#select .*? from #','select count(*) as count from ',$sql),$arg)->fetch()['count'],$per);
  return db($sql.' limit '.(intval(@$_REQUEST['p']?:1) - 1).','.$per,$arg)->fetchAll();
}

function url($act,$arg=[]){
  if(is_array($act)){
    $arg = $act;
    $act = ACT;
  }
  ADMIN && ($arg['admin'] = 1);
  return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/?'. http_build_query(array_merge(['act'=>$act],$arg));
}

function form_text($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
    return <<<HTML
<div class="form-group">
<label class="col-md-{$arg['col'][0]} control-label" for="{$arg['id']}">
      {$arg['label']}
</label>
<div class="col-md-{$arg['col'][1]}">
<input type="text" class="form-control" id="{$arg['id']}" name="{$arg['id']}" value="{$arg['value']}" />
</div>
</div>
HTML
    ;
}

function form_submit($arg){
  if(!is_array($arg))
    $arg = ['text'=>$arg];
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  $arg['text'] = $arg['text']?:'Submit';
  return <<<HTML
<div class="form-group">
<div class="col-md-{$arg['col'][1]} col-md-offset-{$arg['col'][0]}">
<button class="btn btn-primary">
    {$arg['text']}
</button>
</div>
</div>
HTML
    ;
}

function form_static($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  return <<<HTML
<div class="form-group">
<label class="control-label col-md-{$arg['col'][0]}" for="{$arg['id']}">
    {$arg['label']}
</label>
<div class="col-md-{$arg['col'][1]}">
<p class="form-control-static" id="{$arg['id']}">
    {$arg['value']}
</p>
</div>
</div>
HTML
    ;
}

function form_password($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  return <<<HTML
<div class="form-group">
<label class="col-md-{$arg['col'][0]} control-label" for="{$arg['id']}">
    {$arg['label']}
</label>
<div class="col-md-{$arg['col'][1]}">
<input type="password" class="form-control" id="{$arg['id']}" value="{$arg['value']}" name="{$arg['id']}" />
</div>
</div>
HTML
    ;
}

function form_checkbox($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  $html =  <<<HTML
<div class="form-group">
<label class="col-md-{$arg['col'][0]} control-label">
    {$arg['label']}
</label>
HTML
    ;
  foreach($arg['item'] as $v){
    $checked = $v['checked'] ? 'checked' : '';
    $html .= <<<HTML
<label class="control-label">
<input type="checkbox" name="{$arg['id']}[]" value="{$v['value']}" $checked />
      {$v['label']}
</label>
HTML
      ;
  }
  $html .= <<<HTML
</div>
HTML
    ;
  return $html;
}

function form_textarea($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  $arg['rows'] = $arg['rows'] ?: 3;
  return <<<HTML
<div class="form-group">
<label class="control-label col-md-{$arg['col'][0]}" for="{$arg['id']}">
    {$arg['label']}
</label>
<div class="col-md-{$arg['col'][1]}">
    <textarea class="form-control" id="{$arg['id']}" name="{$arg['id']}" rows="{$arg['rows']}">{$arg['value']}</textarea>
</div>
</div>
HTML
    ;
}

function form_hidden($arg){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  return <<<HTML
<input type="hidden" name="{$arg['id']}" value="{$arg['value']}" id="{$arg['id']}" />
HTML
    ;
}

function form_captcha($arg=null){
  $arg['col'] = $GLOBALS['COL'] =  $arg['col']?:$GLOBALS['COL'];
  $arg['captcha'] = $arg['captcha']?:captcha();
  return <<<HTML
<div class="form-group">
<div class="col-md-{$arg['col'][1]} col-md-offset-{$arg['col'][0]}">
<img src="{$arg['captcha']}">
</div>
</div>
HTML
    ;
}

function output($content){
  echo is_ajax() ? json_encode(bool2int($content)) : $content;
}

function v_email($email){
  return 1 == preg_match('/[a-z0-9]+@[a-z0-9]+\.[a-z0-9]+/',$email);
}

function v_assert($validators){
  $validators = is_array($validators) ? $validators : [$validators];
  foreach($validators as $v){
    if(is_callable($v)){
      if(($msg = $v) !== true){
	v_msg($msg);
      }
    }else{
      if($v !== true){
	v_msg($v);
      }
    }
  }
}

function v_msg($msg){
  output(['msg'=>$msg]);
  exit;
}

function _trim(&$str,$char=" \t\n\r\0\x0B"){
  $str = trim($str,$char);
  return $str;
}

function _intval(&$number){
  $number = intval($number);
  return $number;
}