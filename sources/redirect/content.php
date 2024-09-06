<?php 
if (!empty($_GET['id'])) {

	$id = PT_Secure($_GET['id']);
	$_GET['id'] = strip_tags($_GET['id']);
	if (!empty($_GET['type'])) {
		$_GET['type'] = strip_tags($_GET['type']);
	}

	if ($_GET['type'] == 'video' || $_GET['type'] == 'image') {
		$get_ad = $db->where('id', $id)->getOne(T_VIDEO_ADS);
		if (!empty($get_ad)) {
			$update_clicks = $db->where('id', $get_ad->id)->update(T_VIDEO_ADS, array(
                'clicks' => $db->inc(1)
            ));
			header("Location: " . $get_ad->ad_link);
			exit();
		}
	}

	elseif ($_GET['type'] == 'pagead' && !empty($_SESSION['pagead'])) {
		
		if ($id != $_SESSION['pagead']) {
			error_log("Error -Could not make transaction: The Page ad is Invalid", 0);
			pt_redirect(PT_Link(''));
		}

		$ad_id  = $id;
		$ad     = $db->where('id',$ad_id)->getOne(T_USR_ADS);
		if (!empty($ad)) {
			$ad_owner     = $db->where('id',$ad->user_id)->getOne(T_USERS);
			$con_price    = $pt->config->ad_c_price;
			$ad_trans     = false;
			$ad_url       = urldecode($ad->url);
			$is_owner     = false;
			$ad_tans_data = array(
				'results' => ($ad->results += 1)
			);

			if (IS_LOGGED) {
				$is_owner = ($ad->user_id == $user->id) ? true : false;
			}


			if (!array_key_exists($ad_id, $pt->user_ad_cons['uaid_']) && !$is_owner) {
				$ad_tans_data['spent']               = ($ad->spent += $con_price);
				$ad_trans                            = true;
				$pt->user_ad_cons['uaid_'][$ad->id]  = $ad->id;
				setcookie('_uads', htmlentities(serialize($pt->user_ad_cons)), time() + (10 * 365 * 24 * 60 * 60),'/');
				$db->insert(T_ADS_TRANS,array('amount' => $con_price ,'type' => 'spent', 'ad_id' => $ad->id, 'time' => time()));
			}
			if ($ad->type == 1) {
				$type_ = 'click';
			}
			else{
				$type_ = 'view';
			}
			

			$update       = $db->where('id',$ad_id)->update(T_USR_ADS,$ad_tans_data);
			$db->insert(T_ADS_TRANS,array('type' => $type_, 'ad_id' => $ad->id, 'time' => time()));
			if ($update && $ad_trans && !$is_owner) {
				$user_wallet = $ad_owner->wallet - $con_price;
				if ($user_wallet < $con_price) {
					$db->where('id', $ad->id)->delete(T_USR_ADS);
				}
				$db->where('id',$ad_owner->id)->update(T_USERS,array('wallet' => ($ad_owner->wallet -= $con_price)));
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
			header("Location: $ad_url");
			exit();
		}
	}
}

pt_redirect(PT_Link(''));


