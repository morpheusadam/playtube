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
    $id      = PT_Secure($_POST['id']);
    $user_id = $user->id;

    $comment = $db->where('id', $id)->getOne(T_COMMENTS);
    $data    = array('status' => 304);

    if (!empty($comment)) {
    	$db->where('video_id',$comment->video_id);
    	$db->update(T_COMMENTS,array('pinned' => '0'));

    	$pin    = ($comment->pinned == 0) ? '1' : '0';
    	$update = array('pinned' => $pin);
    	$db->where('id', $id)->update(T_COMMENTS,$update);
    	$data['status'] = ($pin == 1) ? 200 : 304;
    	$comm_layout    = ($pin == 1) ? true : false;
        $pt->get_video = PT_GetVideoByID($comment->video_id, 1, 1,2);
    	$data['html']   = pt_comm_object_data($comment,$comm_layout);
    }
}