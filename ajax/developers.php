<?php
if (IS_LOGGED == false) {
    $data = [
        "status" => 400,
        "error" => "Not logged in",
    ];
    header('Content-Type: application/json');
	echo json_encode($data);
	exit();
}
if ($_GET['first'] == "create") {

    if (empty($_POST['app_name']) || empty($_POST['app_website_url']) || empty($_POST['app_description'])) {
        $data["message"] = $lang->please_check_details;
    }
    if (!filter_var($_POST['app_website_url'], FILTER_VALIDATE_URL)) {
        $data["message"] = $lang->invalid_url;
    }
    if (empty($data["message"])) {

        $re_app_data = array(
            'app_user_id' => PT_Secure($pt->user->id),
            'app_name' => PT_Secure($_POST['app_name']),
            'app_website_url' => PT_Secure($_POST['app_website_url']),
            'app_description' => PT_Secure($_POST['app_description']),
            'app_callback_url' => PT_Secure($_POST['app_callback_url'])
        );

        $id_str                          = sha1($re_app_data["app_user_id"] . microtime() . time());
        $re_app_data["app_id"]     = substr($id_str, 0, 20);
        $secret_str                      = sha1($re_app_data["app_user_id"] . PT_GenerateKey(55, 55) . microtime());
        $re_app_data["app_secret"] = substr($secret_str, 0, 39);

        if (!empty($_FILES["app_avatar"]["name"])) {

            $fileInfo      = array(
                'file' => $_FILES["app_avatar"]["tmp_name"],
                'name' => $_FILES['app_avatar']['name'],
                'size' => $_FILES["app_avatar"]["size"],
                'type' => $_FILES["app_avatar"]["type"],
                'types' => 'jpeg,jpg,png,bmp,gif'
            );
            $media         = PT_ShareFile($fileInfo);

            if (empty($media) || empty($media['filename'])) {
                $data["message"] = $lang->ivalid_image_file;
            }
            else{
                $re_app_data['app_avatar'] = $media['filename'];
            }
        }
        if (empty($data["message"])) {
            $app_id      = $db->insert(T_APPS,$re_app_data);
            if ($app_id) {
                $data = array(
                    'status' => 200,
                    'url' => PT_Link("app/".$app_id),
                    'message' => $lang->app_created_successfully
                );
            }
        }
    }
}
if ($_GET['first'] == "edit") {
	if (empty($_POST['app_name']) || empty($_POST['app_website_url']) || empty($_POST['app_description']) || empty($_POST['id'])) {
        $data["message"] = $lang->please_check_details;
    }
    if (!filter_var($_POST['app_website_url'], FILTER_VALIDATE_URL)) {
        $data["message"] = $lang->invalid_url;
    }

    $app_data = $db->where('id',PT_Secure($_POST['id']))->where('app_user_id',$pt->user->id)->getOne(T_APPS);
    if (empty($app_data)) {
        $data["message"] = $lang->app_not_found;
    }

    if (empty($data["message"])) {

        $re_app_data = array(
            'app_name' => PT_Secure($_POST['app_name']),
            'app_website_url' => PT_Secure($_POST['app_website_url']),
            'app_description' => PT_Secure($_POST['app_description']),
            'app_callback_url' => PT_Secure($_POST['app_website_url'])
        );

        if (!empty($_FILES["app_avatar"]["name"])) {

            $fileInfo      = array(
                'file' => $_FILES["app_avatar"]["tmp_name"],
                'name' => $_FILES['app_avatar']['name'],
                'size' => $_FILES["app_avatar"]["size"],
                'type' => $_FILES["app_avatar"]["type"],
                'types' => 'jpeg,jpg,png,bmp,gif'
            );
            $media         = PT_ShareFile($fileInfo);

            if (empty($media) || empty($media['filename'])) {
                $data["message"] = $lang->ivalid_image_file;
            }
            else{
                if ($app_data->app_avatar != 'upload/photos/app-default-icon.png') {
                    @unlink($app_data->app_avatar);
                    PT_DeleteFromToS3($app_data->app_avatar);
                }
                $re_app_data['app_avatar'] = $media['filename'];
            }
        }
        if (empty($data["message"])) {
            $db->where('id',$app_data->id)->update(T_APPS,$re_app_data);
            $data = array(
                'status' => 200,
                'url' => PT_Link("app/".$app_data->id),
                'message' => $lang->app_edited_successfully
            );
        }
    }
}
if ($_GET['first'] == "accept") {
    if (!empty($_POST['id'])) {
        $app = $db->where('app_id',PT_Secure($_POST['id']))->getOne(T_APPS);
        if (!empty($app)) {
            $permission = $db->where('app_id',$app->id)->where('user_id',$pt->user->id)->getOne(T_APPS_PERMISSION);
            if (empty($permission)) {
                $db->insert(T_APPS_PERMISSION,[
                    'user_id' => $pt->user->id,
                    'app_id' => $app->id
                ]);
            }
            $data["status"] = 200;
            $data["message"] = $lang->app_permission_accepted;
            $url = $app->app_website_url;
            if (!empty($app->app_callback_url)) {
                $url = $app->app_callback_url;
            }
            $import = GenrateCode($pt->user->id, $app->id);
            $data["url"] = $url . "?code=" . $import;
        }
        else{
            $data["message"] = $lang->app_not_found;
        }
    }
}