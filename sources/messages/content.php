<?php 
if (IS_LOGGED == false) {
	header("Location: " . PT_Link('login'));
	exit();
}

$pt->page = 'messages';
$pt->title = $lang->messages . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;

$chat_id = 0;
$chat_user = array();

if (!empty($_GET['id'])) {
	$_GET['id'] = strip_tags($_GET['id']);
	$get_user_id = $db->where('username', PT_Secure($_GET['id']))->where('id',$pt->blocked_array , 'NOT IN')->getValue(T_USERS, 'id');
	if (!empty($get_user_id)) {
		$chat_user = PT_UserData($get_user_id);
		if ($chat_user->id != $pt->user->id) {
			$chat_id = $chat_user->id;
		} else {
			$chat_user = array();
		}
	} else {
		$chat_user = array();
	}
}

if (empty($chat_id)) {
	$html = PT_LoadPage("messages/ajax/no-messages");
} else {
	$messages_html = PT_GetMessages($chat_id, array('chat_user' => $chat_user, 'return_method' => 'html'));
	if (!empty($messages_html)) {
		$html = PT_LoadPage("messages/{$pt->config->server}/messages", array('MESSAGES' => $messages_html));
	} else {
		$html = PT_LoadPage("messages/ajax/no-messages-users");
	}
}

$users_html = PT_GetMessagesUserList(array('return_method' => 'html'));
if (empty($users_html)) {
	$users_html = '<p class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>No users found</p>';
}
$pt->page_url_ = $pt->config->site_url.'/messages';
$pt->chat_id = $chat_id;
$pt->chat_user = $chat_user;

$sidebar = PT_LoadPage('messages/sidebar', array('USERS' => $users_html));
$pt->content = PT_LoadPage("messages/{$pt->config->server}/content", array(
	'SIDEBAR' => $sidebar,
	'HTML' => $html
));