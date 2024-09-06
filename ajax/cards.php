<?php
if ($first == 'create') {
	if (!empty($_POST['video_id']) &&is_numeric($_POST['video_id']) && $_POST['video_id'] > 0 && !empty($_POST['type']) && in_array($_POST['type'], array('text','video','subscribe')) && !empty($_POST['part']) && in_array($_POST['part'], array('top_left','top_right','bottom_left','bottom_right','center'))) {
		$insert_array = array();
		$video = $db->where('id',PT_Secure($_POST['video_id']))->where('user_id',$pt->user->id)->getOne(T_VIDEOS);
		if (!empty($video) && empty($video->facebook) && empty($video->vimeo) && empty($video->daily) && empty($video->youtube) && empty($video->twitch) && empty($video->ok)) {
			if($_POST['type'] == 'text'){
				if (!empty($_POST['title'])) {
					if(!empty($_POST['url']) && !pt_is_url($_POST['url'])){
						$error = $lang->invalid_url;
					}
					if(mb_strlen($_POST['title']) < 10 || mb_strlen($_POST['title']) > 200){
						$error = $lang->invalid_title;
					}
					if (empty($_POST['duration'])) {
						$_POST['duration'] = $video->duration;
					}
					if (empty($error)) {
						$ad_date = '';
						$minutes = (int) ($_POST['duration'] / 60);
						$seconds = ($_POST['duration'] % 60);
						if ($minutes < 10 && $minutes > 0) {
							$ad_date = '0'.$minutes.':';
						}
						elseif ($minutes > 9) {
							$ad_date = $minutes.':';
						}
						elseif ($minutes == 0) {
							$ad_date = '00:';
						}
						if ($seconds < 10 && $seconds > 0) {
							$ad_date .= '0'.$seconds;
						}
						elseif ($seconds > 9) {
							$ad_date .= $seconds;
						}
						elseif ($seconds == 0) {
							$ad_date .= '00';
						}
						$link_regex = '/[0-9]*:[0-9]{2}/i';
						if (preg_match($link_regex, $ad_date)) {
							$insert_array = array('video_id' => $video->id,
					                              'title' => PT_Secure($_POST['title'],1),
					                              'type' => 'text',
					                              'user_id' => $pt->user->id,
					                              'duration' => $ad_date,
					                              'part' => PT_Secure($_POST['part']),
					                              'time' => time());
							$re = '/(#([\da-f]{3}){1,2}|(rgb|hsl)a\((\d{1,3}%?,\s?){3}(1|0?\.\d+)\)|(rgb|hsl)\(\d{1,3}%?(,\s?\d{1,3}%?){2}\))/m';
							if (!empty($_POST['color']) && preg_match($re,$_POST['color'])) {
								$insert_array['color'] = PT_Secure($_POST['color']);
							}
							if (!empty($_POST['background_color']) && preg_match($re,$_POST['background_color'])) {
								$insert_array['background_color'] = PT_Secure($_POST['background_color']);
							}
							if (!empty($_POST['url'])) {
								$insert_array['url'] = PT_Secure($_POST['url']);
							}
						}
						else{
							$error = $lang->invalid_video_duration;
						}
					}
				}else{
					$error = $lang->please_check_details;
				}
			}
			if($_POST['type'] == 'video'){
				if (!empty($_POST['ref_video']) && is_numeric($_POST['ref_video']) && $_POST['ref_video'] > 0) {
					$ref_video = $db->where('id',PT_Secure($_POST['ref_video']))->where('user_id',$pt->user->id)->getOne(T_VIDEOS);
					if (!empty($ref_video)) {
						$max = $db->where('video_id',$video->id)->where('user_id',$pt->user->id)->where("(type = 'video' OR type = 'subscribe')")->getValue(T_CARDS,'COUNT(*)');
						if ($max < 5) {
							$there_is_part = $db->where('video_id',$video->id)->where('user_id',$pt->user->id)->where("(type = 'video' OR type = 'subscribe')")->where('part',PT_Secure($_POST['part']))->getValue(T_CARDS,'COUNT(*)');
							if ($there_is_part == 0) {
								$insert_array = array('video_id' => $video->id,
						                              'type' => 'video',
						                              'user_id' => $pt->user->id,
						                              'part' => PT_Secure($_POST['part']),
						                              'ref_video' => PT_Secure($_POST['ref_video']),
						                              'time' => time());
							}
							else{
								$error = $lang->please_select_another_part;
							}
						}
						else{
							$error = $lang->you_cant_make_more;
						}
					}
					else{
						$error = $lang->please_check_details;
					}
				}
				else{
					$error = $lang->please_check_details;
				}

			}
			if($_POST['type'] == 'subscribe'){
				$max = $db->where('video_id',$video->id)->where('user_id',$pt->user->id)->where("(type = 'video' OR type = 'subscribe')")->getValue(T_CARDS,'COUNT(*)');
				if ($max < 5) {
					$there_is_part = $db->where('video_id',$video->id)->where('user_id',$pt->user->id)->where("(type = 'video' OR type = 'subscribe')")->where('part',PT_Secure($_POST['part']))->getValue(T_CARDS,'COUNT(*)');
					if ($there_is_part == 0) {
						$insert_array = array('video_id' => $video->id,
				                              'user_id' => $pt->user->id,
				                              'type' => 'subscribe',
				                              'part' => PT_Secure($_POST['part']),
				                              'time' => time());
					}
					else{
						$error = $lang->please_select_another_part;
					}
				}
				else{
					$error = $lang->you_cant_make_more;
				}
			}
			if (empty($error) && !empty($insert_array)) {
				$db->insert(T_CARDS,$insert_array);
				$data['status'] = 200;
				$data['message'] = $lang->card_published;
			}
			else{
				$data['message'] = $error;
			}
		}
		else{
			$data['message'] = $lang->please_check_details;
		}
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}
if ($first == 'search'){
	if (!empty($_POST['search_value'])) {
		$search_value = PT_Secure($_POST['search_value']);
		$search_result = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE (title LIKE '%$search_value%' OR tags LIKE '%$search_value%' OR description LIKE '%$search_value%') AND privacy = 0 AND user_id = ".$pt->user->id." LIMIT 10");
		if (!empty($search_result)) {
			$html = '';
			foreach ($search_result as $key => $search) {
				$search = PT_GetVideoByID($search, 0, 0, 0);
				$html .= "<div class='search-result pointer' onclick='SelectVideo($search->id,this)'>$search->title</div>";
			}
			$data = array('status' => 200, 'html' => $html);
		}
	}
}
if ($first == 'delete') {
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$db->where('id',PT_Secure($_POST['id']))->where('user_id',$pt->user->id)->delete(T_CARDS);
		$data['status'] = 200;
	}
	else{
		$data['message'] = $lang->please_check_details;
	}
}