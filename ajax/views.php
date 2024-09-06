<?php
$data['status'] = 400;
if (!empty($_GET['type_']) && $_GET['type_'] == 'add' && !empty($_SESSION['finger'])) {
	$video = PT_GetVideoByID($_POST['video_id'], 0, 0, 2);
	if (!empty($video)) {
        if ($video->is_short == 1 && !in_array($video->video_id, $pt->v_shorts)) {
            $pt->v_shorts[] = $video->video_id;
            setcookie('v_shorts', json_encode($pt->v_shorts), time()+(60 * 60 * 24),'/');
        }
		$finger = $_SESSION['finger'];
    	$add = true;
    	if (IS_LOGGED == true) {
    		$is_viewed = $db->where('user_id',$pt->user->id)->where('video_id',$video->id)->where('time',time() - 31556926,'>=')->getValue(T_VIEWS,"count(*)");
    		if ($is_viewed > 0) {
    			$add = false;
    		}
    	}else{
    		$is_viewed = $db->where('fingerprint',$finger)->where('video_id',$video->id)->where('time',time() - 31556926,'>=')->getValue(T_VIEWS,"count(*)");
    		if ($is_viewed > 0) {
    			$add = false;
    		}else{
    			// if (!empty($_COOKIE['views']) && !empty($_SESSION['views'])) {
    			// 	$views_data__COOKIE = unserialize(html_entity_decode($_COOKIE['views']));
			    //     $views_data__SESSION = unserialize(html_entity_decode($_SESSION['views']));
    			// 	foreach ($views_data__COOKIE as $key => $cookie_value) {
    			// 		foreach ($views_data__SESSION as $key => $session_value) {
    			// 			if ($cookie_value[1] != $session_value[1]) {
    			// 				$add = false;
    			// 			}
    			// 			if ($cookie_value[0] == $video->id || $session_value[0] == $video->id) {
    			// 				$add = false;
    			// 			}
    			// 		}
	      //   		}
    			// }
    			// elseif((!empty($_COOKIE['views']) && empty($_SESSION['views'])) || (empty($_COOKIE['views']) && !empty($_SESSION['views']))){
    			// 	$add = false;
    			// }
    		}
    	}
    	if ($add == true) {
    		$insert_data[0]       = $video->id;
    		$insert_data[1]       = $finger;
    		$_data[] = $insert_data;
            $data_s       = htmlentities(serialize($_data));
            setcookie("views", $data_s, time() + 31556926, '/');
            $_SESSION['views'] = $data_s;
            $data_info = array('video_id' => $video->id,
                               'fingerprint' => $finger,
                               'time'    => time());
            if (IS_LOGGED == true) {
            	$data_info['user_id'] = $pt->user->id;
            }
            $db->insert(T_VIEWS,$data_info);
            $db->where('id', $video->id)->update(T_VIDEOS, array('views' => $db->inc(1)));
            $new_video = $db->where('id', $video->id)->getOne(T_VIDEOS);
            RegisterPoint($video->id, "watch");
            $data['count'] = $new_video->views;
            $data['status'] = 200;
    	}
	}
}
else if (!empty($_GET['type_']) && $_GET['type_'] == 'set' && !empty($_POST['finger'])) {
    if (empty($_SESSION['finger'])) {
        $finger = PT_Secure(sha1($_POST['finger']));
        //$data_s       = htmlentities(serialize($finger));
        $_SESSION['finger'] = $finger;
    }
    $data['status'] = 200;
}
