<?php

session_start(); // Important! Required for STATE Variable check and prevent CSRF attacks
require_once __DIR__.'/../../../autoload.php';
use gimucco\TikTokLoginKit;

/*
Example passing the Configuration parameters Inline

$api_key = ''; // Your API Key, as obtained from TikTok Developers portal
$api_secret = ''; // Your API Secret, as obtained from TikTok Developers portal
$redirect_uri = ''; // Where to return after authorization. Must be approved in the TikTok Developers portal
$_TK = new TikTokLoginKit\Connector($api_key, $api_secret, $redirect_uri);
*/

// Example passing the Configuration parameters via .ini file
$_TK = TikTokLoginKit\Connector::fromIni(__DIR__.'/env.ini');


if (TikTokLoginKit\Connector::receivingResponse()) { // Check if you're receiving the Authorisation Code
	try {
		$token = $_TK->verifyCode($_GET[TikTokLoginKit\Connector::CODE_PARAM]); // Verify the Authorisation code and get the Access Token

		/****  Your logic to store the access token goes here ****/

		$user = $_TK->getUser(); // Retrieve the User Object

		/****  Your logic to store or use the User Info goes here ****/

		$videos = $_TK->getUserVideoPages(); // Retrieve all the Videos of the logged User

		/****  Your logic to store or use the Video Info goes here ****/

		// Print some HTML as example
		echo <<<HTML
		<h2>User Info</h2>
		<table width="400">
			<tr>
				<td with="100"><img src="{$user->getAvatarLarger()}" style="width:100%"></td>
				<td with="700">
					<br />
					<strong>ID</strong>: {$user->getOpenID()}<br /><br />
					<strong>Name</strong>: {$user->getDisplayName()}
				</td>
			</tr>
		</table>
HTML;
		$trs = [];
		$videos = array_slice($videos, 0, 3); // Only show the first 3 videos
		foreach ($videos as $v) {
			$trs[] = <<<HTML
				<tr>
					<td width="100"><img src="{$v->getCoverImageURL()}" style="width:100%"></td>
					<td width="100">
						<br />
						<strong>ID</strong>: {$v->getID()}<br /><br />
						<strong>URL</strong>: {$v->getShareURL()}<br /><br />
						<strong>Caption</strong>: {$v->getVideoDescription()}
					</td>
				</tr>
HTML;
		}
		$trs = implode("\n", $trs);
		echo <<<HTML
		<h2>Videos</h2>
		<table width="800">
			{$trs}
		</table>
HTML;
	} catch (Exception $e) {
		echo "Error: ".$e->getMessage();
		echo '<br /><a href="?">Retry</a>';
	}
} else { // Print the initial login button that redirects to TikTok
	echo '<a href="'.$_TK->getRedirect([TikTokLoginKit\Connector::PERMISSION_USER_BASIC, TikTokLoginKit\Connector::PERMISSION_VIDEO_LIST]).'">Log in with TikTok</a>';
}
