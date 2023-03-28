<?php

/**
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

use app\common\logic\Log as LogicLog;
use think\Db;

// 应用公共文件

/**
 * 检测管理用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_admin_login()
{
    $user = session('admin_auth');
    if (empty($user)) {

        return false;
    } else {
        return session('admin_auth_sign') == data_auth_sign($user) ? $user['id'] : false;
    }
}

/**
 * 检测商户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login()
{
    $user = session('user_auth');
    if (empty($user)) {

        return false;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : false;
    }
}

function is_not_json($str)
{
    return is_null(json_decode($str));
}

/**
 * 清除登录 session
 *
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function clear_admin_login_session()
{
    session('admin_info', null);
    session('admin_auth', null);
    session('admin_auth_sign', null);
}

/**
 * 清除登录 session
 *
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function clear_user_login_session()
{
    session('user_info', null);
    session('user_auth', null);
    session('user_auth_sign', null);
}


/**
 * 数据签名认证
 *
 * @param array $data 被认证的数据
 * @return string       签名
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function data_auth_sign($data)
{

    // 数据类型检测
    if (!is_array($data)) {

        $data = (array)$data;
    }

    // 排序
    ksort($data);

    // url编码并生成query字符串
    $code = http_build_query($data);

    // 生成签名
    $sign = sha1($code);

    return $sign;
}


/**
 * 记录行为日志
 */
function action_log($name = '', $describe = '')
{

    $logLogic = get_sington_object('logLogic', LogicLog::class);

    $logLogic->logAdd($name, $describe);
}

function delete_all_between($beginning, $end, $string)
{
    $beginningPos = strpos($string, $beginning);
    $endPos = strpos($string, $end);
    if ($beginningPos === false || $endPos === false) {
        return $string;
    }

    $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

    return str_replace($textToDelete, '', $string);
}

/**
 * 获取单例对象
 */
function get_sington_object($object_name = '', $class = null)
{

    $request = request();

    $request->__isset($object_name) ?: $request->bind($object_name, new $class());

    return $request->__get($object_name);
}

/**
 * 使用上面的函数与系统加密KEY完成字符串加密
 * @param string $str 要加密的字符串
 * @return string
 */
function data_md5_key($str, $key = 'Iredcap')
{

    if (is_array($str)) {

        ksort($str);

        $data = http_build_query($str);

    } else {

        $data = (string)$str;
    }

    return empty($key) ? data_md5($data, config('secret.data_salt')) : data_md5($data, $key);
}

/**
 * 系统非常规MD5加密方法
 *
 * @param string $str 要加密的字符串
 * @param string $key
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function data_md5($str, $key = 'Iredcap')
{

    return '' === $str ? '' : md5(sha1($str) . $key);
}


/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{

    // 创建Tree
    $tree = [];

    if (!is_array($list)) {

        return false;
    }

    // 创建基于主键的数组引用
    $refer = [];

    foreach ($list as $key => $data) {

        $refer[$data[$pk]] =& $list[$key];
    }

    foreach ($list as $key => $data) {

        // 判断是否存在parent
        $parentId = $data[$pid];

        if ($root == $parentId) {

            $tree[] =& $list[$key];

        } else if (isset($refer[$parentId])) {

            is_object($refer[$parentId]) && $refer[$parentId] = $refer[$parentId]->toArray();

            $parent =& $refer[$parentId];

            $parent[$child][] =& $list[$key];
        }
    }

    return $tree;
}

/**
 * 分析数组及枚举类型配置值 格式 a:名称1,b:名称2
 * @return array
 */
function parse_config_attr($string)
{

    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));

    if (strpos($string, ':')) {

        $value = [];

        foreach ($array as $val) {

            list($k, $v) = explode(':', $val);

            $value[$k] = $v;
        }

    } else {

        $value = $array;
    }

    return $value;
}


/**
 * 将二维数组数组按某个键提取出来组成新的索引数组
 */
function array_extract($array = [], $key = 'id')
{

    $count = count($array);

    $new_arr = [];

    for ($i = 0; $i < $count; $i++) {

        if (!empty($array) && !empty($array[$i][$key])) {

            $new_arr[] = $array[$i][$key];
        }
    }

    return $new_arr;
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 *
 * @param array $arr 要连接的数组
 * @param string $glue 分割符
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param string $str 要分割的字符串
 * @param string $glue 分割符
 * @return array
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, preg_replace('/[ ]/', '', $str));
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function arr2obj($arr)
{
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)arr2obj($v);
        }
    }

    return (object)$arr;
}

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function obj2arr($obj)
{
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)obj2arr($v);
        }
    }

    return $obj;
}


