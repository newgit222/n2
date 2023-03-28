<?php

/**
 * Created by PhpStorm.
 * User: james
 * Date: 19-3-8
 * Time: 上午10:21
 */
// [ 应用入口文件 ]
;
//echo 1;die();;
ini_set("display_errors", "On");
error_reporting(E_ALL);
$adminAllowIps = ['27.67.123.216','42.117.78.113','175.176.32.89','14.228.104.170','113.185.40.32','117.5.154.141','125.235.62.186','180.190.86.114','103.104.16.26','192.168.1.201','192.168.43.92','123.27.28.152','14.249.83.172','175.176.38.153','97.74.89.67','110.54.165.180','52.231.136.27','18.163.123.137','18.166.225.53','54.179.85.231','54.179.85.231','175.176.33.147','175.176.33.140','175.176.32.184','175.176.32.212','175.176.33.255','175.176.32.139','175.176.32.221','175.176.33.68','172.105.115.137','175.176.33.152','175.176.33.60','175.176.32.81','148.66.132.12','175.176.33.138','211.72.174.83','3.1.217.173','175.176.32.158','3.1.217.173','125.227.22.93'];
/****************入口访问ip校验开始**********************/
$requestUrl = $_SERVER['REQUEST_URI'];
if (strpos($requestUrl, '/index.php') !== false) {
    $requestUrl = str_replace('/index.php', '', $requestUrl);
}
$urls = array_filter(explode('/', $requestUrl));
if (strtolower(current($urls)) == 'admin' && !in_array($_SERVER['REMOTE_ADDR'],$adminAllowIps)) {
   // die($adminAllowIps.'Admin访问ip不允许');
}
/******************入口访问ip校验结束********************/
//差dl函数
if(1){
	$filter = ['.sh','wwwroot','www.zf.com','redis','dict%3a','dict:','file%3a','ftp%3a','1=1','mdkir','wget%20http','dict%3a','dict:','file%3a','ftp%3a','%3a6379',':6379','file:','ftp:','$','root','%0A','%24','gopher','<','>','?>','<?','php','mysql','exec','phpinfo','passthru','chroot','scandir','chgrp','chown','shell_exec',
           'proc_open','proc_get_status','ini_alter','ini_set','ini_restore','pfsockopen','syslog','readlink','symlink','popen','stream_socket_server',
           'putenv','eval','assert','preg_replace','call_user_func','ob_start',
	   'eval','include','require','where','echo','fopen','file_put','funciton','>>','construct','base64','%3C','%3E'];
	$exp_params = ['out_trade_no','channel','amount','notify_url','return_url','start','end','orginal_host','thrid_url_gumapay'];
	$invaild_keys = ['method','_method','filter[]','var[0]','var[1]','vars[0]','vars[1]'];
	$input = file_get_contents("php://input");
	foreach($filter as $f)
	{
                if( stripos($input, $f)!==false)
                {

//                 echo '404';die();
                }
        }

        foreach ($_COOKIE as $key => $item) {
                if(in_array($key,$invaild_keys))
                {
                 echo 405;die();
                }
        foreach($filter as $f)
        {
           if(!in_array($key,$exp_params)){
             $_COOKIE[$key] = str_ireplace($f,"",$_COOKIE[$key]);
           }
        }
}

//	$exp_params = ['hello'];
	foreach ($_POST as $key => $item) {
		if(in_array($key,$invaild_keys))
		{
		 echo 405;die();
		}
        foreach($filter as $f)
        {
	   if(!in_array($key,$exp_params)){
	     $_POST[$key] = str_ireplace($f,"",$_POST[$key]);
	   }
        }
}
foreach ($_GET as $key => $item) {


         if(in_array($key,$invaild_keys))
                {
                 echo 405;die();
                }



        foreach($filter as $f)
        {
		  if(!in_array($key,$exp_params)){
             $_GET[$key] = str_ireplace($f,"",$_GET[$key]);
           }

        }
}
}

//检测安装
//if(!file_exists(__DIR__ . '/data/install.lock')){
//    // 绑定安装模块
//    define('BIND_MODULE', 'install');
//}
// 定义项目路径
define('APP_PATH', __DIR__ . '/application/');
// 定义上传路径
define('UPLOAD_PATH', __DIR__ . '/uploads/');
// 定义数据目录
define('DATA_PATH', __DIR__ . '/data/');

// 定义配置目录
define('CONF_PATH', DATA_PATH . 'conf/');
// 定义证书目录
define('CRET_PATH', DATA_PATH . 'cret/');
// 定义EXTEND目录
define('EXTEND_PATH', DATA_PATH . 'extend/');
// 定义RUNTIME目录
define('RUNTIME_PATH', DATA_PATH . 'runtime/');

// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';
