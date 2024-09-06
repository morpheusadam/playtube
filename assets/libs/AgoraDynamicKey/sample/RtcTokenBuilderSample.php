<?php
$pt->AgorachannelName = "stream_".$pt->user->id.'_'.rand(1111111,9999999);
$pt->AgoraToken = null;
if (!empty($pt->config->agora_app_certificate)) {
	include(dirname(__DIR__)."/src/RtcTokenBuilder.php");

	$appID = $pt->config->agora_app_id;
	$appCertificate = $pt->config->agora_app_certificate;
	$uid = 0;
	$uidStr = "0";
	$role = RtcTokenBuilder::RoleAttendee;
	$expireTimeInSeconds = 36000000;
	$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
	$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
	$pt->AgoraToken = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $pt->AgorachannelName, $uid, $role, $privilegeExpiredTs);
}
?>