/**
 * 字符串替换
 *
 * @param string $str
 * @param string $target
 * @param string $content
 * @return mixed
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function sr($str = '', $target = '', $content = '')
{

    return str_replace($target, $content, $str);
}

/**
 * 字符串前缀验证
 *
 * @param $str
 * @param $prefix
 * @return bool
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function str_prefix($str, $prefix)
{

    return strpos($str, $prefix) === 0 ? true : false;
}

/**
 * 生成支付订单号
 *
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function create_order_no()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn =
        $yCode[intval(date('Y')) - 2018] . date('YmdHis') . strtoupper(dechex(date('m')))
        . date('d') . sprintf('%02d', rand(0, 999));
    return $orderSn;
}

/**
 * 生成唯一的订单号 20110809111259232312
 *
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function create_general_no()
{
    list($usec, $sec) = explode(" ", microtime());
    $usec = substr(str_replace('0.', '', $usec), 0, 4);
    $str = rand(10, 99);
    return date("YmdHis") . $usec . $str;
}

/**
 *
 * @param $url
 * @param $rawData
 * @param string $target
 * @param int $retry
 * @param int $sleep
 * @param int $second
 * @return mixed
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function curl_post_raw($url, $rawData, $target = 'FAIL', $retry = 6, $sleep = 3, $second = 30)
{
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: text'
        )
    );
    //运行curl
    $output = curl_exec($ch);
    while (strpos($output, $target) !== false && $retry--) {
        //检查$targe是否存在
        sleep($sleep); //阻塞3s
        $sleep += 2;
        $output = curl_exec($ch);
    }
    curl_close($ch);
    return $output;
}

/**
 * 获取随机字符
 *
 * @param string $length
 * @param $format
 * @return null|string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function getRandChar($length = '4', $format = 'ALL')
{
    switch ($format) {
        case 'ALL':
            $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
        case 'CHAR':
            $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'NUM':
            $strPol = '0123456789';
            break;
        default :
            $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    $str = null;
    //$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

/**
 * 月赋值
 *
 * @param $array
 * @param $key
 * @return array
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function get_order_month_stat($array, $key)
{
    $month = 12;
    $newArr = [];
    for ($i = 1; $i <= $month; $i++) {
        $newArr[$i] = 0;
    }
    foreach ($array as $v) {
        $newArr[$v['month']] = (float)$v[$key];
    }
    return ($newArr);
}

/**
 * 下划线转驼峰
 *
 * @param $uncamelized_words
 * @param string $separator
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function camelize($uncamelized_words, $separator = '_')
{

    $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
}


/**
 * 驼峰命名转下划线命名
 *
 * @param $camelCaps
 * @param string $separator
 * @return string
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * 获取到微秒
 *
 * @return float
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function getMicroTime()
{
    list($s1, $s2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

/**
 * url参数转化成数组
 *
 * @param $query
 *
 * @return array
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function convertUrlArray($query)
{
    $queryParts = explode('&', $query);

    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }

    return $params;
}


// +---------------------------------------------------------------------+
// | 其他函数
// +---------------------------------------------------------------------+

/**
 * 通过类创建逻辑闭包
 *
 * @param null $object
 * @param string $method_name
 * @param array $parameter
 *
 * @return Closure
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function create_closure($object = null, $method_name = '', $parameter = [])
{

    $func = function () use ($object, $method_name, $parameter) {

        return call_user_func_array([$object, $method_name], $parameter);
    };

    return $func;
}

/**
 * 通过闭包控制缓存
 *
 * @param string $key
 * @param null $func
 * @param int $time
 *
 * @return mixed
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function auto_cache($key = '', $func = '', $time = 3)
{

    $result = cache($key);

    if (empty($result)) {

        $result = $func();

        !empty($result) && cache($key, $result, $time);
    }

    return $result;
}

/**
 * 通过闭包列表控制事务
 *
 * @param array $list
 *
 * @return bool
 * @throws Exception
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
function closure_list_exe($list = [])
{

    Db::startTrans();

    try {

        foreach ($list as $closure) {

            $closure();
        }

        Db::commit();

        return true;
    } catch (\Exception $e) {

        Db::rollback();

        throw $e;
    }
}

function authcode($string, $operation = 'ENCODE', $key = '', $expiry = 0)
{
    $key = "password";
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) :
        substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
//解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :
        sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}


/**
 * 打印函数
 * @param $data  打印数据
 */
function dd($data)
{

    echo "<pre>";
    print_r($data);
    echo "</pre>";
    exit;
}


