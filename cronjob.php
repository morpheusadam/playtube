<?php
require_once "./assets/init.php";
decryptConfigData();

// Dashboard This Year
$type = 'This Year';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);

// Dashboard Today
$type = 'Today';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);

// Dashboard Yesterday
$type = 'Yesterday';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);

// Dashboard This Week
$type = 'This Week';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);

// Dashboard This Month
$type = 'This Month';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);

// Dashboard Last Month
$type = 'Last Month';

$dashboard = updateDashboardData($type);

updateDashboardDB($type,$dashboard);



$db->where('name', 'cronjob_last_run')->update(T_CONFIG, array('value' => time()));

$getCompletedVideos = $db->rawQuery("SELECT * FROM " . T_UPLOADED_CUNKS . " WHERE timestamp < NOW() - INTERVAL 1 DAY;");
foreach ($getCompletedVideos as $key => $video) {
    $deleteFile = $db->where("id", $video->id)->delete(T_UPLOADED_CUNKS);
    @unlink($video->folderpath . '/' . $video->filename);
    if (file_exists($video->folderpath . '/' . $video->filename . '.part')) {
        @unlink($video->folderpath . '/' . $video->filename . '.part');
    }
}

$update_information = PT_UpdateAdminDetails();

$process_queue = $db->get(T_QUEUE, $pt->config->queue_count, "*");
if (!empty($process_queue) && count($process_queue) > 0) {
    foreach ($process_queue as $key => $value) {
        try {
            if ($value->processing == 0) {
                $video = $db->where("id", $value->video_id)->getOne(T_VIDEOS);
                $video_id = $video->id;
                $video_in_queue = $db
                    ->where("video_id", $video->id)
                    ->getOne(T_QUEUE);
                $db->where("video_id", $video->id);
                $db->update(T_QUEUE, [
                    "processing" => 1,
                ]);
                ob_end_clean();
                header("Content-Encoding: none");
                header("Connection: close");
                ignore_user_abort();
                ob_start();
                header("Content-Type: application/json");
                $size = ob_get_length();
                header("Content-Length: $size");
                ob_end_flush();
                flush();
                session_write_close();
                if (is_callable("fastcgi_finish_request")) {
                    fastcgi_finish_request();
                }
                if (is_callable('litespeed_finish_request')) {
                    litespeed_finish_request();
                }
                $video_res = $video_in_queue->video_res;
                $ffmpeg_b = $pt->config->ffmpeg_binary_file;
                $filepath = explode(".", $video->video_location)[0];
                $time = time();
                $full_dir = str_replace("ajax", "/", __DIR__);

                $video_file_full_path =
                    $full_dir . "/" . $video->video_location;

                $video_time = '';
                $demo_video = '';
                $gif_video = '';
                $gif_time = 3;
                $gif_video_time = '-t '.$gif_time.'  -async 1';
                $have_demo = false;
                if ($pt->config->demo_video == 'on' && !empty($data_insert['sell_video'])) {
                    if (!empty($duration_file['playtime_seconds']) && $duration_file['playtime_seconds'] > 0) {
                        $video_time = round((10 * round($duration_file['playtime_seconds'],0)) / 100,0);
                        $video_time = '-t '.$video_time.'  -async 1';
                        $have_demo = true;
                    }
                }

                if ($pt->config->gif_system == 'on') {
                    $gif_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_small_video_.gif";

                    $shell     = shell_exec("$ffmpeg_b $gif_video_time -y -i $video_file_full_path ".$full_dir .'/'. $gif_video);

                    $upload_s3 = PT_UploadToS3($gif_video);
                    $db->where('id', $video->id);
                    $db->update(T_VIDEOS, array(
                        'gif' => $gif_video
                    ));
                }

                if ($video->is_short == 1) {
                    
                    turnOffVideoQuality($video_res);
                    
                }

                if ($pt->config->p240 == "on") {

                    $video_output_full_path_240 = $full_dir . "/" . $filepath . "_240p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_240,
                        'filepath' => $filepath . "_240p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 426,
                        'col' => '240p',
                    ]);


                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_240p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 426,
                        ]);
                    }
                }

                if ($video->is_stock == 1) {
                    $water = $full_dir."/themes/youplay/img/icon.png";
                    $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_demo.mp4";

                    createDemoSellVideo([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'water' => $water,
                        'demo_video' => $demo_video,
                        'video_id' => $video->id,
                    ]);

                }

                if ($video_res >= 3840 && $pt->config->p4096 == "on") {

                    $video_output_full_path_4096 = $full_dir . "/" . $filepath . "_4096p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_4096,
                        'filepath' => $filepath . "_4096p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 3840,
                        'col' => '4096p',
                    ]);

                    // demo Video

                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_4096p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 3840,
                        ]);

                    }
                    // demo Video

                    // gif video
                    if ($pt->config->gif_system == 'on' && empty($gif_video)) {
                        $gif_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()). "_video_4096p_gif.mp4";

                        createGifVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'gif_video_time' => $gif_video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'gif_video_full_path' => $full_dir . "/" . $gif_video,
                            'video_id' => $video->id,
                        ]);

                    }
                    // gif video
                }


                if ($video_res >= 2048 && $pt->config->p2048 == "on") {

                    $video_output_full_path_2048 = $full_dir . "/" . $filepath . "_2048p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_2048,
                        'filepath' => $filepath . "_2048p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 2048,
                        'col' => '2048p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_2048p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 2048,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 1920 || $video_res == 0) && $pt->config->p1080 == "on") {

                    $video_output_full_path_1080 = $full_dir . "/" . $filepath . "_1080p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_1080,
                        'filepath' => $filepath . "_1080p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 1920,
                        'col' => '1080p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_1080p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 1920,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 1280 || $video_res == 0) && $pt->config->p720 == "on") {

                    $video_output_full_path_720 = $full_dir . "/" . $filepath . "_720p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_720,
                        'filepath' => $filepath . "_720p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 1280,
                        'col' => '720p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {

                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_720p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 1280,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 854 || $video_res == 0) && $pt->config->p480 == "on") {

                    $video_output_full_path_480 = $full_dir . "/" . $filepath . "_480p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_480,
                        'filepath' => $filepath . "_480p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 854,
                        'col' => '480p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_480p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 854,
                        ]);

                    }
                    // demo Video
                }
                if (($video_res >= 640 || $video_res == 0) && $pt->config->p360 == "on") {

                    $video_output_full_path_360 = $full_dir . "/" . $filepath . "_360p_converted.mp4";

                    convertVideoUsingFFMPEG([
                        'ffmpeg_b' => $ffmpeg_b,
                        'video_file_full_path' => $video_file_full_path,
                        'video_output_full_path' => $video_output_full_path_360,
                        'filepath' => $filepath . "_360p_converted.mp4",
                        'video_id' => $video->id,
                        'scale' => 640,
                        'col' => '360p',
                    ]);

                    // demo Video
                    if ($pt->config->demo_video == 'on' && empty($demo_video) && $have_demo == true) {
                        $demo_video = substr($filepath, 0,strpos($filepath, '_video') - 10).sha1(time()) . "_video_360p_demo.mp4";

                        createDemoVideo([
                            'ffmpeg_b' => $ffmpeg_b,
                            'video_time' => $video_time,
                            'video_file_full_path' => $video_file_full_path,
                            'demo_video_full_path' => $full_dir . "/" . $demo_video,
                            'demo_video' => $demo_video,
                            'video_id' => $video->id,
                            'scale' => 640,
                        ]);

                    }
                    // demo Video
                }

                if (file_exists($video->video_location)) {
                    unlink($video->video_location);
                }
                $db->where("video_id", $video->id)->delete(T_QUEUE);
                pt_push_channel_notifiations($video_id);
            }
        } catch (Exception $e) {
            $db->where("video_id", $video->id)->delete(T_QUEUE);
            if (file_exists($video->video_location)) {
                unlink($video->video_location);
            }
        }
    }
}

