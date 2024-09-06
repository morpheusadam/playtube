<?php
if (empty($_GET['app_id']) || empty($_GET['app_secret']) || empty($_GET['code'])) {
    $errors = array(
        'status' => 400,
        'errors' => array(
            'error_code' => 1,
            'message' => 'app_id , app_secret , code can not be empty'
        )
    );
    header("Content-type: application/json");
    echo json_encode($errors, JSON_PRETTY_PRINT);
    exit();
}

$app = $db->where('app_id',PT_Secure($_GET['app_id']))->where('app_secret',PT_Secure($_GET['app_secret']))->getOne(T_APPS);
if (empty($app)) {
    $errors = array(
        'status' => 400,
        'errors' => array(
            'error_code' => 2,
            'message' => 'app not found'
        )
    );
    header("Content-type: application/json");
    echo json_encode($errors, JSON_PRETTY_PRINT);
    exit();
}

$app_code = $db->where('app_id',$app->id)->where('code',PT_Secure($_GET['code']))->getOne(T_APPS_CODES);
if (empty($app_code)) {
    $errors = array(
        'status' => 400,
        'errors' => array(
            'error_code' => 3,
            'message' => 'wrong code'
        )
    );
    header("Content-type: application/json");
    echo json_encode($errors, JSON_PRETTY_PRINT);
    exit();
}

$have_permission = $db->where('app_id',$app->id)->where('user_id',$app_code->user_id)->getValue(T_APPS_PERMISSION,'COUNT(*)');
if ($have_permission == 0) {
    $errors = array(
        'status' => 400,
        'errors' => array(
            'error_code' => 4,
            'message' => 'missing permission'
        )
    );
    header("Content-type: application/json");
    echo json_encode($errors, JSON_PRETTY_PRINT);
    exit();
}

$session_id          = sha1(rand(11111, 99999)) . time() . md5(microtime());
$platform_details = serialize(getBrowser());
$insert_data         = array(
    'user_id' => $app_code->user_id,
    'session_id' => $session_id,
    'platform_details' => $platform_details,
    'time' => time()
);
$insert              = $db->insert(T_SESSIONS, $insert_data);
$_SESSION['user_id'] = $session_id;

$db->where('app_id',$app->id)->where('code',PT_Secure($_GET['code']))->delete(T_APPS_CODES);
$data = array(
            'status' => 200,
            'access_token' => $_SESSION['user_id']
        );

header("Content-type: application/json");
echo json_encode($data, JSON_PRETTY_PRINT);
exit();