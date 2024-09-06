<?php

if (IS_LOGGED === true) {
	if ($pt->config->user_ads != 'on') {
		header('Location: ' . PT_Link('404'));
		exit;
	}

	if ($first == 'create') {
		$data      = array('status' => 400);
		$error     = false;
		$type      = none;
		$request   = array();
		$request[] = (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['title']));
		$request[] = (empty($_POST['desc']) || empty($_FILES['media']));
		$request[] = (empty($_POST['audience-list']) || !is_array($_POST['audience-list']));
		$request[] = (empty($_POST['placement']) || empty($_POST['cost']));
		$request   = (in_array(true, $request,true) === true);

		if ($request) {
			$error = $lang->please_check_details;
		}
		else{
			if (mb_strlen($_POST['name']) < 5 || mb_strlen($_POST['name']) > 100) {
				$error = $lang->invalid_name;
			} 

			else if(!pt_is_url($_POST['url'])){
				$error = $lang->invalid_url;
			} 

			else if(mb_strlen($_POST['title']) < 10 || mb_strlen($_POST['title']) > 150){
				$error = $lang->invalid_ad_title;
			} 

			else if(!file_exists($_FILES['media']['tmp_name']) || !in_array($_FILES['media']['type'], $pt->ads_media_types)){
				$error = $lang->invalid_videoad_media;
			}

			else if(file_exists($_FILES['media']['tmp_name']) && $_FILES['media']['size'] > $pt->config->max_upload && $pt->config->max_upload > 0){
				$max   = pt_size_format($pt->config->max_upload);
        		$error = ($lang->file_is_too_big .": $max");
			}    

			elseif (!in_array($_POST['placement'], array(1,2))) {
				$error = $lang->error_msg;
			} 

			else if(!in_array($_POST['cost'], array(1,2))){
				$error = $lang->error_msg;
			} 

			else if($_POST['placement'] == 2){
				$media_file = getimagesize($_FILES["media"]["tmp_name"]);
				$img_types  = array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP);
                if (!in_array($media_file[2],$img_types)) {
                    $error  = $lang->invalid_pagead_media;
                }
			}
		}

		if (empty($error)) {
			$file_type   = explode("/", $_FILES['media']['type']);

			$insert_data = array(
				"name" => PT_Secure($_POST['name'],1),
				"audience" => implode(',', $_POST['audience-list']),
				"category" => $file_type[0],
				"media" => "",
				"url" => urlencode($_POST['url']),
				"user_id" => $user->id,
				"placement" => intval($_POST['placement']),
				"posted" => time(),
				"headline" => PT_Secure($_POST['title'],1),
				"description" =>  PT_Secure(PT_ShortText($_POST['desc'],1000),1),
				"location" => "",
				"type" => (($_POST['placement'] == 2) ? 1 : intval($_POST['cost']))
			);

			$file_info = array(
		        'file' => $_FILES['media']['tmp_name'],
		        'size' => $_FILES['media']['size'],
		        'name' => $_FILES['media']['name'],
		        'type' => $_FILES['media']['type']
		    );

		    if (!empty($_POST['day_limit']) && is_numeric($_POST['day_limit']) && $_POST['day_limit'] > 0) {
		    	$insert_data['day_limit'] = PT_Secure($_POST['day_limit']);
		    	$insert_data['day'] = date("Y-m-d");
		    }

		    if (!empty($_POST['lifetime_limit']) && is_numeric($_POST['lifetime_limit']) && $_POST['lifetime_limit'] > 0) {
		    	$insert_data['lifetime_limit'] = PT_Secure($_POST['lifetime_limit']);
		    }

		    $file_upload = PT_ShareFile($file_info);
		    if (!empty($file_upload)) {
		    	$insert_data['media'] = $file_upload['filename'];
		    	$insert  = $db->insert(T_USR_ADS,$insert_data);
		    	if (!empty($insert)) {
		    		$data['status'] = 200;
		    		$data['message'] = $lang->ad_published;
		    	}
		    	else{
		    	  $data['message'] = "Error 500 internal server error!";
		    	}
		    	
		    }
		}
		else{
			$data['message'] = $error;
		}
	}

	if ($first == 'edit') {
		$data      = array('status' => 400);
		$error     = false;
		$type      = none;
		$media     = false;
		$cost      = false;
		$request   = array();
		$request[] = (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['title']));
		$request[] = (empty($_POST['desc']) || empty($_POST['id']) || !is_numeric($_POST['id']));
		$request[] = (empty($_POST['audience-list']) || !is_array($_POST['audience-list']));
		$request   = (in_array(true, $request,true) === true);

		if ($request) {
			$error = $lang->please_check_details;
		}
		else{

			$ad_id     = PT_Secure($_POST['id']);
			$ad_data   = $db->where('id',$ad_id)->where('user_id',$user->id)->getOne(T_USR_ADS);
			if (empty($ad_data)) {
				$data['status'] = 404;
	    		$error          = true;
			} 

			else if (mb_strlen($_POST['name']) < 5 || mb_strlen($_POST['name']) > 100) {
				$error = $lang->invalid_name;
			} 

			else if(!pt_is_url($_POST['url'])){
				$error = $lang->invalid_url;
			} 

			else if(mb_strlen($_POST['title']) < 10 || mb_strlen($_POST['title']) > 150){
				$error = $lang->invalid_ad_title;
			} 
			
		}

		
		
		if (empty($error)) {
			$update_data = array(
				"name" => PT_Secure($_POST['name'],1),
				"audience" => implode(',', $_POST['audience-list']),
				"url" => urlencode($_POST['url']),
				"user_id" => $user->id,
				"headline" => PT_Secure($_POST['title'],1),
				"description" =>  PT_Secure(PT_ShortText($_POST['desc'],1000),1),
				"location" => ""
			);

			$update_data['day_limit'] = 0;

			if (!empty($_POST['day_limit']) && is_numeric($_POST['day_limit']) && $_POST['day_limit'] > 0) {
		    	$update_data['day_limit'] = PT_Secure($_POST['day_limit']);
		    	if (empty($ad_data->day)) {
		    		$update_data['day'] = date("Y-m-d");
		    	}
		    }
		    else{
		    	$update_data['day_limit'] = 0;
		    	$update_data['day'] = '';
		    	$update_data['day_spend'] = 0;
		    }

		    if (!empty($_POST['lifetime_limit']) && is_numeric($_POST['lifetime_limit']) && $_POST['lifetime_limit'] > 0) {
		    	$update_data['lifetime_limit'] = PT_Secure($_POST['lifetime_limit']);
		    }


			$ad_id   = PT_Secure($_POST['id']);
		    $update  = $db->where('id',$ad_id)->update(T_USR_ADS,$update_data);
	    	if (!empty($update)) {
	    		$data['status']  = 200;
	    		$data['message'] = $error = $lang->ad_saved;;
	    	}
	    	else{
	    	  $data['message'] = "Error 500 internal server error!";
	    	}
		}
		else{
			$data['message'] = $error;
		}
	}

	if ($first == 'delete') {
		$request = (!empty($_POST['id']) && is_numeric($_POST['id']));
		if ($request === true) {
			$id = PT_Secure($_POST['id']);
			$ad = $db->where('id',$id)->where('user_id',$user->id)->getOne(T_USR_ADS);
			if (!empty($ad)) {
				if (file_exists($ad->media)) {
					unlink($ad->media);
				}
				else if($pt->remoteStorage === true){
					PT_DeleteFromToS3($ad->media);
				}

				$db->where('id',$id)->where('user_id',$user->id)->delete(T_USR_ADS);
				$data['status'] = 200;
			}
		}
	}

	if ($first == 'toggle-stat') {
		$request = (!empty($_POST['id']) && is_numeric($_POST['id']));
		if ($request === true) {
			$id = PT_Secure($_POST['id']);
			$ad = $db->where('id',$id)->where('user_id',$user->id)->getOne(T_USR_ADS);
			if (!empty($ad)) {	
				$stat   = ($ad->status == 1) ? 0 : 1;
				$update = array('status' => $stat);
				$db->where('id',$id)->where('user_id',$user->id)->update(T_USR_ADS,$update);
				$data['status'] = 200;
			}
		}
	}
}

