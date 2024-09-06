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
} 	// $db->where('user_id', $user->id);

else {
	$id           = PT_Secure($_POST['id']);
	$comment_data = $db->where('id', $id)->getOne(T_COMMENTS);
	$is_owner     = false;

	if (!empty($comment_data)) {

		$db->where('id',$comment_data->video_id);
		$db->where('user_id',$user->id);
		$video_owner = ($db->getValue(T_VIDEOS,'count(*)') > 0);

		if ($video_owner === true) {
			$is_owner = true;
		}

		else if($comment_data->user_id == $user->id){
			$is_owner = true;
		}
	}


	if ($is_owner === true) {
		RegisterPoint($comment_data->video_id, "comments",'-',$comment_data->user_id);
		$delete_comment = $db->where('id', $id)->delete(T_COMMENTS);
		if ($delete_comment) {
			$delete_comments_likes   = $db->where('comment_id', $id)->delete(T_COMMENTS_LIKES);
			$comments_replies        = $db->where('comment_id', $id)->get(T_COMM_REPLIES);
			$delete_comments_replies = $db->where('comment_id', $id)->delete(T_COMM_REPLIES);
			foreach ($comments_replies as $reply) {
				$db->where('reply_id', $reply->id)->delete(T_COMMENTS_LIKES);
			}

			if ($delete_comments_likes && $delete_comments_replies) {
				$data = array('status' => 200);
			}
		}
	}
}

?>