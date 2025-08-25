<?php

const PATH_ROOT = __DIR__;
const PATH_TEMPLATE = PATH_ROOT . '/template';
const PATH_DATA = PATH_ROOT . '/data';
const APP_NAMESPACE = 'DbService';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set("pcre.jit", "0");

require_once 'config.php';

spl_autoload_register(function ($class) {
    $prefix = APP_NAMESPACE . '\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
