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

else if (empty($_GET['channel_id']) || !is_numeric($_GET['channel_id'])) {

	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );

}

elseif (intval($_GET['channel_id']) == intval($user->id)) {
    $response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '3',
            'error_text' => 'Bad Request, Invalid channel id please check your details'
        )
    );
}

else{

	$table         = T_SUBSCRIPTIONS;
	$id            = PT_Secure($_GET['channel_id']);
    $is_subscribed = $db->where('user_id', $id)->where('subscriber_id', $user->id)->getValue($table, "count(*)");
    

    if (!empty($is_subscribed)) {
        $delete_sub = $db->where('user_id', $id)->where('subscriber_id', $user->id)->delete($table);
        $response_data = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'code' => 0
        );

    } 

    else {

        if (!empty($_POST['type']) && $_POST['type'] == 'paid') {
            $user2 = PT_UserData($id);
            if (!empty($user2) && $user2->subscriber_price > 0) {

                $admin__com = ($pt->config->admin_com_subscribers * $user2->subscriber_price)/100;
                $paypal_currency = $pt->config->payment_currency.'_PERCENT';
                $payment_data         = array(
                    'user_id' => $id,
                    'video_id'    => 0,
                    'paid_id'  => $pt->user->id,
                    'amount'    => $user2->subscriber_price,
                    'admin_com'    => $pt->config->admin_com_subscribers,
                    'currency'    => $paypal_currency,
                    'time'  => time(),
                    'type' => 'subscribe'
                );
                $db->insert(T_VIDEOS_TRSNS,$payment_data);
                $balance = $user2->subscriber_price - $admin__com;
                $db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' WHERE `id` = '".$id."'");
            }
        }

        $insert_data  = array(
            'user_id' => $id,
            'subscriber_id' => $user->id,
            'time' => time(),
            'active' => 1
        );

        $create_subscription = $db->insert($table, $insert_data);
        if ($create_subscription) {
            $response_data = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'code' => 1
            );
        }
    }

}

