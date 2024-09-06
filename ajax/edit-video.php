<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
	if (empty($_POST['movie_title']) || empty($_POST['movie_description']) || empty($_POST['stars']) || empty($_POST['producer']) || empty($_POST['country']) || empty($_POST['quality']) || empty($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 10 || empty($_POST['release']) || empty($_POST['category']) || !in_array($_POST['category'], array_keys($pt->movies_categories))) {
        $error = $error_icon . $lang->please_check_details;
    }
}
else{
	if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['tags']) || empty($_POST['video-id'])) {
	    $error = $lang->please_check_details;
	}

	if (($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->sell_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->sell_videos_system == 'on' && $pt->user->admin) && !empty($_POST['set_p_v'])) {
	    if (!empty($_POST['set_p_v'])  || (in_array('set_p_v', array_keys($_POST)) && $_POST['set_p_v'] < 0)) {
	        if (!is_numeric($_POST['set_p_v']) || $_POST['set_p_v'] < 0 || (($pt->config->com_type == 0 && $_POST['set_p_v'] <= $pt->config->admin_com_sell_videos)) ) {
	            $error = $lang->video_price_error." ".($pt->config->com_type == 0 ? $pt->config->admin_com_sell_videos : 0);
	        }
	    }
	}
	if (($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'pro_users' && $pt->user->is_pro) || ($pt->config->rent_videos_system == 'on' && $pt->config->who_sell == 'users') || ($pt->config->rent_videos_system == 'on' && $pt->user->admin)) {
        if (!empty($_POST['rent_price'])  || (in_array('rent_price', array_keys($_POST)) && $_POST['rent_price'] < 0)) {
            if (!is_numeric($_POST['rent_price']) || $_POST['rent_price'] < 0 || (($pt->config->com_type == 0 && $_POST['rent_price'] <= $pt->config->admin_com_rent_videos)) ) {
                $error = $lang->video_rent_price_error." ".($pt->config->com_type == 0 ? $pt->config->admin_com_rent_videos : 0);
            }
        }
    }

	if (PT_IsAdmin()) {
		if (!empty($_POST['assign_to_user'])) {
			$id = PT_Secure($_POST['video-id']);
			$username_ = PT_Secure($_POST['assign_to_user']);
			$user_  = $db->where('username', $username_)->getOne(T_USERS);
			if (!empty($user_)) {
				$db->where('id', $id)->update(T_VIDEOS, array('user_id' => $user_->id));
			}
			else{
				$error = $lang->user_not_exists;
			}
		}
	}
}
if (!empty($_POST['date'])) {
    $date = explode('-', $_POST['date']);
    if (strlen($date[0]) != 4 ||  strlen($date[1]) != 2 ||  strlen($date[2]) != 2) {
        $error = $lang->wrong_date_format;
    }
}
if (!empty($_POST['privacy']) && $_POST['privacy'] == 3 && (empty($_POST['date']) || empty($_POST['hour']) || !in_array($_POST['hour'], array_keys($pt->config->hours)))) {
    $error = $lang->wrong_date_format;
}
if (empty($error)) {
	$embedding = 0;
    if ($pt->config->restrict_embedding_system == 'on' && !empty($_POST['embedding']) && $_POST['embedding'] == 'yes') {
        $embedding = 1;
    }
    $id = PT_Secure($_POST['video-id']);
    $video = $db->where('id', $id)->getOne(T_VIDEOS);
    $can_update = false;
    if (PT_IsAdmin() == false) {
    	if ($db->where('user_id', $user->id)->where('id', $id)->getValue(T_VIDEOS, 'count(*)') > 0) {
    		$can_update = true;
    	}
    } else {
    	$can_update = true;
    }
    // if (!empty($_POST['set_p_v']) && $video->sell_video == 0) {
    // 	$can_update = false;
    // }
    if ($can_update == true && !empty($video)) {
    	$video = PT_GetVideoByID($video, 0, 0, 0);
    	$thumbnail = $video->org_thumbnail;
    	if (!empty($_FILES['thumbnail']['tmp_name'])) {
    		if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {
	            $file_info   = array(
		            'file' => $_FILES['thumbnail']['tmp_name'],
		            'size' => $_FILES['thumbnail']['size'],
		            'name' => $_FILES['thumbnail']['name'],
		            'type' => $_FILES['thumbnail']['type'],
		            'allowed' => 'jpg,png,jpeg,gif'
		        );
	        }
	        else{
	        	$file_info   = array(
		            'file' => $_FILES['thumbnail']['tmp_name'],
		            'size' => $_FILES['thumbnail']['size'],
		            'name' => $_FILES['thumbnail']['name'],
		            'type' => $_FILES['thumbnail']['type'],
		            'allowed' => 'jpg,png,jpeg,gif',
		            'crop' => array(
		                'width' => 1920,
		                'height' => 1080
		            )
		        );
	        }

	        
	        $file_upload = PT_ShareFile($file_info);
	        if (!empty($file_upload['filename'])) {
	            $thumbnail = PT_Secure($file_upload['filename']);
	        }
	    }

	    // ******************************
	    if (PT_IsAdmin() && !empty($_POST['is_movie']) && $_POST['is_movie'] == 1) {

	        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
	        $i          = 0;
	        preg_match_all($link_regex, PT_Secure($_POST['movie_description']), $matches);
	        foreach ($matches[0] as $match) {
	            $match_url            = strip_tags($match);
	            $syntax               = '[a]' . urlencode($match_url) . '[/a]';
	            $_POST['movie_description'] = str_replace($match, $syntax, $_POST['movie_description']);
	        }
	        $video_title = PT_Secure(addToHashTags($_POST['movie_title'],1));
            $video_description = PT_Secure(addToHashTags($_POST['movie_description'],1));
	        $data_update = array(
	            'title' =>  $video_title,
	            'category_id' => PT_Secure($_POST['category']),
	            'stars' => PT_Secure($_POST['stars']),
	            'producer' => PT_Secure($_POST['producer']),
	            'country' => PT_Secure($_POST['country']),
	            'movie_release' => PT_Secure($_POST['release']),
	            'quality' => PT_Secure($_POST['quality']),
	            'description' => $video_description,
	            'rating' => PT_Secure($_POST['rating']),
	            'thumbnail' => $thumbnail,
                'embedding' => $embedding
	        );

		    if (isset($_POST['featured_movie']) && (PT_IsAdmin())) {
		    	$data_update['featured_movie'] = PT_Secure($_POST['featured_movie']);
		    }
		    
	        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) &&  is_numeric($_POST['buy_price']) && $_POST['buy_price'] > -1) {
	            $data_update['sell_video'] = PT_Secure($_POST['buy_price']);
	        }
	        if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) && !empty($_POST['movie_rent_price']) && is_numeric($_POST['movie_rent_price']) && $_POST['movie_rent_price'] > 0) {
	            $data_insert['rent_price'] = PT_Secure($_POST['movie_rent_price']);
	        }
	    }
	    else{
		    $category_id = 0;
		    if (!empty($_POST['category_id'])) {
		        if (in_array($_POST['category_id'], array_keys(get_object_vars($pt->categories)))) {
		            $category_id = PT_Secure($_POST['category_id']);
		        }
		    }
		    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
		    $i          = 0;
		    preg_match_all($link_regex, PT_Secure($_POST['description'],1), $matches);
		    foreach ($matches[0] as $match) {
		        $match_url           = strip_tags($match);
		        $syntax              = '[a]' . urlencode($match_url) . '[/a]';
		        $_POST['description'] = str_replace($match, $syntax, $_POST['description']);
		    }
		    $featured = $video->featured;
		    if (isset($_POST['featured']) && (PT_IsAdmin() || $pt->user->is_pro)) {
		    	$featured = PT_Secure($_POST['featured']);
		    }
		    $video_privacy = 0;
		    if (!empty($_POST['privacy'])) {
		        if (in_array($_POST['privacy'], array(0, 1, 2,3,4))) {
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

		    if (!empty($_POST['sub_category_id'])) {
		        $is_found = $db->where('type',PT_Secure($_POST['category_id']))->where('lang_key',PT_Secure($_POST['sub_category_id']))->getValue(T_LANGS,'COUNT(*)');
		        if ($is_found > 0) {
		            $sub_category = PT_Secure($_POST['sub_category_id']);
		        }
		    }
		    $continents_list = array();
		    if (!empty($_POST['continents-list'])) {
		        foreach ($_POST['continents-list'] as $key => $value) {
		            if (in_array($value, $pt->continents)) {
		                $continents_list[] = $value;
		            }
		        }
		    }
		    $publication_date = 0;
	        if (!empty($_POST['date']) && !empty($_POST['hour']) && $_POST['privacy'] == 3) {
	            $publication_date = strtotime($_POST['date']." ".$pt->config->hours[$_POST['hour']]);
	            $video_privacy = 1;
	        }
	        $video_title = PT_Secure(addToHashTags($_POST['title'],1));
            $video_description = PT_Secure(addToHashTags($_POST['description'],1));
		    $data_update = array(
		        'title' => $video_title,
		        'description' => $video_description,
		        'tags' => PT_Secure($_POST['tags'],1),
		        'category_id' => $category_id,
		        'featured' => $featured,
		        'thumbnail' => $thumbnail,
		        'privacy' => $video_privacy,
		        'age_restriction' => $age_restriction,
		        'sub_category' => $sub_category,
		        'geo_blocking' => !empty($continents_list) ? json_encode($continents_list) : '',
                'embedding' => $embedding,
                'publication_date' => $publication_date
		    );
		    $data_update['rent_price'] = 0;
		    if ($video->is_short == 1) {
		    	$_POST['set_p_v'] = 0;
		    	$_POST['rent_price'] = 0;
		    	$_POST['monetization'] = 0;
		    }
		    if (is_numeric($_POST['set_p_v']) && $_POST['set_p_v'] > -1) {
	            $data_update['sell_video'] = PT_Secure($_POST['set_p_v']);
	        }
	        if (!empty($_POST['rent_price']) && is_numeric($_POST['rent_price']) && $_POST['rent_price'] > -1) {
	            $data_update['rent_price'] = PT_Secure($_POST['rent_price']);
	        }
	    }
	    if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1')) &&  $pt->user->video_mon == '1' && in_array($_POST['monetization'], array('0','1'))) {
            $data_update['monetization'] = PT_Secure($_POST['monetization']);
        }


	    $update  = $db->where('id', $id)->update(T_VIDEOS, $data_update);
	    
	    if ($update) {
	        $data = array(
	            'status' => 200,
	            'message' => $success_icon . $lang->video_saved
	        );
	    }
    }
} else {
    $data = array(
        'status' => 400,
        'message' => $error_icon . $error
    );
}
?>