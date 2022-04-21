<?php

use Components\Router;
use Dotenv\Dotenv;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

define('WEBROOT', dirname(__DIR__));

function path(string $path = '/')
{
    $path = implode(DIRECTORY_SEPARATOR, explode('/', ltrim($path, '/')));
    return sprintf('%s%s%s', WEBROOT, DIRECTORY_SEPARATOR, $path);
}

include_once(path('/vendor/autoload.php'));

function show_errors(bool $show_errors)
{
    if ($show_errors) {
        ini_set('display_errors', 1);
        ini_set('error_log', 'php_errors.log');
    } else {
        ini_set('display_errors', 0);
    }
}

try {
    Dotenv::createUnsafeImmutable(WEBROOT)->load();
    show_errors(getenv('DEBUG') === 'true');
} catch (Throwable $e) {
    show_errors(true);
    throw $e;
}

$whoops = new Run;
$whoops->pushHandler(new PrettyPageHandler);
$whoops->register();

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

Router::dispatch($_SERVER['REQUEST_URI']);