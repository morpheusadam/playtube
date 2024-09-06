<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+


  

if (empty($_GET['video_id'])) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '1',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}

else{

	$video_id = PT_Secure($_GET['video_id']);
	$video    = $db->where('video_id',$video_id)->getOne(T_VIDEOS,array('video_id','user_id'));

	if (empty($video)) {
		$response_data       = array(
	        'api_status'     => '404',
	        'api_version'    => $api_version,
	        'errors'         => array(
	            'error_id'   => '2',
	            'error_text' => 'Video does not exist'
	        )
	    );
	}


	else{

		$video_data = PT_GetVideoByID($video->video_id,0,1);

		if (!empty($video_data)) {
			$t_videos                     = T_VIDEOS;
			$video_data->is_subscribed    = 0;
			$video_data->suggested_videos = array();
			$video_data->owner            = array_intersect_key(
				ToArray($video_data->owner), 
				array_flip($user_public_data)
			);

			if (IS_LOGGED && $video_data->user_id != $user->id) {
				$db->where('subscriber_id',$user->id);
				$db->where('user_id',$video_data->user_id);
				$db->where('active',1);
				$subscribed = ($db->getValue(T_SUBSCRIPTIONS,'count(*)') > 0);

				if (($subscribed === true)) {
					$video_data->is_subscribed = 1;
				}
			}


			$video_title       = PT_Secure($video_data->title);

			$sql_query         = "
				SELECT * FROM `$t_videos` 
				WHERE MATCH (title) 
				AGAINST ('$video_title') 
				AND id <> '{$video_data->id}' 
				ORDER BY `id` DESC 
				LIMIT 20";

			$related_videos = $db->rawQuery($sql_query);

			foreach ($related_videos as $related_video) {
				$related_video         = PT_GetVideoByID($related_video, 0, 1, 0);
				$user_data             = PT_UserData($related_video->user_id);
				$related_video->owner  = array_intersect_key(
					ToArray($user_data), 
					array_flip($user_public_data)
				);

				$video_data->suggested_videos[] = $related_video;
			}

			$video_data->video_ad = array();
			if (true) {

				$rand      = (rand(0,1)) ? rand(0,1) :(rand(0,1) ? : rand(0,1));

			    if ($rand == 0) {
			    	$get_random_ad = $db->where('active', 1)->orderBy('RAND()')->getOne(T_VIDEO_ADS);
			        if (!empty($get_random_ad)) {

			            if (!empty($get_random_ad->ad_media)) {
			                $ad_media = $get_random_ad->ad_media;
			                $ad_link = PT_Link('redirect/' . $get_random_ad->id . '?type=video');
			                $is_video_ad = ",'ads'";
			            }

			            if (!empty($get_random_ad->vast_xml_link)) {
			                $vast_url = $get_random_ad->vast_xml_link;
			                $vast_type = $get_random_ad->vast_type;
			                $is_vast_ad = ",'vast'";
			            }

			            if ($get_random_ad->skip_seconds > 0) {
			                $ad_skip = 'true';
			                $ad_skip_num = $get_random_ad->skip_seconds;
			            }

			            if (!empty($get_random_ad->ad_image)) {
			                $ad_image = $pt->ad_image = $get_random_ad->ad_image;
			                $ad_link = PT_Link('redirect/' . $get_random_ad->id . '?type=image');
			            }

			            $update_clicks = $db->where('id', $get_random_ad->id)->update(T_VIDEO_ADS, array(
			                'views' => $db->inc(1)
			            ));
			            $cookie_name = 'last_ads_seen';
			            $cookie_value = time();
			            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
			            $video_data->video_ad = $get_random_ad;
			        }
			    }
			    else{
			    	$user_ads      = pt_get_user_ads();
			        if (!empty($user_ads)) {  
			            $get_random_ad =  $user_ads;
			            $random_ad_id  = $get_random_ad->id;
			            $user_ads->url       = urldecode($get_random_ad->url);
			            $user_ads->domain       = pt_url_domain(urldecode($user_ads->url));
			            $user_ads->media      = PT_GetMedia($get_random_ad->media);
			            $user_ads->time_text      = PT_Time_Elapsed_String($user_ads->posted);;
			            
			            if ($user_ads->type == 1) {
			                $user_ad_trans   = "rad-transaction";
			                $_SESSION['ua_'] = $random_ad_id;
			                $_SESSION['vo_'] = $video_data->user_id;
			            }

			            else{
			                pt_register_ad_views($random_ad_id,$video_data->user_id); 
			                $db->insert(T_ADS_TRANS,array('type' => 'view', 'ad_id' => $random_ad_id, 'video_owner' => $video_data->user_id, 'time' => time()));
			            }
			            if ($user_ads->category == 'image') {
			            	$user_ads->redirect_link = PT_Link('redirect/' . $get_random_ad->id . '?type=image');
			            }
			            else{
			            	$user_ads->redirect_link = PT_Link('redirect/' . $get_random_ad->id . '?type=video');
			            }

			            if ($user_ads->type == 1) {
			            	$user_ads->ad_type = 'click';
			            }
			            else{
			            	$user_ads->ad_type = 'views';
			            	$update_clicks = $db->where('id', $get_random_ad->id)->update(T_VIDEO_ADS, array(
				                'views' => $db->inc(1)
				            ));
			            }
			            
			            
			            $video_data->video_ad = $user_ads;
			        }
			    }
			}
			
			$response_data     = array(
		        'api_status'   => '200',
		        'api_version'  => $api_version,
		        'data'         => $video_data
		    );

		    if (!empty($_POST['android_id'])) {
		    	$finger = PT_Secure($_POST['android_id']);
		    	$is_viewed = $db->where('fingerprint',$finger)->where('video_id',$video_data->id)->where('time',time() - 31556926,'>=')->getValue(T_VIEWS,"count(*)");
	    		if ($is_viewed == 0) {
	    			$data_info = array('video_id' => $video_data->id,
		                               'fingerprint' => $finger,
		                               'time'    => time());
		            if (IS_LOGGED == true) {
		            	$data_info['user_id'] = $user->id;
		            }
		            $db->insert(T_VIEWS,$data_info);
		            $update = array('views' => ($video_data->views += 1));
				    $db->where('video_id',$video_id)->update($t_videos,$update);
	    		}
		    }

		    if (IS_LOGGED == true) {
		    	if ($pt->config->history_system == 'on' && $user->pause_history == 0) {
		    		$history =$db->where('video_id', $video_data->id)->where('user_id', $user->id)->getOne(T_HISTORY);
                    if (!empty($history)) {
                        $db->where('id', $history->id)->delete(T_HISTORY);
                    }

		            $insert_to_history = array(
		                'user_id' => $user->id,
		                'video_id' => $video_data->id,
		                'time' => time()
		            );
		            $insert_to_history_query = $db->insert(T_HISTORY, $insert_to_history);
			    }
		    }
		}

		else{

			$response_data       = array(
		        'api_status'     => '500',
		        'api_version'    => $api_version,
		        'errors'         => array(
		            'error_id'   => '3',
		            'error_text' => 'Error: an unknown error occurred. Please try again later'
		        )
		    );
		}
	}
}