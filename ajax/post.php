<?php
if (IS_LOGGED == false || $pt->config->post_system != 'on' || !$pt->config->can_use_post) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if ($first == 'create') {
	$data['status'] = 400;
	if (empty($_POST['text']) || empty($_FILES["image"])) {
        $data['message'] = $lang->please_check_details;
    }
    else if (!empty($_FILES["image"]["error"]) || !file_exists($_FILES["image"]["tmp_name"])) {
        $data['message'] = $lang->image_not_valid; 
    } 
    else{

    	if (file_exists($_FILES["image"]["tmp_name"])) {
	        $image = getimagesize($_FILES["image"]["tmp_name"]);
	        if (!in_array($image[2], array(
	            IMAGETYPE_GIF,
	            IMAGETYPE_JPEG,
	            IMAGETYPE_PNG,
	            IMAGETYPE_BMP
	        ))){
	            $data['message'] = $lang->image_not_valid; 
	        }
	    }
	    if (empty($data['message'])) {
	    	$file_info   = array(
	            'file' => $_FILES['image']['tmp_name'],
	            'size' => $_FILES['image']['size'],
	            'name' => $_FILES['image']['name'],
	            'type' => $_FILES['image']['type'],
	            'crop' => array(
	                'width' => 600,
	                'height' => 400
	            )
	        );

	        $file_upload     = PT_ShareFile($file_info);

	        if (!empty($file_upload['filename'])) {
	            $post_image  = PT_Secure($file_upload['filename']);
	            $insert_data = array(
	                'image' => $post_image,
	                'text' => PT_Secure($_POST['text'],1),
	                'time' => time(),
	                'user_id' => $pt->user->id
	            );

	            $insert     = $db->insert(T_ACTIVITES,$insert_data);
	            $data['status'] = 200 ;
		        $data['link']    = PT_Link('@'.$pt->user->username.'?page=activities');
	        }
	    }
    }
}

if ($first == 'edit') {
	$data['status'] = 400;
	if (empty($_POST['text']) || empty($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] < 1) {
        $data['message'] = $lang->please_check_details;
    }
    else{
    	if (!empty($_FILES["image"])) {
    		if (file_exists($_FILES["image"]["tmp_name"])) {
		        $image = getimagesize($_FILES["image"]["tmp_name"]);
		        if (!in_array($image[2], array(
		            IMAGETYPE_GIF,
		            IMAGETYPE_JPEG,
		            IMAGETYPE_PNG,
		            IMAGETYPE_BMP
		        ))){
		            $data['message'] = $lang->image_not_valid; 
		        }
		    }
    	}

	    if (empty($data['message'])) {
	    	$id    = PT_Secure($_POST['id']);
	    	$post = $db->where('id',$id)->getOne(T_ACTIVITES);
			if (!empty($post) && ($post->user_id == $pt->user->id || PT_IsAdmin())) {
				$update_data = array(
	                'text' => PT_Secure($_POST['text'],1)
	            );


		    	if (!empty($_FILES['image'])) {
		    		$file_info   = array(
			            'file' => $_FILES['image']['tmp_name'],
			            'size' => $_FILES['image']['size'],
			            'name' => $_FILES['image']['name'],
			            'type' => $_FILES['image']['type'],
			            'crop' => array(
			                'width' => 600,
			                'height' => 400
			            )
			        );

			        $file_upload     = PT_ShareFile($file_info);
			        $update_data['image'] = $file_upload['filename'];

			        if (file_exists($post->image)) {
			            unlink($post->image);
			        }
			        
			        else if ($s3 === true) {
			            PT_DeleteFromToS3($post->image);
			        }
		    	}

	            $insert     = $db->where('id',$id)->update(T_ACTIVITES,$update_data);
	            $data['status'] = 200 ;
		        $data['link']    = PT_Link('@'.$pt->user->username.'?page=activities');
			}
	    }
    }
}