<?php

namespace src\Catalog;

use Components\Plugin;

class Catalog extends Plugin
{
    public static function run()
    {
        $sql = 'select * from t_materials';
        return static::render('list', [
            'materials' => static::getDatabase()->fetchall($sql)
        ]);
    }
}