$users_id = $db->where('subscriber_price',0,'>')->get(T_USERS,null,array('id'));
$ids = array();
foreach ($users_id as $key => $value) {
    $ids[] = $value->id;
}

if (!empty($ids)) {
   $subscribers = $db->where('user_id',$ids,"IN")->where('time',strtotime("-30 days"),'<')->get(T_SUBSCRIPTIONS);
    foreach ($subscribers as $key => $value) {
        $user = $db->where('id',$value->user_id)->getOne(T_USERS);
        $subscriber = $db->where('id',$value->subscriber_id)->where("admin", "0")->getOne(T_USERS);
        if (!empty($user) && !empty($subscriber) && $user->subscriber_price > 0 && $subscriber->wallet >= $user->subscriber_price) {

            $user_id = $user->id;
            $admin__com = ($pt->config->admin_com_subscribers * $user->subscriber_price)/100;
            $pt->config->payment_currency = $pt->config->payment_currency.'_PERCENT';
            $payment_data         = array(
                'user_id' => $user_id,
                'video_id'    => 0,
                'paid_id'  => $subscriber->id,
                'amount'    => $user->subscriber_price,
                'admin_com'    => $pt->config->admin_com_subscribers,
                'currency'    => $pt->config->payment_currency,
                'time'  => time(),
                'type' => 'subscribe'
            );
            $db->insert(T_VIDEOS_TRSNS,$payment_data);
            $balance = $user->subscriber_price - $admin__com;
            $db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' WHERE `id` = '".$user_id."'");

            $update = array('wallet' => $db->dec($user->subscriber_price));
            $go_pro = $db->where('id',$subscriber->id)->update(T_USERS,$update);

            $db->where('id',$value->id)->update(T_SUBSCRIPTIONS,array('time' => time()));
        }
        else{
            $db->where('id',$value->id)->delete(T_SUBSCRIPTIONS);
        }
    }
}



