<?php
$videos = array();
$pt->userid = 0;
$pt->current_index = 0;
$id = 0;

$pt->title = $lang->shorts;
$pt->description = $pt->config->description;;

if (!empty($_GET['user']) && !empty($_GET['id'])) {

	$id = PT_Secure($_GET['id']);
	if (strpos($id, '_') !== false) {
	    $id_array = explode('_', $id);
	    $id_html  = $id_array[1];
	    $id       = str_replace('.html', '', $id_html);
	    $id = PT_Secure($id);
	}

	if (empty($id)) {
		header("Location: " . PT_Link(''));
	    exit();
	}

	if (!IS_LOGGED || (IS_LOGGED && $user->id != $pt->user->id && PT_IsAdmin())) {
		$db->where('privacy',0);
	}

	$current = $db->where('video_id', $id)->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->getOne(T_VIDEOS,array('video_id','id') );

	$username = strip_tags($_GET['user']);
	$username = PT_Secure($username);
	$user  = $db->where('username', $username)->getOne(T_USERS);

	if (empty($user)) {
		header("Location: " . PT_Link(''));
	    exit();
	}
	if (empty($current)) {
		header("Location: " . PT_Link(''));
	    exit();
	}

	$pt->userid = $user->id;

	if (!IS_LOGGED || (IS_LOGGED && $user->id != $pt->user->id && PT_IsAdmin())) {
		$db->where('privacy',0);
	}
	$min = $db->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('user_id',$pt->userid)->where('is_short',1)->where('id',$current->id,'<')->orderBy("id",'DESC')->getOne(T_VIDEOS,array('video_id'));

	if (!empty($min)) {
		$object = new stdClass();
		$object->video_id = $min->video_id;
		$videos[] = $object;
		$pt->current_index = 1;
	}

	$object = new stdClass();
	$object->video_id = $current->video_id;
	$videos[] = $object;

	if (!IS_LOGGED || (IS_LOGGED && $user->id != $pt->user->id && PT_IsAdmin())) {
		$db->where('privacy',0);
	}
	$max = $db->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('user_id',$pt->userid)->where('is_short',1)->where('id',$current->id,'>')->getOne(T_VIDEOS,array('video_id'));

	if (!empty($max)) {
		$object = new stdClass();
		$object->video_id = $max->video_id;
		$videos[] = $object;
	}
}
else{

	if (!empty($_GET['id']) && empty($_GET['user'])) {
		$id = PT_Secure($_GET['id']);
		if (strpos($id, '_') !== false) {
		    $id_array = explode('_', $id);
		    $id_html  = $id_array[1];
		    $id       = str_replace('.html', '', $id_html);
		    $id = PT_Secure($id);
			$db->where('video_id',$id , '<>');
		}
	}

	if (!empty($pt->v_shorts)) {
		$db->where('video_id',$pt->v_shorts , 'NOT IN');
	}

	$videos = $db->where('privacy', 0)->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy("RAND ()")->get(T_VIDEOS,5,array('video_id'));

	if (empty($videos) && !empty($pt->v_shorts)) {
		setcookie('v_shorts', json_encode(array()), time()+(60 * 60 * 24),'/');
		$pt->v_shorts = array();

		if (!empty($id)) {
			$db->where('video_id',$id , '<>');
		}
		$videos = $db->where('privacy', 0)->where('approved',1)->where('user_id',$pt->blocked_array , 'NOT IN')->where('is_short',1)->orderBy("RAND ()")->get(T_VIDEOS,5,array('video_id'));
	}

	if (!empty($id)) {
		$object = new stdClass();
		$object->video_id = $id;
		array_unshift($videos, $object);
	}
}




