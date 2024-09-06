<?php
ini_set('display_errors',0);
ini_set('display_startup_errors', 0);
error_reporting(0);

@ini_set('max_execution_time', 0);
@ini_set("memory_limit", "-1");
@set_time_limit(0);

require 'config.php';
require 'phpMailer_config.php';
require 'assets/libs/DB/vendor/autoload.php';
require 'assets/libs/getID3-1.9.14/getid3/getid3.php';
require 'assets/libs/youtube-sdk/vendor/autoload.php';
require 'assets/libs/php-rss/vendor/autoload.php';

$pt     = ToObject(array());

// Connect to MySQL Server
$mysqli     = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
$sqlConnect = $mysqli;


// Handling Server Errors
$ServerErrors = array();
if (mysqli_connect_errno()) {
    $ServerErrors[] = "Failed to connect to MySQL: " . mysqli_connect_error();
}
if (!function_exists('curl_init')) {
    $ServerErrors[] = "PHP CURL is NOT installed on your web server !";
}
if (!extension_loaded('gd') && !function_exists('gd_info')) {
    $ServerErrors[] = "PHP GD library is NOT installed on your web server !";
}
if (!extension_loaded('zip')) {
    $ServerErrors[] = "ZipArchive extension is NOT installed on your web server !";
}

if (isset($ServerErrors) && !empty($ServerErrors)) {
    foreach ($ServerErrors as $Error) {
        echo "<h3>" . $Error . "</h3>";
    }
    die();
}
$query = $mysqli->query("SET NAMES utf8mb4");
// Connecting to DB after verfication

$db = new MysqliDb($mysqli);


$http_header = 'http://';
if (!empty($_SERVER['HTTPS'])) {
    $http_header = 'https://';
}

$pt->site_pages           = array('home');
$pt->actual_link          = $http_header . $_SERVER['HTTP_HOST'] . urlencode($_SERVER['REQUEST_URI']);
$pt->actual_link = filter_var($pt->actual_link, FILTER_UNSAFE_RAW);

$config                   = PT_GetConfig();
if ($config['developer_mode'] == 'on') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
$config['affiliate_type'] = json_decode($config['affiliate_type']);
$pt->loggedin             = false;
$config['user_statics']   = stripslashes(htmlspecialchars_decode($config['user_statics']));
$config['videos_statics'] = stripslashes(htmlspecialchars_decode($config['videos_statics']));
$config['main_currency']        = !empty($config['currency_symbol_array'][$config['payment_currency']]) ? $config['currency_symbol_array'][$config['payment_currency']] : '$';
$_SESSION['theme'] = (!empty($_SESSION['theme'])) ? $_SESSION['theme'] : '';
if (!empty($_GET['theme'])) {
    if ($_GET['theme'] == 'default' || $_GET['theme'] == 'youplay' || $_GET['theme'] == 'vidplay') {
        $_SESSION['theme'] = $_GET['theme'];
    }
}
if (!empty($_SESSION['theme'])) {
    $config['theme'] = $_SESSION['theme'];
}

$config['theme_url']      = $site_url . '/themes/' . $config['theme'];
$config['site_url']       = $site_url;
$pt->script_version = $config['version'];
$config['script_version'] = $pt->script_version;

$pt->extra_config = array();
$get_nodejs_config = file_get_contents('nodejs/config.json');
$config['hostname'] = '';
$config['server_port'] = '';
if (!empty($get_nodejs_config)) {
    $pt->extra_config = json_decode($get_nodejs_config);
    $config['hostname']  = $pt->extra_config->server_ip;
    $config['server_port']  = $pt->extra_config->server_port;
} else {
    exit('Please make sure the file: nodejs/config.json exists and readable.');
}

$site = parse_url($site_url);
if (empty($site['host'])) {
    $config['hostname'] = $site['scheme'] . '://' .  $site['host'];
}


$pt->config               = ToObject($config);
$pt->config->withdrawal_payment_method = json_decode($config['withdrawal_payment_method'],true);
$langs                    = pt_db_langs();
$pt->langs                = $langs;

try {
    $pt->iso = GetIso();
} catch (Exception $e) {
    
}

