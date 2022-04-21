<?php

namespace Components;

use src\Catalog\Catalog;
use src\Edit\Edit;
use src\Show\Show;

class Router
{
    static function dispatch(string $reqest_uri)
    {
        $url_components = parse_url($reqest_uri);
        $path_components = explode('/', trim($url_components['path'], '/'));

        switch (true) {

            case $path_components[0] === '':
                echo Show::run('index');
                break;

            case $path_components[0] === 'edit':
                $is_mobile = ($path_components[2] ?? '') === 'mobile';
                echo Edit::open($path_components[1], $is_mobile);
                break;

            case $path_components[0] === 'save':
                echo Edit::save();
                break;

            case $path_components[0] === 'list':
                echo Catalog::run();
                break;

            default:
                echo Show::run($path_components[0]);
        }
    }
}
