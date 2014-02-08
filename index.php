<?php
define('DEV',0);
DEV || error_reporting(0);
@session_start();
define('ROOT',dirname(__FILE__));
define('LIB',dirname(__FILE__).'/lib');
define('ADMIN',empty($_REQUEST['admin']) ? 0 : 1);
define('HTML',ROOT.'/'.(ADMIN ? 'back' : 'front'));
define('ACT',empty($_REQUEST['act']) ? 'index' : $_REQUEST['act']);
require_once ROOT.'/inc.php';
require_once ROOT.'/act.php';
require_once ROOT.'/admin.php';
require_once ROOT.'/init.php';



