if (PT_IsLogged() == true) {
    $session_id        = (!empty($_SESSION['user_id'])) ? $_SESSION['user_id'] : $_COOKIE['user_id'];
    $pt->user_session  = PT_GetUserFromSessionID($session_id);
    $user = $pt->user  = PT_UserData($pt->user_session);
    $user->wallet      = number_format($user->wallet,2);

    if (!empty($user->language) && in_array($user->language, $langs)) {
        $_SESSION['lang'] = $user->language;
    }

    if ($user->id < 0 || empty($user->id) || !is_numeric($user->id) || PT_UserActive($user->id) === false) {
        header("Location: " . PT_Link('logout'));
    }
    $pt->loggedin   = true;
}

else if (!empty($_POST['user_id']) && !empty($_POST['s'])) {
    $platform       = ((!empty($_POST['platform'])) ? $_POST['platform'] : 'phone');
    $s              = PT_Secure($_POST['s']);
    $user_id        = PT_Secure($_POST['user_id']);
    $verify_session = verify_api_auth($user_id, $s, $platform);
    if ($verify_session === true) {
        $user = $pt->user  = PT_UserData($user_id);
        if (empty($user) || PT_UserActive($user->id) === false) {
            $json_error_data = array(
                'api_status' => '400',
                'api_text' => 'authentication_failed',
                'errors' => array(
                    'error_id' => '1',
                    'error_text' => 'Error 400 - The user does not exist'
                )
            );

            echo json_encode($json_error_data, JSON_PRETTY_PRINT);
            exit();
        }

        $pt->loggedin = true;
    }
    else {
        $json_error_data = array(
            'api_status' => '400',
            'api_text' => 'authentication_failed',
            'errors' => array(
                'error_id' => '1',
                'error_text' => 'Error 400 - Session does not exist'
            )
        );
        echo json_encode($json_error_data, JSON_PRETTY_PRINT);
        exit();
    }
}
else if (!empty($_GET['access_token'])) {
    $session = $db->where('session_id', PT_Secure($_GET['access_token']))->getOne(T_SESSIONS);
    if (!empty($session) && !empty($session->user_id)) {
        $user = $pt->user  = PT_UserData($session->user_id);
        if (empty($user) || PT_UserActive($user->id) === false) {
            $json_error_data = array(
                'api_status' => '400',
                'api_text' => 'authentication_failed',
                'errors' => array(
                    'error_id' => '1',
                    'error_text' => 'Error 400 - The user does not exist'
                )
            );

            echo json_encode($json_error_data, JSON_PRETTY_PRINT);
            exit();
        }

        $pt->loggedin = true;
    }
    else {
        $json_error_data = array(
            'api_status' => '400',
            'api_text' => 'authentication_failed',
            'errors' => array(
                'error_id' => '1',
                'error_text' => 'Error 400 - Session does not exist'
            )
        );
        echo json_encode($json_error_data, JSON_PRETTY_PRINT);
        exit();
    }
} else if (!empty($_GET['user_id']) && !empty($_GET['s'])) {
    $platform       = ((!empty($_GET['platform'])) ? $_GET['platform'] : 'phone');
    $s              = PT_Secure($_GET['s']);
    $user_id        = PT_Secure($_GET['user_id']);
    $verify_session = verify_api_auth($user_id, $s, $platform);
    if ($verify_session === true) {
        $user = $pt->user  = PT_UserData($user_id);
        if (empty($user) || PT_UserActive($user->id) === false) {
            $json_error_data = array(
                'api_status' => '400',
                'api_text' => 'authentication_failed',
                'errors' => array(
                    'error_id' => '1',
                    'error_text' => 'Error 400 - The user does not exist'
                )
            );

            echo json_encode($json_error_data, JSON_PRETTY_PRINT);
            exit();
        }

        $pt->loggedin = true;
    }
    else {
        $json_error_data = array(
            'api_status' => '400',
            'api_text' => 'authentication_failed',
            'errors' => array(
                'error_id' => '1',
                'error_text' => 'Error 400 - Session does not exist'
            )
        );
        echo json_encode($json_error_data, JSON_PRETTY_PRINT);
        exit();
    }
}

