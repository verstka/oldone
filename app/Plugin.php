<?php

namespace Components;

use Aura\Sql\ExtendedPdo;
use Exception;
use ReflectionClass;

class Plugin
{
    public static function getDatabase(): ExtendedPdo
    {
        return Sqlite::getConnection();
    }

    public static function render($template, $data)
    {
        $template_path = static::getTemplateFile($template);
        if (!file_exists($template_path)) {
            throw new Exception(sprintf('%s not exist', $template_path));
        }

        extract($data);
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    private static function getTemplateFile(string $template)
    {
        return static::getPath() . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $template . '.php';
    }

    private static function getPath()
    {
        return dirname((new ReflectionClass(get_called_class()))->getFileName());
    }

    public static function getWebPath(string $path = '')
    {
        $sheme = $_SERVER['REQUEST_SCHEME'];
        $host = $_SERVER['HTTP_HOST'];

        return sprintf('%s://%s%s', $sheme, $host, $path);
    }
}