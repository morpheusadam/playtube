<?php
if (IS_LOGGED == false) {
	header("Location: " . PT_Link('login'));
	exit();
}

$list = '<div class="text-center no-content-found empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video-off"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' . $lang->no_videos_found_for_now . '</div>';
$final = '';
$pt->show_textarea = false;
if (isset($_POST['type']) && !empty($_POST['type'])) {
	$_GET['type'] = strip_tags($_GET['type']);
    if ($_POST['type'] == 'views') {
    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
			$db->where('is_movie', 1);
		}
		else{
			$db->where('is_movie', 0);
		}
        $videos = $db->where('user_id', $user->id)->orderBy('views', 'DESC')->get(T_VIDEOS, 20);
    }
    elseif ($_POST['type'] == 'likes') {
    	$limit = 20;
    	$movies_query = ' AND is_movie = 0 ';
    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
			$movies_query = ' AND is_movie = 1 ';
		}
        $top_likes = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.''.$movies_query.' AND id = l.video_id) = video_id AND type = 1  GROUP BY video_id ORDER BY count DESC LIMIT '.$limit);
        if (!empty($top_likes)) {
	        if (count($top_likes) < $limit) {
	        	$liked_ids = array();
	        	foreach ($top_likes as $key => $value) {
	        		$liked_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        	$n_limit = ($limit - count($top_likes) == 1) ? 2 : $limit - count($top_likes);
	        	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
					$db->where('is_movie', 1);
				}
				else{
					$db->where('is_movie', 0);
				}
	        	$other_videos = $db->where('id',$liked_ids, 'NOT IN')->where('user_id', $pt->user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $n_limit);
	        	if (!empty($other_videos)) {
	        		foreach ($other_videos as $key => $value) {
		        		$liked_ids[] = $value->id;
		        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
		        	}
	        	}

	        }
	        else{
	        	foreach ($top_likes as $key => $value) {
	        		$liked_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        }
	    }
	    else{
	    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
				$db->where('is_movie', 1);
			}
			else{
				$db->where('is_movie', 0);
			}
	    	$other_videos = $db->where('user_id', $user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $limit);
	    	foreach ($other_videos as $key => $value) {
        		$comments_ids[] = $value->id;
        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
        	}
	    }
    }
    elseif ($_POST['type'] == 'dislikes') {
        $limit = 20;
        $movies_query = ' AND is_movie = 0 ';
    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
			$movies_query = ' AND is_movie = 1 ';
		}
        $top_dislikes = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_DIS_LIKES.' l WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.''.$movies_query.' AND id = l.video_id) = video_id AND type = 2  GROUP BY video_id ORDER BY count DESC LIMIT '.$limit);
        if (!empty($top_dislikes)) {
	        if (count($top_dislikes) < $limit) {
	        	$disliked_ids = array();
	        	foreach ($top_dislikes as $key => $value) {
	        		$disliked_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        	$n_limit = ($limit - count($top_dislikes) == 1) ? 2 : $limit - count($top_dislikes);
	        	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
					$db->where('is_movie', 1);
				}
				else{
					$db->where('is_movie', 0);
				}
	        	$other_videos = $db->where('id',$disliked_ids, 'NOT IN')->where('user_id', $pt->user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $n_limit);
	        	if (!empty($other_videos)) {
	        		foreach ($other_videos as $key => $value) {
		        		$disliked_ids[] = $value->id;
		        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
		        	}
	        	}
	        }
	        else{
	        	foreach ($top_dislikes as $key => $value) {
	        		$disliked_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        }
	    }
	    else{
	    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
				$db->where('is_movie', 1);
			}
			else{
				$db->where('is_movie', 0);
			}
	    	$other_videos = $db->where('user_id', $user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $limit);
	    	foreach ($other_videos as $key => $value) {
        		$comments_ids[] = $value->id;
        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
        	}
	    }
    }
    elseif ($_POST['type'] == 'comments') {
        $limit = 20;
        $movies_query = ' AND is_movie = 0 ';
    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
			$movies_query = ' AND is_movie = 1 ';
		}

        $top_comments = $db->rawQuery('SELECT video_id, COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE (SELECT id FROM '.T_VIDEOS.' WHERE user_id = '.$pt->user->id.''.$movies_query.' AND id = c.video_id) = video_id GROUP BY video_id ORDER BY count DESC LIMIT '.$limit);
        if (!empty($top_comments)) {
	        if (count($top_comments) < $limit) {
	        	$comments_ids = array();
	        	foreach ($top_comments as $key => $value) {
	        		$comments_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        	$n_limit = ($limit - count($top_comments) == 1) ? 2 : $limit - count($top_comments);
	        	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
					$db->where('is_movie', 1);
				}
				else{
					$db->where('is_movie', 0);
				}
	        	$other_videos = $db->where('id',$comments_ids, 'NOT IN')->where('user_id', $pt->user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $n_limit);
	        	if (!empty($other_videos)) {
	        		foreach ($other_videos as $key => $value) {
		        		$comments_ids[] = $value->id;
		        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
		        	}
	        	}
	        }
	        else{
	        	foreach ($top_comments as $key => $value) {
	        		$comments_ids[] = $value->video_id;
	        		$videos[] = $db->where('id',  $value->video_id)->getOne(T_VIDEOS);
	        	}
	        }
	    }
	    else{
	    	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
				$db->where('is_movie', 1);
			}
			else{
				$db->where('is_movie', 0);
			}
	    	$other_videos = $db->where('user_id', $user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, $limit);
	    	foreach ($other_videos as $key => $value) {
        		$comments_ids[] = $value->id;
        		$videos[] = $db->where('id',  $value->id)->getOne(T_VIDEOS);
        	}
	    }
    }
}
else{
	if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
		$db->where('is_movie', 1);
	}
	else{
		$db->where('is_movie', 0);
	}
	$videos = $db->where('user_id', $user->id)->orderBy('id', 'DESC')->get(T_VIDEOS, 20);
}