elseif (!empty($_GET['cookie']) && $pt->loggedin != true) {
    $session_id            = $_GET['cookie'];
    $pt->user_session      = PT_GetUserFromSessionID($session_id);
    if (!empty($pt->user_session) && is_numeric($pt->user_session)) {
        $user = $pt->user  = PT_UserData($pt->user_session);
        $pt->loggedin      = true;

        if (!empty($user->language)) {
            if (file_exists(__DIR__ . '/../langs/' . $user->language . '.php')) {
                $_SESSION['lang'] = $user->language;
            }
        }
        setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
    }
}
$pt->phone_api        = 'no';
if (!empty($_GET['cookie'])) {
    $session_id            = $_GET['cookie'];
    $pt->user_session      = PT_GetUserFromSessionID($session_id);
    if (!empty($pt->user_session) && is_numeric($pt->user_session)) {
        $pt->phone_api        = 'yes';
    }
}

if (isset($_GET['lang']) AND !empty($_GET['lang'])) {
    $lang_name = PT_Secure(strtolower($_GET['lang']));

    if (in_array($lang_name, $langs)) {
        $_SESSION['lang'] = $lang_name;
        if ($pt->loggedin == true) {
            $db->where('id', $user->id)->update(T_USERS, array('language' => $lang_name));
        }
    }
}

if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = $pt->config->language;
}

if (isset($_SESSION['user_id'])) {
    if (empty($_COOKIE['user_id'])) {
        setcookie("user_id", $_SESSION['user_id'], time() + (10 * 365 * 24 * 60 * 60), "/");
    }
}

$pt->language      = $_SESSION['lang'];
$pt->language_type = 'ltr';

// Add rtl languages here.
$rtl_langs           = array(
    'arabic',
    'persian',
    'hebrew',
    'urdu'
);

// checking if corrent language is rtl.
foreach ($rtl_langs as $lang) {
    if ($pt->language == strtolower($lang)) {
        $pt->language_type = 'rtl';
    }
}


// Include Language File
$lang_file = 'assets/langs/' . $pt->language . '.php';
if (file_exists($lang_file)) {
    require($lang_file);
}



$lang_array = pt_get_langs($pt->language);

if (empty($lang_array)) {
    $lang_array = pt_get_langs();
}

$lang       = ToObject($lang_array);
$pt->all_lang = $lang;
$pt->langs_status = LangsStatus();

$pt->exp_feed    = false;
$pt->userDefaultAvatar = 'upload/photos/d-avatar.jpg';
$pt->categories  = ToObject($categories);
$categories = array();
$sub_categories = array();

try {
    $all_categories = $db->where('type','category')->get(T_LANGS);
    $sub_categories = array();
    foreach ($all_categories as $key => $value) {
        $array_keys = array_keys($all_categories);
        if ($value->lang_key != 'other') {
            if (!empty($value->lang_key) && !empty($lang->{$value->lang_key})) {
                $categories[$value->lang_key] = $lang->{$value->lang_key};
            }
            $all_sub_categories = $db->where('type',$value->lang_key)->get(T_LANGS);

            if (!empty($all_sub_categories)) {
                foreach ($all_sub_categories as $key => $sub) {
                    $array = array();
                    if (!empty($sub->lang_key) && !empty($lang->{$sub->lang_key})) {
                        $array[$sub->lang_key] = $lang->{$sub->lang_key};
                        $sub_categories[$value->lang_key][] = $array;
                    }
                }
            }
        }
        if (end($array_keys) == $key) {
            $categories['other'] = $lang->other;
        }

    }
} catch (Exception $e) {

}

$pt->categories  = ToObject($categories);
$pt->sub_categories = $sub_categories;

$movies_categories = array();
try {
    $all_movies_categories = $db->where('type','movie_category')->get(T_LANGS);
    if (!empty($all_movies_categories)) {

        foreach ($all_movies_categories as $key => $value) {
            $array_keys = array_keys($all_movies_categories);
            if ($value->lang_key != 'other') {
                if (!empty($value->lang_key) && !empty($lang->{$value->lang_key})) {
                    $movies_categories[$value->lang_key] = $lang->{$value->lang_key};
                }
            }
            if (end($array_keys) == $key) {
                $movies_categories['other'] = $lang->other;
            }
        }
    }
    else{
        $movies_categories['other'] = $lang->other;
    }
} catch (Exception $e) {

}
$pt->movies_categories = $movies_categories;



$error_icon   = '<i class="fa fa-exclamation-circle"></i> ';
$success_icon = '<i class="fa fa-check"></i> ';
define('IS_LOGGED', $pt->loggedin);
define('none', null);



