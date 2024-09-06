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

else if (empty($_GET['playlist_id'])) {

	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );

}
elseif ($pt->config->playlist_subscribe != 'on') {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '3',
            'error_text' => 'playlist_subscribe not available at this time'
        )
    );
}
else{

    $list_id = PT_Secure($_GET['playlist_id']);
    $list_data  = $db->where("list_id", $list_id)->getOne(T_LISTS);
    if (!empty($list_data)) {


        $is_subscribed = $db->where('list_id', $list_id)->where('subscriber_id', $pt->user->id)->getValue(T_PLAYLIST_SUB, "count(*)");
        
        if ($is_subscribed > 0) {
            $delete_sub = $db->where('list_id', $list_id)->where('subscriber_id', $pt->user->id)->delete(T_PLAYLIST_SUB);
            if ($delete_sub) {
                $response_data = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'code' => 0
                );
            }

            $notif_data = array(
                'notifier_id' => $pt->user->id,
                'recipient_id' => $list_data->user_id,
                'type' => 'unsubscribed_u_playlist',
                'url' => ('@' . $pt->user->username),
                'time' => time()
            );

            pt_notify($notif_data);
        } 
        else {
            $insert_data         = array(
                'list_id' => $list_id,
                'subscriber_id' => $pt->user->id,
                'time' => time(),
                'active' => 1
            );
            $create_subscription = $db->insert(T_PLAYLIST_SUB, $insert_data);
            if ($create_subscription) {
                $response_data = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'code' => 1
                );

                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $list_data->user_id,
                    'type' => 'subscribed_u_playlist',
                    'url' => ('@' . $pt->user->username),
                    'time' => time()
                );

                pt_notify($notif_data);
            }
        }
    }
    else{
        $response_data       = array(
            'api_status'     => '400',
            'api_version'    => $api_version,
            'errors'         => array(
                'error_id'   => '4',
                'error_text' => 'playlist not found'
            )
        );
    }
}