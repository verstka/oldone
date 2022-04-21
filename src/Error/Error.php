<?php

namespace src\Error;

class Error
{
    public static function run(int $code = 500, string $message = 'default error')
    {
        header(sprintf('%s 5d %s', $_SERVER['SERVER_PROTOCOL'], $code, $message));
        die('uups');
    }
}