if (pt_is_banned($_SERVER["REMOTE_ADDR"]) === true) {
    $banpage = PT_LoadPage('terms/ban');
    exit($banpage);
}


if ($pt->config->user_ads == 'on') {

    if (!isset($_COOKIE['_uads'])) {
        setcookie('_uads', htmlentities(serialize(array(
            'date' => strtotime('+1 day'),
            'uaid_' => array()
        ))), time() + (10 * 365 * 24 * 60 * 60),'/');
    }

    $pt->user_ad_cons = array(
        'date' => strtotime('+1 day'),
        'uaid_' => array()
    );

    if (!empty($_COOKIE['_uads'])) {
        $pt->user_ad_cons = unserialize(html_entity_decode($_COOKIE['_uads']));
    }

    if (!is_array($pt->user_ad_cons) || !isset($pt->user_ad_cons['date']) || !isset($pt->user_ad_cons['uaid_'])) {
        setcookie('_uads', htmlentities(serialize(array(
            'date' => strtotime('+1 day'),
            'uaid_' => array()
        ))), time() + (10 * 365 * 24 * 60 * 60),'/');
    }

    if (is_array($pt->user_ad_cons) && isset($pt->user_ad_cons['date']) && $pt->user_ad_cons['date'] < time()) {
        setcookie('_uads', htmlentities(serialize(array(
            'date' => strtotime('+1 day'),
            'uaid_' => array()
        ))),time() + (10 * 365 * 24 * 60 * 60),'/');
    }
}

$pt->mode = (!empty($_COOKIE['mode'])) ? $_COOKIE['mode'] : null;
if ($pt->config->night_mode == 'night_default' && empty($pt->mode)) {
    $pt->mode = 'night';
}
if (empty($_COOKIE['mode']) || !in_array($_COOKIE['mode'], array('night','day')) && empty($pt->mode)) {
    $pt->mode = ($pt->config->night_mode == 'night_default' || $pt->config->night_mode == 'night') ? 'night' : 'day';
    setcookie("mode", $pt->mode, time() + (10 * 365 * 24 * 60 * 60), "/");
}

if (!empty($_POST['mode']) && in_array($_POST['mode'], array('night','day'))) {
    setcookie("mode", $_POST['mode'], time() + (10 * 365 * 24 * 60 * 60), "/");
    $pt->mode = $_POST['mode'];
}

if (!empty($_GET['mode']) && in_array($_GET['mode'], array('night','day'))) {
    setcookie("mode", $_GET['mode'], time() + (10 * 365 * 24 * 60 * 60), "/");
    $pt->mode = $_GET['mode'];
}

if ($pt->config->night_mode == 'light') {
    $pt->mode = 'light';
}

$site_url    = $pt->config->site_url;
$request_url = $_SERVER['REQUEST_URI'];
$fl_currpage = "{$site_url}{$request_url}";


if (empty($_SESSION['uploads'])) {

    $_SESSION['uploads'] = array();

    if (empty($_SESSION['uploads']['videos'])) {
        $_SESSION['uploads']['videos'] = array();
    }

    if (empty($_SESSION['uploads']['images'])) {
        $_SESSION['uploads']['images'] = array();
    }
}

$pt->theme_using = 'default';
$path_to_details = './themes/' . $config['theme'] . '/fonts/info.json';
if (file_exists($path_to_details)) {
    $get_theme_info = file_get_contents($path_to_details);
    $decode_json = json_decode($get_theme_info, true);
    if (!empty($decode_json['name'])) {
        $pt->theme_using = $decode_json['name'];
    }
}

$pt->continents = array('Asia','Australia','Africa','Europe','America','Atlantic','Pacific','Indian');
try {
    $pt->blocked_array = GetBlockedIds();
} catch (Exception $e) {
    $pt->blocked_array = [];
}

try {
    $pt->custom_pages = $db->get(T_CUSTOM_PAGES);
} catch (Exception $e) {
    $pt->custom_pages = [];
}
$pt->v_shorts = array();
if (!empty($_COOKIE['v_shorts'])) {
    $pt->v_shorts = json_decode($_COOKIE['v_shorts'],true);
}

$pt->config->currency_array = unserialize($pt->config->currency_array);
$pt->config->currency_symbol_array = unserialize($pt->config->currency_symbol_array);

