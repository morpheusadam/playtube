<?php 
if (IS_LOGGED == false) {
	$data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}
if (!empty($_POST['video_id'])) {
	$video_id = PT_Secure($_POST['video_id']);
	$is_video_valid = $db->where('id', $video_id)->getValue(T_VIDEOS, 'count(*)');
	if ($is_video_valid > 0) {
		$check_if_saved = $db->where('video_id', $video_id)->where('user_id', $user->id)->getValue(T_SAVED, "count(*)");
		if ($check_if_saved > 0) {
			$db->where('video_id', $video_id)->where('user_id', $user->id);
			$delete = $db->delete(T_SAVED);
			if ($delete > 0) {
				$data = array('status' => 300);
			}
		} else {
			$insert_data = array('user_id' => $user->id, 'video_id' => $video_id, 'time' => time());
			$insert = $db->insert(T_SAVED, $insert_data);
			if ($insert) {
				$data = array('status' => 200);
			}
		}
	}
}