if ($first == 'rad-transaction') {

	$request   = (!empty($_SESSION['ua_']) && !empty($_SESSION['vo_']));


	if ($request === true) {
		$ad_id   = PT_Secure($_SESSION['ua_']);
		$pub_id  = PT_Secure($_SESSION['vo_']);
		$ad      = $db->where('id',$ad_id)->getOne(T_USR_ADS);
		if (!empty($ad)) {
			$ad_owner     = $db->where('id',$ad->user_id)->getOne(T_USERS);
			$con_price    = $pt->config->ad_c_price;
			$pub_price    = $pt->config->pub_price;
			$ad_trans     = false;
			$is_owner     = false;
			$ad_tans_data = array(
				'results' => ($ad->results += 1)
			);

			if (IS_LOGGED) {
				$is_owner = ($ad->user_id == $user->id) ? true : false;
			}

			if (!array_key_exists($ad_id, $pt->user_ad_cons['uaid_']) && !$is_owner) {
				$video_owner = $db->where('id',$pub_id)->getOne(T_USERS);
				if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $video_owner->monetization == '1')) && $video_owner->video_mon == 1){
					
					if (!empty($video_owner) && ($ad->user_id != $video_owner->id)) {
						$db->where('id',$pub_id)->update(T_USERS,array(
							'balance' => (($video_owner->balance += $pub_price))
						));
						$db->insert(T_ADS_TRANS,array('amount' => $pub_price,'type' => 'video', 'ad_id' => $ad_id, 'video_owner' => $pub_id, 'time' => time()));
					}
				}

				$ad_tans_data['spent']               = ($ad->spent += $con_price);
				$ad_trans                            = true;
				$pt->user_ad_cons['uaid_'][$ad->id]  = $ad->id;
				setcookie('_uads', htmlentities(serialize($pt->user_ad_cons)), time() + (10 * 365 * 24 * 60 * 60),'/');
	            $db->insert(T_ADS_TRANS,array('amount' => $con_price ,'type' => 'spent', 'ad_id' => $ad_id, 'video_owner' => $pub_id, 'time' => time()));
			}
			$db->insert(T_ADS_TRANS,array('type' => 'click', 'ad_id' => $ad_id, 'video_owner' => $pub_id, 'time' => time()));

			$update       = $db->where('id',$ad_id)->update(T_USR_ADS,$ad_tans_data);
			if ($update && $ad_trans && !$is_owner) {
				$ad_value = ($ad_owner->wallet -= $con_price);
	            if ($ad_value < 0) {
	                $ad_value = 0;
	            }
				$db->where('id',$ad_owner->id)->update(T_USERS,array('wallet' => $ad_value));
				if ($ad->day_limit > 0) {
					if ($ad->day == date("Y-m-d")) {
						$db->where('id',$ad->id)->update(T_USR_ADS,array('day_spend' => ($ad->day_spend + $con_price)));
					}
					else{
						$db->where('id',$ad->id)->update(T_USR_ADS,array('day_spend' => $con_price ,
					                                                     'day'       => date("Y-m-d")));
					}
				}
			}

			$data['status'] = 200;
			unset($_SESSION['ua_']); 
		}
	}
}
if ($first == 'transfer_balance') {
	$data['status'] = 400;
	if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] < 1) {
		$data['message'] = $lang->please_check_details;
	}
	elseif ($_POST['amount'] > $pt->user->balance) {
		$currency        = '$';

		if ($pt->config->payment_currency == 'EUR') {
			$currency    = '€';
		}
		$data['message'] = $lang->max_can_transfer.' '.$currency.$pt->user->balance;
	}
	else{
		$amount = PT_Secure($_POST['amount']);
		$user   = $db->where('id',$pt->user->id)->getOne(T_USERS);
		$user_balance = $user->balance - $amount;
		$user_wallet = $user->wallet + $amount;
		$db->where('id', $pt->user->id)->update(T_USERS, array('balance' => $user_balance,
															   'wallet'  => $user_wallet));
		$data['status'] = 200;
		$data['message'] = $lang->balance_transferred;
	}
}