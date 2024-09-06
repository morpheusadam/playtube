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
if (!empty($_POST['user_id'])) {
    $id = PT_Secure($_POST['user_id']);
    $is_subscribed = $db->where('user_id', $id)->where('subscriber_id', $user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
    
    if ($is_subscribed > 0) {
        // if ($pt->config->payed_subscribers == 'on') {
        //     $check_if_payed = $db->where('user_id', $id)->where('paid_id', $user->id)->where('type','subscribe')->getValue(T_VIDEOS_TRSNS, 'count(*)');
        //     if ($check_if_payed > 0) {
        //         $db->where('user_id', $id)->where('paid_id', $user->id)->where('type','subscribe')->delete(T_VIDEOS_TRSNS);
        //     }
        // }
        $delete_sub = $db->where('user_id', $id)->where('subscriber_id', $user->id)->delete(T_SUBSCRIPTIONS);
        if ($delete_sub) {
            $data = array(
                'status' => 304
            );
        }

        $notif_data = array(
            'notifier_id' => $pt->user->id,
            'recipient_id' => $id,
            'type' => 'unsubscribed_u',
            'url' => ('@' . $pt->user->username),
            'time' => time()
        );

        pt_notify($notif_data);
    } 
    else {
        $pay_system = false;
        if ($pt->config->payed_subscribers == 'on') {
            $user_data = PT_UserData($id);
            if ($user_data->subscriber_price > 0) {
                $pay_system = true;
            }
        }

        if ($pay_system == false) {
            $insert_data         = array(
                'user_id' => $id,
                'subscriber_id' => $user->id,
                'time' => time(),
                'active' => 1
            );
            $create_subscription = $db->insert(T_SUBSCRIPTIONS, $insert_data);
            if ($create_subscription) {
                $data = array(
                    'status' => 200
                );

                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $id,
                    'type' => 'subscribed_u',
                    'url' => ('@' . $pt->user->username),
                    'time' => time()
                );

                pt_notify($notif_data);
            }
        }
    }
}