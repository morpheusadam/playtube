<?php
if ($pt->config->switch_account == 'on') {
    if (IS_LOGGED == true) {
        if (empty($_GET['type']) || $_GET['type'] != 'add_account') {
            header("Location: " . PT_Link(''));
            exit();
        }
    }
}
else{
    if (IS_LOGGED == true) {
        header("Location: " . PT_Link(''));
        exit();
    }
}


$color1 = '0095D8';
$color2 = '87ddff';

$errors   = '';
$username = '';
if (!empty($_POST)) {
    $sessionUserId = '';
    if (!empty($_SESSION['user_id'])) {
        $sessionUserId = $_SESSION['user_id'];
        if (empty($_GET['type']) || $_GET['type'] != 'add_account') {
            $_SESSION['user_id'] = '';
            unset($_SESSION['user_id']);
        }
    }
    if (!empty($_COOKIE['user_id'])) {
        $sessionUserId =$_COOKIE['user_id'];
        if (empty($_GET['type']) || $_GET['type'] != 'add_account') {
            $_COOKIE['user_id'] = '';
            unset($_COOKIE['user_id']);
            setcookie('user_id', '', -1);
            setcookie('user_id', '', -1, '/');
        }
    }

    if (empty($_POST['username']) || empty($_POST['password'])) {
        $errors = $error_icon . $lang->please_check_details;
    } 
    else {
        if ($pt->config->prevent_system == 1) {
            if (!CheckCanLogin()) {
                $errors = $lang->login_attempts;
            }
        }
        if (empty($errors)) {
            $username        = PT_Secure($_POST['username']);
            $password        = $_POST['password'];
            //$password_hashed = sha1($password);
            $db->where("(username = ? or email = ?)", array(
                $username,
                $username
            ));

            //$db->where("password", $password_hashed);
            $login = $db->getOne(T_USERS);
            
            if (!empty($login)) {
                $hash                = 'sha1';
                if (strlen($login->password) == 60) {
                    $hash = 'password_hash';
                }

                $logged = false;
                if ($hash == 'password_hash') {
                    if (password_verify(PT_Secure($password), $login->password)) {
                        $logged = true;
                    }
                } else {
                    $login_password = $hash(PT_Secure($password));
                    $is_logged = $db->where("(username = ? or email = ?)", array(
                                    $username,
                                    $username
                                ))->where("password", $login_password)->getOne(T_USERS);
                    if (!empty($is_logged)) {
                        $new_password = PT_Secure(password_hash($password, PASSWORD_DEFAULT));
                        $db->where('id',$is_logged->id)->update(T_USERS,array('password' => $new_password));
                        $logged = true;
                    }
                }

                if ($logged) {
                    if ($login->active == 0) {
                        $errors = $error_icon . $lang->account_is_not_active . ' <a href="{{LINK resend/' . $login->email_code . '/' . $login->username . '}}">' . $lang->resend_email . '</a>';
                    } 

                    else {
                        if ($pt->config->two_factor_setting == 'on' && $login->two_factor == 1) {
                            $email        = $login->email;
                            $db->where("email", $email);
                            $user_id = $db->getValue(T_USERS, "id");
                            setcookie("two_factor_method", $login->two_factor_method, time() + (60 * 60), "/");
                            setcookie("two_factor_username", $login->username, time() + (60 * 60), "/");
                            if (!empty($user_id) && $login->two_factor_method == 'two_factor') {
                                   $rest_user = PT_UserData($user_id);
                                   $email_code = rand(111111, 999999);
                                   $hash_code = md5($email_code);
                                   $update_data = array('email_code' => $hash_code);
                                   $db->where('id', $rest_user->id);
                                   $update = $db->update(T_USERS, $update_data);
                                   $update_data['USER_DATA'] = $rest_user;
                                   $message = "Your confirmation code is: $email_code";
                                   $send_email_data = array(
                                        'from_email' => $pt->config->email,
                                        'from_name' => $pt->config->name,
                                        'to_email' => $email,
                                        'to_name' => $rest_user->name,
                                        'subject' => 'Confirmation code',
                                        'charSet' => 'UTF-8',
                                        'message_body' => $message,
                                        'is_html' => true
                                    );
                                    $send_message = PT_SendMessage($send_email_data);
                                    if ($send_message) {
                                        $success = $success_icon . $lang->email_sent;
                                    }
                            }

                            if ($pt->config->switch_account == 'on') {
                                if ($username !== $pt->user->username) {
                                    setcookie("add_switched_account", $sessionUserId, time() + (60 * 60), "/");
                                }
                            }

                            header("Location: $site_url/two_factor_login");
                            exit();
                        }
                        $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
                        $platform_details = serialize(getBrowser());
                        $insert_data         = array(
                            'user_id' => $login->id,
                            'session_id' => $session_id,
                            'platform_details' => $platform_details,
                            'time' => time()
                        );
                        $insert              = $db->insert(T_SESSIONS, $insert_data);
                        $_SESSION['user_id'] = $session_id;
                        if ($pt->config->remember_device == 1 && !empty($_POST['remember_device']) && $_POST['remember_device'] == 'on') {
                            setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
                        }
                        $pt->loggedin = true;

                        if (!empty($_GET['to']) && strpos($_GET['to'], $pt->config->site_url) !== false) {
                            $_GET['to'] = strip_tags($_GET['to']);
                            $site_url = $_GET['to'];
                        }

                        if (IS_LOGGED == true && $username !== $pt->user->username && !empty($sessionUserId)) {
                            $user_id             = $login->id;
                            if (!in_array($user_id, array_keys($pt->switched_accounts))) {
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
                            // session_unset();
                            // $_SESSION['user_id'] = '';
                            // session_destroy();
                            // $_SESSION = array();
                            // unset($_SESSION);
                            // if(isset($_COOKIE['user_id'])) {
                            //     $_COOKIE['user_id'] = '';
                            //     unset($_COOKIE['user_id']);
                            //     setcookie('user_id', '', -1);
                            //     setcookie('user_id', '', -1,'/');
                            // }
                        }
                        

                        $db->where('id',$login->id)->update(T_USERS,array(
                            'ip_address' => get_ip_address()
                        ));
                        
                        if (!empty($_GET['red'])) {
                            $site_url = urldecode($_GET['red']);
                        }
                        else{
                            if (!empty($_SERVER) && !empty($_SERVER['HTTP_REFERER']) && pt_is_url($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '?type=add_account') === false) {
                                $site_url = PT_Secure($_SERVER['HTTP_REFERER']);
                            }
                        }
                        header("Location: $site_url");
                        exit();
                    }
                }
                else{
                    $errors = $error_icon . $lang->invalid_username_or_password;
                    if ($pt->config->prevent_system == 1) {
                        AddBadLoginLog();
                    }
                }
            } else {
                $errors = $error_icon . $lang->invalid_username_or_password;
                if ($pt->config->prevent_system == 1) {
                    AddBadLoginLog();
                }
            }
        } 
    }
}
if (empty($_POST) && !empty($_GET['resend'])) {
    $_GET['resend'] = strip_tags($_GET['resend']);
    $errors = $success_icon . $lang->email_sent;
}
$pt->page        = 'login';
$pt->title       = $lang->login . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('auth/login/content', array(
    'COLOR1' => $color1,
    'COLOR2' => $color2,
    'ERRORS' => $errors,
    'USERNAME' => $username
));