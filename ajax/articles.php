<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if ($first == 'new-article') {
    $error = false;
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['text']) || empty($_POST['tags']) || empty($_FILES["image"])) {
        $error = $lang->please_check_details;
    }
    else{

        if (strlen($_POST['title']) < 5) {
            $error = $lang->short_title; 
        }

        else if(strlen($_POST['description']) < 15){
            $error = $lang->short_description; 
        }

        else if (!empty($_FILES["image"]["error"]) || !file_exists($_FILES["image"]["tmp_name"])) {
            $error = $lang->image_not_valid; 
        } 

        else if (file_exists($_FILES["image"]["tmp_name"])) {
            $image = getimagesize($_FILES["image"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP,
                IMAGETYPE_WEBP
            ))){
                $error = $lang->image_not_valid; 
            }
        }

        else if (empty($_POST['category']) || !in_array($_POST['category'],array_keys(get_object_vars($pt->categories)))) {
            $error = $lang->category_not_valid;
        }
    }

    if (empty($error)) {

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
        $insert          = false;

        if (!empty($file_upload['filename'])) {
            $post_image  = PT_Secure($file_upload['filename']);
            $insert_data = array(
                'title' => PT_Secure(PT_ShortText($_POST['title'],150),1),
                'description' => PT_Secure(PT_ShortText($_POST['description'],200),1),
                'category' => PT_Secure($_POST['category']),
                'image' => $post_image,
                'text' => htmlspecialchars($_POST['text']),
                'tags' => PT_Secure(PT_ShortText($_POST['tags']),250),
                'time' => time(),
                'user_id' => $pt->user->id,
                'active' => 0,
                'views' => 0,
                'shared' => 0,
            );

            $insert     = $db->insert(T_POSTS,$insert_data);
            $data['status'] = 200 ;
	        $data['link']    = PT_Link('articles/read/' . PT_URLSlug($insert_data['title'],$insert));
        }
    }

    else{
        $data['status'] = 400;
        $data['message'] = $error;
    }
}

if ($first == 'delete-article') {
	$data['status'] = 400;
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$article = $db->where('id',PT_Secure($_POST['id']))->getOne(T_POSTS);
		if (!empty($article) && (PT_IsAdmin() || $article->user_id == $pt->user->id)) {
            if (file_exists($article->image)) {
                unlink($article->image);
            }
            
            else if ($pt->remoteStorage === true) {
                PT_DeleteFromToS3($article->image);
            }
        
            $delete  = $db->where('id',PT_Secure($_POST['id']))->delete(T_POSTS);
            $delete  = $db->where('post_id',PT_Secure($_POST['id']))->delete(T_DIS_LIKES);

            //Delete related data
            $post_comments = $db->where('post_id',PT_Secure($_POST['id']))->get(T_COMMENTS);

            foreach ($post_comments as $comment_data) {
                $delete    = $db->where('comment_id',$comment_data->id)->delete(T_COMMENTS_LIKES);
                $replies   = $db->where('comment_id',$comment_data->id)->get(T_COMM_REPLIES);
                $db->where('comment_id',$comment_data->id)->delete(T_COMM_REPLIES);
                
                foreach ($replies as $comment_reply) {
                    $db->where('reply_id',$comment_reply->id)->delete(T_COMMENTS_LIKES);
                }
            }

            if (!empty($post_comments)) {
                $delete    = $db->where('post_id',PT_Secure($_POST['id']))->delete(T_COMMENTS);   
            }
            
            if ($delete) {
                $data = array('status' => 200);
            }
		}
	}
}

if ($first == 'update-article') {
    $error = false;
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['text']) || empty($_POST['tags']) || empty($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] < 1) {
        $error = $lang->please_check_details;
    }
    else{
        $article = $db->where('id',PT_Secure($_POST['id']))->getOne(T_POSTS);
        if (empty($article) || $article->user_id != $pt->user->id) {
            $error = $lang->please_check_details;
        }

        if (strlen($_POST['title']) < 5) {
            $error = $lang->short_title; 
        }

        else if(strlen($_POST['description']) < 15){
            $error = $lang->short_description; 
        }

        else if(empty($_POST['text'])){
            $error = 403; 
        }

        else if (empty($_POST['category']) || !in_array($_POST['category'],array_keys(get_object_vars($pt->categories)))) {
            $error = $lang->category_not_valid;
        }
    }

    if (empty($error)) {
        $insert      = false;
        $active      = '0';
        $id          = PT_Secure($_POST['id']);

        $update_data = array(
            'title' => PT_Secure(PT_ShortText($_POST['title'],150),1),
            'description' => PT_Secure(PT_ShortText($_POST['description'],200),1),
            'category' => PT_Secure($_POST['category']),
            'text' => htmlspecialchars($_POST['text']),
            'tags' => PT_Secure(PT_ShortText($_POST['tags']),250),
            'time' => time(),
            'active' => $active,
            'shared' => 0,
        );

        if (!empty($_FILES["image"])) {
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
                $update_data['image'] = PT_Secure($file_upload['filename']);  
            }
            else{
                $error = true;
            }
        }

        $insert         = $db->where('id',$id)->update(T_POSTS,$update_data);
        $data['status'] = 200;
        $data['url']    = PT_Link('articles/read/' . PT_URLSlug($update_data['title'],$id));
    }

    else{
        $data['status'] = 400;
        $data['message'] = $error;
    }
}