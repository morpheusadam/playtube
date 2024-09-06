<?php
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}
$data['status'] = 400;
if (!empty($_POST['playlist']) && $pt->config->playlist_subscribe == 'on') {

    $list_id = PT_Secure($_POST['playlist']);
    $list_data  = $db->where("list_id", $list_id)->getOne(T_LISTS);
    if (!empty($list_data)) {


        $is_subscribed = $db->where('list_id', $list_id)->where('subscriber_id', $user->id)->getValue(T_PLAYLIST_SUB, "count(*)");
        
        if ($is_subscribed > 0) {
            $delete_sub = $db->where('list_id', $list_id)->where('subscriber_id', $user->id)->delete(T_PLAYLIST_SUB);
            if ($delete_sub) {
                $data = array(
                    'status' => 304
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
                'subscriber_id' => $user->id,
                'time' => time(),
                'active' => 1
            );
            $create_subscription = $db->insert(T_PLAYLIST_SUB, $insert_data);
            if ($create_subscription) {
                $data = array(
                    'status' => 200
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
}