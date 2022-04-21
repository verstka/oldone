<?php

namespace src\Edit;

use Components\Plugin;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;

class Edit extends Plugin
{
    static function open(string $name, bool $is_mobile)
    {
        $sql = 'SELECT * FROM t_materials WHERE name = :name';
        $article = static::getDatabase()->fetchOne($sql, ['name' => $name]);

        $body = $is_mobile ? $article['mobile_html'] : $article['desktop_html'];

        $custom_fields = [
            'auth_user' => 'test',        //if You have http authorization on callback url
            'auth_pw' => 'test',          //if You have http authorization on callback url
            'mobile' => $is_mobile,       //if You edit mobile version of article
            'fonts.css' => '/static/vms_fonts.css', //if You use custom fonts set
            'version' => 1.0
        ];

        $params = [
            'form_params' => [
                'user_id' => $_SERVER['PHP_AUTH_USER'] ?? 1,
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'material_id' => $name,
                'html_body' => $body,
                'callback_url' => static::getWebPath('/save'),
                'host_name' => $_SERVER['HTTP_HOST'],
                'api-key' => getenv('verstka_apikey'),
                'custom_fields' => json_encode($custom_fields)
            ],
//            'allow_redirects' => ['track_redirects' => true],
            'connect_timeout' => 3.14,
            'headers' => [
                'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
            ]
//            'auth' => ['username', 'password']
        ];

        $params['form_params']['callback_sign'] = static::getRequestSalt(getenv('verstka_secret'), $params['form_params'], 'api-key, material_id, user_id, callback_url');

        $verstka_url_open = (getenv('SSL') ? 'https://' : 'http://') . getenv('verstka_host') . '/1/open';

        $guzzle_client = new Client(['timeout' => 60.0]); //Base URI is used with relative requests // 'base_uri' => 'http://httpbin.org',
        $response = $guzzle_client->post($verstka_url_open, $params);
        $result_json = $response->getBody()->getContents();
        $code = $response->getStatusCode();
        $result = json_decode($result_json, true);

        if ($code !== 200 || json_last_error() || empty($result['data']['edit_url']) || empty($result['rc']) || $result['rc'] !== 1) {
            throw new \Exception(sprintf("verstka api open return %d\n%s", $code, $result_json));
        }

        return static::render('redirect', ['verstka_url' => $result['data']['edit_url']]);
    }

    public static function save()
    {
        return static::verstkaCallback('\src\Edit\clientCallback', $_POST);
    }

    private static function verstkaCallback($client_callback_function, $data)
    {
        set_time_limit(0);
        try {
            if (empty($data['download_url']) || (static::getRequestSalt(getenv('verstka_secret'), $data, 'session_id, user_id, material_id, download_url') !== $data['callback_sign'])) {
                throw new \Exception('invalid callback sign');
            }

//          Article params:
            $article_body = $data['html_body'];
            $verstka_url_download = $data['download_url'];
            $custom_fields = json_decode($data['custom_fields'], true);
            $is_mobile = $custom_fields['mobile'] === true;
            $material_id = $data['material_id'];
            $user_id = $data['user_id'];

//          Request list of images
            $params = [
                'connect_timeout' => 3.14,
                'form_params' => [
                    'api-key' => getenv('verstka_apikey'),
                    'unixtime' => time()
                ]
            ];

            $guzzle_client = new Client(['timeout' => 60.0]); //Base URI is used with relative requests // 'base_uri' => 'http://httpbin.org',
            $response = $guzzle_client->post($verstka_url_download, $params);
            $result_json = $response->getBody()->getContents();
            $code = $response->getStatusCode();
            $result = json_decode($result_json, true);

            if ($code !== 200 || json_last_error() || empty($result['data']) || empty($result['rc']) || $result['rc'] !== 1) {
                throw new \Exception(sprintf("verstka api contents return %d\n%s", $code, $result_json));
            }

            $images_list = $result['data'];
            $images_to_download = $images_list;

            $guzzle_client = new Client([
                'timeout' => 180.0, // see how i set a timeout
                'handler' => HandlerStack::create(new CurlMultiHandler([
                    'options' => [
                        CURLMOPT_MAX_TOTAL_CONNECTIONS => 20,
                        CURLMOPT_MAX_HOST_CONNECTIONS => 20,
                    ]
                ]))
            ]);

            $attempts = [];
            $images_ready = [];
            for ($i = 1; $i <= 3; $i++) {

                $requestPromises = [];
                $temp_files = [];
                foreach ($images_to_download as $image_name) {
                    $image_url = sprintf('%s/%s', $verstka_url_download, $image_name);
                    $tmp_file = tempnam(sys_get_temp_dir(), str_replace('.', '_', uniqid('vms_' . microtime(true) . '_' . $image_name)));
                    $temp_files[$image_name] = $tmp_file;
                    $requestPromises[$image_name] = $guzzle_client->getAsync($image_url, [
                        'sink' => $tmp_file,
                        'connect_timeout' => 3.14
                    ]);
                    $attempts[$image_name] = empty($attempts[$image_name]) ? 1 : $attempts[$image_name] + 1;
                }

                $images_to_download = [];
                $results = \GuzzleHttp\Promise\Utils::settle($requestPromises)->wait();
                foreach ($results as $image_name => $image_result) {
                    if (
                        $image_result['state'] !== 'fulfilled'
                        || !file_exists($temp_files[$image_name])
                        || (filesize($temp_files[$image_name]) === 0)
                    ) {
                        $images_to_download[] = $image_name;
                        unlink($temp_files[$image_name]);
                    } else {
                        $images_ready[$image_name] = $temp_files[$image_name];
                    }
                }
            }

            $lacking_images = [];
            foreach ($images_list as $image_name) {
                if (empty($images_ready[$image_name])) {
                    $lacking_images[] = $image_name;
                }
            }

            $call_back_result = call_user_func($client_callback_function, [
                'article_body' => $article_body,
                'custom_fields' => $custom_fields,
                'is_mobile' => $is_mobile,
                'material_id' => $material_id,
                'user_id' => $user_id,
                'images' => $images_ready
            ]);

            $debug = [];
            if ($call_back_result === true) {
                $debug[] = 'eee';
                foreach ($images_ready as $image => $image_temp_file) {    // clean temp folder if callback successfull
                    if (is_readable($image_temp_file)) {
                        unlink($image_temp_file);
                        $debug[] = $image_temp_file;
                    }
                }
            }

            $additional_data = [
//                'images_list' => $images_list,
//                'results' => $results,
//                'temp_files' => $temp_files,
//                'attempts' => $attempts,
                'debug' => $debug,
                'custom_fields' => $custom_fields,
                'lacking_images' => $lacking_images
            ];
            return static::formJSON(1, 'save sucessfull', $additional_data);
        } catch (\Throwable $e) {
            return static::formJSON($e->getCode(), $e->getMessage(), $data);
        }
    }

    private static function formJSON($res_code, $res_msg, $data = array())
    {
        return json_encode(array(
            'rc' => $res_code,
            'rm' => $res_msg,
            'data' => $data
        ), JSON_NUMERIC_CHECK);
    }

    private static function getRequestSalt($secret, $request, $fields)
    {
        $fields = array_filter(array_map('trim', explode(',', $fields)));
        $data = $secret;
        foreach ($fields as $field) {
            $data .= $request[$field];
        }
        return md5($data);
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