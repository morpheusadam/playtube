<?php
$types = array('block','get');
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
else if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else{

	if ($_POST['type'] == 'block') {
        if (!empty($_POST['block_id']) && is_numeric($_POST['block_id']) && $_POST['block_id'] > 0 && $pt->user->id != $_POST['block_id']) {
            $user_id = PT_Secure($_POST['block_id']);
            $check_if_admin = $db->where('id', $user_id)->where('admin', 0,'>')->getValue(T_USERS, 'count(*)');
            if ($check_if_admin == 0) {
                $check_if_block = $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->getValue(T_BLOCK, 'count(*)');
                if ($check_if_block > 0) {
                    $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->delete(T_BLOCK);
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'block',
                        'message'    => 'User successfully unblocked.',
                        'code'      => 0
                    );
                }
                else{
                    $db->insert(T_BLOCK,array('user_id' => $pt->user->id,
                                          'blocked_id' => $user_id,
                                          'time' => time()));
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'block',
                        'message'    => 'User successfully blocked.',
                        'code'      => 1
                    );
                }
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '4',
                        'error_text' => 'You can not block this user'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '3',
                    'error_text' => 'block_id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'get') {
        $users = GetBlockedUsers();
        foreach ($users as $key => $user) {
            $users[$key]  = array_intersect_key(
                    ToArray($user), 
                    array_flip($user_public_data)
                );
        }
        
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'get_blocked',
            'users'    => $users
        );

    }
}