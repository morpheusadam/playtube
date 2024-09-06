<?php
if (!IS_LOGGED) {
	$response_data    = array(
	    'api_status'  => '400',
	    'api_version' => $api_version,
	    'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
	);
}
else{
	if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
		$id                  = PT_Secure($_POST['post_id']);
	    $is_this_valid_post  = $db->where('id', $id)->getValue(T_POSTS, 'count(*)');

	    if ($is_this_valid_post > 0) {
	    	if (!empty($_POST['type']) && in_array($_POST['type'], array('like','dislike'))) {
		    	$response_data     = array(
		            'api_status'   => '200',
		            'api_version'  => $api_version
		        );
		        if ($_POST['type'] == 'like') {
		            $db->where('user_id', $user->id);
		            $db->where('post_id', $id);
		            $db->where('type', 1);
		            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
		            if ($check_for_like > 0) {
		                $db->where('user_id', $user->id);
		                $db->where('post_id', $id);
		                $db->where('type', 1);
		                $delete = $db->delete(T_DIS_LIKES);
		                $response_data['code'] = 0;
		            } 

		            else {
		            	$db->where('user_id', $user->id);
			            $db->where('post_id', $id);
			            $db->where('type', 2);
			            $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
		            	if ($check_for_dislike) {
		            		$db->where('user_id', $user->id);
		                    $db->where('post_id', $id);
		                    $db->where('type', 2);
		                    $delete = $db->delete(T_DIS_LIKES);
		            	}

		                $insert_data = array(
		                    'user_id' => $user->id,
		                    'post_id' => $id,
		                    'time' => time(),
		                    'type' => 1
		                );
		                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
		                if ($insert) {
		                	$response_data['code'] = 1;
		                }
		            }
		        } 
		        else if ($_POST['type'] == 'dislike') {
		        	$db->where('user_id', $user->id);
		            $db->where('post_id', $id);
		            $db->where('type', 2);
		            $check_for_like = $db->getValue(T_DIS_LIKES, 'count(*)');
		            if ($check_for_like > 0) {
		                $db->where('user_id', $user->id);
		                $db->where('post_id', $id);
		                $db->where('type', 2);
		                $delete = $db->delete(T_DIS_LIKES);
		                $response_data['code'] = 0;
		            } 

		            else {
		            	$db->where('user_id', $user->id);
			            $db->where('post_id', $id);
			            $db->where('type', 1);
			            $check_for_dislike = $db->getValue(T_DIS_LIKES, 'count(*)');
		            	if ($check_for_dislike) {
		            		$db->where('user_id', $user->id);
		                    $db->where('post_id', $id);
		                    $db->where('type', 1);
		                    $delete = $db->delete(T_DIS_LIKES);
		            	}
		                $insert_data = array(
		                    'user_id' => $user->id,
		                    'post_id' => $id,
		                    'time' => time(),
		                    'type' => 2
		                );
		                $insert      = $db->insert(T_DIS_LIKES, $insert_data);
		                if ($insert) {
		                    $response_data['code'] = 1;
		                }
		            }
		        }

		        $response_data['likes']    = $db->where('post_id', $id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
		        $response_data ['dislikes'] = $db->where('post_id', $id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
		    }
		    else{
		    	$response_data    = array(
				    'api_status'  => '400',
				    'api_version' => $api_version,
				    'errors' => array(
			            'error_id' => '4',
			            'error_text' => 'type can not be empty'
			        )
				);
		    }
	    }
	    else{
	    	$response_data    = array(
			    'api_status'  => '400',
			    'api_version' => $api_version,
			    'errors' => array(
		            'error_id' => '5',
		            'error_text' => 'post not found'
		        )
			);
	    }
	}
	else{
		$response_data    = array(
		    'api_status'  => '400',
		    'api_version' => $api_version,
		    'errors' => array(
	            'error_id' => '4',
	            'error_text' => 'post_id can not be empty'
	        )
		);
	}
}