$expired_subs   = $db->where('expire',time(),'<')->where('expire',0,'>')->get(T_PAYMENTS);
$admin = $db->where('admin',1)->getOne(T_USERS); 
foreach ($expired_subs as $value){
    $subscriber = $db->where('id',$value->user_id)->where("admin", "0")->getOne(T_USERS);
    $db->where('id',$value->id)->update(T_PAYMENTS,array('expire' => 0));

    $package = $pt->pro_packages[$subscriber->pro_type];

    if (!empty($subscriber) && $subscriber->wallet >= $package['price']) {
        $price = $package['price'];

        $update = array('is_pro' => 1,'wallet' => $db->dec($price),'pro_type' => $package['id']);
        if ($package['verified_badge'] == 1) {
            $update['verified'] = 1;
        }

        $go_pro = $db->where('id',$subscriber->id)->update(T_USERS,$update);
        if ($go_pro === true) {
            $payment_data         = array(
                'user_id' => $subscriber->id,
                'type'    => 'pro',
                'amount'  => $price,
                'date'    => date('n') . '/' . date('Y'),
                'expire'  => (time() + $package['ex_time'])
            );

            $db->insert(T_PAYMENTS,$payment_data);
            $db->where('user_id',$subscriber->id)->update(T_VIDEOS,array('featured' => 1));

            $notif_data = array(
                'notifier_id' => $admin->id,
                'recipient_id' => $subscriber->id,
                'type' => 'pro_renew',
                'url' => "wallet",
                'time' => time()
            );
            pt_notify($notif_data);
        }

    }
    else{
        $update         = array('is_pro' => 0,'verified' => 0);
        $db->where('id',$subscriber->id)->update(T_USERS,$update);
        $db->where('user_id',$subscriber->id)->update(T_VIDEOS,array('featured' => 0));
        $notif_data = array(
            'notifier_id' => $admin->id,
            'recipient_id' => $subscriber->id,
            'type' => 'pro_ended',
            'url' => "go_pro",
            'time' => time()
        );
        pt_notify($notif_data);
    }
}

PT_UpdateAdminDetails();
header("Content-type: application/json");
echo json_encode(["status" => 200, "message" => "success"]);
exit();