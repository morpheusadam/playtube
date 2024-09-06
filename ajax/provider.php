<?php
if (IS_LOGGED == false) {
    $data = array('status' => 200);
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
        'Mailru'
    );
    if (!empty($_POST['provider']) && in_array($_POST['provider'], $types)) {
        if (!empty($_COOKIE['provider'])) {
            $_COOKIE['provider'] = '';
            unset($_COOKIE['provider']);
            setcookie('provider', null, -1);
            setcookie('provider', null, -1, '/');
        }
        $provider = PT_Secure($_POST['provider']);
        setcookie('provider', $provider, time() + (60 * 60), '/');
    }
}