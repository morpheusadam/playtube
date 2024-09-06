<?php 
if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
	exit('Not logged in');
}
$content         = 'content';

if($pt->config->ffmpeg_system == 'on'){
	$content     = 'ffmpeg';
}

$max_user_upload = $pt->config->user_max_upload;
if ($pt->user->is_pro != 1 && $pt->config->go_pro == "on") {
	if ($pt->user->uploads >= $max_user_upload) {
		$content = "buy_pro";
	}
}
$pt->is_short = false;
if ($pt->config->shorts_system == 'on' && !empty($_GET['type']) && $_GET['type'] == 'shorts') {
	$pt->is_short = true;
}
$pt->sub_categories_array = array();
foreach ($pt->sub_categories as $cat_key => $subs) {
	$pt->sub_categories_array["'".$cat_key."'"] = '<option value="0">'.$lang->none.'</option>';
	foreach ($subs as $sub_key => $sub_value) {
		$pt->sub_categories_array["'".$cat_key."'"] .= '<option value="'.array_keys($sub_value)[0].'">'.$sub_value[array_keys($sub_value)[0]].'</option>';
	}
}

$pt->page        = 'upload-video-api';
$pt->title       = $lang->home . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage("upload-video/$content");