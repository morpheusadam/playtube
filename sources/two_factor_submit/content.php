<?php 
// var_dump($_COOKIE['add_switched_account']);
// exit();
if (!isset($_COOKIE['two_factor_method']) || ($pt->config->two_factor_setting != 'on' && $pt->config->google_authenticator != 'on' && $pt->config->authy_settings != 'on') || empty($_POST['code']) || empty($_COOKIE['two_factor_username'])) {
	header("Location: " . PT_Link(''));
	exit();
}
$user = $db->where("username", PT_Secure($_COOKIE['two_factor_username']))->getOne(T_USERS);
if (empty($user)) {
    header("Location: " . PT_Link(''));
    exit();
}
$error = '';
if ($pt->config->prevent_system == 1) {
    if (!CheckCanLogin()) {
        $error = $lang->login_attempts;
    }
}
$verify = false;
if (empty($error)) {

    if ($user->two_factor_method == 'google' || $user->two_factor_method == 'authy') {
        $codes = $db->where('user_id',$user->id)->getOne(T_BACKUP_CODES);
        if (!empty($codes) && !empty($codes->codes)) {
            $backupCodes = json_decode($codes->codes,true);
            if (in_array($_POST['code'], $backupCodes)) {
                $key = array_search($_POST['code'], $backupCodes);
                $backupCodes[$key] = rand(111111,999999);
                $db->where('user_id',$user->id)->update(T_BACKUP_CODES,[
                    'codes' => json_encode($backupCodes)
                ]);
                $verify = true;
            }
        }
    }


    if ($user->two_factor_method == 'two_factor' && $user->email_code == md5($_POST['code'])) {
        $verify = true;
    }
    else if ($user->two_factor_method == 'google' && !empty($user->google_secret) && !$verify) {
        require_once 'assets/libs/google_auth/vendor/autoload.php';
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        if ($google2fa->verifyKey($user->google_secret, $_POST['code'])) {
            $verify = true;
        }
    }
    else if ($user->two_factor_method == 'authy' && !empty($user->authy_id) && !$verify && verifyAuthy($_POST['code'],$user->authy_id)) {
        $verify = true;
    }
}

if ($verify) {
	$session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
    $insert_data         = array(
        'user_id' => $user->id,
        'session_id' => $session_id,
        'time' => time()
    );
    $insert              = $db->insert(T_SESSIONS, $insert_data);

    if (!empty($_COOKIE['add_switched_account'])) {

        $session_data = $db->where('session_id',PT_Secure($_COOKIE['add_switched_account']))->getOne(T_SESSIONS);
        if (!empty($session_data) && $session_data->user_id != $user->id) {
            
            $_COOKIE['add_switched_account'] = '';
            unset($_COOKIE['add_switched_account']);
            setcookie('add_switched_account', '', -1);
            setcookie('add_switched_account', '', -1,'/');

            $pt->user = PT_UserData($session_data->user_id);

            $sessionUserId = $session_data->session_id;

            if (!in_array($user->id, array_keys($pt->switched_accounts))) {
                $info = array(
                    'email' => $pt->user->email,
                    'name'  => $pt->user->name,
                    'avatar' => $pt->user->avatar,
                    'session' => $sessionUserId,
                    'user_id' => $pt->user->id
                );

                $pt->switched_accounts[$pt->user->id] = $info;
                setcookie("switched_accounts", json_encode($pt->switched_accounts), time() + (10 * 365 * 24 * 60 * 60), "/");
            }
            session_unset();
            $_SESSION['user_id'] = '';
            session_destroy();
            $_SESSION = array();
            unset($_SESSION);
            if(isset($_COOKIE['user_id'])) {
                $_COOKIE['user_id'] = '';
                unset($_COOKIE['user_id']);
                setcookie('user_id', '', -1);
                setcookie('user_id', '', -1,'/');
            }
        }
    }

    if(isset($_COOKIE['two_factor_method'])) {
        $_COOKIE['two_factor_method'] = '';
        unset($_COOKIE['two_factor_method']);
        setcookie('two_factor_method', '', -1);
        setcookie('two_factor_method', '', -1,'/');
    }


    $_SESSION['user_id'] = $session_id;
    setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
    $pt->loggedin = true;
    if (!empty($_GET['to'])) {
        $_GET['to'] = strip_tags($_GET['to']);
        $site_url = $_GET['to'];
    }

    $db->where('id',$user->id)->update(T_USERS,array(
        'ip_address' => get_ip_address()
    ));
    
    header("Location: $site_url");
    exit();
}
else{
    if (empty($error)) {
        $error = $lang->wrong_confirm_code;
    }
	
    if ($pt->config->prevent_system == 1) {
        AddBadLoginLog();
    }
    $two_factor_method = 'two_factor';
    $message = $lang->sent_two_factor_email;
    if (!empty($_COOKIE['two_factor_method']) && in_array($_COOKIE['two_factor_method'],array('two_factor','google','authy'))) {
        $two_factor_method = $_COOKIE['two_factor_method'];
    }
    if ($two_factor_method == 'authy') {
        $message = $lang->use_authy_app;
    }
    else if ($two_factor_method == 'google') {
        $message = $lang->use_google_authenticator_app;
    }

	$pt->page        = 'login';
	$pt->title       = $lang->two_factor . ' | ' . $pt->config->title;
	$pt->description = $pt->config->description;
	$pt->keyword     = $pt->config->keyword;
	$pt->content     = PT_LoadPage('auth/two_factor_login/content',array('MESSAGE' => $message,
                                                                         'ERROR' => $error));
}

