<?php  
if (IS_LOGGED == false) {
    $data = array(
        'status' => 400,
        'error' => 'Not logged in'
    );
    echo json_encode($data);
    exit();
}

if ($first == 'new') {
	if (!empty($_POST['id']) && !empty($_POST['new-message'])) {
		$link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	    $i          = 0;
	    preg_match_all($link_regex, PT_Secure($_POST['new-message'],1), $matches);
	    foreach ($matches[0] as $match) {
	        $match_url           = strip_tags($match);
	        $syntax              = '[a]' . urlencode($match_url) . '[/a]';
	        $_POST['new-message'] = str_replace($match, $syntax, $_POST['new-message']);
	    }
		$new_message = PT_Secure($_POST['new-message'],1);
		$id = PT_Secure($_POST['id']);
		$secondUser = PT_UserData($id);
		if ($secondUser->privacy->who_can_message_me == 'subscribers') {
			$is_subscribed = $db->where('user_id', $id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
			if ($is_subscribed == 0) {
				$data['status'] = 400;
				$data['message'] = $lang->something_wrong_send_messages;
				header('Content-Type: application/json');
				echo json_encode($data);
				exit();
			}
		}
		if ($secondUser->privacy->who_can_message_me == 'no_one') {
			$data['status'] = 400;
			$data['message'] = $lang->something_wrong_send_messages;
			header('Content-Type: application/json');
			echo json_encode($data);
			exit();
		}
		if ($id != $pt->user->id) {
			$chat_exits = $db->where("user_one", $pt->user->id)->where("user_two", $id)->getValue(T_CHATS, 'count(*)');
			if (!empty($chat_exits)) {
				$db->where("user_two", $pt->user->id)->where("user_one", $id)->update(T_CHATS, array('time' => time()));
				$db->where("user_one", $pt->user->id)->where("user_two", $id)->update(T_CHATS, array('time' => time()));
				if ($db->where("user_two", $pt->user->id)->where("user_one", $id)->getValue(T_CHATS, 'count(*)') == 0) {
					$db->insert(T_CHATS, array('user_two' => $pt->user->id, 'user_one' => $id,'time' => time()));
				}
			} else {
				$db->insert(T_CHATS, array('user_one' => $pt->user->id, 'user_two' => $id,'time' => time()));
				if (empty($db->where("user_two", $pt->user->id)->where("user_one", $id)->getValue(T_CHATS, 'count(*)'))) {
					$db->insert(T_CHATS, array('user_two' => $pt->user->id, 'user_one' => $id,'time' => time()));
				}
			}
			$insert_message = array(
				'from_id' => $pt->user->id,
				'to_id' => $id,
				'text' => $new_message,
				'time' => time()
			);
			$insert = $db->insert(T_MESSAGES, $insert_message);
			if ($insert) {
				$pt->message = PT_GetMessageData($insert);
				$data = array(
					'status' => 200,
					'message_id' => $_POST['message_id'],
					'message' => PT_LoadPage('messages/ajax/outgoing', array(
						'ID' => $pt->message->id,
						'AVATAR' => $pt->user->avatar,
						'TEXT' => $pt->message->text,
						'TIME' => PT_Time_Elapsed_String_chat($pt->message->time),
					))
				);
			}
		}
	}
}

if ($first == 'fetch') {
    if (empty($_POST['last_id'])) {
		$_POST['last_id'] = 0;
	}
	if (empty($_POST['id'])) {
		$_POST['id'] = 0;
	}
	if (empty($_POST['first_id'])) {
		$_POST['first_id'] = 0;
	}
	$messages_html = PT_GetMessages($_POST['id'], array('last_id' => $_POST['last_id'], 'first_id' => $_POST['first_id'], 'return_method' => 'html'));
	if (!empty($messages_html)) {
		$html = PT_LoadPage("messages/{$pt->config->server}/messages", array('MESSAGES' => $messages_html));
	} else {
		$html = PT_LoadPage("messages/ajax/no-messages");
	}

	$users_html = PT_GetMessagesUserList(array('return_method' => 'html'));

	if (!empty($messages_html) || !empty($users_html)) {
		$data = array('status' => 200, 'message' => $messages_html, 'users' => $users_html);
	}
}

if ($first == 'search') {
	$keyword = '';
	$users_html = '<p class="text-center">' . $lang->no_match_found . '</p>';
	if (isset($_POST['keyword'])) {
		$users_html = PT_GetMessagesUserList(array('return_method' => 'html', 'keyword' => $_POST['keyword']));
	}
	$data = array('status' => 200, 'users' => $users_html);
}

if ($first == 'delete_chat') {
	if (!empty($_POST['id'])) {
		$id = PT_Secure($_POST['id']);
		$messages = $db->where("(from_id = {$pt->user->id} AND to_id = {$id}) OR (from_id = {$id} AND to_id = {$pt->user->id})")->get(T_MESSAGES);
		$update1 = array();
		$update2 = array();
		$erase = array();
		foreach ($messages as $key => $message) {
			if ($message->from_deleted == 1 || $message->to_deleted == 1) {
				$erase[] = $message->id;
			} else {
				if ($message->to_id == $pt->user->id) {
					$update2[] = $message->id;
				} else {
					$update1[] = $message->id;
				}
			}
		}
		if (!empty($erase)) {
			$erase = implode(',', $erase);
			$final_query = "DELETE FROM " . T_MESSAGES . " WHERE id IN ($erase)";
			$db->rawQuery($final_query);
		}
		if (!empty($update1)) {
			$update1 = implode(',', $update1);
			$final_query = "UPDATE " . T_MESSAGES . " set `from_deleted` = '1' WHERE `id` IN({$update1}) ";
			$db->rawQuery($final_query);
		}
		if (!empty($update2)) {
			$update2 = implode(',', $update2);
			$final_query = "UPDATE " . T_MESSAGES . " set `to_deleted` = '1' WHERE `id` IN({$update2}) ";
			$db->rawQuery($final_query);
		}
		$delete_chats = $db->rawQuery("DELETE FROM " . T_CHATS . " WHERE user_one = {$pt->user->id} AND user_two = $id");
	}
}
if ($first == "load_user_chat") {
    $data = ["status" => 400];
    if (!empty($_POST['time']) && is_numeric($_POST['time'])) {
        $users_html = PT_GetMessagesUserList(array('return_method' => 'html'),20,PT_Secure($_POST['time']));
        if (!empty($users_html)) {
            $data = ["status" => 200,'html' => $users_html];
        }
    }
}
if ($first == 'mark_read') {
    $data = ["status" => 200];
    $db->where("to_id",$pt->user->id)->update(T_MESSAGES, array('seen' => time()));
}
?>