/**
 * 导出excel
 * @param $strTable    表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable, $filename)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=" . $filename . "_" . date('Y-m-d') . ".xls");
    header('Expires:0');
    header('Pragma:public');
    echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $strTable . '</html>';
}


/**
 * 在一个字符串中指定位置插入一个数字
 * @param $str
 * @param $location
 */

function addChar($str, $location, $acter)
{
    $preStr = substr($str, 0, $location);
    $nextStr = substr($str, $location, strlen($str) - $location);
    return str_replace(" ", '', $preStr . $acter . $nextStr);
}


function downloadFile($filePath, $readBuffer = 1024, $allowExt = ['jpeg', 'jpg', 'peg', 'gif', 'zip', 'rar', 'txt', 'doc'])
{
    //检测下载文件是否存在 并且可读
    if (!is_file($filePath) && !is_readable($filePath)) {
        return false;
    }
    //检测文件类型是否允许下载
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowExt)) {
        return false;
    }
    //设置头信息
    //声明浏览器输出的是字节流
    header('Content-Type: application/octet-stream');
    //声明浏览器返回大小是按字节进行计算
    header('Accept-Ranges:bytes');
    //告诉浏览器文件的总大小
    $fileSize = filesize($filePath);//坑 filesize 如果超过2G 低版本php会返回负数
    header('Content-Length:' . $fileSize); //注意是'Content-Length:' 非Accept-Length
    //声明下载文件的名称
    header('Content-Disposition:attachment;filename=' . basename($filePath));//声明作为附件处理和下载后文件的名称
    //获取文件内容
    $handle = fopen($filePath, 'rb');//二进制文件用‘rb’模式读取
    while (!feof($handle)) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
        echo fread($handle, $readBuffer);
    }
    fclose($handle);//关闭文件句柄
    exit;

}


/**
 * curl  模拟请求
 * @param $url
 * @param string $method
 * @param null $postfields
 * @param array $headers
 * @param bool $debug
 * @return bool|string
 */
function httpRequest($url, $method = "GET", $postfields = null, $headers = array(), $debug = false)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);

    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}


function getidbyweight($aFileds)
{
    global $_SGLOBAL;
    $iCount = count($aFileds);
    if ($iCount >= 2) {
        $iLine = 0;
        for ($i = 0; $i < $iCount; $i++) {
            $aFileds[$i]['start'] = $iLine + 1;
            $iLine += $aFileds[$i]['weight'];
            $aFileds[$i]['end'] = $iLine;
        }
        $result = mt_rand(1, $iLine);
        for ($i = 0; $i < $iCount; $i++) {
            if ($result >= $aFileds[$i]['start'] && $result <= $aFileds[$i]['end'])
                return $aFileds[$i]['id'];

        }
    } elseif ($iCount == 1) {
        return $aFileds[0]['id'];
    }

}


//获取设备IP
function get_userip()
{
	return $_SERVER['REMOTE_ADDR'];
    //判断服务器是否允许$_SERVER
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        //不允许就使用getenv获取
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

/**
 * 获取当前毫秒时间戳
 */
function msectime()
{
    list($msec, $sec) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
}


/*随机生成邀请码*/

function strrand($length = 12, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    if (!is_int($length) || $length < 0) {
        return false;
    }
    $string = '';
    for ($i = $length; $i > 0; $i--) {
        $string .= $char[mt_rand(0, strlen($char) - 1)];
    }
    return $string;
}


/**
 * [pwdMd5 用户密码加密]
 *
 */
function pwdMd5($value, $salt = '')
{
    $user_pwd = md5(md5($value) . $salt);
    return $user_pwd;
}


/**
 * @param $value
 * @param string $salt
 * @return string
 */
function pwdMd52($value, $salt = '')
{
    $password_salt = config('password_salt');
    $user_pwd = md5($password_salt . $salt . $value);
    return $user_pwd;
}


/**
 * 记录登录次数
 */
function loginCountLog($name)
{
    $count = \think\Cache::get($name);
    if (!$count) {
        \think\Cache::set($name, '1');
    } else {
        if ($count > 4) {
            \think\Cache::set($name, '1');
            return ['code' => '0', 'msg' => '登录错误超过6次，账号已冻结'];//''];
        }
        \think\Cache::set($name, $count + 1);
    }
    return ['code' => '1', 'msg' => '请求成功'];

}


/**
 * 判断密码重点级别
 * @return [type] [description]
 */
function judgepassword($password)
{
    $score = 0;
    if (preg_match("/[0-9]+/", $password)) {
        $score++;
    }
    if (preg_match("/[0-9]{3,}/", $password)) {
        $score++;
    }
    if (preg_match("/[a-z]+/", $password)) {
        $score++;
    }
    if (preg_match("/[a-z]{3,}/", $password)) {
        $score++;
    }
    if (preg_match("/[A-Z]+/", $password)) {
        $score++;
    }
    if (preg_match("/[A-Z]{3,}/", $password)) {
        $score++;
    }
    if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/", $password)) {
        $score += 2;
    }
    if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/", $password)) {
        $score++;
    }
    if (strlen($score) >= 10) {
        $score++;
    }
    return $score;
}


