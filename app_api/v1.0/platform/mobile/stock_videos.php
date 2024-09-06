<?php
$response_data        = array(
    'api_status'      => '200',
    'api_version'     => $api_version,
    'videos'            => array()
);
$limit    = ((!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0) ? PT_Secure($_POST['limit']) : 10);
if (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) {
	$db->where('id', PT_Secure($_POST['offset']),'<');
}
$license_array = array('rights_managed_license','editorial_use_license','royalty_free_license','royalty_free_extended_license','creative_commons_license','public_domain');
if (!empty($_POST['keyword'])) {
    $_POST['keyword'] = strip_tags($_POST['keyword']);
    $db->where("(title LIKE '%".PT_Secure($_POST['keyword'])."%' OR description LIKE '%".PT_Secure($_POST['keyword'])."%')");
}
if (!empty($_POST['min_price']) && is_numeric($_POST['min_price']) && $_POST['min_price'] >= 0) {
    $db->where('sell_video',PT_Secure($_POST['min_price']),">=");
}
if (!empty($_POST['max_price']) && is_numeric($_POST['max_price']) && $_POST['max_price'] >= 0) {
    $db->where('sell_video',PT_Secure($_POST['max_price']),"<=");
}
if (!empty($_POST['license']) && in_array($_POST['license'], $license_array)) {
    $db->where('license',PT_Secure($_POST['license']));
}
$videos = $db->where('privacy', 0)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_stock',1)->where('approved',1)->orderBy('id', 'DESC')->get(T_VIDEOS,$limit);
foreach ($videos as $video) {
	$video = PT_GetVideoByID($video->video_id);
	if (!empty($video)) {
		$video->owner = array_intersect_key(ToArray($video->owner), array_flip($user_public_data));
		$response_data['videos'][] = $video;
	}
}