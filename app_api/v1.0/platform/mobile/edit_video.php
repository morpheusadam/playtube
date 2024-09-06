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
else{
	if (!empty($_POST['video_id']) && is_numeric($_POST['video_id']) && $_POST['video_id'] > 0) {
		$id = PT_Secure($_POST['video_id']);
		$video = $db->where('id', $id)->getOne(T_VIDEOS);
		if (!empty($video)) {
			if ($video->user_id == $pt->user->id) {

				if (!empty($_POST['title']) && !empty($_POST['description']) && !empty($_POST['tags'])) {
					$error = '';

					if (($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->sell_videos_system == 'on' && $pt->user->admin) && !empty($_POST['set_p_v'])) {
					    if (!empty($_POST['set_p_v']) || $_POST['set_p_v'] < 0) {
					        if (!is_numeric($_POST['set_p_v']) || $_POST['set_p_v'] < 0 || (($pt->config->com_type == 0 && $_POST['set_p_v'] <= $pt->config->admin_com_sell_videos)) ) {
					            $error = "The video price should be numeric and more than ".($pt->config->com_type == 0 ? $pt->config->admin_com_sell_videos : 0);
					            $response_data       = array(
							        'api_status'     => '400',
							        'api_version'    => $api_version,
							        'errors'         => array(
							            'error_id'   => '5',
							            'error_text' => $error
							        )
							    );
					        }
					    }
					}
					if (empty($error)) {

						$can_update = false;
					    if ($video->user_id == $user->id) {
				    		$can_update = true;
				    	}

					    if (!empty($_POST['set_p_v']) && $video->sell_video == 0) {
					    	$can_update = false;
					    }
					    if ($can_update == true && !empty($video)) {
					    	$video = PT_GetVideoByID($video, 0, 0, 0);
					    	$thumbnail = $video->org_thumbnail;
					    	if (!empty($_FILES['thumbnail']['tmp_name'])) {
						        $file_info   = array(
						            'file' => $_FILES['thumbnail']['tmp_name'],
						            'size' => $_FILES['thumbnail']['size'],
						            'name' => $_FILES['thumbnail']['name'],
						            'type' => $_FILES['thumbnail']['type'],
						            'allowed' => 'jpg,png,jpeg,gif',
						            'crop' => array(
						                'width' => 1076,
						                'height' => 604
						            )
						        );
						        $file_upload = PT_ShareFile($file_info);
						        if (!empty($file_upload['filename'])) {
						            $thumbnail = PT_Secure($file_upload['filename']);
						        }
						    }
						    $category_id = 0;
						    if (!empty($_POST['category_id'])) {
						        if (in_array($_POST['category_id'], array_keys($categories))) {
						            $category_id = PT_Secure($_POST['category_id']);
						        }
						    }
						    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
						    $i          = 0;
						    preg_match_all($link_regex, PT_Secure($_POST['description']), $matches);
						    foreach ($matches[0] as $match) {
						        $match_url           = strip_tags($match);
						        $syntax              = '[a]' . urlencode($match_url) . '[/a]';
						        $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
						    }
						     $featured = $video->featured;
						    // if (isset($_POST['featured']) && PT_IsAdmin()) {
						    // 	$featured = PT_Secure($_POST['featured']);
						    // }
						    $video_privacy = 0;
						    if (!empty($_POST['privacy'])) {
						        if (in_array($_POST['privacy'], array(0, 1, 2))) {
						            $video_privacy = PT_Secure($_POST['privacy']);
						        }
						    }
						    $age_restriction = 1;
					        if (!empty($_POST['age_restriction'])) {
					            if (in_array($_POST['age_restriction'], array(1, 2))) {
					                $age_restriction = PT_Secure($_POST['age_restriction']);
					            }
					        }
					        $sub_category = 0;

						    if (!empty($_POST['sub_category_id']) && is_numeric($_POST['sub_category_id']) && $_POST['sub_category_id'] > 0) {
						        $is_found = $db->where('type',PT_Secure($_POST['category_id']))->where('lang_key',PT_Secure($_POST['sub_category_id']))->getValue(T_LANGS,'COUNT(*)');
						        if ($is_found > 0) {
						            $sub_category = PT_Secure($_POST['sub_category_id']);
						        }
						    }
						    $data_update = array(
						        'title' => PT_Secure($_POST['title']),
						        'description' => PT_Secure($_POST['description']),
						        'tags' => PT_Secure($_POST['tags']),
						        'category_id' => $category_id,
						        'featured' => $featured,
						        'thumbnail' => $thumbnail,
						        'privacy' => $video_privacy,
						        'age_restriction' => $age_restriction,
						        'sub_category' => $sub_category
						    );
						    if (!empty($_POST['set_p_v']) && is_numeric($_POST['set_p_v']) && $_POST['set_p_v'] > 0) {
					            $data_update['sell_video'] = PT_Secure($_POST['set_p_v']);
					        }
						    $update  = $db->where('id', $id)->update(T_VIDEOS, $data_update);
						    
						    if ($update) {
						        $response_data     = array(
								    'api_status'   => '200',
								    'api_version'  => $api_version,
								    'success_type' => 'edit_video',
								    'message'      => 'Your video successfully edited.'
								);
						    }
					    }


					}



				}
				else{
					$response_data       = array(
				        'api_status'     => '400',
				        'api_version'    => $api_version,
				        'errors'         => array(
				            'error_id'   => '4',
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
			            'error_id'   => '5',
			            'error_text' => 'You can not edit the video you are not the video owner'
			        )
			    );
			}
		}
		else{
			$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '3',
		            'error_text' => 'Video not found'
		        )
			);
		}

	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '2',
	            'error_text' => 'the video_id should be numeric'
	        )
		);
	}
}