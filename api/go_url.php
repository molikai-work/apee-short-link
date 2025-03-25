<?php

/**
 * 跳转链接
 * 
 * @param string $end 自定义后缀
 * @param string $password 密码
 * @param string $type 返回类型
 * 
 * @return json
 */

require 'Tool.php';
require '../config.php';

use Tool\Tool;

$Tool = new Tool();
$end = $Tool->defalutGetData($_GET, 'end', '');
$password = $Tool->defalutGetData($_GET, 'password', '');
$type = $Tool->defalutGetData($_GET, 'type', 'cdx');
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

// 查找URL
$now_time = time();
$sql = "SELECT * FROM `$table` WHERE `end` = ? AND ((`create_time` + `guoqi` * 24 * 3600 > ?) OR (`guoqi` = 0))";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'si', $end, $now_time);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Tool->sqlError($result);

if (mysqli_num_rows($result) === 0) {
    $Tool->error(904, '链接不存在或已失效');
    exit;
}

$row = mysqli_fetch_assoc($result);

if ($row['password'] !== $password) {
    $Tool->error(901, '密码错误');
    exit;
}

if ($type === 'cdx') {
    header('Location: ' . $row['url']);
    exit;
} elseif ($type === 'json') {
    unset($row['id']);
    $Tool->success('获取成功', $row);
    exit;
}

$Tool->error(905, '无效的请求类型');
