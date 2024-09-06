<?php 
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}

if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
	$video_id    = PT_Secure($_POST['id']);
	$video_data  = $db->where('id',$video_id)->getOne(T_VIDEOS);
	$user_id     = $user->id;
	if (!empty($video_data)) {
		$db->where('video_id',$video_id);
		$db->where('user_id',$user_id);

		$reports = $db->getValue(T_REPORTS,'count(*)');

		if(!empty($_POST['text']) && empty($reports)){
			$text    = PT_Secure($_POST['text'],1);
			$re_data = array(
				'user_id' => $user_id,
				'video_id' => $video_id,
				'type' => 'video',
				'time' => time(),
				'text' => $text,
			);

			if ($db->insert(T_REPORTS,$re_data)) {
				$notif_data = array(
	                'recipient_id' => 0,
	                'type' => 'report',
	                'admin' => 1,
	                'time' => time()
	            );
	            
	            pt_notify($notif_data);
				$data['status'] = 200;	
			}
		}

		else if (!empty($reports)) {
			$db->where('video_id',$video_id);
			$db->where('user_id',$user_id);
			$db->delete(T_REPORTS);
			$data['status'] = 304;	
		}
	}
}

