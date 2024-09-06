<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

$id = PT_Secure($_POST['id']);
$video = $db->where('id', $id)->where('user_id', $user->id)->getOne(T_SAVED);

if (!empty($video)) {
	$db->where('id', $id)->delete(T_SAVED);
	$data = array('status' => 200);
}