$html = '';
$pt->is_empty = false;
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
		if (!empty($_GET['user']) && !empty($_GET['id']) && !empty($user)) {
			$get_video->url = $get_video->url."?user=".$user->username;
			$get_video->ajax_url = $get_video->ajax_url."&user=".$user->username;
		}
		$pt->count_comments  = $db->where('video_id', $get_video->id)->where('user_id',$pt->blocked_array , 'NOT IN')->getValue(T_COMMENTS, 'count(*)');
		$pt->get_video = $get_video;
		$pt->converted   = true;
		if ($get_video->converted != 1) {
		    $pt->converted = false;
		}
		$pt->is_first_video = false;
		if ($key == $pt->current_index && !$pt->is_ajax_load) {
			$pt->is_first_video = true;
		}
		if ($key == $pt->current_index) {
			$pt->page_url_ = $get_video->url;
			$pt->title = (!empty($get_video) && !empty($get_video->title) ? $get_video->title : $pt->config->title);
		}
		if ($key == 0) {
			if (empty($_GET['id']) && !$pt->is_ajax_load) {
				header("Location: " . $get_video->url);
    			exit();
			}
			if (!empty($_GET['id'])) {
				if ($get_video->privacy == 1) {
				    if (!IS_LOGGED) {
				        header("Location: " . PT_Link(''));
	    				exit();
				    } else if (($get_video->user_id != $pt->user->id) && ($pt->user->admin == 0)) {
				        header("Location: " . PT_Link(''));
	    				exit();
				    }
				}
			}

			$desc = strip_tags($get_video->edit_description);
			$desc = str_replace('"', "'", $desc);
			$desc = str_replace('<br>', "", $desc);
			$desc = str_replace("\n", "", $desc);
			$desc = str_replace("\r", "", $desc);
			$desc = mb_substr($desc, 0, 220, "UTF-8");
			$desc = htmlspecialchars($desc);
			$desc = filter_var ( $desc, FILTER_UNSAFE_RAW);
			$pt->description = $desc;
		}
		if ($pt->config->history_system == 'on' && IS_LOGGED == true && $user->pause_history == 0) {
			$history = $db->where('video_id', $get_video->id)->where('user_id', $user->id)->getOne(T_HISTORY);
	        if (!empty($history)) {
	            $db->where('id', $history->id)->delete(T_HISTORY);
	        }

            $insert_to_history = array(
                'user_id' => $user->id,
                'video_id' => $get_video->id,
                'time' => time()
            );
            $insert_to_history_query = $db->insert(T_HISTORY, $insert_to_history);
	    }

	    if (!in_array($get_video->video_id, $pt->v_shorts) && $key == $pt->current_index) {
	    	$pt->v_shorts[] = $get_video->video_id;
	    }
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

if (!empty($pt->v_shorts)) {
	setcookie('v_shorts', json_encode($pt->v_shorts), time()+(60 * 60 * 24),'/');
}

if (empty($html)) {
	$pt->is_empty = true;
	$html = '<div class="text-center no-comments-found empty_state"><svg class="feather" width="24" height="24" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 98.94 122.88" xml:space="preserve"><g><path fill="currentColor" class="st0" d="M63.49,2.71c11.59-6.04,25.94-1.64,32.04,9.83c6.1,11.47,1.65,25.66-9.94,31.7l-9.53,5.01 c8.21,0.3,16.04,4.81,20.14,12.52c6.1,11.47,1.66,25.66-9.94,31.7l-50.82,26.7c-11.59,6.04-25.94,1.64-32.04-9.83 c-6.1-11.47-1.65-25.66,9.94-31.7l9.53-5.01c-8.21-0.3-16.04-4.81-20.14-12.52c-6.1-11.47-1.65-25.66,9.94-31.7L63.49,2.71 L63.49,2.71z M36.06,42.53l30.76,18.99l-30.76,18.9V42.53L36.06,42.53z"></path></g></svg>'.$pt->all_lang->no_videos_found_for_now.'</div>';
	$pt->page_url_ = PT_Link('shorts');
}
if ($get_video->converted != 1) {
    $pt->in_queue = true;
    $pt->converted = false;
}

$pt->order = '';

$pt->page = $page = 'shorts';
$pt->keyword     = @$pt->config->keyword;
$pt->content  = PT_LoadPage('shorts/content', array('HTML' => $html,
                                                    'main_user' => $pt->userid));
