<?php
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
elseif (empty($_POST['type'])) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '4',
            'error_text' => 'type can not be empty'
        )
	);
}
else{
	if ($_POST['type'] == 'create') {
		if (empty($_POST['stream_name'])) {
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '5',
		            'error_text' => 'stream_name can not be empty'
		        )
			);
    	}
    	else{
    		$video_id        = PT_GenerateKey(15, 15);
	        $check_for_video = $db->where('video_id', $video_id)->getValue(T_VIDEOS, 'count(*)');
	        if ($check_for_video > 0) {
	            $video_id = PT_GenerateKey(15, 15);
	        }
	        $token = null;
            if (!empty($_POST['token']) && !is_null($_POST['token'])) {
                $token = PT_Secure($_POST['token']);
            }
			$post_id = $db->insert(T_VIDEOS,array('user_id' => $pt->user->id,
	                                             'type' => 'live',
	                                             'title' => 'live video '.$pt->user->name,
	                                             'stream_name' => PT_Secure($_POST['stream_name']),
	                                             'registered' => date('Y') . '/' . intval(date('m')),
	                                             'agora_token' => $token,
	                                             'video_id' => $video_id,
	                                             'time' => time()));
	        PT_RunInBackground(array('status' => 200,
	                                 'post_id' => $post_id));

	        if ($pt->config->live_video == 1 && !empty($pt->config->agora_app_id) && !empty($pt->config->agora_customer_id) && !empty($pt->config->agora_customer_certificate) && $pt->config->live_video_save == 1) {

	            if ($pt->config->amazone_s3_2 == 1 && !empty($pt->config->bucket_name_2) && !empty($pt->config->amazone_s3_key_2) && !empty($pt->config->amazone_s3_s_key_2) && !empty($pt->config->region_2)) {

	                $region_array = array('us-east-1' => 0,'us-east-2' => 1,'us-west-1' => 2,'us-west-2' => 3,'eu-west-1' => 4,'eu-west-2' => 5,'eu-west-3' => 6,'eu-central-1' => 7,'ap-southeast-1' => 8,'ap-southeast-2' => 9,'ap-northeast-1' => 10,'ap-northeast-2' => 11,'sa-east-1' => 12,'ca-central-1' => 13,'ap-south-1' => 14,'cn-north-1' => 15,'us-gov-west-1' => 17);

	                if (in_array(strtolower($pt->config->region_2),array_keys($region_array) )) {

	                    StartCloudRecording(1,$region_array[strtolower($pt->config->region_2)],$pt->config->bucket_name_2,$pt->config->amazone_s3_key_2,$pt->config->amazone_s3_s_key_2,$_POST['stream_name'],explode('_', $_POST['stream_name'])[2],$post_id);
	                }
	                
	            }
	        }
	        pt_push_channel_notifiations($post_id,'started_live_video');
	        $response_data     = array(
		        'api_status'   => '200',
		        'api_version'  => $api_version,
		        'post_id'  => $post_id,
		    );
    	}
	}
	elseif ($_POST['type'] == 'check_comments') {
		if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
			$post_id = PT_Secure($_POST['post_id']);
			$post_data = $video_data = $pt->get_video = $db->where('id',$post_id)->getOne(T_VIDEOS);
			if (!empty($post_data)) {
	            if ($post_data->live_ended == 0) {
	                //if ($_POST['page'] == 'story') {
	                    $user_comment = $db->where('video_id',$post_id)->where('user_id',$pt->user->id)->getOne(T_COMMENTS);
	                    if (!empty($user_comment)) {
	                        $db->where('id',$user_comment->id,'>');
	                    }
	                //}
	                if (!empty($_POST['ids'])) {
	                    $ids = array();
	                    foreach ($_POST['ids'] as $key => $one_id) {
	                        $ids[] = PT_Secure($one_id);
	                    }
	                    $db->where('id',$ids,'NOT IN')->where('id',end($ids),'>');
	                }
	                //if ($_POST['page'] == 'story') {
	                    $db->where('user_id',$pt->user->id,'!=');
	                //}
	                $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 0;
					$offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;
					if (!empty($offset) && $offset > 0) {
						$db->where('id', $offset,'>');
					}

					if (!empty($limit) && $limit > 0) {
						$comments = $db->where('video_id',$post_id)->where('text','','!=')->get(T_COMMENTS,$limit);
					}
					else{
						$comments = $db->where('video_id',$post_id)->where('text','','!=')->get(T_COMMENTS);
					}

					
					$html = '';
	                $count = 0;
	                $comments_all = array();
					foreach ($comments as $key => $get_comment) {
						if (!empty($get_comment->text)) {
	                        $user_data   = PT_UserData($get_comment->user_id);
	                        unset($user_data->password);
	                        $get_comment->user_data = $user_data;
	                        $comments_all[] = $get_comment;
						}
					}


	                
	                $word = $lang->offline;
	                $left_users_all = array();
	                $joined_users_all = array();
	                if (!empty($post_data->live_time) && $post_data->live_time >= (time() - 10)) {
	                    //$db->where('post_id',$post_id)->where('time',time()-6,'<')->update(T_LIVE_SUB,array('is_watching' => 0));
	                    $word = $lang->live;
	                    $count = $db->where('post_id',$post_id)->where('time',time()-6,'>=')->getValue(T_LIVE_SUB,'COUNT(*)');

	                    if ($pt->user->id == $post_data->user_id) {
	                        $joined_users = $db->where('post_id',$post_id)->where('time',time()-6,'>=')->where('is_watching',0)->get(T_LIVE_SUB);
	                        $joined_ids = array();
	                        
	                        if (!empty($joined_users)) {
	                            foreach ($joined_users as $key => $value) {
	                                $joined_ids[] = $value->user_id;
	                                $user_data   = PT_UserData($value->user_id);
	                                unset($user_data->password);
	                                $joined_users_all[] = $user_data;
	                            }
	                            if (!empty($joined_ids)) {
	                                $db->where('post_id',$post_id)->where('user_id',$joined_ids,'IN')->update(T_LIVE_SUB,array('is_watching' => 1));
	                            }
	                        }

	                        $left_users = $db->where('post_id',$post_id)->where('time',time()-6,'<')->where('is_watching',1)->get(T_LIVE_SUB);
	                        $left_ids = array();
	                        
	                        if (!empty($left_users)) {
	                            foreach ($left_users as $key => $value) {
	                                $left_ids[] = $value->user_id;
	                                $user_data   = PT_UserData($value->user_id);
	                                unset($user_data->password);
	                                $left_users_all[] = $user_data;
	                            }
	                            if (!empty($left_ids)) {
	                                $db->where('post_id',$post_id)->where('user_id',$left_ids,'IN')->delete(T_LIVE_SUB);
	                            }
	                        }
	                    }
	                }
	                $still_live = 'offline';
	                if (!empty($post_data) && $post_data->live_time >= (time() - 10)){
	                    $still_live = 'live';
	                }
	                $response_data = array(
	                    'api_status' => 200,
	                    'comments' => $comments_all,
	                    'count' => $count,
	                    'word' => $word,
	                    'still_live' => $still_live,
	                    'left' => $left_users_all,
	                    'joined' => $joined_users_all,
	                    'api_version' => $api_version,
	                );
	                
	                // Wo_RunInBackground(array(
	                //     'status' => 200,
	                //     'html' => $html,
	                //     'count' => $count,
	                //     'word' => $word,
	                //     'still_live' => $still_live
	                // ));
	                
	                if ($pt->user->id == $post_data->user_id) {
	                    if ($_POST['page'] == 'live') {
	                        $time = time();
	                        $db->where('id',$post_id)->update(T_VIDEOS,array('live_time' => $time));
	                    }
	                }
	                else{
	                    if (!empty($post_data->live_time) && $post_data->live_time >= (time() - 10) && $_POST['page'] == 'watch') {
	                        $is_watching = $db->where('user_id',$pt->user->id)->where('post_id',$post_id)->getValue(T_LIVE_SUB,'COUNT(*)');
	                        if ($is_watching > 0) {
	                            $db->where('user_id',$pt->user->id)->where('post_id',$post_id)->update(T_LIVE_SUB,array('time' => time()));
	                        }
	                        else{
	                            $db->insert(T_LIVE_SUB,array('user_id' => $pt->user->id,
	                                                         'post_id' => $post_id,
	                                                         'time' => time(),
	                                                         'is_watching' => 0));
	                        }
	                    }
	                }
	            }
	            else{
	                $response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '8',
				            'error_text' => 'The live video ended'
				        )
					);
	            }
	            
			}
			else{
				$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '7',
			            'error_text' => 'The video not found'
			        )
				);
			}
		}
		else{
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '6',
		            'error_text' => 'please check your details'
		        )
			);
		}
	}
	elseif ($_POST['type'] == 'delete') {
		if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
	        $db->where('id',PT_Secure($_POST['post_id']))->where('user_id',$pt->user->id)->update(T_VIDEOS,array('live_ended' => 1));
	        if ($pt->config->live_video_save == 0) {
	            PT_DeleteVideo(PT_Secure($_POST['post_id']));
	            $response_data     = array(
			        'api_status'   => '200',
			        'api_version'  => $api_version,
			        'message'  => 'deleted successfully'
			    );
	        }
	        else{
	            if ($pt->config->live_video == 1 && !empty($pt->config->agora_app_id) && !empty($pt->config->agora_customer_id) && !empty($pt->config->agora_customer_certificate) && $pt->config->live_video_save == 1) {
	                $post = $db->where('id',PT_Secure($_POST['post_id']))->getOne(T_VIDEOS);
	                if (!empty($post)) {
	                    StopCloudRecording(array('resourceId' => $post->agora_resource_id,
	                                             'sid' => $post->agora_sid,
	                                             'cname' => $post->stream_name,
	                                             'post_id' => $post->id,
	                                             'uid' => explode('_', $post->stream_name)[2]));
	                }
	            }
	            if ($pt->config->live_video == 1 && $pt->config->amazone_s3_2 != 1) {
	                try {
	                    PT_DeleteVideo(PT_Secure($_POST['post_id']));
	                    $response_data     = array(
					        'api_status'   => '200',
					        'api_version'  => $api_version,
					        'message'  => 'deleted successfully'
					    );
	                } catch (Exception $e) {
	                	$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '6',
					            'error_text' => 'something went wrong'
					        )
						);
	                }
	            }
	        }
	    }
	    else{
	    	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '5',
		            'error_text' => 'post_id can not be empty'
		        )
			);
	    }
	}
	elseif ($_POST['type'] == 'create_thumb') {
		if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0 && !empty($_FILES['thumb'])) {
	        $is_post = $db->where('id',PT_Secure($_POST['post_id']))->where('user_id',$pt->user->id)->getValue(T_VIDEOS,'COUNT(*)');
	        if ($is_post > 0) {
	            $fileInfo = array(
	                'file' => $_FILES["thumb"]["tmp_name"],
	                'name' => $_FILES['thumb']['name'],
	                'size' => $_FILES["thumb"]["size"],
	                'type' => $_FILES["thumb"]["type"],
	                'types' => 'jpeg,png,jpg,gif',
	                'crop' => array(
	                    'width' => 1076,
	                    'height' => 604
	                )
	            );
	            $media    = PT_ShareFile($fileInfo);
	            if (!empty($media)) {
	                $thumb = $media['filename'];
	                if (!empty($thumb)) {
	                    $db->where('id',PT_Secure($_POST['post_id']))->where('user_id',$pt->user->id)->update(T_VIDEOS,array('thumbnail' => $thumb));
	                    $response_data     = array(
					        'api_status'   => '200',
					        'api_version'  => $api_version,
					        'message'  => 'uploaded successfully'
					    );
	                }
	                else{
	                	$response_data    = array(
						    'api_status'  => '400',
						    'api_version' => $api_version,
						    'errors' => array(
					            'error_id' => '8',
					            'error_text' => 'invalid file'
					        )
						);
	                }
	            }
	            else{
	            	$response_data    = array(
					    'api_status'  => '400',
					    'api_version' => $api_version,
					    'errors' => array(
				            'error_id' => '7',
				            'error_text' => 'invalid file'
				        )
					);
	            }
	        }
	        else{
	        	$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '6',
			            'error_text' => 'post not found'
			        )
				);
	        }
	    }
	    else{
	    	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '5',
		            'error_text' => 'post_id , thumb can not be empty'
		        )
			);
	    }
	}
}