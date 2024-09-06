<?php
$types = array('add','delete','edit','reply','like','dislike','fetch_comments','fetch_replies');
if (!IS_LOGGED && $_POST['type'] != 'fetch_comments' && $_POST['type'] != 'fetch_replies') {

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

	if ($_POST['type'] == 'add') {
		if (!empty($_POST['video_id'])) {
			$id = PT_Secure($_POST['video_id']);
			$table = T_VIDEOS;
			$col = 'video_id';
		}
		else{
			$id = PT_Secure($_POST['post_id']);
			$table = T_POSTS;
			$col = 'post_id';
		}
		if (!empty($id) && is_numeric($id) && $id > 0) {
			if (!empty($_POST['text'])) {
				$text = PT_Secure($_POST['text']);
			    
			    $data_info = $db->where('id', $id)->getOne($table);
			    if (!empty($data_info)) {
			        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
			        $i          = 0;
			        preg_match_all($link_regex, $text, $matches);
			        foreach ($matches[0] as $match) {
			            $match_url = strip_tags($match);
			            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
			            $text      = str_replace($match, $syntax, $text);
			        }

			        if (empty($data_info->facebook) && empty($data_info->vimeo) && empty($data_info->daily) && !empty($comment_data->video_id)) {
			            $link_regex = '/[0-9]*:[0-9]{2}/i';
			            $i          = 0;
			            preg_match_all($link_regex, $text, $matches);
			            
			            foreach ($matches[0] as $match) {
			                $syntax    = '[d]' . $match . '[/d]';
			                $text      = str_replace($match, $syntax, $text);
			            }
			        }
			        $insert_data    = array(
			            'user_id' => $user->id,
			            $col => $id,
			            'text' => $text,
			            'time' => time()
			        );
			        $insert_comment = $db->insert(T_COMMENTS, $insert_data);
			        if ($insert_comment) {
			        	if ($col == 'video_id') {
			        		if ($data_info->user_id != $user->id) {
				                $type    = 'commented_ur_video';
				                $uniq_id = $data_info->video_id;
				                $notif_data = array(
				                    'notifier_id' => $pt->user->id,
				                    'recipient_id' => $data_info->user_id,
				                    'type' => $type,
				                    'url' => "watch/$uniq_id&cl=$insert_comment",
				                    'video_id' => $id,
				                    'time' => time()
				                );
				                
				                pt_notify($notif_data);
				            }
			        	}
			            
			            $response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'add comment',
						    'message'    => 'Your comment successfully added.',
						    'id'      => $insert_comment
						);
			        }
			    }
			    else{
			    	$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '4',
				            'error_text' => 'Video not found'
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
			            'error_text' => 'The text should not be empty'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '2',
		            'error_text' => 'Bad Request, Invalid or missing parameter'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'delete') {

		if (!empty($_POST['comment_id'])) {
			$id           = PT_Secure($_POST['comment_id']);
			$table = T_COMMENTS;
			$col = 'comment_id';
		}
		else{
			$id           = PT_Secure($_POST['reply_id']);
			$table = T_COMM_REPLIES;
			$col = 'reply_id';
		}
		if (!empty($id) && is_numeric($id) && $id > 0) {
			$comment_data = $db->where('id', $id)->getOne($table);
			$is_owner     = false;

			if (!empty($comment_data)) {

				$db->where('id',$comment_data->video_id);
				$db->where('user_id',$user->id);
				$video_owner = ($db->getValue(T_VIDEOS,'count(*)') > 0);

				if ($video_owner === true) {
					$is_owner = true;
				}

				else if($comment_data->user_id == $user->id){
					$is_owner = true;
				}

				if ($is_owner === true) {
					$delete_comment = $db->where('id', $id)->delete($table);

					if ($col == 'comment_id') {
						$delete_comments_likes   = $db->where('comment_id', $id)->delete(T_COMMENTS_LIKES);
						$comments_replies        = $db->where('comment_id', $id)->get(T_COMM_REPLIES);
						$delete_comments_replies = $db->where('comment_id', $id)->delete(T_COMM_REPLIES);
						foreach ($comments_replies as $reply) {
							$db->where('reply_id', $reply->id)->delete(T_COMMENTS_LIKES);
						}
					}
					else{
						$db->where('reply_id', $id)->delete(T_COMMENTS_LIKES);
					}
					
					if ($delete_comment) {
						$response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'delete_comment',
						    'message'      => 'Your comment successfully deleted.'
						);
					}
				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '6',
				            'error_text' => 'You can not delete the comment you are not the comment owner'
				        )
				    );
				}
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '7',
			            'error_text' => 'The comment not found'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '11',
		            'error_text' => 'The comment_id or reply_id should be numeric'
		        )
		    );
		}



	}
	else if ($_POST['type'] == 'edit') {
		if (!empty($_POST['comment_id'])) {
			$id           = PT_Secure($_POST['comment_id']);
			$table = T_COMMENTS;
			$col = 'comment_id';
		}
		else{
			$id           = PT_Secure($_POST['reply_id']);
			$table = T_COMM_REPLIES;
			$col = 'reply_id';
		}

		if (!empty($id) && is_numeric($id) && $id > 0) {
			if (!empty($_POST['text'])) {
				$comment_data = $db->where('id', $id)->getOne($table);

				if (!empty($comment_data)) {
					if($comment_data->user_id == $user->id){
						$text = PT_Secure($_POST['text']);
						$link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
					    $i          = 0;
					    preg_match_all($link_regex, $text, $matches);
					    foreach ($matches[0] as $match) {
					        $match_url = strip_tags($match);
					        $syntax    = '[a]' . urlencode($match_url) . '[/a]';
					        $text      = str_replace($match, $syntax, $text);
					    }
					    if (!empty($comment_data->video_id)) {
					    	$link_regex = '/[0-9]*:[0-9]{2}/i';
						    $i          = 0;
						    preg_match_all($link_regex, $text, $matches);
						    
						    foreach ($matches[0] as $match) {
						        $syntax    = '[d]' . $match . '[/d]';
						        $text      = str_replace($match, $syntax, $text);
						    }
					    }
					    
					    $db->where('id',$id)->update($table,array('text' => $text));
					    $response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'edit_comment',
						    'message'      => 'Your comment successfully edited.'
						);
					}
					else{
						$response_data       = array(
					        'api_status'     => '400',
					        'api_version'    => $api_version,
					        'errors'         => array(
					            'error_id'   => '9',
					            'error_text' => 'You can not edit the comment you are not the comment owner'
					        )
					    );
					}
				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '7',
				            'error_text' => 'The comment not found'
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
			            'error_text' => 'The text should not be empty'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '11',
		            'error_text' => 'The comment_id or reply_id should be numeric'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'like') {
		if (!empty($_POST['comment_id'])) {
			$id           = PT_Secure($_POST['comment_id']);
			$table = T_COMMENTS;
			$col = 'comment_id';
		}
		else{
			$id           = PT_Secure($_POST['reply_id']);
			$table = T_COMM_REPLIES;
			$col = 'reply_id';
		}
		if (!empty($id) && is_numeric($id) && $id > 0) {
			
			$comment_data = $db->where('id', $id)->getOne($table);

			if (!empty($comment_data)) {

	            $db->where('user_id', $user->id);
	            $db->where($col, $id);
	            $db->where('type', 1);
	            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            if ($check_for_like > 0) {

	                $db->where('user_id', $user->id);
	                $db->where($col, $id);
	                $db->where('type', 1);

	                $delete = $db->delete(T_COMMENTS_LIKES);
	                $response_data     = array(
					    'api_status'   => '200',
					    'api_version'  => $api_version,
					    'success_type' => 'like_comment',
					    'liked'      => 0
					);

	                $ud = array(
	                    'likes' => ($comment_data->likes -=1 )
	                );

	                $db->where('id', $id)->update(T_COMMENTS,$ud);
	            }

	            else {

	                $db->where('user_id', $user->id);
	                $db->where($col, $id);
	                $db->where('type', 2);

	                if ($db->getValue(T_COMMENTS_LIKES,'count(*)') > 0) {
	                    $db->where('user_id', $user->id);
	                    $db->where($col, $id);
	                    $db->where('type', 2);
	                    $delete = $db->delete(T_COMMENTS_LIKES);

	                    if ($col == 'comment_id') {
	                    	$ud = array(
		                        'dis_likes' => ($comment_data->dis_likes -=1 )
		                    );

		                    $db->where('id', $id)->update(T_COMMENTS,$ud);
	                    }

	                    
	                }
	                $insert_data = array(
	                    'user_id' => $user->id,
	                    $col => $id,
	                    'time' => time(),
	                    'type' => 1
	                );

	                $insert      = $db->insert(T_COMMENTS_LIKES, $insert_data);

	                if ($insert) {
	                    $response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'like_comment',
						    'liked'      => 1
						);


						if ($comment_data->user_id != $user->id) {
			                $type       = 'liked_ur_comment';   
			                $notif_data = array(
			                    'notifier_id' => $pt->user->id,
			                    'recipient_id' => $comment_data->user_id,
			                    'type' => $type,
			                    'url' => ('@'.$pt->user->username),
			                    'time' => time()
			                );

			                
			                if (!empty($comment_data->video_id)) {
			                    $video_data = $db->where('id',$comment_data->video_id)->getOne(T_VIDEOS);
			                    $uniq_id           = $video_data->video_id;
			                    $notif_data['url'] = "watch/$uniq_id&cl=$id";
			                }
			                
			                else if(!empty($comment_data->post_id)){
			                    $post_data = $db->where('id',$comment_data->post_id)->getOne(T_POSTS);
			                    $uniq_id           = $post_data->id;
			                    $notif_data['url'] = "articles/read/$uniq_id&cl=$id";
			                }

			                pt_notify($notif_data);
			            }

	                    #PHP trigger on insert likes
	                    if ($col == 'comment_id') {
	                    	$ud = array(
		                        'likes' => ($comment_data->likes += 1)
		                    );

		                    $db->where('id', $id)->update(T_COMMENTS,$ud);
	                    }
	                    
	                }
	            }
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '7',
			            'error_text' => 'The comment not found'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '11',
		            'error_text' => 'The comment_id or reply_id should be numeric'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'dislike') {
		if (!empty($_POST['comment_id'])) {
			$id           = PT_Secure($_POST['comment_id']);
			$table = T_COMMENTS;
			$col = 'comment_id';
		}
		else{
			$id           = PT_Secure($_POST['reply_id']);
			$table = T_COMM_REPLIES;
			$col = 'reply_id';
		}
		if (!empty($id) && is_numeric($id) && $id > 0) {
			$comment_data = $db->where('id', $id)->getOne($table);

			if (!empty($comment_data)) {
				$db->where('user_id', $user->id);
	            $db->where($col, $id);
	            $db->where('type', 2);
	            $check_for_like = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

	            if ($check_for_like > 0) {
	                $db->where('user_id', $user->id);
	                $db->where($col, $id);
	                $db->where('type', 2);
	                $delete = $db->delete(T_COMMENTS_LIKES);
	                $response_data     = array(
					    'api_status'   => '200',
					    'api_version'  => $api_version,
					    'success_type' => 'dislike_comment',
					    'dislike'      => 0
					);

	                #PHP trigger on delete dis likes
	                if ($col == 'comment_id') {
                    	$ud = array(
		                    'dis_likes' => ($comment_data->dis_likes -= 1)
		                );

		                $db->where('id', $id)->update(T_COMMENTS,$ud);
                    }
	                
	            }

	            else {
	                
	                $db->where('user_id', $user->id);
	                $db->where($col, $id);
	                $db->where('type', 1);

	                if ($db->getValue(T_COMMENTS_LIKES,'count(*)') > 0) {
	                    $db->where('user_id', $user->id);
	                    $db->where($col, $id);
	                    $db->where('type', 1);

	                    $delete = $db->delete(T_COMMENTS_LIKES);

	                    $ud = array(
	                        'likes' => ($comment_data->likes -= 1)
	                    );
	                    $db->where('id', $id)->update(T_COMMENTS,$ud);
	                }
	                
	                $insert_data = array(
	                    'user_id' => $user->id,
	                    $col => $id,
	                    'time' => time(),
	                    'type' => 2
	                );

	                $insert      = $db->insert(T_COMMENTS_LIKES, $insert_data);
	                if ($insert) {
	                    $response_data     = array(
						    'api_status'   => '200',
						    'api_version'  => $api_version,
						    'success_type' => 'dislike_comment',
						    'dislike'      => 1
						);

						if ($comment_data->user_id != $user->id) {
			                $type       = 'disliked_ur_comment';   
			                $notif_data = array(
			                    'notifier_id' => $pt->user->id,
			                    'recipient_id' => $comment_data->user_id,
			                    'type' => $type,
			                    'url' => ('@'.$pt->user->username),
			                    'time' => time()
			                );

			                
			                if (!empty($comment_data->video_id)) {
			                    $video_data = $db->where('id',$comment_data->video_id)->getOne(T_VIDEOS);
			                    $uniq_id           = $video_data->video_id;
			                    $notif_data['url'] = "watch/$uniq_id&cl=$id";
			                }
			                
			                else if(!empty($comment_data->post_id)){
			                    $post_data = $db->where('id',$comment_data->post_id)->getOne(T_POSTS);
			                    $uniq_id           = $post_data->id;
			                    $notif_data['url'] = "articles/read/$uniq_id&cl=$id";
			                }

			                pt_notify($notif_data);
			            }

			            if ($col == 'comment_id') {
	                    	$ud = array(
		                        'dis_likes' => ($comment_data->dis_likes += 1)
		                    );

		                    $db->where('id', $id)->update(T_COMMENTS,$ud);
	                    }

	                    
	                }
	            }
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '7',
			            'error_text' => 'The comment not found'
			        )
			    );
			}
		}
	    else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '11',
		            'error_text' => 'The comment_id or reply_id should be numeric'
		        )
		    );
		}
	}
	else if ($_POST['type'] == 'reply') {

		if (!empty($_POST['video_id']) || !empty($_POST['post_id'])) {
			if (!empty($_POST['video_id'])) {
				$id = PT_Secure($_POST['video_id']);
				$table = T_VIDEOS;
			}
			else{
				$id = PT_Secure($_POST['post_id']);
				$table = T_POSTS;
			}

			if (!empty($id) && is_numeric($id) && $id > 0) {

				if (!empty($_POST['text']) && !empty($_POST['comment_id']) && is_numeric($_POST['comment_id']) && $_POST['comment_id'] > 0) {
					$comm_id           = PT_Secure($_POST['comment_id']);
					$text           = PT_Secure($_POST['text']);


				    $reply_id = (!empty($_POST['reply']) && is_numeric($_POST['reply'])) ? $_POST['reply'] : 0;
				    
				    $comm_data  = $db->where('id', $comm_id)->getOne(T_COMMENTS);
				    if (!empty($comm_data->video_id)) {
						$id = $comm_data->video_id;
					}
					else{
						$id = $comm_data->post_id;
					}

				    $data_info = $db->where('id', $id)->getOne($table);
				    if (!empty($data_info) && !empty($comm_data)) {
				        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
				        $i          = 0;
				        preg_match_all($link_regex, $text, $matches);
				        foreach ($matches[0] as $match) {
				            $match_url = strip_tags($match);
				            $syntax    = '[a]' . urlencode($match_url) . '[/a]';
				            $text      = str_replace($match, $syntax, $text);
				        }
				        if (empty($data_info->facebook) && empty($data_info->vimeo) && empty($data_info->daily)) {
				            $link_regex = '/[0-9]*:[0-9]{2}/i';
				            $i          = 0;
				            preg_match_all($link_regex, $text, $matches);
				            
				            foreach ($matches[0] as $match) {
				                $syntax    = '[d]' . $match . '[/d]';
				                $text      = str_replace($match, $syntax, $text);
				            }
				        }

				        $insert_data     = array(
				            'user_id'    => $user->id,
				            'comment_id' => $comm_id,
				            'text' => $text,
				            'time' => time()
				        );
				        if (!empty($comm_data->video_id)) {
							$insert_data['video_id'] = $comm_data->video_id;
							$uniq_id = $data_info->video_id;
						}
						else{
							$insert_data['post_id'] = $comm_data->post_id;
							$uniq_id = $data_info->id;
						}

				        $insert_reply = $db->insert(T_COMM_REPLIES, $insert_data);
				        if ($insert_reply) {

				            $response_data     = array(
							    'api_status'   => '200',
							    'api_version'  => $api_version,
							    'success_type' => 'reply_comment',
							    'message'      => 'Your reply successfully added.',
							    'reply_id'     => $insert_reply
							);

					        if (!empty($reply_id)) {
				            	$reply_data = $db->where('id',$reply_id)->getOne(T_COMM_REPLIES);
				            	if (!empty($reply_data) && $reply_data->user_id != $user->id) {
				            		$type    = 'replied_2ur_comment';
					                $notif_data = array(
					                    'notifier_id' => $pt->user->id,
					                    'recipient_id' => $reply_data->user_id,
					                    'type' => $type,
					                    'time' => time()
					                );
					                if (!empty($_POST['video_id'])) {
										$notif_data['url'] = "watch/$uniq_id&rl=$insert_reply";
									}
									else{
										$notif_data['url'] = "articles/read/$uniq_id&rl=$insert_reply";
									}
					                
					                pt_notify($notif_data);
				            	}
				            }
				            else if($comm_data->user_id != $user->id && empty($reply_id)){
				            	$type    = 'replied_2ur_comment';
				                $notif_data = array(
				                    'notifier_id' => $pt->user->id,
				                    'recipient_id' => $comm_data->user_id,
				                    'type' => $type,
				                    'time' => time()
				                );
				                if (!empty($_POST['video_id'])) {
									$notif_data['url'] = "watch/$uniq_id&rl=$insert_reply";
								}
								else{
									$notif_data['url'] = "articles/read/$uniq_id&rl=$insert_reply";
								}
				                
				                pt_notify($notif_data);
				            }
				        }
				    }
				    else{
				    	$response_data       = array(
					        'api_status'     => '400',
					        'api_version'    => $api_version,
					        'errors'         => array(
					            'error_id'   => '10',
					            'error_text' => 'wrong video_id or post_id or comment_id'
					        )
					    );
				    }

				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '8',
				            'error_text' => 'The text should not be empty and The comment_id should be numeric'
				        )
				    );
				}
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '2',
			            'error_text' => 'Bad Request, Invalid or missing parameter'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '2',
		            'error_text' => 'Bad Request, Invalid or missing parameter'
		        )
		    );
		}	
	}
	else if ($_POST['type'] == 'fetch_comments') {

		if (!empty($_POST['video_id']) || !empty($_POST['post_id'])) {
			if (!empty($_POST['video_id'])) {
				$id = PT_Secure($_POST['video_id']);
				$table = T_VIDEOS;
				$col = 'video_id';
			}
			else{
				$id = PT_Secure($_POST['post_id']);
				$table = T_POSTS;
				$col = 'post_id';
			}
			if (!empty($id) && is_numeric($id) && $id > 0) {

				$data_info = $db->where('id', $id)->getOne($table);
				if (!empty($data_info)) {
					$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
					$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;


					$db->where($col, $data_info->id);
					$db->where('pinned', '1','<>');
					if ($offset > 0) {
						$db->where('id', $offset,'<');
					}
					$db->orderBy('id', 'DESC');
					$get_comments = $db->get(T_COMMENTS,$limit);

					foreach ($get_comments as $key => $comment) {
						$comment_user_data    = PT_UserData($comment->user_id);
						if (!empty($comment_user_data)) {
							$comment->comment_user_data = $comment_user_data;

					        $text = $comment->text;

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


					        $duration_search = '/\[d\](.*?)\[\/d\]/i';

						    if (preg_match_all($duration_search, $text, $matches)) {
						        foreach ($matches[1] as $match) {
						            $time = explode(":", $match);
						            $current_time = ($time[0]*60)+$time[1];
						            $text = str_replace('[d]' . $match . '[/d]', $match, $text);
						        }
						    }

					        $comment->text = $text;


					        $comment->is_liked_comment = 0;
					        $comment->is_comment_owner = false;      
					        $replies              = "";
					        $pt->pin              = false;
					        $comment->replies_count      = $db->where('comment_id', $comment->id)->getValue(T_COMM_REPLIES,'COUNT(*)');
					        $comment->comment_replies      = $db->where('comment_id', $comment->id)->get(T_COMM_REPLIES,10);
					        $comment->is_liked_comment     = 0;
					        $comment->is_disliked_comment  = 0;
					        
					        $comment->comment_user_data->is_subscribed_to_channel = $db->where('user_id', $comment->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
					        unset($comment->comment_user_data->password);

					        foreach ($comment->comment_replies as $reply) {
					        	$reply_user_data    = PT_UserData($reply->user_id);
					        	if (!empty($reply_user_data)) {
					        		$reply->reply_user_data = $reply_user_data;
					        		$reply->is_reply_owner = false;
						            
						            $reply->reply_user_data->is_subscribed_to_channel = $db->where('user_id', $reply->user_id)->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)");
						            unset($reply->reply_user_data->password);
						            $reply->is_liked_reply     = 0;
						            $reply->is_disliked_reply  = 0;

					                $reply->is_reply_owner = false;
					                if ($reply->user_id == $user->id  || $comment->user_id == $user->id || $data_info->user_id == $user->id) {
					                    $reply->is_reply_owner = true;
					                }

					                //Check is this reply  voted by logged-in user
					                $db->where('reply_id', $reply->id);
					                $db->where('user_id', $user->id);
					                $db->where('type', 1);
					                $reply->is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

					                $db->where('reply_id', $reply->id);
					                $db->where('user_id', $user->id);
					                $db->where('type', 2);
					                $reply->is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
						            

						            //Get related to reply likes
						            $db->where('reply_id', $reply->id);
						            $db->where('type', 1);
						            $reply->reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

						            $db->where('reply_id', $reply->id);
						            $db->where('type', 2);
						            $reply->reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');


						            $text = $reply->text;

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


							        $duration_search = '/\[d\](.*?)\[\/d\]/i';

								    if (preg_match_all($duration_search, $text, $matches)) {
								        foreach ($matches[1] as $match) {
								            $time = explode(":", $match);
								            $current_time = ($time[0]*60)+$time[1];
								            $text = str_replace('[d]' . $match . '[/d]', $match, $text);
								        }
								    }

							        $reply->text = $text;
							        $reply->text_time = PT_Time_Elapsed_String($reply->time);
					        	}
					        }
					       

				            //Check is comment voted by logged-in user
				            $db->where('comment_id', $comment->id);
				            $db->where('user_id', $user->id);
				            $db->where('type', 1);
				            $comment->is_liked_comment   = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

				            $db->where('comment_id', $comment->id);
				            $db->where('user_id', $user->id);
				            $db->where('type', 2);
				            $comment->is_disliked_comment = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

				            if ($user->id == $comment->user_id || $data_info->user_id == $user->id) {
				                $comment->is_comment_owner = true;
				            }
				            $comment->text_time = PT_Time_Elapsed_String($comment->time);
				        }
				    }
				    $response_data     = array(
					    'api_status'   => '200',
					    'api_version'  => $api_version,
					    'success_type' => 'fetch_comments',
					    'data'      => $get_comments
					);



				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '2',
				            'error_text' => 'Bad Request, Invalid or missing parameter'
				        )
				    );
				}
			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '2',
			            'error_text' => 'Bad Request, Invalid or missing parameter'
			        )
			    );
			}
		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '2',
		            'error_text' => 'Bad Request, Invalid or missing parameter'
		        )
		    );
		}	

	}
	else if ($_POST['type'] == 'fetch_replies') {
		if (!empty($_POST['comment_id']) && is_numeric($_POST['comment_id']) && $_POST['comment_id'] > 0) {
			$id = PT_Secure($_POST['comment_id']);
			$comment_data = $db->where('id', $id)->getOne(T_COMMENTS);

			if ($comment_data->video_id > 0) {
				$data_info = $db->where('id', $comment_data->video_id)->getOne(T_VIDEOS);
			}
			else{
				$data_info = $db->where('id', $comment_data->post_id)->getOne(T_POSTS);
			}

			if (!empty($comment_data)) {
				$limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
				$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;

				if ($offset > 0) {
					$comment_replies      = $db->where('comment_id', $comment_data->id)->where('id', $offset,'>')->get(T_COMM_REPLIES,$limit);
				}
				else{
					$comment_replies      = $db->where('comment_id', $comment_data->id)->get(T_COMM_REPLIES,$limit);
				}

				

				foreach ($comment_replies as $reply) {
		        	$reply->is_reply_owner = false;
		            $reply->reply_user_data    = PT_UserData($reply->user_id);
		            unset($reply->reply_user_data->password);
		            $reply->is_liked_reply     = 0;
		            $reply->is_disliked_reply  = 0;

	                $reply->is_reply_owner = false;
	                if ($reply->user_id == $user->id  || $comment_data->user_id == $user->id || $data_info->user_id == $user->id) {
	                    $reply->is_reply_owner = true;
	                }

	                //Check is this reply  voted by logged-in user
	                $db->where('reply_id', $reply->id);
	                $db->where('user_id', $user->id);
	                $db->where('type', 1);
	                $reply->is_liked_reply    = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;

	                $db->where('reply_id', $reply->id);
	                $db->where('user_id', $user->id);
	                $db->where('type', 2);
	                $reply->is_disliked_reply = ($db->getValue(T_COMMENTS_LIKES, 'count(*)') > 0) ? 1 : 0;
		            

		            //Get related to reply likes
		            $db->where('reply_id', $reply->id);
		            $db->where('type', 1);
		            $reply->reply_likes    = $db->getValue(T_COMMENTS_LIKES, 'count(*)');

		            $db->where('reply_id', $reply->id);
		            $db->where('type', 2);
		            $reply->reply_dislikes = $db->getValue(T_COMMENTS_LIKES, 'count(*)');


		            $text = $reply->text;

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


			        $duration_search = '/\[d\](.*?)\[\/d\]/i';

				    if (preg_match_all($duration_search, $text, $matches)) {
				        foreach ($matches[1] as $match) {
				            $time = explode(":", $match);
				            $current_time = ($time[0]*60)+$time[1];
				            $text = str_replace('[d]' . $match . '[/d]', $match, $text);
				        }
				    }

			        $reply->text = $text;
			        $reply->text_time = PT_Time_Elapsed_String($reply->time);


		        }
		        $response_data     = array(
				    'api_status'   => '200',
				    'api_version'  => $api_version,
				    'success_type' => 'fetch_replies',
				    'data'      => $comment_replies
				);



			}
			else{
				$response_data       = array(
			        'api_status'     => '400',
			        'api_version'    => $api_version,
			        'errors'         => array(
			            'error_id'   => '7',
			            'error_text' => 'The comment not found'
			        )
			    );
			}

		}
		else{
			$response_data       = array(
		        'api_status'     => '400',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '12',
		            'error_text' => 'comment_id should be numeric'
		        )
		    );
		}
	}

}