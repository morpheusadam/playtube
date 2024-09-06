<?php 
	$last_id       = (!empty($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
	$video_id      = (!empty($_GET['video_id']) && is_numeric($_GET['video_id'])) ? $_GET['video_id'] : 0;
	$data          = array('status' => 404);
	$t_videos      = T_VIDEOS;
	$video_sidebar = "";

	if (!empty($last_id) && !empty($video_id)) {
		$video_data = $db->where('id',$video_id)->where('user_id',$pt->blocked_array , 'NOT IN')->getOne($t_videos);

		if (!empty($video_data)) {
			$video_title    = $video_data->title;
			$sql_query      = "
				SELECT * FROM `$t_videos` 
				WHERE MATCH (title) AGAINST ('$video_title') 
				AND id <> '{$video_id}'  AND user_id NOT IN (".implode(',', $pt->blocked_array).")
				AND id < '{$last_id}'
				AND id <> '{$last_id}'
				AND privacy = 0
				ORDER BY `id` DESC
				LIMIT 20";

			$related_videos = $db->rawQuery($sql_query);
			if (count($related_videos) > 0) {
				foreach ($related_videos as $key => $related_video) {
				    $related_video  = PT_GetVideoByID($related_video, 0, 0, 0);
				    $video_sidebar .= PT_LoadPage('watch/video-sidebar', array(
				        'ID' => $related_video->id,
				        'TITLE' => $related_video->title,
				        'URL' => $related_video->url,
				        'THUMBNAIL' => $related_video->thumbnail,
				        'USER_NAME' => $related_video->owner->name,
				        'VIEWS' => $related_video->views,
				        'TIME' => $related_video->time_alpha,
				        'V_ID' => $related_video->video_id,
				        'GIF' => $related_video->gif,
				        'DURATION' => $related_video->duration,
                        'USER_DATA' => $related_video->owner,
                        'CATEGORY' => $related_video->category_name,
				        'CATEGORY_LINK' => PT_Link('videos/category/'.$related_video->category_id)
				    ));
				}

				$data['status'] = 200;
				$data['html']   = $video_sidebar;
			}
		}
	}
 ?>