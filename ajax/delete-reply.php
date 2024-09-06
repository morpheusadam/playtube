<?php 
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}

if (empty($_POST['id'])) {
	$data = array('status' => 400);
} 

else {
	$id           = PT_Secure($_POST['id']);
	$reply_data   = $db->where('id', $id)->getOne(T_COMM_REPLIES);
	$is_owner     = false;

	if (!empty($reply_data)) {

		$db->where('id',$reply_data->video_id);
		$db->where('user_id',$user->id);
		$video_owner = ($db->getValue(T_VIDEOS,'count(*)') > 0);

		if ($video_owner === true) {
			$is_owner = true;
		}

		else if($reply_data->user_id == $user->id){
			$is_owner = true;
		}
	}

	if ($is_owner === true) {
		$delete_reply = $db->where('id', $id)->delete(T_COMM_REPLIES);
		if ($delete_reply) {
			$delete_reply_likes = $db->where('reply_id', $id)->delete(T_COMMENTS_LIKES);
			$data               = array('status' => 200);
		}
	}
}

?>