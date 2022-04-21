<?php

namespace src\Show;

use Components\Plugin;

class Show extends Plugin
{
    static function run($name)
    {
        $sql = 'SELECT * FROM t_materials WHERE name = :name';
        $article = static::getDatabase()->fetchOne($sql, ['name' => $name]);

        $desktop_html = trim(json_encode($article['desktop_html']), '"');
        $mobile_html = trim(json_encode($article['mobile_html']), '"');

        $template_data = array(
            'desktop' => $desktop_html,
            'mobile' => empty($mobile_html) ? $desktop_html : $mobile_html
        );

        return static::render('article', $template_data);

//        return static::render('default', ['body' => $post]);
    }
}