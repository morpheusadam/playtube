<?php
$response_data = array('status' => 400);
if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
	$id = PT_Secure($_POST['id']);
	$video = $db->where('id', $id)->getOne(T_VIDEOS);

	if ($video->converted != 1) {
		$data = array('status' => 400);
			
	}else{
		$data = array('status' => 200);
	}
}