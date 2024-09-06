<?php
if (!IS_LOGGED) {
	header('Location: ' . PT_Link('404'));
	exit;
}
if ($pt->config->live_video == 1 && ($pt->config->who_use_live == 'all' || ($pt->config->who_use_live == 'admin' && PT_IsAdmin()) || ($pt->config->who_use_live == 'pro' && $pt->user->is_pro > 0))) {
}
else{
	header('Location: ' . PT_Link('404'));
	exit;
}
$if_live = $db->where('user_id',$pt->user->id)->where('stream_name','','!=')->where('live_time',time() - 5,'>=')->getValue(T_VIDEOS,'COUNT(*)');
if ($if_live > 0) {
	header('Location: ' . PT_Link('404'));
	exit;
}
include_once 'assets/libs/AgoraDynamicKey/sample/RtcTokenBuilderSample.php';
$db->where('time',time()-60,'<')->delete(T_LIVE_SUB);

$pt->page_url_ = $pt->config->site_url.'/live';
$pt->title       = $lang->live . ' | ' . $pt->config->title;
$pt->page        = "live";
$pt->description = $pt->config->description;
$pt->keyword     = @$pt->config->keyword;
$pt->content     = PT_LoadPage('live/content');