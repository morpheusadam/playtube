<?php
$types = array('get_chats','get_user_messages','delete_user_messages','send_message');
if (!IS_LOGGED) {

	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
	);
}
else if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else{
	if ($_POST['type'] == 'get_chats') {
		$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
		$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;

		$db->where("user_one = {$pt->user->id}");
		if ($offset > 0) {
			//$db->where('id',$offset,'<');
			$db->where('time',$offset,'<');
		}
		$users = $db->orderBy('time', 'DESC')->get(T_CHATS, $limit);


		foreach ($users as $key => $m_user) {
			$m_user->text_time = PT_Time_Elapsed_String($m_user->time);
	        $user = PT_UserData($m_user->user_two);
	        if (!empty($user) && !empty($user->id)) {
	        	unset($user->password);
		        $user->text_time = PT_Time_Elapsed_String($user->last_active);
		        if (!empty($user)) {
		            $get_last_message = $db->where("((from_id = {$pt->user->id} AND to_id = $user->id AND `from_deleted` = '0') OR (from_id = $user->id AND to_id = {$pt->user->id} AND `to_deleted` = '0'))")->orderBy('id', 'DESC')->getOne(T_MESSAGES);
		            $m_user->get_count_seen = 0;
		            if (!empty($get_last_message)) {
		            	$text = $get_last_message->text;

				        $link_search = '/\[a\](.*?)\[\/a\]/i';
				        if (preg_match_all($link_search, $text, $matches)) {
				            foreach ($matches[1] as $match) {
				                $match_decode     = urldecode($match);
				                $match_decode_url = $match_decode;
				                $match_url = $match_decode;
				                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
				                    $match_url = 'http://' . $match_url;
				                }
				                $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
				            }
				        }
				        $get_last_message->text = $text;

			            $get_count_seen = $db->where("to_id = {$pt->user->id} AND from_id = $user->id AND `from_deleted` = '0' AND seen = 0")->orderBy('id', 'DESC')->getValue(T_MESSAGES, 'COUNT(*)');
			            $get_last_message->text_time = PT_Time_Elapsed_String($get_last_message->time);
		            } 
	                $m_user->user = $user;
	                $m_user->get_count_seen = $get_count_seen;
	                $m_user->get_last_message = $get_last_message;
		        }
	        }  
	    }
	    $response_data     = array(
		    'api_status'   => '200',
		    'api_version'  => $api_version,
		    'success_type' => 'get_chats',
		    'data'      => $users
		);
	}
	else if ($_POST['type'] == 'get_user_messages') {
		if ($pt->user->id != $_POST['recipient_id'] && !empty($_POST['recipient_id']) && is_numeric($_POST['recipient_id']) && $_POST['recipient_id'] > 0) {
			$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
			$first_id = (!empty($_POST['first_id']) && is_numeric($_POST['first_id']) && $_POST['first_id'] > 0) ? PT_Secure($_POST['first_id']) : 0;
			$last_id = (!empty($_POST['last_id']) && is_numeric($_POST['last_id']) && $_POST['last_id'] > 0) ? PT_Secure($_POST['last_id']) : 0;
			$get_user_id = PT_Secure($_POST['recipient_id']);
			$chat_user = PT_UserData($get_user_id);
			if (!empty($chat_user) && !empty($chat_user->id)) {
				$chat_user->is_subscribed_to_channel = $db->where('user_id', $get_user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
				unset($chat_user->password);
				$chat_id = $chat_user->id;
				$messages = array();
				$chat_user->text_time = PT_Time_Elapsed_String($chat_user->last_active);
				$messages['user_data'] = $chat_user;
				$messages['messages'] =  PT_GetMessages($chat_id, array('chat_user' => $chat_user, 'return_method' => 'obj' , 'first_id' => $first_id , 'last_id' => $last_id),$limit);
				foreach ($messages['messages'] as $key => $message) {
					$message->text_time = PT_Time_Elapsed_String($message->time);
					$message->position  = 'left';
	                if ($message->from_id == $pt->user->id) {
	                    $message->position  = 'right';
	                }
	                $text = $message->text;

			        $link_search = '/\[a\](.*?)\[\/a\]/i';
			        if (preg_match_all($link_search, $text, $matches)) {
			            foreach ($matches[1] as $match) {
			                $match_decode     = urldecode($match);
			                $match_decode_url = $match_decode;
			                $match_url = $match_decode;
			                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
			                    $match_url = 'http://' . $match_url;
			                }
			                $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
			            }
			        }
			        $message->text = $text;
				}
				$response_data     = array(
				    'api_status'   => '200',
				    'api_version'  => $api_version,
				    'success_type' => 'get_user_messages',
				    'data'      => $messages
				);
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '4',
			            'error_text' => 'user not found'
			        )
			    );
			}
				
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '3',
		            'error_text' => 'recipient_id should be numeric'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'delete_user_messages') {
		if ($pt->user->id != $_POST['recipient_id'] && !empty($_POST['recipient_id']) && is_numeric($_POST['recipient_id']) && $_POST['recipient_id'] > 0) {
			$id = PT_Secure($_POST['recipient_id']);
			$messages = $db->where("(from_id = {$pt->user->id} AND to_id = {$id}) OR (from_id = {$id} AND to_id = {$pt->user->id})")->get(T_MESSAGES);
			if (!empty($messages)) {
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
				$response_data     = array(
				    'api_status'   => '200',
				    'api_version'  => $api_version,
				    'success_type' => 'delete_user_messages',
				    'message'    => 'Your messages successfully deleted.'
				);
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '4',
			            'error_text' => 'there is no message to delete it'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '3',
		            'error_text' => 'recipient_id should be numeric'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'send_message') {
		if (!empty($_POST['hash_id'])) {
			if ($pt->user->id != $_POST['recipient_id'] && !empty($_POST['recipient_id']) && is_numeric($_POST['recipient_id']) && $_POST['recipient_id'] > 0) {
				if (!empty($_POST['text'])) {
					$link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
				    $i          = 0;
				    preg_match_all($link_regex, PT_Secure($_POST['text']), $matches);
				    foreach ($matches[0] as $match) {
				        $match_url           = strip_tags($match);
				        $syntax              = '[a]' . urlencode($match_url) . '[/a]';
				        $_POST['text'] = str_replace($match, $syntax, $_POST['text']);
				    }
					$new_message = PT_Secure($_POST['text']);
					$id = PT_Secure($_POST['recipient_id']);
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
							$message = $db->where('id',$insert)->getOne(T_MESSAGES);

							$text = $message->text;

					        $link_search = '/\[a\](.*?)\[\/a\]/i';
					        if (preg_match_all($link_search, $text, $matches)) {
					            foreach ($matches[1] as $match) {
					                $match_decode     = urldecode($match);
					                $match_decode_url = $match_decode;
					                $match_url = $match_decode;
					                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
					                    $match_url = 'http://' . $match_url;
					                }
					                $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
					            }
					        }
					        $message->text = $text;
					        $message->text_time = PT_Time_Elapsed_String($message->time);
					        $message->hash_id = $_POST['hash_id'];

							$response_data     = array(
							    'api_status'   => '200',
							    'api_version'  => $api_version,
							    'success_type' => 'send_message',
							    'data'      => $message
							);
						}
					}
				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '4',
				            'error_text' => 'text should not be empty'
				        )
				    );
				}
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '3',
			            'error_text' => 'recipient_id should be numeric'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '5',
		            'error_text' => 'hash_id should not be empty'
		        )
		    );
		}
	}
}






