<?php
if ($first == 'create') {
    if ($pt->config->live_video == 1 && ($pt->config->who_use_live == 'all' || ($pt->config->who_use_live == 'admin' && PT_IsAdmin()) || ($pt->config->who_use_live == 'pro' && $pt->user->is_pro > 0))) {
    }
    else{
        $data['message'] = $lang->please_check_details;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
	if (empty($_POST['stream_name'])) {
		$data['message'] = $lang->please_check_details;
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
        $video_name = 'live video '.$pt->user->name;
        if (!empty($_POST['video_name'])) {
            $video_name = PT_Secure($_POST['video_name']);
        }
		$post_id = $db->insert(T_VIDEOS,array('user_id' => $pt->user->id,
                                             'type' => 'live',
                                             'title' => $video_name,
                                             'stream_name' => PT_Secure($_POST['stream_name']),
                                             'registered' => date('Y') . '/' . intval(date('m')),
                                             'video_id' => $video_id,
                                             'agora_token' => $token,
                                             'time' => time()));
        PT_RunInBackground(array('status' => 200,
                                 'post_id' => $post_id));

        if ($pt->config->live_video == 1 && !empty($pt->config->agora_app_id) && !empty($pt->config->agora_customer_id) && !empty($pt->config->agora_customer_certificate) && $pt->config->live_video_save == 1) {

            if ($pt->config->amazone_s3_2 == 1 && !empty($pt->config->bucket_name_2) && !empty($pt->config->amazone_s3_key_2) && !empty($pt->config->amazone_s3_s_key_2) && !empty($pt->config->region_2)) {

                $region_array = array('us-east-1' => 0,'us-east-2' => 1,'us-west-1' => 2,'us-west-2' => 3,'eu-west-1' => 4,'eu-west-2' => 5,'eu-west-3' => 6,'eu-central-1' => 7,'ap-southeast-1' => 8,'ap-southeast-2' => 9,'ap-northeast-1' => 10,'ap-northeast-2' => 11,'sa-east-1' => 12,'ca-central-1' => 13,'ap-south-1' => 14,'cn-north-1' => 15,'us-gov-west-1' => 17);

                if (in_array(strtolower($pt->config->region_2),array_keys($region_array) )) {

                    StartCloudRecording(1,$region_array[strtolower($pt->config->region_2)],$pt->config->bucket_name_2,$pt->config->amazone_s3_key_2,$pt->config->amazone_s3_s_key_2,$_POST['stream_name'],explode('_', $_POST['stream_name'])[2],$post_id, $token);
                }
                
            }
        }
        pt_push_channel_notifiations($post_id,'started_live_video');
        $data['status'] = 200;
        $data['post_id'] = $post_id;

	}
	header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($first == 'check_comments') {
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
                $html = '';
                if ($post_data->live_chating == 'on') {
                    $comments = $db->where('video_id',$post_id)->where('text','','!=')->get(T_COMMENTS);
                    $count = 0;
                    foreach ($comments as $key => $get_comment) {
                        if (!empty($get_comment->text)) {
                            $user_data   = PT_UserData($get_comment->user_id);
                            $pt->is_comment_owner = false;
                            $pt->is_verified      = ($user_data->verified == 1) ? true : false;
                            $pt->video_owner      = false;

                            if ($user->id == $get_comment->user_id) {
                                $pt->is_comment_owner = true;
                            }

                            if ($video_data->user_id == $user->id) {
                                $pt->video_owner = true;
                            }
                            $get_comment->text = PT_Duration($get_comment->text);

                            $html     .= PT_LoadPage('watch/live_comment', array(
                                'ID' => $get_comment->id,
                                'TEXT' => PT_Markup($get_comment->text),
                                'TIME' => PT_Time_Elapsed_String($get_comment->time),
                                'USER_DATA' => $user_data,
                                'LIKES' => 0,
                                'DIS_LIKES' => 0,
                                'LIKED' => '',
                                'DIS_LIKED' => '',
                                'LIKED_ATTR' => '',
                                'COMM_REPLIES' => '',
                                'VID_ID' => $get_comment->id
                            ));
                            $count = $count + 1;
                            if ($count == 4) {
                              break;
                            }
                        }
                    }
                }
                
                $word = $lang->offline;
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
                                $pt->is_verified      = ($user_data->verified == 1) ? true : false;
                                $html     .= PT_LoadPage('watch/live_comment', array(
                                    'ID' => '',
                                    'TEXT' => $lang->joined_live_video,
                                    'TIME' => '',
                                    'USER_DATA' => $user_data,
                                    'LIKES' => 0,
                                    'DIS_LIKES' => 0,
                                    'LIKED' => '',
                                    'DIS_LIKED' => '',
                                    'LIKED_ATTR' => '',
                                    'COMM_REPLIES' => '',
                                    'VID_ID' => ''
                                ));


                                // $wo['comment'] = array('id' => '',
                                //                        'text' => 'joined live video');
                                // $user_data = Wo_UserData($value->user_id);
                                // if (!empty($user_data)) {
                                //     $wo['comment']['publisher'] = $user_data;
                                //     $html .= Wo_LoadPage('story/includes/live_comment');
                                // }
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
                                $pt->is_verified      = ($user_data->verified == 1) ? true : false;
                                $html     .= PT_LoadPage('watch/live_comment', array(
                                    'ID' => '',
                                    'TEXT' => $lang->left_live_video,
                                    'TIME' => '',
                                    'USER_DATA' => $user_data,
                                    'LIKES' => 0,
                                    'DIS_LIKES' => 0,
                                    'LIKED' => '',
                                    'DIS_LIKED' => '',
                                    'LIKED_ATTR' => '',
                                    'COMM_REPLIES' => '',
                                    'VID_ID' => ''
                                ));


                                // $wo['comment'] = array('id' => '',
                                //                        'text' => 'left live video');
                                // $user_data = Wo_UserData($value->user_id);
                                // if (!empty($user_data)) {
                                //     $wo['comment']['publisher'] = $user_data;
                                //     $html .= Wo_LoadPage('story/includes/live_comment');
                                // }
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
                $comments = 'on';
                if ($post_data->live_chating == 'off') {
                    $comments = 'off';
                }
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'count' => $count,
                    'word' => $word,
                    'still_live' => $still_live,
                    'comments' => $comments
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
                $data['message'] = $lang->please_check_details;
            }
            
		}
		else{
			$data['message'] = $lang->please_check_details;
            $data['removed'] = 'yes';
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
	header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($first == 'delete') {
    if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
        $video = $db->where('id',PT_Secure($_POST['post_id']))->getOne(T_VIDEOS);
        if (!empty($video) && ($video->user_id == $pt->user->id || PT_IsAdmin())) {
            $db->where('id',PT_Secure($_POST['post_id']))->where('user_id',$pt->user->id)->update(T_VIDEOS,array('live_ended' => 1));
            if ($pt->config->live_video_save == 0) {
                PT_DeleteVideo(PT_Secure($_POST['post_id']));
            }
            else{
                if ($pt->config->live_video == 1 && !empty($pt->config->agora_app_id) && !empty($pt->config->agora_customer_id) && !empty($pt->config->agora_customer_certificate) && $pt->config->live_video_save == 1) {
                    StopCloudRecording(array('resourceId' => $video->agora_resource_id,
                                             'sid' => $video->agora_sid,
                                             'cname' => $video->stream_name,
                                             'post_id' => $video->id,
                                             'token' => $video->agora_token,
                                             'uid' => explode('_', $video->stream_name)[2]));
                }
                if ($pt->config->live_video == 1 && $pt->config->amazone_s3_2 != 1) {
                    try {
                        PT_DeleteVideo(PT_Secure($_POST['post_id']));
                    } catch (Exception $e) {
                        
                    }
                }
            }
        }
    }
}
if ($first == 'create_thumb') {
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
                    $data['status'] = 200;
                    header("Content-type: application/json");
                    echo json_encode($data);
                    exit();
                }
            }
        }
    }
}
if ($first == 'live_chating') {
    $data['status'] = 400;
    if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0 && !empty($_POST['type']) && in_array($_POST['type'], array('on','off'))) {
        $post_id = PT_Secure($_POST['post_id']);
        $pt->get_video = $db->where('id',$post_id)->where('user_id',$pt->user->id)->getOne(T_VIDEOS);
        if (!empty($pt->get_video)) {
            $db->where('id',$post_id)->where('user_id',$pt->user->id)->update(T_VIDEOS,array('live_chating' => PT_Secure($_POST['type'])));
            $data['status'] = 200;
            $data['type'] = PT_Secure($_POST['type']);
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}