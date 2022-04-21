<?php

use devnow\VMSsdk;

include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'global.php');

die;

// simple database for storage in json file:
$db_path = dirname(__DIR__) . '/portable_db.json';
$db = json_decode(file_get_contents($db_path), true);

// parsing requested addresses for simple routing:
$url_components = parse_url($_SERVER['REQUEST_URI']);
$path_components = explode('/', trim($url_components['path'], '/'));

/*
 * including to define the following variables:
 * $apikey, $secret, $web_root_abs, $temp_dir_abs, $call_back_url, $call_back_function
 */
include_once(__DIR__ . '/config.php');

if (empty($apikey)) {

    $config_example = <<<'EOL'
<?php //variables examples:
	$apikey = '1234567890abcdefghai';
	$secret = '1234567890abcdefghai';
	$web_root_abs = $_SERVER['DOCUMENT_ROOT'];
	$temp_dir_abs = $web_root_abs . '/temp';
	$call_back_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PATH_INFO'];
	$call_back_function = 'saveArticle';
?>
EOL;
    file_put_contents(__DIR__ . '/config.php', $config_example);
    die('Please specify valid api key and secret in ' . __DIR__ . '/config.php');
}

// simple routing implementation:
switch ($url_path_components[0]) {

    case 'edit':

        // connection of verstka.io php sdk:
        include_once(dirname(__DIR__) . '/vendor/verstka.io/php-sdk/class.VMSsdk.php');
        $modern = new VMSsdk($apikey, $secret, $call_back_url, $call_back_function, $temp_dir_abs, $web_root_abs, $static_host_name);
        // this is not an authorization, this code only defines user, and differ him from each others
        define_user_name($db);

        //define verstka.io api variables:
        $article_body = $db['articles'][$url_path_components[1]]['html'];
        $material_id = $url_path_components[1];
        $user_id = $_SERVER['PHP_AUTH_USER'];
        $custom_fields = array(
            'edit_url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'],
            'auth_user' => $_SERVER['PHP_AUTH_USER'],
            'auth_pw' => $_SERVER['PHP_AUTH_PW']
        );

        if (!empty($url_path_components[1])) { // check article name (use 'index' for main page)
            $result = $modern->edit($article_body, $material_id, $user_id, $custom_fields);
            if (!empty($result['edit_url'])) {
                header('Location: ' . $result['edit_url']);
                die('<a href="' . $result['edit_url'] . '" target="_blank">Edit ' . $result['edit_url'] . '</a>');
            } else {
                print_r($result); // print debug if mishandling
                die;
            }
        } else {
            die('enter article name');
        }
        break;

    case 'list':

        echo 'Articles count: ' . count($db['articles']) . '<br>' . PHP_EOL;
        $articles = array_keys($db['articles']);
        foreach ($articles as $article) {
            if (strpos($article, '_mobile') === false) {
                echo '<a href="' . '/' . $article . '">/' . $article . '</a> <a href="' . '/edit/' . $article . '">edit /' . $article . '</a><br>' . PHP_EOL;
                echo '<a href="' . '/' . $article . '_mobile">/' . $article . '_mobile</a> <a href="' . '/edit/' . $article . '_mobile">edit /' . $article . '_mobile</a><br>' . PHP_EOL;
            }
        }
        break;

    case 'export':

        if (empty($url_path_components[2])) {
            $template = 'default_template';
        } else {
            $template = $url_path_components[2];
        }

		if (empty($url_path_components[1])) {
            $url_path_components[1] = 'index';
        }
        $article_body = $db['articles'][$url_path_components[1]]['html'];

        include_once(dirname(__DIR__) . '/vendor/verstka.io/php-sdk/class.VMSsdk.php');
        $modern = new VMSsdk($apikey, $secret, $call_back_url, $call_back_function, $temp_dir_abs, $web_root_abs);
        $base64body = $modern->make_template($article_body);

        die(renderTemplate($template . '.php', array('body' => $base64body)));

    default:

        if (empty($url_path_components[0])) {
            $url_path_components[0] = 'index';
        }

        $mobile = false;
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            $mobile = true;
        }

        if (!empty($db['articles'][$url_path_components[0] . '_mobile']['html']) && $mobile) {
            $url_path_components[0] = $url_path_components[0] . '_mobile';
        }

        if (empty($db['articles'][$url_path_components[0]]['html'])) {
            define_user_name($db);
            die('<a href="/edit/' . $url_path_components[0] . '">edit ' . $url_path_components[0] . '<br>' . PHP_EOL);
        }

        echo renderTemplate('default_template.php', array('body' => $db['articles'][$url_path_components[0]]['html']));
}

