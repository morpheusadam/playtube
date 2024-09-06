<?php
if ($first == 'load') {
	$html = '';
	$data['status'] = 400;
	$videos = array();
	$userid = 0;

	
	if (!empty($_POST['user']) && is_numeric($_POST['user']) && !empty($_POST['video_id']) && is_numeric($_POST['video_id']) && !empty($_POST['sort_type']) && in_array($_POST['sort_type'], array('next','before'))) {
		$userid = PT_Secure($_POST['user']);
		$user  = $db->where('id', $userid)->getOne(T_USERS);

		if (!empty($user)) {
			if ($_POST['sort_type'] == 'next') {
				$videos = $db->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->where('user_id',$user->id)->where('id',PT_Secure($_POST['video_id']),'>')->get(T_VIDEOS,5,array('video_id'));
			}
			else{
				$u_videos = $db->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->where('user_id',$user->id)->where('id',PT_Secure($_POST['video_id']),'<')->orderBy('id','DESC')->get(T_VIDEOS,5,array('video_id'));

				for ($i= count($u_videos) - 1; $i >= 0 ; $i--) { 
					$videos[] = $u_videos[$i];
				}
			}
		}
	}
	else{
		if (!empty($pt->v_shorts) && !empty($_POST['seen_videos']) && !empty($_POST['seen_videos'])) {
			$seen_videos = array(0);
			foreach ($_POST['seen_videos'] as $key => $value) {
				if (is_numeric($value)) {
					$seen_videos[] = PT_Secure($value);
				}
			}
			$videos = $db->where('privacy', 0)->where('approved',1)->where('video_id',$pt->v_shorts , 'NOT IN')->where('id',$seen_videos , 'NOT IN')->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy("RAND ()")->get(T_VIDEOS,5,array('video_id'));
		}
	}
	if (!empty($videos)) {
		$data['status'] = 200;
	}
		

	foreach ($videos as $key => $value) {

		if (!empty($value->video_id)) {
			$get_video = PT_GetVideoByID($value->video_id, 1, 1);

			$pt->video_240 = 0;
			$pt->video_360 = 0;
			$pt->video_480 = 0;
			$pt->video_720 = 0;
			$pt->video_1080 = 0;
			$pt->video_2048 = 0;
			$pt->video_4096 = 0;
			if ($pt->config->ffmpeg_system == 'on') {
				$explode_video = explode('_video', $get_video->video_location);
				if ($get_video->{"240p"} == 1) {
			        $pt->video_240 = $explode_video[0] . '_video_shorts_240p_converted.mp4';
			    }
			    if ($get_video->{"360p"} == 1) {
			        $pt->video_360 = $explode_video[0] . '_video_shorts_360p_converted.mp4';
			    }
			    if ($get_video->{"480p"} == 1) {
			        $pt->video_480 = $explode_video[0] . '_video_shorts_480p_converted.mp4';
			    }
			    if ($get_video->{"720p"} == 1) {
			        $pt->video_720 = $explode_video[0] . '_video_shorts_720p_converted.mp4';
			    }
			    if ($get_video->{"1080p"} == 1) {
			        $pt->video_1080 = $explode_video[0] . '_video_shorts_1080p_converted.mp4';
			    }
			    if ($get_video->{"4096p"} == 1) {
			        $pt->video_4096 = $explode_video[0] . '_video_shorts_4096p_converted.mp4';
			    }
			    if ($get_video->{"2048p"} == 1) {
			        $pt->video_2048 = $explode_video[0] . '_video_shorts_2048p_converted.mp4';
			    }
			}
			$video_type = 'video/mp4';
			if (!empty($_POST['user']) && is_numeric($_POST['user']) && !empty($_POST['video_id']) && is_numeric($_POST['video_id']) && !empty($_POST['sort_type']) && in_array($_POST['sort_type'], array('next','before'))) {
				$get_video->url = $get_video->url."?user=".$user->username;
				$get_video->ajax_url = $get_video->ajax_url."&user=".$user->username;
			}
			$pt->count_comments  = $db->where('video_id', $get_video->id)->where('user_id',$pt->blocked_array , 'NOT IN')->getValue(T_COMMENTS, 'count(*)');
			$pt->get_video = $get_video;
			$pt->is_first_video = false;
			$pt->converted   = true;
			if ($pt->config->ffmpeg_system == 'on' && $get_video->converted != 1) {
			    $pt->converted = false;
			}
			$pt->is_first_video = false;
			$html  .= PT_LoadPage('shorts/list', array(
								    'ID' => $get_video->id,
								    'KEY' => $get_video->video_id,
								    'THUMBNAIL' => $get_video->thumbnail,
								    'TITLE' => $get_video->title,
								    'DESC' => $get_video->markup_description,
								    'URL' => $get_video->url,
								    'VIDEO_TYPE' => $video_type,
								    'VIDEO_LOCATION_240' => $pt->video_240,
								    'VIDEO_LOCATION' => $get_video->video_location,
								    'VIDEO_LOCATION_360' => $pt->video_360,
								    'VIDEO_LOCATION_480' => $pt->video_480,
								    'VIDEO_LOCATION_720' => $pt->video_720,
								    'VIDEO_LOCATION_1080' => $pt->video_1080,
								    'VIDEO_LOCATION_4096' => $pt->video_4096,
								    'VIDEO_LOCATION_2048' => $pt->video_2048,
								    'VIDEO_MAIN_ID' => $get_video->video_id,
								    'VIDEO_MAIN_ID' => $get_video->video_id,
								    'VIDEO_ID' => $get_video->video_id_,
								    'LIKES' => number_format($get_video->likes),
								    'DISLIKES' => number_format($get_video->dislikes),
								    'COUNT_COMMENTS' => $pt->count_comments,
								    'USER_DATA' => $get_video->owner,
								    'LIKE_ACTIVE_CLASS' => ($get_video->is_liked > 0) ? 'active' : '',
								    'DIS_ACTIVE_CLASS' => ($get_video->is_disliked > 0) ? 'active' : '',
								    'RAEL_LIKES' => $get_video->likes,
								    'RAEL_DISLIKES' => $get_video->dislikes,
								    'ISLIKED' => ($get_video->is_liked > 0) ? 'liked="true"' : '',
								    'ISDISLIKED' => ($get_video->is_disliked > 0) ? 'disliked="true"' : '',
								    'VIEWS' => number_format($get_video->views),));






		}
	}

	$data['html'] = $html;
}