$pt->paypal_currency = array('USD','EUR','AUD','BRL','CAD','CZK','DKK','HKD','HUF','INR','ILS','JPY','MYR','MXN','TWD','NZD','NOK','PHP','PLN','GBP','RUB','SGD','SEK','CHF','THB');
$pt->checkout_currency = array('USD','EUR','AED','AFN','ALL','ARS','AUD','AZN','BBD','BDT','BGN','BMD','BND','BOB','BRL','BSD','BWP','BYN','BZD','CAD','CHF','CLP','CNY','COP','CRC','CZK','DKK','DOP','DZD','EGP','FJD','GBP','GTQ','HKD','HNL','HRK','HUF','IDR','ILS','INR','JMD','JOD','JPY','KES','KRW','KWD','KZT','LAK','LBP','LKR','LRD','MAD','MDL','MMK','MOP','MRO','MUR','MVR','MXN','MYR','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','SAR','SBD','SCR','SEK','SGD','SYP','THB','TND','TOP','TRY','TTD','TWD','UAH','UYU','VND','VUV','WST','XCD','XOF','YER','ZAR');
$pt->stripe_currency = array('USD','EUR','AUD','BRL','CAD','CZK','DKK','HKD','HUF','ILS','JPY','MYR','MXN','TWD','NZD','NOK','PHP','PLN','RUB','SGD','SEK','CHF','THB','GBP');
if (!empty($pt->config->fav_category)) {
    $pt->config->fav_category = json_decode($pt->config->fav_category);
}
else{
    $pt->config->fav_category = array();
}
$pt->config->hours = array('12:00AM' => '12:00 AM','12:15AM' => '12:15 AM','12:30AM' => '12:30 AM','12:45AM' => '12:45 AM','1:00AM' => '1:00 AM','1:15AM' => '1:15 AM','1:30AM' => '1:30 AM','1:45AM' => '1:45 AM','2:00AM' => '2:00 AM','2:15AM' => '2:15 AM','2:30AM' => '2:30 AM','2:45AM' => '2:45 AM','3:00AM' => '3:00 AM','3:15AM' => '3:15 AM','3:30AM' => '3:30 AM','3:45AM' => '3:45 AM','4:00AM' => '4:00 AM','4:15AM' => '4:15 AM','4:30AM' => '4:30 AM','4:45AM' => '4:45 AM','5:00AM' => '5:00 AM','5:15AM' => '5:15 AM','5:30AM' => '5:30 AM','5:45AM' => '5:45 AM','6:00AM' => '6:00 AM','6:15AM' => '6:15 AM','6:30AM' => '6:30 AM','6:45AM' => '6:45 AM','7:00AM' => '7:00 AM','7:15AM' => '7:15 AM','7:30AM' => '7:30 AM','7:45AM' => '7:45 AM','8:00AM' => '8:00 AM','8:15AM' => '8:15 AM','8:30AM' => '8:30 AM','8:45AM' => '8:45 AM','9:00AM' => '9:00 AM','9:15AM' => '9:15 AM','9:30AM' => '9:30 AM','9:45AM' => '9:45 AM','10:00AM' => '10:00 AM','10:15AM' => '10:15 AM','10:30AM' => '10:30 AM','10:45AM' => '10:45 AM','11:00AM' => '11:00 AM','11:15AM' => '11:15 AM','11:30AM' => '11:30 AM','11:45AM' => '11:45 AM','12:00PM' => '12:00 PM','12:15PM' => '12:15 PM','12:30PM' => '12:30 PM','12:45PM' => '12:45 PM','1:00PM' => '1:00 PM','1:15PM' => '1:15 PM','1:30PM' => '1:30 PM','1:45PM' => '1:45 PM','2:00PM' => '2:00 PM','2:15PM' => '2:15 PM','2:30PM' => '2:30 PM','2:45PM' => '2:45 PM','3:00PM' => '3:00 PM','3:15PM' => '3:15 PM','3:30PM' => '3:30 PM','3:45PM' => '3:45 PM','4:00PM' => '4:00 PM','4:15PM' => '4:15 PM','4:30PM' => '4:30 PM','4:45PM' => '4:45 PM','5:00PM' => '5:00 PM','5:15PM' => '5:15 PM','5:30PM' => '5:30 PM','5:45PM' => '5:45 PM','6:00PM' => '6:00 PM','6:15PM' => '6:15 PM','6:30PM' => '6:30 PM','6:45PM' => '6:45 PM','7:00PM' => '7:00 PM','7:15PM' => '7:15 PM','7:30PM' => '7:30 PM','7:45PM' => '7:45 PM','8:00PM' => '8:00 PM','8:15PM' => '8:15 PM','8:30PM' => '8:30 PM','8:45PM' => '8:45 PM','9:00PM' => '9:00 PM','9:15PM' => '9:15 PM','9:30PM' => '9:30 PM','9:45PM' => '9:45 PM','10:00PM' => '10:00 PM','10:15PM' => '10:15 PM','10:30PM' => '10:30 PM','10:45PM' => '10:45 PM','11:00PM' => '11:00 PM','11:15PM' => '11:15 PM','11:30PM' => '11:30 PM','11:45PM' => '11:45 PM');


