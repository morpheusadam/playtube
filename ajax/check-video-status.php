<?php
$id = 0;
$data = array('status' => 400);
if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
	$id = PT_Secure($_GET['id']);
	$video = $db->where('id', $id)->getOne(T_VIDEOS);

	if ($video->converted != 1) {
		$data = array('status' => 400);
			
	}else{
		$data = array('status' => 200);
	}
}