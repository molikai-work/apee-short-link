<?php

/**
 * 生成短链接
 * 
 * @param string $url 长链接
 * @param string $password 密码
 * @param string $desc 描述
 * @param int $guoqi 有效天数
 * @param string $key_time 时间戳
 * @param string $key_val 验证码
 * @param string $endd 自定义后缀
 * 
 * @return json
 */

require '../config.php';
require 'Tool.php';

use Tool\Tool;

$Tool = new Tool();
header('Content-type: application/json; charset=utf-8');
sleep(1);

// 获取请求数据
$url = $Tool->defalutGetData($_POST, 'a', '');
$password = $Tool->defalutGetData($_POST, 'b', '');
$desc = $Tool->defalutGetData($_POST, 'c', '');
$guoqi = (int)$Tool->defalutGetData($_POST, 'd', 0);
$key_time = $Tool->defalutGetData($_POST, 'e', '');
$key_val = $Tool->defalutGetData($_POST, 'f', '');
$endd = $Tool->defalutGetData($_POST, 'g', '');

// 数据验证
if ($Tool->encodeStr([$url, $password, $guoqi, $key_time]) !== $key_val) {
    $Tool->error(901, '验证出错');
    exit;
}

if (time() * 1000 - $key_time > 10000) {
    $Tool->error(904, '请求过期');
    exit;
}

$create_time = time();
$mysql = $config['mysql'];
$table = $config['table']['url'];
$conn = mysqli_connect($mysql['host'], $mysql['user'], $mysql['pass'], $mysql['db']);
mysqli_set_charset($conn, "utf8");

// 初始化数据表
$sql = "CREATE TABLE IF NOT EXISTS `$table` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `url` TEXT NOT NULL,
    `password` VARCHAR(255),
    `desc` VARCHAR(255),
    `guoqi` INT(11) NOT NULL,
    `end` VARCHAR(255) NOT NULL,
    `create_time` INT(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$result = mysqli_query($conn, $sql);
$Tool->sqlError($result);

// 数据校验
if (!filter_var($url, FILTER_VALIDATE_URL) || strlen($url) > 2084) {
    $Tool->error(905, 'URL格式错误');
}

if (!preg_match('/^\w{0,20}$/', $password)) {
    $Tool->error(905, '密码格式错误，要求1-20位数字、字母、下划线');
}

if (mb_strlen($desc) > 200) {
    $Tool->error(905, '描述文本长度不能超过200');
}

if ($guoqi < 0 || $guoqi > 365) {
    $Tool->error(905, '有效天数必须是1-365的整数');
}

if ($endd && !preg_match('/^\w{6,20}$/', $endd)) {
    $Tool->error(905, '自定义后缀格式错误，要求6-20位数字、字母、下划线');
}

// 生成后缀
function generateEndId() {
    global $conn, $table, $Tool;
    do {
        $end = $Tool->randId(6);
        $sql = "SELECT `end` FROM `$table` WHERE `end` = '$end';";
        $result = mysqli_query($conn, $sql);
    } while (mysqli_num_rows($result) > 0);
    return $end;
}

$end = $endd ?: generateEndId();

// 查询重复记录
if (!$password && !$guoqi && !$endd) {
    $sql = "SELECT * FROM `$table` WHERE `url` = ? AND `desc` = ? AND `password` = '';";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $url, $desc);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        unset($row['id']);
        $Tool->success('生成成功', $row);
    }
}

// 检查后缀是否重复
$sql = "SELECT `end` FROM `$table` WHERE `end` = ?;";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $Tool->error(902, '后缀已存在，请换一个试试');
}

// 插入记录
$sql = "INSERT INTO `$table` (`url`, `password`, `desc`, `guoqi`, `end`, `create_time`) VALUES (?, ?, ?, ?, ?, ?);";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sssisi', $url, $password, $desc, $guoqi, $end, $create_time);
$result = mysqli_stmt_execute($stmt);

$Tool->sqlError($result);
$Tool->success('生成成功', [
    'url' => $url,
    'password' => $password,
    'desc' => $desc,
    'guoqi' => $guoqi,
    'end' => $end,
    'create_time' => $create_time,
]);