require 'context_data.php';
if ($pt->config->push == 1) {
    require_once('assets/includes/onesignal_config.php');
}
$pt->config->main_payment_currency = $config['main_payment_currency'] = !empty($pt->config->currency_symbol_array[$pt->config->payment_currency]) ? $pt->config->currency_symbol_array[$pt->config->payment_currency] : '$';

$pt->week_days = array('saturday' => 'saturday',
                       'sunday' => 'sunday',
                       'monday' => 'monday',
                       'tuesday' => 'tuesday',
                       'wednesday' => 'wednesday',
                       'thursday' => 'thursday',
                       'friday' => 'friday',
                       'sat' => 'saturday',
                       'sun' => 'sunday',
                       'mon' => 'monday',
                       'tue' => 'tuesday',
                       'wed' => 'wednesday',
                       'thu' => 'thursday',
                       'fri' => 'friday');

$pt->year_months = array('january' => 'january',
                         'february' => 'february',
                         'march' => 'march',
                         'april' => 'april',
                         'may' => 'may',
                         'june' => 'june',
                         'july' => 'july',
                         'august' => 'august',
                         'september' => 'september',
                         'october' => 'october',
                         'november' => 'november',
                         'december' => 'december',
                         'jan' => 'january',
                         'feb' => 'february',
                         'mar' => 'march',
                         'apr' => 'april',
                         'may' => 'may',
                         'jun' => 'june',
                         'jul' => 'july',
                         'aug' => 'august',
                         'sep' => 'september',
                         'oct' => 'october',
                         'nov' => 'november',
                         'dec' => 'december');

$terms_langs = $db->where("lang_key = 'terms_of_use_page' OR lang_key = 'privacy_policy_page' OR lang_key = 'about_page' OR lang_key = 'refund_terms_page'")->get(T_LANGS);

$pt->terms_of_use_page = 1;
$pt->privacy_policy_page = 1;
$pt->about_page = 1;
$pt->refund_terms_page = 1;
foreach ($terms_langs as $key => $value) {
    if (in_array($value->type, array('on','off'))) {
        if ($value->type == 'on') {
            $pt->{$value->lang_key} = 1;
        }
        else{
            $pt->{$value->lang_key} = 0;
        }
    }
    else{
        $db->where('lang_key',$value->lang_key)->update(T_LANGS,array('type' => 'on'));
    }
}

$pt->config->filesVersion = '3.1';

if ($pt->config->filesVersion != $pt->config->version) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
$pt->page_url_ = PT_Link('');
$pt->remoteStorage = ($pt->config->s3_upload == 'on' || $pt->config->ftp_upload == 'on' || $pt->config->spaces == 'on' || $pt->config->wasabi_storage == 'on' || $pt->config->backblaze_storage == 'on' || $pt->config->cloud_upload == 'on' || $pt->config->yandex_storage == 'on') ? true : false;
try {
    $pt->pro_packages       = GetAllProInfo();
} catch (\Throwable $th) {
    
}

