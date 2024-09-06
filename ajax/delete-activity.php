<?php 

if (IS_LOGGED == false || $pt->config->post_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

$data['status'] = 400;
if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
    $article = $db->where('id',PT_Secure($_POST['id']))->getOne(T_ACTIVITES);
    if (!empty($article) && (PT_IsAdmin() || $article->user_id == $pt->user->id)) {
        if (file_exists($article->image)) {
            unlink($article->image);
        }
        
        else if ($pt->remoteStorage === true) {
            PT_DeleteFromToS3($article->image);
        }
    
        $delete  = $db->where('id',PT_Secure($_POST['id']))->delete(T_ACTIVITES);
        $delete  = $db->where('activity_id',PT_Secure($_POST['id']))->delete(T_DIS_LIKES);

        //Delete related data
        $post_comments = $db->where('activity_id',PT_Secure($_POST['id']))->get(T_COMMENTS);

        foreach ($post_comments as $comment_data) {
            $delete    = $db->where('comment_id',$comment_data->id)->delete(T_COMMENTS_LIKES);
            $replies   = $db->where('comment_id',$comment_data->id)->get(T_COMM_REPLIES);
            $db->where('comment_id',$comment_data->id)->delete(T_COMM_REPLIES);
            
            foreach ($replies as $comment_reply) {
                $db->where('reply_id',$comment_reply->id)->delete(T_COMMENTS_LIKES);
            }
        }

        if (!empty($post_comments)) {
            $delete    = $db->where('activity_id',PT_Secure($_POST['id']))->delete(T_COMMENTS);   
        }
        
        if ($delete) {
            $data = array('status' => 200);
        }
    }
}