if (!empty($videos)) {
	$len = count($videos);
	foreach ($videos as $key => $video) {
		$video = PT_GetVideoByID($video, 0, 1, 0);
	    $pt->last_video = false;
	    if ($key == $len - 1) {
	        $pt->last_video = true;
	    }
			$file_name = 'list';
	    $comments_count = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_COMMENTS.' c WHERE  video_id = '.$video->id);
			if ($video->is_short == 1) {
				$file_name = 'shorts_list';
			}
	    $final .= PT_LoadPage("video_studio/$file_name", array(
			        'ID' => $video->id,
			        'USER_DATA' => $video->owner,
			        'THUMBNAIL' => $video->thumbnail,
			        'URL'       => $video->url,
			        'TITLE'     => $video->title,
			        'DESC'      => $video->markup_description,
			        'VIEWS'     => number_format($video->views),
			        'TIME'      => $video->time_ago,
			        'VIDEO_ID_' => PT_Slug($video->title, $video->video_id),
			        'V_ID'      => $video->video_id,
			        'LIKES'     => number_format($video->likes),
			        'DISLIKES'  => number_format($video->dislikes),
			        'COMMENTS'  => number_format($comments_count[0]->count)
			    ));
	}
}

if (empty($final)) {
	$final = $list;
}
$pt->videos_type = 'videos';
if (!empty($_GET['videos_type']) && $_GET['videos_type'] == 'movies') {
	$pt->videos_type = 'movies';
	$pt->page_url_ = $pt->config->site_url.'/video_studio?videos_type=movies';
}
else{
	$pt->page_url_ = $pt->config->site_url.'/video_studio';
}

$pt->page = 'video_studio';
$pt->videos  = $videos;
$pt->title = $lang->video_studio . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage('video_studio/content', array('VIDEOS' => $final));
