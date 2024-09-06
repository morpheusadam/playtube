<?php

$vl1 = (empty($_POST['first_name']) || empty($_POST['last_name']));
$vl2 = (empty($_POST['email']) || empty($_POST['message']));
$vl3 = ($vl1 || $vl2);

$data['status'] = 400;

if ($vl3 === true) {
    $data['message'] = $error_icon . $lang->please_check_details;
} 

else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $data['message'] = $error_icon . $lang->email_invalid_characters;
}

else{

    $first_name        = PT_Secure($_POST['first_name'],1);
    $last_name         = PT_Secure($_POST['last_name'],1);
    $email             = PT_Secure($_POST['email']);
    $message           = PT_Secure($_POST['message'],1);
    $message              = "Name: {$first_name} {$last_name}<br>E-mail: {$email}<br><br>Message:<br>{$message}";

    $send_message_data = array(
        'from_email' => $pt->config->email,
        'from_name' => $first_name,
        'reply-to' => $email,
        'to_email' => $pt->config->contact_us_email,
        'to_name' => $pt->config->name,
        'subject' => 'Contact us new message',
        'charSet' => 'utf-8',
        'message_body' => $message,
        'is_html' => true
    );

    $send = PT_SendMessage($send_message_data);
    if ($send) {
        $data = array(
            'status' => 200,
            'message' => $success_icon . $lang->email_sent
        );
    } 

    else {
        $data['message'] = $error_icon . $lang->error_msg;
    }
}
