<?php
if (IS_LOGGED == false || $pt->config->upload_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

$thumbnail = 'upload/photos/thumbnail.jpg';
if (!empty($_FILES['thumbnail']['tmp_name'])) {
    $file_info   = array(
        'file' => $_FILES['thumbnail']['tmp_name'],
        'size' => $_FILES['thumbnail']['size'],
        'name' => $_FILES['thumbnail']['name'],
        'type' => $_FILES['thumbnail']['type'],
        'crop' => array(
            'width' => 1076,
            'height' => 604
        )
    );
    if ($pt->config->shorts_system == 'on' && !empty($_POST['is_short']) && $_POST['is_short'] == 'yes') {
        $file_info['crop'] = array('width' => 604,
                                   'height' => 1076);
    }
    $pt->remoteStorage = false;
    $file_upload = PT_ShareFile($file_info);
    if (!empty($file_upload['filename'])) {
        $getFileName = substr($file_upload['filename'], strrpos($file_upload['filename'], '/') + 1);
        $folderName = str_replace("/" .$getFileName, "", $file_upload['filename']);
        $db->insert(T_UPLOADED_CUNKS, [
            "filename" => $getFileName, 
            "user_id" => $pt->user->id, 
            "folderpath" => $folderName, 
            "status" => "completed", 
            "type" => "thumbnail"
        ]);
        $thumbnail = PT_Secure($file_upload['filename'], 0);
        $_SESSION['ffempg_uploads'][] = $thumbnail;
        $data = array('status' => 200, 'thumbnail' => $thumbnail);
    }
}
?>