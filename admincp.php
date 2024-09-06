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

// You can access the admin panel by using the following url: http://yoursite.com/admincp 

require 'assets/init.php';

if (IS_LOGGED == false || (PT_IsAdmin() == false && !in_array($pt->user->admin, array(1,2,3)))) {
	header("Location: " . PT_Link(''));
    exit();
}
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

// autoload admin panel files
require 'admin-panel/autoload.php';