<?php 

if (!empty($_POST['search_value'])) {
	$filter_keyword = PT_Secure($_POST['search_value']);
	$sql = "SELECT * FROM ".T_USERS." WHERE username LIKE '%$filter_keyword%' OR email LIKE '%$filter_keyword%' OR first_name LIKE '%$filter_keyword%' OR last_name LIKE '%$filter_keyword%' LIMIT 10";
	$users = $db->rawQuery($sql);
	if (!empty($users)) {
		$html = '';
		foreach ($users as $key => $user) {
			$user = PT_UserData($user->id);
			$user_name = '"'.$user->username.'"';
			$html .= "<div class='search-result-' style='padding:10px;'><a href='javascript:void(0)' onclick='add_to_input($user_name)' >$user->name</a></div>";
		}
		$data = array('status' => 200, 'html' => $html);
	}
	else{
		$data['status'] = 400;
	}
}