<?php 

if (!empty($first)) {
	
	if ($first == 'share' && !empty($_POST['post_id']) && is_numeric($_POST['post_id'])) {
		$post_id     = $_POST['post_id'];
		$get_post    = $db->where('id', $post_id)->getOne(T_POSTS);
		$data        = array('status' => 400);
		if (!empty($get_post)) {
			$shared  = ($get_post->shared += 1);
			$up_data = array('shared' => $shared);
			$db->where('id', $post_id)->update(T_POSTS,$up_data);
			$data['status'] = 200;
			$data['shared'] = $shared;
		}
	}
}

if ($first == 'hide-announcement') {
    $request        = (!empty($_POST['id']) && is_numeric($_POST['id']));
    $data['status'] = 400;
    if ($request === true) {
        $announcement_id = PT_Secure($_POST['id']);
		if (IS_LOGGED === true) {
			$insert_data     = array(
				'announcement_id' => $announcement_id,
				'user_id'         => $pt->user->id
			);
			$db->insert(T_ANNOUNCEMENT_VIEWS,$insert_data);
		}
        
        setcookie("ANNOUNCEMENT", 'yes', time() + (24 * 60 * 60), "/");
        $data['status'] = 200;
    }
}

if ($first == 'yes_18') {
	setcookie("pop_up_18", 'yes', time() + (10 * 365 * 24 * 60 * 60), "/");
	$data['status'] = 200;
}
if ($first == 'no_18') {
	setcookie("pop_up_18", 'no', time() + ($pt->config->time_18 * 60), "/");
	$data['url']    = $pt->config->site_url.'/age_block';
	$data['status'] = 200;
}
if ($first == 'copy_report' && $pt->config->report_copyright == 'on') {

	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$video_id    = PT_Secure($_POST['id']);
		$video_data  = $db->where('id',$video_id)->getOne(T_VIDEOS);
		$user_id     = $user->id;
		if (!empty($video_data)) {

			if(!empty($_POST['text'])){
				$text    = PT_Secure($_POST['text']);
				$re_data = array(
					'user_id' => $user_id,
					'video_id' => $video_id,
					'time' => time(),
					'text' => $text,
				);

				if ($db->insert(T_COPYRIGHT,$re_data)) {
					$notif_data = array(
		                'recipient_id' => 0,
		                'type' => 'copy',
		                'admin' => 1,
		                'time' => time()
		            );
		            
		            pt_notify($notif_data);
					$data['status'] = 200;	
				}
			}
		}
	}
}
if ($first == 'monetization' && $pt->config->user_mon_approve == 'on') {

	$error          = false;
	$user_id        = $pt->user->id;
	$request_exists = ($db->where('user_id',$user_id)->getValue(T_MON_REQUESTS,'count(*)'));
	$post           = (empty($_POST['name']) || empty($_POST['message']) || empty($_FILES['personal_photo']) || empty($_FILES['id_photo']));

	if (!empty($request_exists)) {
		$error = $lang->submit_monetization_request_error;
	}

	elseif ($post == true) {
		$error = $lang->please_check_details;
	}

	else{

		if (!empty($_FILES["personal_photo"]["error"]) || ($_FILES["personal_photo"]["size"] > $pt->config->max_upload && $pt->config->max_upload > 0)) {
	        $max   = pt_size_format($pt->config->max_upload);
        	$error = ($lang->file_is_too_big .": $max");
	    }
	    if (!empty($_FILES["id_photo"]["error"]) || ($_FILES["id_photo"]["size"] > $pt->config->max_upload && $pt->config->max_upload > 0)) {
	        $max   = pt_size_format($pt->config->max_upload);
        	$error = ($lang->file_is_too_big .": $max");
	    } 

	    
	    else if (!file_exists($_FILES['personal_photo']['tmp_name'])) {
	        $error = $lang->ivalid_image_file;
	    } 

	    else if (file_exists($_FILES["personal_photo"]["tmp_name"])) {
	        $image = getimagesize($_FILES["personal_photo"]["tmp_name"]);
	        if (!in_array($image[2], array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP,IMAGETYPE_WEBP))) {
	            $error = $lang->ivalid_id_file;
	        }
	    }

	    else if (!file_exists($_FILES['id_photo']['tmp_name'])) {
	        $error = $lang->ivalid_image_file;
	    } 

	    else if (file_exists($_FILES["id_photo"]["tmp_name"])) {
	        $image = getimagesize($_FILES["id_photo"]["tmp_name"]);
	        if (!in_array($image[2], array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP,IMAGETYPE_WEBP))) {
	            $error = $lang->ivalid_id_file;
	        }
	    }  
	}

	if (empty($error)) {

        $file_info = array(
            'file' => $_FILES['personal_photo']['tmp_name'],
            'size' => $_FILES['personal_photo']['size'],
            'name' => $_FILES['personal_photo']['name'],
            'type' => $_FILES['personal_photo']['type']
        );

        $upload_personal          = PT_ShareFile($file_info);

        $file_info = array(
            'file' => $_FILES['id_photo']['tmp_name'],
            'size' => $_FILES['id_photo']['size'],
            'name' => $_FILES['id_photo']['name'],
            'type' => $_FILES['id_photo']['type']
        );

        $upload_id          = PT_ShareFile($file_info);
    	$re_data         = array(
            'user_id'    => $user_id,
            'name'       => PT_Secure($_POST['name']),
            'message'    => PT_Secure($_POST['message']),
            'time'       => time(),
            'personal_photo' => $upload_personal['filename'],
            'id_photo' => $upload_id['filename']
        );

    	$insert = $db->insert(T_MON_REQUESTS,$re_data);

    	if ($insert) {
    		$notif_data = array(
                'recipient_id' => 0,
                'type' => 'mon',
                'admin' => 1,
                'time' => time()
            );
            
            pt_notify($notif_data);
    		$data['status']  = 200;
        	$data['message'] = $lang->verif_request_sent;
    	}

    	else{
        	$data['status']  = 500;
        	$data['message'] = $lang->unknown_error;
        }
	}
	else{
		$data['status']  = 400;
		$data['message'] = $error;
	}
}