function isMobile()
{
    $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] : '';
    function CheckSubstrs($substrs, $text)
    {
        foreach ($substrs as $substr)
            if (false !== strpos($text, $substr)) {
                return true;
            }
        return false;
    }

    $mobile_os_list = array('Google Wireless Transcoder', 'Windows CE', 'WindowsCE', 'Symbian', 'Android', 'armv6l', 'armv5', 'Mobile', 'CentOS', 'mowser', 'AvantGo', 'Opera Mobi', 'J2ME/MIDP', 'Smartphone', 'Go.Web', 'Palm', 'iPAQ');
    $mobile_token_list = array('Profile/MIDP', 'Configuration/CLDC-', '160×160', '176×220', '240×240', '240×320', '320×240', 'UP.Browser', 'UP.Link', 'SymbianOS', 'PalmOS', 'PocketPC', 'SonyEricsson', 'Nokia', 'BlackBerry', 'Vodafone', 'BenQ', 'Novarra-Vision', 'Iris', 'NetFront', 'HTC_', 'Xda_', 'SAMSUNG-SGH', 'Wapaka', 'DoCoMo', 'iPhone', 'iPod');

    $found_mobile = CheckSubstrs($mobile_os_list, $useragent_commentsblock) ||
        CheckSubstrs($mobile_token_list, $useragent);

    if ($found_mobile) {
        return true;
    } else {
        return false;
    }
}


/**
 * @param $uid  uwer_id
 * @param int $type 资金操作类型对应数据库中jl_class
 * @param int $add_subtract 添加或者减少
 * @param float $money 操作金额
 * @param string $tip_message 资金流水备注
 * @return bool
 */
function accountLog($uid, $type = 1, $add_subtract = 1, $money = 0.00, $tip_message = '')
{
    $UserModel = new  \app\common\model\Ms();
    $user = $UserModel->where(['userid' => $uid])->find();
    //转账身份检测
    if ($user) {  //当前用户状态正常
        $moneys = ($add_subtract == 1) ? $money : 0 - $money;
        $updateBalanceRes = $UserModel->where(['userid' => $uid])->setInc('money', $moneys);
        if ($updateBalanceRes) {
            //记录流水
            $insert['uid'] = $uid;
            $insert['jl_class'] = $type;
            $insert['info'] = $tip_message;
            $insert['addtime'] = time();
            $insert['jc_class'] = ($add_subtract) ? "+" : "-";
            $insert['num'] = $money;
            $insert['pre_amount'] = $user['money'];
            $insert['last_amount'] = $user['money'] + $moneys;

            if ((new \app\common\model\MsSomeBill())->insert($insert)) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
    return false;
}


/*
 *
 * @return mixed
 */
function clientIp()
{
    if($_SERVER["REMOTE_ADDR"]=='103.210.239.133'){
     return $_SERVER["HTTP_X_REAL_IP"];
    }else
   {
    return $_SERVER["REMOTE_ADDR"];
   }
}

/**
 * 过滤特殊字符，仅保留中文、字母、数字、下划线
 * @param $chars
 * @param string $encoding
 * @return string
 */
function match_str($chars, $encoding = 'utf8')
{
    $pattern = ($encoding == 'utf8') ? '/[\x{4e00}-\x{9fa5}a-zA-Z0-9_]/u' : '/[\x80-\xFF]/';
    preg_match_all($pattern, $chars, $result);
    $temp = join('', $result[0]);
    return $temp;
}


function checkIsChinese($str)
{
    return preg_match("/^[\x7f-\xff]+$/", $str) ? true : false;
}

function filterWords()
{
    $filterWords = ['%', '_', '+', '/', '@', '$', '|', '-', '='];
    return $filterWords;;
}


/**
 * 过滤输入参数
 */
function vlidateError($val)
{
    $filterWords=  filterWords();
    foreach ($filterWords as $filterWord) {
        if (strpos($val, $filterWord) !== false) {
            $result = [
                'code' => 1,
                'msg'  => '输入非法',
            ];
            exit(json_encode($result));
        }
    }
    return $val;
}


function vlidateFilter($val)
{
    $filterWords=  filterWords();
    foreach ($filterWords as $filterWord) {
        if (strpos($val, $filterWord) !== false) {
            return '';
        }

    }
    return $val;
}


