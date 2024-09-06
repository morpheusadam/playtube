<?php
if (!IS_LOGGED) {

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
	);
}
elseif (isset($_GET['lang']) AND !empty($_GET['lang'])) {
    $lang_name = PT_Secure(strtolower($_GET['lang']));

    if (in_array($lang_name, $langs)) {
        if ($pt->loggedin == true) {
            $db->where('id', $user->id)->update(T_USERS, array('language' => $lang_name));
        }
    }
    $response_data       = array(
	    'api_status'     => '200',
	    'api_version'    => $api_version
	);
}