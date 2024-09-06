<?php
$types = array('create','edit','delete','fetch','fetch_by_id','status','wallet','random','site');
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
else if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
	$response_data       = array(
        'api_status'     => '400',
        'api_version'    => $api_version,
        'errors'         => array(
            'error_id'   => '2',
            'error_text' => 'Bad Request, Invalid or missing parameter'
        )
    );
}
else{
    if ($_POST['type'] == 'create') {

        $error     = false;
        $type      = none;
        $request   = array();
        $_POST['audience-list'] = explode(',', $_POST['audience-list']);
        $request[] = (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['title']));
        $request[] = (empty($_POST['desc']) || empty($_FILES['media']));
        $request[] = (empty($_POST['audience-list']) || !is_array($_POST['audience-list']));
        $request[] = (empty($_POST['placement']) || empty($_POST['cost']));
        
        $request   = (in_array(true, $request,true) === true);

        if ($request) {
            $error = 'Please check the details';
        }
        else{
            if (mb_strlen($_POST['name']) < 5 || mb_strlen($_POST['name']) > 100) {
                $error = 'Name must be between 5/32';
            } 

            else if(!pt_is_url($_POST['url'])){
                $error = 'The URL is invalid. Please enter a valid URL';
            } 

            else if(mb_strlen($_POST['title']) < 10 || mb_strlen($_POST['title']) > 150){
                $error = 'Ad title must be between 5/100';
            } 

            else if(!file_exists($_FILES['media']['tmp_name']) || !in_array($_FILES['media']['type'], $pt->ads_media_types)){
                $error = 'Media file is invalid. Please select a valid image / video';
            }

            else if(file_exists($_FILES['media']['tmp_name']) && $_FILES['media']['size'] > $pt->config->max_upload && $pt->config->max_upload > 0){
                $max   = pt_size_format($pt->config->max_upload);
                $error = ("File is too big, Max upload size is: $max");
            }    

            elseif (!in_array($_POST['placement'], array(1,2))) {
                $error = 'Something went wrong Please try again later!';
            } 

            else if(!in_array($_POST['cost'], array(1,2))){
                $error = 'Something went wrong Please try again later!';
            } 

            else if($_POST['placement'] == 2){
                $media_file = getimagesize($_FILES["media"]["tmp_name"]);
                $img_types  = array(IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_BMP);
                if (!in_array($media_file[2],$img_types)) {
                    $error  = 'Media file is invalid. Please select a valid image';
                }
            }
        }

        if (empty($error)) {
            $file_type   = explode("/", $_FILES['media']['type']);

            $insert_data = array(
                "name" => PT_Secure($_POST['name']),
                "audience" => implode(',', $_POST['audience-list']),
                "category" => $file_type[0],
                "media" => "",
                "url" => urlencode($_POST['url']),
                "user_id" => $user->id,
                "placement" => intval($_POST['placement']),
                "posted" => time(),
                "headline" => PT_Secure($_POST['title']),
                "description" =>  PT_Secure(PT_ShortText($_POST['desc'],1000)),
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
                    $user_ad = $db->where('id',$insert)->getOne(T_USR_ADS);
                    $user_ad->media = PT_GetMedia($user_ad->media);
                    $user_ad->url = urldecode($user_ad->url);
                    $response_data     = array(
                        'api_status'   => '200',
                        'api_version'  => $api_version,
                        'success_type' => 'create_ad',
                        'data'    => $user_ad
                    );
                }
                else{
                    $response_data       = array(
                        'api_status'     => '400',
                        'api_version'    => $api_version,
                        'errors'         => array(
                            'error_id'   => '5',
                            'error_text' => 'Error 500 internal server error!'
                        )
                    );
                }
                
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '4',
                    'error_text' => $error
                )
            );
        }
    }
    elseif ($_POST['type'] == 'edit') {

        $error     = false;
        $type      = none;
        $media     = false;
        $cost      = false;
        $request   = array();
        $_POST['audience-list'] = explode(',', $_POST['audience-list']);
        $request[] = (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['title']));
        $request[] = (empty($_POST['desc']) || empty($_POST['id']) || !is_numeric($_POST['id']));
        $request[] = (empty($_POST['audience-list']) || !is_array($_POST['audience-list']));
        $request   = (in_array(true, $request,true) === true);

        if ($request) {
            $error = 'Please check the details';
        }
        else{

            $ad_id     = PT_Secure($_POST['id']);
            $ad_data   = $db->where('id',$ad_id)->where('user_id',$user->id)->getOne(T_USR_ADS);
            if (empty($ad_data)) {
                $error = 'Ad not found';
            } 

            else if (mb_strlen($_POST['name']) < 5 || mb_strlen($_POST['name']) > 100) {
                $error = 'Name must be between 5/32';
            } 

            else if(!pt_is_url($_POST['url'])){
                $error = 'The URL is invalid. Please enter a valid URL';
            } 

            else if(mb_strlen($_POST['title']) < 10 || mb_strlen($_POST['title']) > 150){
                $error = 'Ad title must be between 5/100';
            } 
            
        }

        
        
        if (empty($error)) {
            $update_data = array(
                "name" => PT_Secure($_POST['name']),
                "audience" => implode(',', $_POST['audience-list']),
                "url" => urlencode($_POST['url']),
                "user_id" => $user->id,
                "headline" => PT_Secure($_POST['title']),
                "description" =>  PT_Secure(PT_ShortText($_POST['desc'],1000)),
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
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'edit_ad',
                    'message'    => 'Your ad successfully edited.'
                );
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '5',
                        'error_text' => 'Error 500 internal server error!'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '4',
                    'error_text' => $error
                )
            );
        }
    }
    elseif ($_POST['type'] == 'delete') {
        $request = (!empty($_POST['id']) && is_numeric($_POST['id']));
        if ($request === true) {
            $id = $_POST['id'];
            $ad = $db->where('id',$id)->where('user_id',$user->id)->getOne(T_USR_ADS);
            if (!empty($ad)) {
                if (file_exists($ad->media)) {
                    unlink($ad->media);
                }
                else if($pt->remoteStorage === true){
                    PT_DeleteFromToS3($ad->media);
                }

                $db->where('id',$id)->where('user_id',$user->id)->delete(T_USR_ADS);
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'delete_ad',
                    'message'    => 'Your ad successfully deleted.'
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '5',
                    'error_text' => 'id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'fetch') {
        $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
        $offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;
        if (!empty($offset)) {
            $db->where('id',PT_Secure($offset),'<');
        }
        if (!empty($_POST['ad_type']) && $_POST['ad_type'] == 'my_ads') {
            $db->where('user_id',$pt->user->id);
        }
        $user_ads        = $db->orderBy('id','DESC')->get(T_USR_ADS,$limit);
        foreach ($user_ads as $key => $ad) {
            $user_ads[$key]->media = PT_GetMedia($ad->media);
            $user_ads[$key]->url = urldecode($ad->url);
        }
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'fetch_ad',
            'data'    => $user_ads
        );
    }
    elseif ($_POST['type'] == 'fetch_by_id') {
        if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
            $user_ad        = $db->where('id',PT_Secure($_POST['id']))->where('user_id',$pt->user->id)->getOne(T_USR_ADS);
            if (!empty($user_ad)) {
                $user_ad->media = PT_GetMedia($user_ad->media);
                $user_ad->url = urldecode($user_ad->url);
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'fetch_by_id',
                    'data'    => $user_ad
                );
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '6',
                        'error_text' => 'ad not found'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '5',
                    'error_text' => 'id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'status') {
        $request = (!empty($_POST['id']) && is_numeric($_POST['id']));
        if ($request === true) {
            $id = $_POST['id'];
            $ad = $db->where('id',$id)->where('user_id',$user->id)->getOne(T_USR_ADS);
            if (!empty($ad)) {  
                $stat   = ($ad->status == 1) ? 0 : 1;
                $update = array('status' => $stat);
                $db->where('id',$id)->where('user_id',$user->id)->update(T_USR_ADS,$update);
                $response_data     = array(
                    'api_status'   => '200',
                    'api_version'  => $api_version,
                    'success_type' => 'status',
                    'code' => $stat,
                    'message'    => 'status updated'
                );
            }
            else{
                $response_data       = array(
                    'api_status'     => '400',
                    'api_version'    => $api_version,
                    'errors'         => array(
                        'error_id'   => '6',
                        'error_text' => 'ad not found'
                    )
                );
            }
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '5',
                    'error_text' => 'id can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'wallet') {
        if (!empty($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
            $amount = PT_Secure($_POST['amount']);
            $update  = array('wallet' => ($pt->user->wallet += $amount));
            $db->where('id',$pt->user->id)->update(T_USERS,$update);
            $payment_data         = array(
                'user_id' => $pt->user->id,
                'paid_id'  => $pt->user->id,
                'admin_com'    => 0,
                'currency'    => $pt->config->payment_currency,
                'time'  => time(),
                'amount' => $amount,
                'type' => 'ad'
            );
            $db->insert(T_VIDEOS_TRSNS,$payment_data);
            $response_data     = array(
                'api_status'   => '200',
                'api_version'  => $api_version,
                'success_type' => 'wallet',
                'message'    => 'Your wallet successfully updated.'
            );
        }
        else{
            $response_data       = array(
                'api_status'     => '400',
                'api_version'    => $api_version,
                'errors'         => array(
                    'error_id'   => '6',
                    'error_text' => 'amount can not be empty'
                )
            );
        }
    }
    elseif ($_POST['type'] == 'random') {

        $ad = $db->where('active', 1)->orderBy('RAND()')->getOne(T_VIDEO_ADS);


        $user_ads      = pt_get_user_ads();
        if (!empty($user_ads)) {
            $user_ads->media = PT_GetMedia($user_ads->media);
            $user_ads->url = urldecode($user_ads->url);
        }
            
        $sidebar_ad      = pt_get_user_ads(2);
        if (!empty($sidebar_ad)) {
            $sidebar_ad->media = PT_GetMedia($sidebar_ad->media);
            $sidebar_ad->url = urldecode($sidebar_ad->url);
        }
            
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'fetch_by_id',
            'ad'    => $ad,
            'video_ad' => $user_ads,
            'sidebar_ad' => $sidebar_ad
        );
    }
    elseif ($_POST['type'] == 'site') {
        $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50) ? PT_Secure($_POST['limit']) : 20;
        $offset = (!empty($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset'] > 0) ? PT_Secure($_POST['offset']) : 0;
        if (!empty($offset)) {
            $db->where('id',PT_Secure($offset),'<');
        }
        $site_ads        = $db->orderBy('id','DESC')->get(T_VIDEO_ADS,$limit);
        $response_data     = array(
            'api_status'   => '200',
            'api_version'  => $api_version,
            'success_type' => 'success',
            'ads'    => $site_ads,
        );
    }
}