$pt->manage_pro_features = array('who_can_article' => 'can_use_article',
                                 'who_can_playlist' => 'can_use_playlist',
                                 'who_can_post' => 'can_use_post',
                                 'who_can_payed_subscribers' => 'can_use_payed_subscribers',
                                 'who_can_donate' => 'can_use_donate',
                                 'who_can_invite_links' => 'can_use_invite_links',
                                 'who_can_point' => 'can_use_point',
                                 'who_can_upload' => 'can_use_upload',
                                 'who_can_import' => 'can_use_import',
                                 'who_can_youtube_short' => 'can_use_youtube_short',
                                 'who_can_ok_import' => 'can_use_ok_import',
                                 'who_can_facebook_import' => 'can_use_facebook_import',
                                 'who_can_instagram_import' => 'can_use_instagram_import',
                                 'who_can_twitch_import' => 'can_use_twitch_import',
                                 'who_can_tiktok_import' => 'can_use_tiktok_import',
                                 'who_can_m3u8_import' => 'can_use_m3u8_import',
                                 'who_can_embed_videos' => 'can_use_embed_videos',
                                 'who_can_trailer_system' => 'can_use_trailer_system',
                                 'who_can_restrict_embedding' => 'can_use_restrict_embedding',
                                 'who_can_video_text' => 'can_use_video_text',
                                 'who_can_stock_videos' => 'can_use_stock_videos',
                                 'who_can_download_videos' => 'can_use_download_videos',
                                 'who_can_movies_videos' => 'can_use_movies_videos',
                                 'who_can_geo_blocking' => 'can_use_geo_blocking',
                                 'who_can_shorts' => 'can_use_shorts',
                                 'who_can_hashtag' => 'can_use_hashtag',
                                 'who_can_sell_videos' => 'can_use_sell_videos',
                                 'who_can_rent_videos' => 'can_use_rent_videos',
                                 'who_can_live_video' => 'can_use_live_video',
                                 'who_can_live_save' => 'can_use_live_save',
                                 'who_can_user_ads' => 'can_use_user_ads',
                                 'who_can_usr_v_mon' => 'can_use_usr_v_mon',
                                 'who_can_affiliate' => 'can_use_affiliate',
                                 'who_can_affiliate_new_user' => 'can_use_affiliate_new_user',
                                 'who_can_affiliate_pro' => 'can_use_affiliate_pro',
                                 'who_can_affiliate_subscribe' => 'can_use_affiliate_subscribe',
                                 'who_can_affiliate_buy_rent' => 'can_use_affiliate_buy_rent',
                                 'who_can_pro_google' => 'can_use_pro_google',
                                );
$pt->proFeaturesKeys = array();
foreach ($pt->manage_pro_features as $key => $value) {
    $pt->config->{$value} = true;
    if ($pt->loggedin && !empty($pt->user)) {
        if ($pt->config->{$key} == 'admin' && !$pt->user->admin) {
            $pt->config->{$value} = false;
        }
        if ($pt->config->{$key} == 'pro' && !$pt->user->is_pro) {
            $pt->config->{$value} = false;
        }
        if ($pt->config->{$key} == 'pro' && $pt->user->is_pro && !empty($pt->pro_packages[$pt->user->pro_type]) && $pt->pro_packages[$pt->user->pro_type][$value] != 1) {
            $pt->config->{$value} = false;
        }
        if ($pt->user->admin) {
            $pt->config->{$value} = true;
        }
        $pt->proFeaturesKeys[$value] = $key;
    }
}
if ($pt->config->article_system == 'on') {
    $pt->config->all_create_articles = 'off';
    if ($pt->config->can_use_article) {
        $pt->config->all_create_articles = 'on';
    }
}
if ($pt->config->point_level_system == 1 && !$pt->config->can_use_point) {
    $pt->config->point_level_system = 0;
}
$pt->config->upload_system_type = 0;
if ($pt->config->upload_system == 'on' && !$pt->config->can_use_upload) {
    $pt->config->upload_system = 'off';
}
if ($pt->config->restrict_embedding_system == 'on' && !$pt->config->can_use_restrict_embedding) {
    $pt->config->restrict_embedding_system = 'off';
}

if ($pt->config->video_text_system == 'on' && !$pt->config->can_use_video_text) {
    $pt->config->video_text_system = 'off';
}
if ($pt->config->download_videos == 'on' && !$pt->config->can_use_download_videos) {
    $pt->config->download_videos = 'off';
}
if ($pt->config->hashtag_system == 'on' && !$pt->config->can_use_hashtag) {
    $pt->config->hashtag_system = 'off';
}
if ($pt->config->pro_google == 'on' && !$pt->config->can_use_pro_google) {
    $pt->config->pro_google = 'off';
}
$pt->config->who_use_live = 'all';
if ($pt->config->live_video == 1 && !$pt->config->can_use_live_video) {
    $pt->config->who_use_live = 'admin';
}
if ($pt->config->live_video_save == 1 && !$pt->config->can_use_live_save) {
    $pt->config->live_video_save = 0;
}



