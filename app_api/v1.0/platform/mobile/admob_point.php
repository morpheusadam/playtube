<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'You ain\'t logged in!');
    echo json_encode($data);
    exit();
}

RegisterPoint(0,"admob");
$response_data     = array(
    'api_status'   => '200',
    'api_version'  => $api_version,
    'success_type' => 'admob',
    'message'    => 'Points added'
);