function renderTemplate($template_path, $data)
{
    extract($data);
    ob_start();
    include $template_path;
    return ob_get_clean();
}

function saveArticle($body, $material_id, $user_id, $images, $custom_fields)
{
    global $db;
    global $db_path;

    $ds = DIRECTORY_SEPARATOR;

    $material_images_dir_relative = $ds . 'upload' . $ds . $material_id . $ds;
    $material_images_dir_absolute = $_SERVER['DOCUMENT_ROOT'] . $material_images_dir_relative;
    @mkdir($material_images_dir_absolute, 0777, true);

    $body = str_replace('/vms_images/', $material_images_dir_relative, $body);

    $db['articles'][$material_id] = array('html' => $body, 'user_id' => $user_id);
    if (file_put_contents($db_path, json_encode($db))) {

        $moved = true;
        foreach ($images as $image_name => $image_file) {
            $moved = $moved & rename($image_file, $material_images_dir_absolute . $image_name);
        }
        return !!$moved;

    } else {
        return 'client call back function fail message';
    }
}

/*
 *  this is not an authorization, this code only defines user, and differ him from each others
 * 	DO NOT USE THIS CODE FOR AUTHORIZATION ON PRODUCTION !!!
 */
function define_user_name(&$db)
{
    if (!empty($db['suspects'][$_SERVER['REMOTE_ADDR']]) && ($db['suspects'][$_SERVER['REMOTE_ADDR']] > 5)) {
        header('WWW-Authenticate: Basic realm="Please signup."');
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Undefine_user_named');
        die('sorry, but open edit registration period are closed');
    }

    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Please signup."');
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Undefine_user_named');
        die('Please signup.');
    } else {
        if (empty($db['auth'])) {
            $db['auth'][$_SERVER['PHP_AUTH_USER']] = $_SERVER['PHP_AUTH_PW'];
            file_put_contents(__DIR__ . '/portable_db.json', json_encode($db));
        } else {

            if ((!empty($db['auth'][$_SERVER['PHP_AUTH_USER']]) && ($db['auth'][$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW'])) || (!empty($db['suspects'][$_SERVER['REMOTE_ADDR']]) && $db['suspects'][$_SERVER['REMOTE_ADDR']] > 15)) {

                if (empty($db['suspects'][$_SERVER['REMOTE_ADDR']])) {
                    $db['suspects'][$_SERVER['REMOTE_ADDR']] = 1;
                } else {
                    $db['suspects'][$_SERVER['REMOTE_ADDR']]++;
                }
                if (count($db['suspects']) > 32) {
                    array_shift($db['suspects']);
                }
                file_put_contents(__DIR__ . '/portable_db.json', json_encode($db));
                header('WWW-Authenticate: Basic realm="Please signup."');
                header($_SERVER['SERVER_PROTOCOL'] . ' 401 Undefine_user_named');
                die('Wrong auth data');
            }

            if (empty($db['auth'][$_SERVER['PHP_AUTH_USER']])) {
                header('WWW-Authenticate: Basic realm="Please signup."');
                header($_SERVER['SERVER_PROTOCOL'] . ' 401 Undefine_user_named');
                die('sorry, but open edit registration period are closed');
                $db['auth'][$_SERVER['PHP_AUTH_USER']] = $_SERVER['PHP_AUTH_PW'];
            }
        }
    }
}

?>
