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


header_remove('Server');
header("Content-type: application/json");
require('assets/init.php');
decryptConfigData();

$api_versions      = array('1.0');
$response_data     = array();
$api_version       = (!empty($_GET['v']) && in_array($_GET['v'],$api_versions)) ? PT_Secure($_GET['v']) : '';
$api               = "app_api/v$api_version/api-v$api_version.php"; 

$type          = (!empty($_GET['type'])) ? PT_Secure($_GET['type']) : '';

if ((empty($_POST['server_key']) || $_POST['server_key'] != $pt->config->server_key) && $type != 'get_channel_info') {
	$response_data       = array(
        'api_status'     => '404',
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Wrong server key'
        )
    );

    echo json_encode($response_data, JSON_PRETTY_PRINT);
    exit();
}
if ($type == 'get_channel_info' && !empty($_GET['access_token']) && !empty($pt->user)) {
    $_GET['channel_id'] = $pt->user->id;
}


if (!file_exists($api)) {
    $response_data       = array(
        'api_status'     => '404',
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Error: 404 API Version Not Found'
        )
    );

    echo json_encode($response_data, JSON_PRETTY_PRINT);
    exit();
}




$applications  = array('phone');
$application   = (!empty($_GET['platform']) && in_array($_GET['platform'], $applications)) ? PT_Secure($_GET['platform']) : 'phone';
require_once   "$api";


echo json_encode($response_data, JSON_PRETTY_PRINT);
exit();

$db->disconnect();
unset($pt);
?>