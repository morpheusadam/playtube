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

$data         = array();
$first        = '';
$second       = '';
$api_requests = array('go_pro','wallet','download_user_info');
$type         = (!empty($_GET['type'])) ? PT_Secure($_GET['type']) : '';

if (!empty($_GET['first'])) {
	$first = PT_Secure($_GET['first'], 0);
}
if (!empty($_GET['second'])) {
	$second = PT_Secure($_GET['second'], 0);
}

if ($type  != 'ap' && !in_array($type,$api_requests) && $first != 'download_user_info') {
	$hash_id = '';
	$is_error = 0;

	if (!empty($_POST['hash'])) {
		$hash_id = PT_Secure($_POST['hash']);;
	}

	else if (!empty($_GET['hash'])) {
		$hash_id = PT_Secure($_GET['hash']);
	}

	if (empty($hash_id)) {
		$is_error = 1;
	} 

	else {
		if (PT_CheckMainSession($hash_id) == false) {
			$is_error = 1;
		}
	}
	if ($is_error == 1) {
		header('Content-Type: application/json');
		$data = array('status' => 400, 'message' => 'bad-request');
		echo json_encode($data);
		exit();
	}
}


if (!empty($_GET['type'])) {
	$file = PT_Secure($_GET['type']);
	$files = scandir('ajax');
	unset($files[0]);
	unset($files[1]);
	if (file_exists("./ajax/$file.php") && in_array($file . '.php', $files)) {
		require "./ajax/$file.php";
	} else {
		$data = array('error' => 404, 'error_message' => 'type not found');
	}
}

header('Content-Type: application/json');
echo json_encode($data);
exit();
?>