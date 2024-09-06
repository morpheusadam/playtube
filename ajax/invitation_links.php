<?php
if (IS_LOGGED == false || $pt->config->invite_links_system != 'on') {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

if ($first == 'create') {
    if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0 && (PT_IsAdmin() || $user->id == $_POST['user_id']) && IfCanGenerateLink($_POST['user_id'])) {
        $user_id = PT_Secure($_POST['user_id']);
        $code    = uniqid(rand(), true);
        $db->insert(T_INVITAION_LINKS, array(
            'user_id' => $user_id,
            'code' => $code,
            'time' => time()
        ));
        $data['status']  = 200;
        $data['message'] = $lang->code_successfully;
    } else {
        $data['status']  = 400;
        $data['message'] = $lang->please_check_details;
    }
}