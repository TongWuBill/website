<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// 如果请求的是 public 目录里真实存在的文件，就直接返回给内置服务器
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

$uri = rtrim($path, '/');

if ($uri === '') {
    $uri = '/';
}

switch ($uri) {
    case '/':
        require __DIR__ . '/home.php';
        break;

    case '/work':
        require __DIR__ . '/projects.php';
        break;

    case '/experiments':
        require __DIR__ . '/experiments.php';
        break;

    case '/about':
        require __DIR__ . '/about.php';
        break;

    case '/contact':
        require __DIR__ . '/contact.php';
        break;

    case '/admin':
        header('Location: /admin/login.php');
        exit;

    default:
        if (preg_match('#^/p/(.+)$#', $uri, $matches)) {
            $_GET['slug'] = $matches[1];
            require __DIR__ . '/project.php';
        } else {
            http_response_code(404);
            echo "404";
        }
        break;
}