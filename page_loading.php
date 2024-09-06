<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+


require_once('./assets/init.php');
decryptConfigData();

$page = 'home';
if (isset($_GET['link1'])) {
    $page = $_GET['link1'];
}
$pt->is_ajax_load = true;
if ($pt->config->pop_up_18 == 'on' && (((!empty($_COOKIE['pop_up_18']) && $_COOKIE['pop_up_18'] == 'no') || empty($_COOKIE['pop_up_18']))  && $page != 'age_block' && !IS_LOGGED)) {
    header('Location: ' .PT_Link($page));
    exit();
}

$maintenance_mode = false;
if ( $pt->config->maintenance_mode == 'on' ) {
    if ( IS_LOGGED === false ) {
        $maintenance_mode = true;
        if(isset($_GET['access']) && $_GET['access'] == 'admin'){
            $maintenance_mode = false;
        }
    } else {
        if ($pt->user->admin === "0") {
            $maintenance_mode = true;
        }

    }

    if( $maintenance_mode === true ){
        $file_location = "./sources/maintenance/content.php";
        if (file_exists($file_location)) {
            require_once $file_location;
        }
    }
}

if (IS_LOGGED == true) {
    if ($user->last_active < (time() - 60)) {
        $update = $db->where('id', $user->id)->update('users', array(
            'last_active' => time()
        ));
    }
}

if (IS_LOGGED) {
    if ($pt->config->require_subcription == 'on' && !$pt->user->is_pro && !PT_IsAdmin() && $page == 'watch') {
        $page = 'go_pro';
    }
}
else{
    $require_pages = array('login','confirm','contact','embed','forgot_password','register','404','logout','resend','reset-password','terms','articles','videos','movies','popular_channels','home','affiliates');
    if ($pt->config->require_subcription == 'on' && !in_array($page, $require_pages)) {
        $page = 'login';
    }
}


if (file_exists("./sources/$page/content.php")) {
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            if (!is_array($value)) {
                $value = preg_replace('/on[^<>=]+=[^<>]*/m', '', $value);
                $_GET[$key] = strip_tags($value);
            }
            else{
                foreach ($value as $keyv => $valuev) {
                    $valuev = preg_replace('/on[^<>=]+=[^<>]*/m', '', $valuev);
                    $value[$keyv] = strip_tags($valuev);
                }
                $_GET[$key] = $value;
            }
        }
    }
    if (!empty($_POST)) {
        foreach ($_POST as $key => $value) {
            if (!is_array($value)) {
                $value = preg_replace('/on[^<>=]+=[^<>]*/m', '', $value);
                $_POST[$key] = strip_tags($value);
            }
            else{
                foreach ($value as $keyv => $valuev) {
                    $valuev = preg_replace('/on[^<>=]+=[^<>]*/m', '', $valuev);
                    $value[$keyv] = strip_tags($valuev);
                }
                $_POST[$key] = $value;
            }
        }
    }
    include("./sources/$page/content.php");
}

if (empty($pt->content)) {
    include("./sources/404/content.php");
}


if (!empty($pt->config->seo)) {
    $seo = json_decode($pt->config->seo,true);
    if (in_array($pt->page, array_keys($seo))) {
        $pt->title       = str_replace('{SITE_TITLE}', $pt->config->title, $seo[$page]['title']);
        $pt->title = preg_replace_callback("/{LANG_KEY (.*?)}/", function($m) use ($lang_array) {
            return (isset($lang_array[$m[1]])) ? $lang_array[$m[1]] : '';
        }, $pt->title);
        $pt->description = str_replace('{SITE_DESC}', $pt->config->description, $seo[$page]['meta_description']);
        $pt->keyword     = str_replace('{SITE_KEYWORDS}', $pt->config->keyword, $seo[$page]['meta_keywords']);
    }
}


$data['title'] = $pt->title;
$data['description'] = $pt->description;
$data['keyword'] = $pt->keyword;
$data['page'] = $pt->page;
$data['url'] = $pt->page_url_;
$data['is_movie'] = false;
if ((!empty($pt->get_video) && $pt->get_video->is_movie) || $pt->page == 'movies') {
	$data['is_movie'] = true;
}

?>
<input type="hidden" id="json-data" value='<?php echo htmlspecialchars(json_encode($data));?>'>
<?php
echo $pt->content;
$db->disconnect();
unset($pt);
?>
