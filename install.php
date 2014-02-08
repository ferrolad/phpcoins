<?php

version_compare(PHP_VERSION,'5.5.0','>=') || die('php版本需要5.5.0以上');
class_exists('PDO') || die('pdo扩展需要启用');
extension_loaded('gd') && function_exists('gd_info') || die('gd扩展需要启用');
array_map(function($sql){
    if(!db(trim($sql),[],1)){
      die('db error');
    }
  },explode(';',file_get_contents(ROOT.'/install.sql')));
file_put_contents(ROOT.'/install.lock','');

