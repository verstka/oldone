<?php

namespace src\Edit;

use Components\Plugin;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Verstka\Verstka;

class Edit extends Plugin
{
    static function open(string $name, bool $is_mobile)
    {
        $sql = 'SELECT * FROM t_materials WHERE name = :name';
        $article = static::getDatabase()->fetchOne($sql, ['name' => $name]);

        $body = $is_mobile ? $article['mobile_html'] : $article['desktop_html'];
        $verstka = new Verstka();
        $verstka_url = $verstka->open($name, $body, $is_mobile, static::getWebPath('/save'));

        return static::render('redirect', ['verstka_url' => $verstka_url]);
    }

    public static function save()
    {
        return static::verstkaCallback('\src\Edit\clientCallback', $_POST);
    }

    private static function verstkaCallback($client_callback_function, $data)
    {
        $verstka = new Verstka();
        return $verstka->save($client_callback_function, $data);
    }
}

function clientCallback($data)
{
    file_put_contents('/tmp/client_callback.log', print_r($data, true));

    $is_fail = false;
    $article_body = $data['article_body'];
    $article_static_dir_rel = sprintf('/upload/%s%s', $data['is_mobile'] ? 'm_':'', $data['material_id']);
    $article_static_dir_abs = path('/public/'.$article_static_dir_rel);
    @mkdir($article_static_dir_abs,  0777, true);
    foreach ($data['images'] as $image_name => $image_file) {
        $is_renamed = rename($image_file, sprintf('%s/%s', $article_static_dir_abs, $image_name));
        $is_fail = $is_fail || !$is_renamed;
        $html_image_name_old = sprintf('/vms_images/%s', $image_name);
        $html_image_name_new = sprintf('%s/%s', $article_static_dir_rel, $image_name);
        if ($is_renamed) {
            $article_body = str_replace($html_image_name_old, $html_image_name_new, $article_body);
        }
    }

    if ($data['is_mobile']) {
        $sql = 'update t_materials set mobile_html =  :article_body where name = :name;';
    } else {
        $sql = 'update t_materials set desktop_html = :article_body where name = :name;';
    }

    $db = Plugin::getDatabase();
    $saved = (bool)$db->fetchAffected($sql, ['article_body' => $article_body, 'name' => $data['material_id']]);
    $is_fail = $is_fail || !$saved;

    return !$is_fail;
}