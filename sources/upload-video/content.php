<?php 
if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
	header("Location: " . PT_Link('login'));
	exit();
}
if (($pt->config->who_upload == 'admin' && !PT_IsAdmin())) {
	header("Location: " . PT_Link(''));
	exit();
}
if ($pt->user->suspend_upload) {
	header("Location: " . PT_Link(''));
	exit();
}
$content         = 'content';
if ($pt->config->stock_videos == 'on' && !canUseFeature($pt->user->id,'who_can_stock_videos')) {
	$pt->config->stock_videos = 'off';
}
if ($pt->config->geo_blocking == 'on' && !canUseFeature($pt->user->id,'who_can_geo_blocking')) {
	$pt->config->geo_blocking = 'off';
}
if ($pt->config->sell_videos_system == 'on' && !canUseFeature($pt->user->id,'who_can_sell_videos')) {
	$pt->config->sell_videos_system = 'off';
}
if ($pt->config->rent_videos_system == 'on' && !canUseFeature($pt->user->id,'who_can_rent_videos')) {
	$pt->config->rent_videos_system = 'off';
}
if ($pt->config->usr_v_mon == 'on' && !canUseFeature($pt->user->id,'who_can_usr_v_mon')) {
	$pt->config->usr_v_mon = 'off';
}

$time = time() - (60*60*24);
$expired_videos = $db->where('time',$time,'<=')->get(T_UPLOADED);
if (!empty($expired_videos)) {
	foreach ($expired_videos as $key => $video) {
		if (!empty($video->path) && file_exists($video->path)) {
			@unlink($video->path);
            PT_DeleteFromToS3($video->path);
		}
		$db->where('id',$video->id)->delete(T_UPLOADED);
	}
}

if($pt->config->ffmpeg_system == 'on'){
	$content     = 'ffmpeg';
}
if (!PT_IsAdmin()) {
	if ($pt->user->user_upload_limit != '0') {
		if ($pt->user->user_upload_limit != 'unlimited') {
			if ($pt->user->uploads >= $pt->user->user_upload_limit) {
				$content = "reached_limit";
			}
		}
	}
	else{
		if ($pt->config->upload_system_type == '0') {
			if ($pt->config->max_upload_all_users != '0' && $pt->user->uploads >= $pt->config->max_upload_all_users) {
				$pt->max_upload_users_ = $pt->config->max_upload_all_users;
				$content = "reached_limit_all";
			}
		}
		elseif ($pt->config->upload_system_type == '1') {
			if ($pt->user->is_pro == '0' && $pt->user->uploads >= $pt->config->max_upload_free_users && $pt->config->max_upload_free_users != 0) {
				$pt->max_upload_users_ = $pt->config->max_upload_free_users;
				$content = "reached_limit_all";
			}
			elseif ($pt->user->is_pro > '0' && $pt->user->uploads >= $pt->config->max_upload_pro_users && $pt->config->max_upload_pro_users != 0) {
				$pt->max_upload_users_ = $pt->config->max_upload_pro_users;
				$content = "reached_limit_all";
			}
		}
	}
}

// $max_videos_upload_limit_user_upload = $pt->config->videos_upload_limit;
// if ($max_videos_upload_limit_user_upload > 0 && $pt->config->go_pro != "on") {
// 	$count_user_videos = $db->where('user_id', $user->id)->where('video_location', '', '<>')->getValue(T_VIDEOS, 'COUNT(*)');
// 	if ($count_user_videos > $max_videos_upload_limit_user_upload) {
// 		$content = "reached_limit";
// 	}
// }

// $max_user_upload = $pt->config->user_max_upload;
// if ($pt->user->is_pro != 1 && $pt->config->go_pro == "on") {
// 	if ($pt->user->uploads >= $max_user_upload) {
// 		$content = "buy_pro";
// 	}
// }
$pt->sub_categories_array = array();
foreach ($pt->sub_categories as $cat_key => $subs) {
	$pt->sub_categories_array["'".$cat_key."'"] = '<option value="0">'.$lang->none.'</option>';
	foreach ($subs as $sub_key => $sub_value) {
		$pt->sub_categories_array["'".$cat_key."'"] .= '<option value="'.array_keys($sub_value)[0].'">'.$sub_value[array_keys($sub_value)[0]].'</option>';
	}
}

$payment_currency = $pt->config->payment_currency;
$currency         = "";
if ($payment_currency == "USD") {
	$currency     = "$";
}
else if($payment_currency == "EUR"){
	$currency     = "€";
}
$pt->is_short = false;
if ($pt->config->shorts_system == 'on' && !empty($_GET['type']) && $_GET['type'] == 'shorts') {
	$pt->is_short = true;
}
$pt->page_url_ = $pt->config->site_url.'/upload-video';
if (!empty($_GET['type']) && $_GET['type'] == 'shorts') {
	$pt->page_url_ = $pt->config->site_url.'/upload-video?type=shorts';
}
$pt->page        = 'upload-video';
$pt->title       = $lang->upload . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage("upload-video/$content",array('CURRENCY'   => $currency));