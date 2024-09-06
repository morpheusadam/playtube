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
elseif (empty($_POST['video_id']) || !is_numeric($_POST['video_id']) || $_POST['video_id'] < 1) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '2',
            'error_text' => 'video_id can not be empty'
        )
	);
}
else{
	$video_id = PT_Secure($_POST['video_id']);
	if (!empty($video_id)) {
    	$video = PT_GetVideoByID($video_id, 0,0,2);
    	if (!empty($video)) {
    		$notify_sent = false;
    		if (!empty($video->is_movie)) {

    			$payment_data         = array(
		    		'user_id' => $video->user_id,
		    		'video_id'    => $video->id,
		    		'paid_id'  => $pt->user->id,
		    		'admin_com'    => 0,
		    		'currency'    => $pt->config->payment_currency,
		    		'time'  => time(),
		    		'type' => 'rent'
		    	);
		    	
		    	$total = $video->rent_price;
	    		$payment_data['amount'] = $total;
	    		$db->insert(T_VIDEOS_TRSNS,$payment_data);
    		}
    		else{
    		
	    		$admin__com = $pt->config->admin_com_rent_videos;
	    		$payment_currency = $pt->config->payment_currency;
	    		if ($pt->config->com_type == 1) {
	    			$admin__com = ($pt->config->admin_com_rent_videos * $video->sell_video)/100;
	    			$payment_currency = $pt->config->payment_currency.'_PERCENT';
	    		}
	    		$payment_data         = array(
		    		'user_id' => $video->user_id,
		    		'video_id'    => $video->id,
		    		'paid_id'  => $pt->user->id,
		    		'amount'    => $video->rent_price,
		    		'admin_com'    => $pt->config->admin_com_rent_videos,
		    		'currency'    => $payment_currency,
		    		'time'  => time(),
		    		'type' => 'rent'
		    	);
		    	$db->insert(T_VIDEOS_TRSNS,$payment_data);
		    	$balance = $video->rent_price - $admin__com;
		    	$db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' , `verified` = 1 WHERE `id` = '".$video->user_id."'");
		    }
		    if ($notify_sent == false) {
		    	$uniq_id = $video->video_id;
                $notif_data = array(
                    'notifier_id' => $pt->user->id,
                    'recipient_id' => $video->user_id,
                    'type' => 'paid_to_see',
                    'url' => "watch/$uniq_id",
                    'video_id' => $video->id,
                    'time' => time()
                );
                
                pt_notify($notif_data);
		    }

	    	$response_data     = array(
			    'api_status'   => '200',
			    'api_version'  => $api_version,
			    'success_type' => 'rent_video',
			    'message'    => 'Video successfully paid.'
			);
    	}
    	else{
    		$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => 'video not found'
		        )
			);
    	}
    	
    }
}