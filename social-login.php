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
$provider = "";
$types = array(
    'Google',
    'Facebook',
    'Twitter',
    'Vkontakte',
    'LinkedIn',
    'Instagram',
    'QQ',
    'WeChat',
    'Discord',
    'Mailru',
    'TikTok'
);

if (isset($_GET['provider']) && in_array($_GET['provider'], $types)) {
    $provider = PT_Secure($_GET['provider']);
}
if(empty($_GET['provider']) && !empty($_COOKIE['provider']) && in_array($_COOKIE['provider'], $types)){
    $provider = PT_Secure($_COOKIE['provider']);
}


require_once('./assets/libs/social-login/config.php');
require_once('./assets/libs/social-login/vendor/autoload.php');

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

if (isset($provider) && in_array($provider, $types)) {
    try {
        if ($provider != 'TikTok') {
            $hybridauth   = new Hybridauth($LoginWithConfig);
            $authProvider = $hybridauth->authenticate($provider);
            $tokens = $authProvider->getAccessToken();
            $user_profile = $authProvider->getUserProfile();
        }
        else{
            require_once('./assets/libs/tiktok/src/Connector.php');
            $_TK = new Connector($pt->config->tiktok_client_key, $pt->config->tiktok_client_secret, $LoginWithConfig['callback']);
            if (Connector::receivingResponse()) { 
                try {
                    $token = $_TK->verifyCode($_GET[Connector::CODE_PARAM]);
                    // Your logic to store the access token
                    $user_profile = $_TK->getUser();
                    $user_profile->identifier = $user_profile->union_id;
                    $user_profile->displayName = $user_profile->display_name;
                    $user_profile->firstName = $user_profile->display_name;
                    $user_profile->email = '';
                    $user_profile->profileURL = '';
                    $user_profile->lastName = '';
                    $user_profile->photoURL = $user_profile->avatar_larger;
                    $user_profile->description = '';
                    $user_profile->gender = '';
                    // Your logic to manage the User info
                    //$videos = $_TK->getUserVideoPages();
                    // Your logic to manage the Video info
                } catch (Exception $e) {
                    echo "Error: ".$e->getMessage();
                    echo '<br /><a href="'.$_TK->getRedirect().'">Retry</a>';
                    exit();
                }
            } else {
                header("Location: " . $_TK->getRedirect());
                exit();
            }
        }
        
        if ($user_profile && isset($user_profile->identifier)) {
            $name = $user_profile->firstName;
            if ($provider == 'Google') {
                $notfound_email     = 'go_';
                $notfound_email_com = '@google.com';
            } else if ($provider == 'Facebook') {
                $notfound_email     = 'fa_';
                $notfound_email_com = '@facebook.com';
            } else if ($provider == 'Twitter') {
                $notfound_email     = 'tw_';
                $notfound_email_com = '@twitter.com';
            } else if ($provider == 'LinkedIn') {
                $notfound_email     = 'li_';
                $notfound_email_com = '@linkedIn.com';
            } else if ($provider == 'Vkontakte') {
                $notfound_email     = 'vk_';
                $notfound_email_com = '@vk.com';
            } else if ($provider == 'Instagram') {
                $notfound_email     = 'in_';
                $notfound_email_com = '@instagram.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'QQ') {
                $notfound_email     = 'qq_';
                $notfound_email_com = '@qq.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'WeChat') {
                $notfound_email     = 'wechat_';
                $notfound_email_com = '@wechat.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'Discord') {
                $notfound_email     = 'discord_';
                $notfound_email_com = '@discord.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'Mailru') {
                $notfound_email     = 'mailru_';
                $notfound_email_com = '@mailru.com';
                $name = $user_profile->displayName;
            }
            $user_name  = $notfound_email . $user_profile->identifier;
            $user_email = $user_name . $notfound_email_com;
            if (!empty($user_profile->email)) {
                $user_email = $user_profile->email;
                if(empty($user_profile->emailVerified) && $provider == 'Discord') {
                    exit("Your E-mail is not verfied on Discord.");
                }
            }
            if (PT_UserEmailExists($user_email) === true) {
            	$db->where('email', $user_email);
            	$login = $db->getOne(T_USERS);
                $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
                $insert_data         = array(
                    'user_id' => $login->id,
                    'session_id' => $session_id,
                    'time' => time()
                );
                $insert              = $db->insert(T_SESSIONS, $insert_data);
                $_SESSION['user_id'] = $session_id;
                setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
                header("Location: $site_url");
                exit();
            } else {
                $str          = md5(microtime());
                $id           = substr($str, 0, 9);
                $password     = substr(md5(time()), 0, 9);
                $user_uniq_id = (empty($db->where('username', $id)->getValue(T_USERS, 'id'))) ? $id : 'u_' . $id;
                $social_url   = substr($user_profile->profileURL, strrpos($user_profile->profileURL, '/') + 1);
                $re_data      = array(
                    'username' => PT_Secure($user_uniq_id, 0),
                    'email' => PT_Secure($user_email, 0),
                    'password' => PT_Secure(sha1($password), 0),
                    'email_code' => PT_Secure(sha1($user_uniq_id), 0),
                    'first_name' => PT_Secure($name),
                    'last_name' => PT_Secure($user_profile->lastName),
                    'avatar' => PT_Secure(PT_ImportImageFromLogin($user_profile->photoURL)),
                    'src' => PT_Secure($provider),
                    'active' => '1'
                );
                $re_data['language'] = $pt->config->language;
                if (!empty($_SESSION['lang'])) {
                    if (in_array($_SESSION['lang'], $langs)) {
                        $re_data['language'] = $_SESSION['lang'];
                    }
                }
                if ($provider == 'Google') {
                    $re_data['about']  = PT_Secure($user_profile->description);
                    $re_data['google'] = PT_Secure($social_url);
                }
                if ($provider == 'Facebook') {
                    $fa_social_url       = @explode('/', $user_profile->profileURL);
                    $re_data['facebook'] = PT_Secure($fa_social_url[4]);
                    $re_data['gender'] = 'male';
                    if (!empty($user_profile->gender)) {
                        if ($user_profile->gender == 'male') {
                            $re_data['gender'] = 'male';
                        } else if ($user_profile->gender == 'female') {
                            $re_data['gender'] = 'female';
                        }
                    }
                }
                if ($provider == 'Twitter') {
                    $re_data['twitter'] = PT_Secure($social_url);
                }
                if ($provider == 'LinkedIn') {
                    $re_data['about'] = PT_Secure($user_profile->description);
                    $re_data['linkedIn']    = PT_Secure($social_url);
                }
                if ($provider == 'Vkontakte') {
                    $re_data['about'] = PT_Secure($user_profile->description);
                    $re_data['vk']    = PT_Secure($social_url);
                }
                if ($provider == 'Instagram') {
                    $re_data['instagram']   = PT_Secure($user_profile->username);
                }
                if ($provider == 'QQ') {
                    $re_data['qq']   = PT_Secure($social_url);
                }
                if ($provider == 'WeChat') {
                    $re_data['wechat']   = PT_Secure($social_url);
                }
                if ($provider == 'Discord') {
                    $re_data['discord']   = PT_Secure($social_url);
                }
                if ($provider == 'Mailru') {
                    $re_data['mailru']   = PT_Secure($social_url);
                }
                $insert_id = $db->insert(T_USERS, $re_data);
                if ($insert_id) {

                    if (!empty($pt->config->auto_subscribe)) {
                        $get_users = explode(',', $pt->config->auto_subscribe);
                        foreach ($get_users as $key => $username) {
                            $user  = $db->where('username', $username)->getOne(T_USERS);
                            if (!empty($user)) {
                                $insert_data         = array(
                                    'user_id' => $user->id,
                                    'subscriber_id' => $insert_id,
                                    'time' => time(),
                                    'active' => 1
                                );
                                $create_subscription = $db->insert(T_SUBSCRIPTIONS, $insert_data);
                                if ($create_subscription) {
                                    $current_user = $db->where('id', $insert_id)->getOne(T_USERS);
                                    $data = array(
                                        'status' => 200
                                    );

                                    $notif_data = array(
                                        'notifier_id' => $insert_id,
                                        'recipient_id' => $user->id,
                                        'type' => 'subscribed_u',
                                        'url' => ('@' . $current_user->username),
                                        'time' => time()
                                    );

                                    pt_notify($notif_data);
                                }
                            }
                        }
                    }

                    $session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
	                $insert_data         = array(
	                    'user_id' => $insert_id,
	                    'session_id' => $session_id,
	                    'time' => time()
	                );
	                $insert              = $db->insert(T_SESSIONS, $insert_data);
	                $_SESSION['user_id'] = $session_id;
	                setcookie("user_id", $session_id, time() + (10 * 365 * 24 * 60 * 60), "/");
	                header("Location: $site_url");
	                exit();
                }
            }
        }
    }
    catch (Exception $e) {
        exit($e->getMessage());
        switch ($e->getCode()) {
            case 0:
                echo "Unspecified error.";
                break;
            case 1:
                echo "Hybridauth configuration error.";
                break;
            case 2:
                echo "Provider not properly configured.";
                break;
            case 3:
                echo "Unknown or disabled provider.";
                break;
            case 4:
                echo "Missing provider application credentials.";
                break;
            case 5:
                echo "Authentication failed The user has canceled the authentication or the provider refused the connection.";
                break;
            case 6:
                echo "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.";
                break;
            case 7:
                echo "User not connected to the provider.";
                break;
            case 8:
                echo "Provider does not support this feature.";
                break;
        }
        echo " an error found while processing your request!";
        echo " <b><a href='" . PT_Link('') . "'>Try again<a></b>";
    }
} else {
    header("Location: " . PT_Link(''));
    exit();
}