if ($pt->config->movies_videos == 'on') {
    if (!$pt->config->can_use_trailer_system) {
        $pt->config->trailer_system = 'off';
    }
    if (!$pt->config->can_use_movies_videos) {
        $pt->config->movies_videos = 'off';
    }
}
if ($pt->config->affiliate_system == 1) {
    if (!$pt->config->can_use_affiliate_new_user) {
        $pt->config->affiliate_type->affiliate_new_user = 0;
    }
    if (!$pt->config->can_use_affiliate_pro) {
        $pt->config->affiliate_type->affiliate_pro = 0;
    }
    if (!$pt->config->can_use_affiliate_subscribe) {
        $pt->config->affiliate_type->affiliate_subscribe = 0;
    }
    if (!$pt->config->can_use_affiliate_buy_rent) {
        $pt->config->affiliate_type->affiliate_buy_rent = 0;
    }

    if (!$pt->config->can_use_affiliate) {
        $pt->config->affiliate_type->affiliate_new_user = 0;
        $pt->config->affiliate_type->affiliate_pro = 0;
        $pt->config->affiliate_type->affiliate_subscribe = 0;
        $pt->config->affiliate_type->affiliate_buy_rent = 0;
    }
}
if ($pt->config->import_system == 'on') {
    if (!$pt->config->can_use_youtube_short) {
        $pt->config->youtube_short = 'off';
    }
    if (!$pt->config->can_use_ok_import) {
        $pt->config->ok_import = 'off';
    }
    if (!$pt->config->can_use_facebook_import) {
        $pt->config->facebook_import = 'off';
    }
    if (!$pt->config->can_use_instagram_import) {
        $pt->config->instagram_import = 'off';
    }
    if (!$pt->config->can_use_twitch_import) {
        $pt->config->twitch_import = 'off';
    }
    if (!$pt->config->can_use_tiktok_import) {
        $pt->config->tiktok_import = 'off';
    }
    if (!$pt->config->can_use_m3u8_import) {
        $pt->config->m3u8_import = 'off';
    }
    if (!$pt->config->can_use_embed_videos) {
        $pt->config->embed_videos = 'off';
    }
    if (!$pt->config->can_use_import) {
        $pt->config->import_system = 'off';
        $pt->config->youtube_short = 'off';
        $pt->config->ok_import = 'off';
        $pt->config->facebook_import = 'off';
        $pt->config->instagram_import = 'off';
        $pt->config->twitch_import = 'off';
        $pt->config->tiktok_import = 'off';
        $pt->config->embed_videos = 'off';
        $pt->config->m3u8_import = 'off';
    }
}
$pt->hiddenConfig = (array)$pt->config;

$pt->switched_accounts = [];
if (!empty($_COOKIE['switched_accounts']) && $pt->loggedin) {
    $switched_accounts = json_decode($_COOKIE['switched_accounts'],true);

    $sessionUserId = '';
    if (!empty($_SESSION['user_id'])) {
        $sessionUserId = $_SESSION['user_id'];
    }
    else if (!empty($_COOKIE['user_id'])) {
        $sessionUserId =$_COOKIE['user_id'];
    }

    $info = array(
        'email' => $pt->user->email,
        'name'  => $pt->user->name,
        'avatar' => $pt->user->avatar,
        'session' => $sessionUserId,
        'user_id' => $pt->user->id
    );

    $pt->switched_accounts[$pt->user->id] = $info;

    foreach ($switched_accounts as $key => $value) {
        if (!in_array($value['user_id'], array_keys($pt->switched_accounts))) {
            $sessionExist =  $db->where('user_id', $value['user_id'])->where('session_id', $value['session'])->getValue(T_SESSIONS, 'COUNT(*)');
            if ($sessionExist > 0) {
                $pt->switched_accounts[$value['user_id']] = $value;
            }
        }
    }
    setcookie("switched_accounts", json_encode($pt->switched_accounts), time() + (10 * 365 * 24 * 60 * 60));
}