<?php

cleanConfigData();
$page = 'dashboard';
if (!empty($_GET['page'])) {
    $page = PT_Secure($_GET['page']);
}


$page_loaded = '';
$pages = array(
    'dashboard',
    'general-settings',
    'site-settings',
    'email-settings',
    'social-login',
    's3',
    'prosys-settings',
    'manage-payments',
    'payment-requests',
    'manage-users',
    'manage-videos',
    'import-from-youtube',
    'import-from-dailymotion',
    'import-from-twitch',
    'manage-video-ads',
    'create-video-ad',
    'edit-video-ad',
    'manage-website-ads',
    'manage-user-ads',
    'manage-themes',
    'change-site-desgin',
    'create-new-sitemap',
    'manage-pages',
    'manage-faqs',
    'changelog',
    'backup',
    'create-article',
    'edit-article',
    'manage-articles',
    'manage-profile-fields',
    'add-new-profile-field',
    'edit-profile-field',
    'payment-settings',
    'verification-requests',
    'manage-announcements',
    'ban-users',
    'custom-design',
    'api-settings',
    'manage-video-reports',
    'manage-languages',
    'add-language',
    'edit-lang',
    'manage_categories',
    'manage_sub_categories',
    'push-notifications-system',
    'sold_videos_analytics',
    'manage-movies',
    'manage-movies-category',
    'manage-comments',
    'manage-custom-pages',
    'add-new-custom-page',
    'edit-custom-page',
    'manage-currencies',
    'bank-receipts',
    'earnings',
    'copy_report',
    'monetization-requests',
    'mass-notifications',
    'manage-invitation-keys',
    'auto_subscribe',
    'auto-delete',
    'manage-activities',
    'live',
    'ffmpeg',
    'video_settings',
    'newsletters',
    'ads-settings',
    'clean-videos',
    'edit-terms-pages',
    'seo',
    'manage-invitation',
    'manage-permission',
    'upload-to-storage',
    'system_status',
    'cronjob_settings',
    'affiliates-settings',
);
if ($pt->user->admin != 1 && !CheckHavePermission($page) && $page != 'changelog') {
    $permission = json_decode($pt->user->permission,true);
    if (!empty($permission) && is_array($permission)) {
        foreach ($permission as $key => $value) {
            if(isset($permission[$key]) && $permission[$key] == "1") {
                header("Location: " . PT_LoadAdminLinkSettings($key));
                exit();
            }
        }
    }
    header("Location: " . PT_Link(''));
    exit();
}
if (in_array($page, $pages)) {
    $page_loaded = PT_LoadAdminPage("$page/content");
}

if (empty($page_loaded)) {
    header("Location: " . PT_Link('admincp'));
    exit();
}

if ($page == 'dashboard') {
    if ($pt->config->last_admin_collection < (time() - 18000)) {
        $update_information = PT_UpdateAdminDetails();
    }
}

if ($pt->config->live_video == 1) {

    if ($pt->config->live_video_save == 0) {
        try {
            $posts = $db->where('live_time','0','!=')->where('live_time',time() - 11,'<=')->get(T_VIDEOS);
            foreach ($posts as $key => $post) {
                if ($pt->config->live_video == 1 && !empty($pt->config->agora_app_id) && !empty($pt->config->agora_customer_id) && !empty($pt->config->agora_customer_certificate) && $pt->config->live_video_save == 1) {
                    StopCloudRecording(array('resourceId' => $post->agora_resource_id,
                                             'sid' => $post->agora_sid,
                                             'cname' => $post->stream_name,
                                             'post_id' => $post->id,
                                             'uid' => explode('_', $post->stream_name)[2]));
                }
                PT_DeleteVideo(PT_Secure($post->id));
            }
        } catch (Exception $e) {

        }

    }
    else{
        if ($pt->config->live_video == 1 && $pt->config->amazone_s3_2 != 1) {
            try {
            $posts = $db->where('live_time','0','!=')->where('live_time',time() - 11,'<=')->get(T_VIDEOS);
            foreach ($posts as $key => $post) {
                PT_DeleteVideo(PT_Secure($post->id));
            }
        } catch (Exception $e) {

        }
        }
    }
}
$notify_count = $db->where('recipient_id',0)->where('admin',1)->where('seen',0)->getValue(T_NOTIFICATIONS,'COUNT(*)');
$notifications = $db->where('recipient_id',0)->where('admin',1)->where('seen',0)->orderBy('id','DESC')->get(T_NOTIFICATIONS);
$old_notifications = $db->where('recipient_id',0)->where('admin',1)->where('seen',0,'!=')->orderBy('id','DESC')->get(T_NOTIFICATIONS,5);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Panel | <?php echo $pt->config->title; ?></title>
    <link rel="icon" href="<?php echo $pt->config->theme_url ?>/img/icon.png" type="image/png">


    <!-- Main css -->
    <link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('vendors/bundle.css')) ?>" type="text/css">

    <!-- Google font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Daterangepicker -->
    <link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('vendors/datepicker/daterangepicker.css')) ?>" type="text/css">

    <!-- DataTable -->
    <link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('vendors/dataTable/datatables.min.css')) ?>" type="text/css">

<!-- App css -->
    <link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('assets/css/app.css')) ?>" type="text/css">
    <!-- Main scripts -->
<script src="<?php echo(PT_LoadAdminLink('vendors/bundle.js')) ?>"></script>

    <!-- Apex chart -->
    <script src="<?php echo(PT_LoadAdminLink('vendors/charts/apex/apexcharts.min.js')) ?>"></script>

    <!-- Daterangepicker -->
    <script src="<?php echo(PT_LoadAdminLink('vendors/datepicker/daterangepicker.js')) ?>"></script>

    <!-- DataTable -->
    <script src="<?php echo(PT_LoadAdminLink('vendors/dataTable/datatables.min.js')) ?>"></script>

    <!-- Dashboard scripts -->
    <script src="<?php echo(PT_LoadAdminLink('assets/js/examples/pages/dashboard.js')) ?>"></script>
    <script src="<?php echo PT_LoadAdminLink('vendors/charts/chartjs/chart.min.js'); ?>"></script>

<!-- App scripts -->

<script type="text/javascript" src="<?php echo $pt->config->theme_url; ?>/js/jquery.form.min.js"></script>
<link href="<?php echo PT_LoadAdminLink('vendors/sweetalert/sweetalert.css'); ?>" rel="stylesheet" />
<script src="<?php echo PT_LoadAdminLink('assets/js/admin.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('vendors/select2/css/select2.min.css')) ?>" type="text/css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" type="text/css">
<?php if ($page == 'create-article' || $page == 'edit-article' || $page == 'manage-announcements' || $page == 'newsletters') { ?>
<script src="<?php echo PT_LoadAdminLink('vendors/tinymce/js/tinymce/tinymce.min.js'); ?>"></script>
<script src="<?php echo PT_LoadAdminLink('vendors/bootstrap-tagsinput/src/bootstrap-tagsinput.js'); ?>"></script>
<link href="<?php echo PT_LoadAdminLink('vendors/bootstrap-tagsinput/src/bootstrap-tagsinput.css'); ?>" rel="stylesheet" />
<?php } ?>
<?php if ($page == 'custom-design') { ?>
<script src="<?php echo PT_LoadAdminLink('vendors/codemirror-5.30.0/lib/codemirror.js'); ?>"></script>
<script src="<?php echo PT_LoadAdminLink('vendors/codemirror-5.30.0/mode/css/css.js'); ?>"></script>
<script src="<?php echo PT_LoadAdminLink('vendors/codemirror-5.30.0/mode/javascript/javascript.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo PT_LoadAdminLink('vendors/codemirror-5.30.0/lib/codemirror.css'); ?>">
<?php } ?>


    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <?php if ($page == 'bank-receipts' || $page == 'verification-requests' || $page == 'monetization-requests' || $page == 'manage-user-ads') { ?>
        <!-- Css -->
        <link rel="stylesheet" href="<?php echo(PT_LoadAdminLink('vendors/lightbox/magnific-popup.css')) ?>" type="text/css">

        <!-- Javascript -->
        <script src="<?php echo(PT_LoadAdminLink('vendors/lightbox/jquery.magnific-popup.min.js')) ?>"></script>
        <script src="<?php echo(PT_LoadAdminLink('vendors/charts/justgage/raphael-2.1.4.min.js')) ?>"></script>
        <script src="<?php echo(PT_LoadAdminLink('vendors/charts/justgage/justgage.js')) ?>"></script>
    <?php } ?>
</head>
<script type="text/javascript">

    $(function() {

        $(document).on('click', 'a[data-ajax]', function(e) {
            $(document).off('click', '.ranges ul li');
            $(document).off('click', '.applyBtn');
            e.preventDefault();
            if (($(this)[0].hasAttribute("data-sent") && $(this).attr('data-sent') == '0') || !$(this)[0].hasAttribute("data-sent")) {
                if (!$(this)[0].hasAttribute("data-sent") && !$(this).hasClass('waves-effect')) {
                    $('.navigation-menu-body').find('a').removeClass('active');
                    $(this).addClass('active');
                }
                window.history.pushState({state:'new'},'', $(this).attr('href'));
                $(".barloading").css("display","block");
                if ($(this)[0].hasAttribute("data-sent")) {
                    $(this).attr('data-sent', "1");
                }
                var url = $(this).attr('data-ajax');
                $.post("<?php echo $pt->config->site_url.'/admin_load.php';?>" + url, {url:url}, function (data) {
                    $(".barloading").css("display","none");
                    if ($('#redirect_link')[0].hasAttribute("data-sent")) {
                        $('#redirect_link').attr('data-sent', "0");
                    }
                    json_data = JSON.parse($(data).filter('#json-data').val());
                    $('.content').html(data);
                    setTimeout(function () {
                      $(".content").getNiceScroll().resize()
                    }, 500);
                    $(".content").animate({ scrollTop: 0 }, "slow");
                    showEncryptedAlert();
                });
            }
        });
        $(window).on("popstate", function (e) {
            location.reload();
        });
    });
</script>
<body <?php echo ($pt->mode == 'night' || $pt->config->night_mode == 'night' ? 'class="dark"' : ''); ?>>
    <div class="barloading" style="display: none;"></div>
    <a id="redirect_link" href="" data-ajax="" data-sent="0"></a>
    <div class="colors"> <!-- To use theme colors with Javascript -->
        <div class="bg-primary"></div>
        <div class="bg-primary-bright"></div>
        <div class="bg-secondary"></div>
        <div class="bg-secondary-bright"></div>
        <div class="bg-info"></div>
        <div class="bg-info-bright"></div>
        <div class="bg-success"></div>
        <div class="bg-success-bright"></div>
        <div class="bg-danger"></div>
        <div class="bg-danger-bright"></div>
        <div class="bg-warning"></div>
        <div class="bg-warning-bright"></div>
    </div>
<!-- Preloader -->
<div class="preloader">
    <div class="preloader-icon"></div>
    <span>Loading...</span>
</div>
<!-- ./ Preloader -->

<!-- Sidebar group -->
<div class="sidebar-group">

</div>
<!-- ./ Sidebar group -->

<!-- Layout wrapper -->
<div class="layout-wrapper">

    <!-- Header -->
    <div class="header d-print-none">
        <div class="header-container">
            <div class="header-left">
                <div class="navigation-toggler">
                    <a href="#" data-action="navigation-toggler">
                        <i data-feather="menu"></i>
                    </a>
                </div>

                <div class="header-logo">
                    <a href="<?php echo $pt->config->site_url ?>">
                        <img class="logo" src="<?php echo $pt->config->theme_url ?>/img/logo-light.png?cache=<?php echo($pt->config->logo_cache) ?>" alt="logo">
                    </a>
                </div>
            </div>

            <div class="header-body">
                <div class="header-body-left">
                    <ul class="navbar-nav">
                        <li class="nav-item mr-3">
                            <div class="header-search-form">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button class="btn">
                                            <i data-feather="search"></i>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control" placeholder="Search"  onkeyup="searchInFiles($(this).val())">
                                    <div class="pt_admin_hdr_srch_reslts" id="search_for_bar"></div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="header-body-right">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link <?php if ($notify_count > 0) { ?> nav-link-notify<?php } ?>" title="Notifications" data-toggle="dropdown">
                                <i data-feather="bell"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-big">
                                <div
                                    class="border-bottom px-3 py-3 text-center d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Notifications</h5>
                                    <?php if ($notify_count > 0) { ?>
                                    <a class="btn btn-sm btn-success" href="javascript:void(0)" onclick="ReadNotify()"><svg xmlns="http://www.w3.org/2000/svg" class="mr-2" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M0.41,13.41L6,19L7.41,17.58L1.83,12M22.24,5.58L11.66,16.17L7.5,12L6.07,13.41L11.66,19L23.66,7M18,7L16.59,5.58L10.24,11.93L11.66,13.34L18,7Z" /></svg> Mark All Read</a>
                                    <?php } ?>
                                </div>
                                <div class="dropdown-scroll">
                                    <ul class="list-group list-group-flush">
                                        <?php if ($notify_count > 0) { ?>
                                            <li class="px-4 py-2 text-center small text-muted bg-light"><?php echo $notify_count; ?> Unread Notifications</li>
                                            <?php if (!empty($notifications)) {
                                                    foreach ($notifications as $key => $notify) {
                                                        $page_ = '';
                                                        $text = '';
                                                        if ($notify->type == 'bank') {
                                                            $page_ = 'bank-receipts';
                                                            $text = 'You have a new bank payment awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'verify') {
                                                            $page_ = 'verification-requests';
                                                            $text = 'You have a new verification requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'mon') {
                                                            $page_ = 'monetization-requests';
                                                            $text = 'You have a new monetization requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'with') {
                                                            $page_ = 'payment-requests';
                                                            $text = 'You have a new withdrawal requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'report') {
                                                            $page_ = 'manage-video-reports';
                                                            $text = 'You have a new video reports awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'copy') {
                                                            $page_ = 'copy_report';
                                                            $text = 'You have a new copyright reports awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'approve') {
                                                            $page_ = 'manage-videos?type=review';
                                                            $text = 'You have a new video awaiting your approval';
                                                        }
                                                ?>
                                            <li class="px-3 py-3 list-group-item">
                                                <a href="<?php echo PT_LoadAdminLinkSettings($page_); ?>" class="d-flex align-items-center hide-show-toggler">
                                                    <div class="flex-shrink-0">
                                                        <figure class="avatar mr-3">
                                                            <span
                                                                class="avatar-title bg-info-bright text-info rounded-circle">
                                                                <?php if ($notify->type == 'bank') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                                                <?php }elseif ($notify->type == 'verify') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#2196f3" d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z"></path></svg>
                                                                <?php }elseif ($notify->type == 'mon') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                                                <?php }elseif ($notify->type == 'with') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                                                <?php }elseif ($notify->type == 'report') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-flag"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                                                <?php }elseif ($notify->type == 'copy') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-flag"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                                                <?php }elseif ($notify->type == 'approve') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.667 426.667" height="20" width="20" fill="#04abf2"> <g> <g> <g> <path d="M42.667,85.333H0V384c0,23.573,19.093,42.667,42.667,42.667h298.667V384H42.667V85.333z"></path> <path d="M384,0H128c-23.573,0-42.667,19.093-42.667,42.667v256c0,23.573,19.093,42.667,42.667,42.667h256 c23.573,0,42.667-19.093,42.667-42.667v-256C426.667,19.093,407.573,0,384,0z M213.333,266.667v-192l128,96L213.333,266.667z"></path> </g> </g> </g></svg>
                                                                <?php } ?>

                                                            </span>
                                                        </figure>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-0 line-height-20 d-flex justify-content-between">
                                                            <?php echo $text; ?>
                                                        </p>
                                                        <span class="text-muted small"><?php echo PT_Time_Elapsed_String($notify->time); ?></span>
                                                    </div>
                                                </a>
                                            </li>
                                            <?php } } ?>
                                        <?php } ?>
                                        <?php if ($notify_count == 0 && !empty($old_notifications)) { ?>
                                            <li class="px-4 py-2 text-center small text-muted bg-light">Old Notifications</li>
                                            <?php
                                                    foreach ($old_notifications as $key => $notify) {
                                                        $page_ = '';
                                                        $text = '';
                                                        if ($notify->type == 'bank') {
                                                            $page_ = 'bank-receipts';
                                                            $text = 'You have a new bank payment awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'verify') {
                                                            $page_ = 'verification-requests';
                                                            $text = 'You have a new verification requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'mon') {
                                                            $page_ = 'monetization-requests';
                                                            $text = 'You have a new monetization requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'with') {
                                                            $page_ = 'payment-requests';
                                                            $text = 'You have a new withdrawal requests awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'report') {
                                                            $page_ = 'manage-video-reports';
                                                            $text = 'You have a new video reports awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'copy') {
                                                            $page_ = 'copy_report';
                                                            $text = 'You have a new copyright reports awaiting your approval';
                                                        }
                                                        elseif ($notify->type == 'approve') {
                                                            $page_ = 'manage-videos?type=review';
                                                            $text = 'You have a new video awaiting your approval';
                                                        }
                                                ?>
                                            <li class="px-4 py-3 list-group-item">
                                                <a href="<?php echo PT_LoadAdminLinkSettings($page_); ?>" class="d-flex align-items-center hide-show-toggler">
                                                    <div class="flex-shrink-0">
                                                        <figure class="avatar mr-3">
                                                            <span class="avatar-title bg-secondary-bright text-secondary rounded-circle">
                                                                <?php if ($notify->type == 'bank') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                                                <?php }elseif ($notify->type == 'verify') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#2196f3" d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z"></path></svg>
                                                                <?php }elseif ($notify->type == 'mon') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                                                <?php }elseif ($notify->type == 'with') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                                                <?php }elseif ($notify->type == 'report') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-flag"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                                                <?php }elseif ($notify->type == 'copy') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-flag"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                                                <?php }elseif ($notify->type == 'approve') { ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.667 426.667" height="20" width="20" fill="#04abf2"> <g> <g> <g> <path d="M42.667,85.333H0V384c0,23.573,19.093,42.667,42.667,42.667h298.667V384H42.667V85.333z"></path> <path d="M384,0H128c-23.573,0-42.667,19.093-42.667,42.667v256c0,23.573,19.093,42.667,42.667,42.667h256 c23.573,0,42.667-19.093,42.667-42.667v-256C426.667,19.093,407.573,0,384,0z M213.333,266.667v-192l128,96L213.333,266.667z"></path> </g> </g> </g></svg>
                                                                <?php } ?>
                                                            </span>
                                                        </figure>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-0 line-height-20 d-flex justify-content-between">
                                                            <?php echo $text; ?>
                                                        </p>
                                                        <span class="text-muted small"><?php echo PT_Time_Elapsed_String($notify->time); ?></span>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php } }else{ ?>
                                            <li class="px-4 py-3 list-group-item">
                                                <a href="javascript:void(0)" class="d-flex align-items-center hide-show-toggler">
                                                    <div class="flex-grow-1">
                                                        <p class="mb-0 line-height-20 d-flex justify-content-between">
                                                            No notifications found
                                                        </p>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php }  ?>
                                    </ul>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" title="User menu" data-toggle="dropdown">
                                <figure class="avatar avatar-sm">
                                    <img src="<?php echo $pt->user->avatar; ?>"
                                         class="rounded-circle"
                                         alt="avatar">
                                </figure>
                                <span class="ml-2 d-sm-inline d-none"><?php echo $pt->user->name; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-big">
                                <div class="text-center py-4">
                                    <figure class="avatar avatar-lg mb-3 border-0">
                                        <img src="<?php echo $pt->user->avatar; ?>"
                                             class="rounded-circle" alt="image">
                                    </figure>
                                    <h5 class="text-center"><?php echo $pt->user->name; ?></h5>
                                    <div class="mb-3 small text-center text-muted"><?php echo $pt->user->email; ?></div>
                                    <a href="<?php echo $pt->user->url; ?>" class="btn btn-outline-light btn-rounded">View Profile</a>
                                </div>
                                <div class="list-group">
                                    <a href="<?php echo(PT_Link('logout')) ?>" class="list-group-item text-danger">Sign Out!</a>
                                    <?php if ($pt->config->night_mode == 'both' || $pt->config->night_mode == 'night_default'){ ?>
                                    <?php if ($pt->mode == 'night') { ?>
                                        <a href="javascript:void(0)" class="list-group-item admin_mode" onclick="ChangeMode('day')">
                                            <span id="night-mode-text">Day mode </span>
                                            <svg class="feather feather-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                                        </a>
                                    <?php }else{ ?>
                                        <a href="javascript:void(0)" class="list-group-item admin_mode" onclick="ChangeMode('night')">
                                            <span id="night-mode-text">Night mode </span>
                                            <svg class="feather feather-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                                        </a>
                                    <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item header-toggler">
                    <a href="#" class="nav-link">
                        <i data-feather="arrow-down"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- ./ Header -->

    <!-- Content wrapper -->
    <div class="content-wrapper">
        <!-- begin::navigation -->
        <div class="navigation">
            <div class="navigation-header">
                <span>Navigation</span>
                <a href="#">
                    <i class="ti-close"></i>
                </a>
            </div>
            <div class="navigation-menu-body">
                <ul>
                    <?php if ($pt->user->admin == 1 || CheckHavePermission('dashboard')) { ?>
                    <li>
                        <a <?php echo ($page == 'dashboard') ? 'class="active"' : ''; ?>  href="<?php echo PT_LoadAdminLinkSettings(''); ?>" data-ajax="?path=dashboard">
                            <span class="nav-link-icon">
                                <i class="material-icons">dashboard</i>
                            </span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['general-settings','site-settings','ffmpeg','video_settings','email-settings','social-login','live', 'upload-to-storage', 'cronjob_settings'])) { ?>
                    <li <?php echo ($page == 'general-settings' || $page == 'site-settings' || $page == 'upload-to-storage' || $page == 'email-settings' || $page == 'social-login' || $page == 's3' || $page == 'live' || $page == 'ffmpeg' || $page == 'video_settings' || $page == 'cronjob_settings') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">settings</i>
                            </span>
                            <span>Settings</span>
                        </a>
                        <ul <?php echo ($page == 'general-settings' || $page == 'site-settings' || $page == 'email-settings' || $page == 'social-login' || $page == 's3' || $page == 'live') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('general-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'general-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('general-settings'); ?>" data-ajax="?path=general-settings">General Configuration</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('site-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'site-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('site-settings'); ?>" data-ajax="?path=site-settings">Website Information</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('ffmpeg')) { ?>
                            <li>
                                <a <?php echo ($page == 'ffmpeg' || $page == 'upload-to-storage') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('ffmpeg'); ?>" data-ajax="?path=ffmpeg">Import & Upload Configuration</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('video_settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'video_settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('video_settings'); ?>" data-ajax="?path=video_settings">Video & Player Settings</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('email-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'email-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('email-settings'); ?>" data-ajax="?path=email-settings">E-mail Setup</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('social-login')) { ?>
                            <li>
                                <a <?php echo ($page == 'social-login') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('social-login'); ?>" data-ajax="?path=social-login">Social Login Settings</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('live')) { ?>
                             <li>
                                <a <?php echo ($page == 'live') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('live'); ?>" data-ajax="?path=live">Setup Live Streaming</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('cronjob_settings')) { ?>
                             <li>
                                <a <?php echo ($page == 'cronjob_settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('cronjob_settings'); ?>" data-ajax="?path=cronjob_settings">CronJob Settings</a>
                            </li>
                            <?php } ?>


                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['payment-settings','ads-settings','bank-receipts','manage-video-ads','manage-website-ads','manage-user-ads','payment-requests','manage-currencies','earnings'])) { ?>
                    <li <?php echo ($page == 'payment-settings' || $page == 'ads-settings' || $page == 'bank-receipts' || $page == 'manage-video-ads' || $page == 'create-video-ad' || $page == 'manage-website-ads' || $page == 'manage-user-ads' || $page == 'earnings' || $page == 'payment-requests') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">attach_money</i>
                            </span>
                            <span>Payments & Ads</span>
                        </a>
                        <ul <?php echo ($page == 'payment-settings' || $page == 'ads-settings' || $page == 'bank-receipts' || $page == 'manage-video-ads' || $page == 'create-video-ad' || $page == 'manage-website-ads' || $page == 'manage-user-ads' || $page == 'payment-requests' || $page == 'manage-currencies') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('payment-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'payment-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('payment-settings'); ?>" data-ajax="?path=payment-settings">Payment Configuration</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('ads-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'ads-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('ads-settings'); ?>" data-ajax="?path=ads-settings">Advertisement Settings</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('bank-receipts')) { ?>
                            <li>
                                <a <?php echo ($page == 'bank-receipts') ? 'class="active"' : ''; ?>  href="<?php echo PT_LoadAdminLinkSettings('bank-receipts'); ?>" data-ajax="?path=bank-receipts">
                                    Manage Bank Receipts
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-video-ads')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-video-ads' || $page ==  'create-video-ad') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-video-ads'); ?>" data-ajax="?path=manage-video-ads">Manage Video Ads</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-website-ads')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-website-ads') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-website-ads'); ?>" data-ajax="?path=manage-website-ads">Manage Website Ads</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-user-ads')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-user-ads') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-user-ads'); ?>" data-ajax="?path=manage-user-ads">Manage User Ads</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('payment-requests')) { ?>
                            <li>
                                <a <?php echo ($page == 'payment-requests') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('payment-requests'); ?>" data-ajax="?path=payment-requests">Payment Requests</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-currencies')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-currencies') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-currencies'); ?>" data-ajax="?path=manage-currencies">Manage Currencies</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('earnings')) { ?>
                            <li>
                                <a <?php echo ($page == 'earnings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('earnings'); ?>" data-ajax="?path=earnings">Earnings</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['add-language','manage-languages'])) { ?>

                    <li <?php echo ($page == 'manage-languages' || $page == 'add-language' || $page == 'edit-lang') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">language</i>
                            </span>
                            <span>Languages</span>
                        </a>
                        <ul <?php echo ($page == 'manage-languages' || $page == 'add-language' || $page == 'edit-lang') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('add-language')) { ?>
                            <li>
                                <a <?php echo ($page == 'add-language') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('add-language'); ?>" data-ajax="?path=add-language">Add New Language & Keys</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-languages')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-languages' || $page == 'edit-lang') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-languages'); ?>" data-ajax="?path=manage-languages">Manage Languages</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-users','manage-profile-fields','verification-requests','monetization-requests','affiliates-settings'])) { ?>

                    <li <?php echo ($page == 'manage-users'  || $page == 'add-new-profile-field' || $page == 'edit-profile-field' || $page == 'verification-requests' || $page == 'monetization-requests' || $page == 'affiliates-settings') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">account_circle</i>
                            </span>
                            <span>Users</span>
                        </a>
                        <ul <?php echo ($page == 'manage-users'  || $page == 'add-new-profile-field'  || $page == 'manage-profile-fields' || $page == 'edit-profile-field' || $page == 'verification-requests' || $page == 'monetization-requests') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-users')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-users') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-users'); ?>" data-ajax="?path=manage-users">Manage Users</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('affiliates-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'affiliates-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('affiliates-settings'); ?>" data-ajax="?path=affiliates-settings">Affiliates Settings</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-profile-fields')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-profile-fields' || $page == 'edit-profile-field') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-profile-fields'); ?>" data-ajax="?path=manage-profile-fields">Manage Custom Profile Fields</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('verification-requests')) { ?>
                            <li>
                                <a <?php echo ($page == 'verification-requests') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('verification-requests'); ?>" data-ajax="?path=verification-requests">Manage Verification Requests</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('monetization-requests')) { ?>
                            <li>
                                <a <?php echo ($page == 'monetization-requests') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('monetization-requests'); ?>" data-ajax="?path=monetization-requests">Manage Monetization Requests</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-videos','manage-comments','import-from-youtube','import-from-dailymotion','import-from-twitch'])) { ?>

                    <li <?php echo ($page == 'manage-videos' || $page == 'manage-comments' || $page == 'import-from-youtube' || $page == 'import-from-dailymotion' || $page == 'sold_videos_analytics' || $page == 'import-from-twitch') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">video_library</i>
                            </span>
                            <span>Videos</span>
                        </a>
                        <ul <?php echo ($page == 'manage-videos' || $page == 'import-from-youtube' || $page == 'import-from-dailymotion' || $page == 'sold_videos_analytics' || $page == 'import-from-twitch') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-videos')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-videos') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-videos'); ?>" data-ajax="?path=manage-videos">Manage Videos</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-comments')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-comments') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-comments'); ?>" data-ajax="?path=manage-comments">Manage Video Comments</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['import-from-youtube','import-from-dailymotion','import-from-twitch'])) { ?>
                            <li <?php echo ($page == 'import-from-youtube' || $page == 'import-from-dailymotion' || $page == 'import-from-twitch') ? 'class="open"' : ''; ?>>
                                <a href="#">
                                    <span>Import Videos</span>
                                </a>
                                <ul <?php echo ($page == 'import-from-youtube' || $page == 'import-from-dailymotion' || $page == 'sold_videos_analytics' || $page == 'import-from-twitch') ? 'style="display: block;"' : ''; ?>>
                                    <?php if ($pt->user->admin == 1 || CheckHavePermission('import-from-youtube')) { ?>
                                    <li>
                                        <a <?php echo ($page == 'import-from-youtube') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('import-from-youtube'); ?>" data-ajax="?path=import-from-youtube">Import From YouTube</a>
                                    </li>
                                    <?php } ?>
                                    <?php if ($pt->user->admin == 1 || CheckHavePermission('import-from-dailymotion')) { ?>
                                    <li>
                                        <a <?php echo ($page == 'import-from-dailymotion') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('import-from-dailymotion'); ?>" data-ajax="?path=import-from-dailymotion">Import From Dailymotion</a>
                                    </li>
                                    <?php } ?>
                                    <?php if ($pt->user->admin == 1 || CheckHavePermission('import-from-twitch')) { ?>
                                    <li>
                                        <a <?php echo ($page == 'import-from-twitch') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('import-from-twitch'); ?>" data-ajax="?path=import-from-twitch">Import From Twitch</a>
                                    </li>
                                    <?php } ?>
                                </ul>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>




                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-movies','manage-movies-category'])) { ?>

                    <li <?php echo ($page == 'manage-movies' || $page == 'manage-movies-category') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">video_library</i>
                            </span>
                            <span>Movies</span>
                        </a>
                        <ul <?php echo ($page == 'manage-movies' || $page == 'manage-movies-category') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-movies')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-movies') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-movies'); ?>" data-ajax="?path=manage-movies">Manage Movies</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-movies-category')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-movies-category') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-movies-category'); ?>" data-ajax="?path=manage-movies-category">Manage Categories</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['create-article','manage-articles'])) { ?>

                    <li <?php echo ($page == 'manage-articles' || $page == 'create-article' || $page == 'edit-article') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">library_books</i>
                            </span>
                            <span>Articles</span>
                        </a>
                        <ul <?php echo ($page == 'manage-articles' || $page == 'create-article' || $page == 'edit-article') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('create-article')) { ?>
                            <li>
                                <a <?php echo ($page == 'create-article') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('create-article'); ?>" >Create New Article</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-articles')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-articles' || $page == 'edit-article') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-articles'); ?>" data-ajax="?path=manage-articles">Manage Articles</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage_categories','manage_sub_categories'])) { ?>

                    <li <?php echo ($page == 'manage_categories' || $page == 'manage_sub_categories') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">more_vert</i>
                            </span>
                            <span>Categories</span>
                        </a>
                        <ul <?php echo ($page == 'manage_categories' || $page == 'manage_sub_categories') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage_categories')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage_categories') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage_categories'); ?>" data-ajax="?path=manage_categories">Manage Categories</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage_sub_categories')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage_sub_categories') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage_sub_categories'); ?>" data-ajax="?path=manage_sub_categories">Manage Sub Categories</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['prosys-settings','manage-payments'])) { ?>




                    <li <?php echo ($page == 'prosys-settings' || $page == 'manage-payments') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">star</i>
                            </span>
                            <span>Pro System</span>
                        </a>
                        <ul <?php echo ($page == 'prosys-settings' || $page == 'manage-payments') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('prosys-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'prosys-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('prosys-settings'); ?>" data-ajax="?path=prosys-settings">Pro System Settings</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-payments')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-payments') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-payments'); ?>" data-ajax="?path=manage-payments">Recent Payments</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-themes','change-site-desgin','custom-design'])) { ?>




                    <li <?php echo ($page == 'manage-themes' || $page == 'change-site-desgin' || $page == 'custom-design') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">color_lens</i>
                            </span>
                            <span>Design</span>
                        </a>
                        <ul <?php echo ($page == 'manage-themes' || $page == 'change-site-desgin' || $page == 'custom-design') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-themes')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-themes') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-themes'); ?>" data-ajax="?path=manage-themes">Themes</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('change-site-desgin')) { ?>
                            <li>
                                <a <?php echo ($page == 'change-site-desgin') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('change-site-desgin'); ?>" data-ajax="?path=change-site-desgin">Update Website Design</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('custom-design')) { ?>
                            <li>
                                <a <?php echo ($page == 'custom-design') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('custom-design'); ?>" data-ajax="?path=custom-design">Custom Design</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>



                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-announcements','ban-users','manage-activities','mass-notifications','manage-invitation-keys','manage-invitation','auto_subscribe','auto-delete','clean-videos','newsletters'])) { ?>

                    <li <?php echo ($page == 'manage-announcements' || $page == 'ban-users' || $page == 'mass-notifications' || $page == 'manage-invitation-keys' || $page == 'manage-invitation' || $page == 'auto_subscribe' || $page == 'auto-delete' || $page == 'manage-activities' || $page == 'newsletters') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">build</i>
                            </span>
                            <span>Tools</span>
                        </a>
                        <ul <?php echo ($page == 'manage-announcements' || $page == 'ban-users' || $page == 'mass-notifications' || $page == 'manage-invitation-keys' || $page == 'manage-invitation' || $page == 'auto_subscribe' || $page == 'clean-videos' || $page == 'auto-delete' || $page == 'manage-activities' || $page == 'newsletters') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-announcements')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-announcements') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-announcements'); ?>" data-ajax="?path=manage-announcements">Manage Announcements</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('ban-users')) { ?>
                            <li>
                                <a <?php echo ($page == 'ban-users') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('ban-users'); ?>" data-ajax="?path=ban-users">Ban Users</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-activities')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-activities') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-activities'); ?>" data-ajax="?path=manage-activities">Manage Activities</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('mass-notifications')) { ?>
                            <li>
                                <a <?php echo ($page == 'mass-notifications') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('mass-notifications'); ?>" data-ajax="?path=mass-notifications">Mass Notifications</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-invitation-keys')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-invitation-keys') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-invitation-keys'); ?>" data-ajax="?path=manage-invitation-keys">Manage Invitation Keys</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-invitation')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-invitation') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-invitation'); ?>" data-ajax="?path=manage-invitation">Users Invitation</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('auto_subscribe')) { ?>
                            <li>
                                <a <?php echo ($page == 'auto_subscribe') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('auto_subscribe'); ?>" data-ajax="?path=auto_subscribe">Auto Subscribe</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('auto-delete')) { ?>
                            <li>
                                <a <?php echo ($page == 'auto-delete') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('auto-delete'); ?>" data-ajax="?path=auto-delete">Auto Delete Videos</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('clean-videos')) { ?>
                            <li>
                                <a <?php echo ($page == 'clean-videos') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('clean-videos'); ?>" data-ajax="?path=clean-videos">Clean Dead Videos</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('newsletters')) { ?>
                            <li <?php echo ($page == 'newsletters') ? 'class="active"' : ''; ?>>
                                <a href="<?php echo PT_LoadAdminLinkSettings('newsletters'); ?>" data-ajax="?path=newsletters">Newsletter</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>


                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-video-reports','copy_report'])) { ?>

                    <li <?php echo ($page == 'manage-video-reports' || $page == 'copy_report') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">flag</i>
                            </span>
                            <span>Reports</span>
                        </a>
                        <ul <?php echo ($page == 'manage-video-reports' || $page == 'copy_report') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-video-reports')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-video-reports') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-video-reports'); ?>" data-ajax="?path=manage-video-reports">
                                    Manage video reports
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('copy_report')) { ?>
                            <li>
                                <a <?php echo ($page == 'copy_report') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('copy_report'); ?>" data-ajax="?path=copy_report">
                                    Manage Copyright Reports
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['manage-custom-pages','manage-pages','manage-faqs','seo'])) { ?>




                    <li <?php echo ($page == 'manage-pages' || $page == 'add-new-custom-page' || $page == 'manage-custom-pages' || $page == 'edit-custom-page' || $page == 'manage-faqs' || $page == 'seo') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">description</i>
                            </span>
                            <span>Pages</span>
                        </a>
                        <ul <?php echo ($page == 'manage-pages' || $page == 'add-new-custom-page' || $page == 'manage-custom-pages' || $page == 'edit-custom-page' || $page == 'seo') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-custom-pages')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-custom-pages' || $page == 'add-new-custom-page' || $page == 'edit-custom-page') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-custom-pages'); ?>" data-ajax="?path=manage-custom-pages">Manage Custom Pages</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-pages')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-pages') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-pages'); ?>" data-ajax="?path=manage-pages">Manage Pages</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('manage-faqs')) { ?>
                            <li>
                                <a <?php echo ($page == 'manage-faqs') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('manage-faqs'); ?>" data-ajax="?path=manage-faqs">Manage FAQs</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('seo')) { ?>
                            <li>
                                <a <?php echo ($page == 'seo') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('seo'); ?>" data-ajax="?path=seo">Manage Pages SEO</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['create-new-sitemap'])) { ?>


                    <li <?php echo ($page == 'create-new-sitemap') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">power_input</i>
                            </span>
                            <span>Sitemap</span>
                        </a>
                        <ul <?php echo ($page == 'create-new-sitemap') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('create-new-sitemap')) { ?>
                            <li>
                                <a <?php echo ($page == 'create-new-sitemap') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('create-new-sitemap'); ?>" data-ajax="?path=create-new-sitemap">Create Sitemap</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['api-settings','push-notifications-system'])) { ?>




                    <li <?php echo ($page == 'api-settings' || $page == 'push-notifications-system') ? 'class="open"' : ''; ?>>
                        <a href="#">
                            <span class="nav-link-icon">
                                <i class="material-icons">compare_arrows</i>
                            </span>
                            <span>Mobile & API Settings</span>
                        </a>
                        <ul <?php echo ($page == 'api-settings' || $page == 'push-notifications-system') ? 'style="display: block;"' : ''; ?>>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('api-settings')) { ?>
                            <li>
                                <a <?php echo ($page == 'api-settings') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('api-settings'); ?>" data-ajax="?path=api-settings">Manage API Access Keys</a>
                            </li>
                            <?php } ?>
                            <?php if ($pt->user->admin == 1 || CheckHavePermission('push-notifications-system')) { ?>
                            <li>
                                <a <?php echo ($page == 'push-notifications-system') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('push-notifications-system'); ?>" data-ajax="?path=push-notifications-system">Push Notifications System</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if ($pt->user->admin == 1 || CheckHaveMultiPermission(['backup'])) { ?>

                    <li>
                        <a <?php echo ($page == 'backup') ? 'class="active"' : ''; ?>  href="<?php echo PT_LoadAdminLinkSettings('backup'); ?>" data-ajax="?path=backup">
                            <span class="nav-link-icon">
                                <i class="material-icons">backup</i>
                            </span>
                            <span>Backup</span>
                        </a>
                    </li>
                    <?php } ?>

                    <li>
                        <a <?php echo ($page == 'system_status') ? 'class="active"' : ''; ?> href="<?php echo PT_LoadAdminLinkSettings('system_status'); ?>" data-ajax="?path=system_status">
                            <span class="nav-link-icon">
                                <i class="material-icons">info</i>
                            </span>
                            <span>System Status</span>
                        </a>
                    </li>

                    <li>
                        <a <?php echo ($page == 'changelog') ? 'class="active"' : ''; ?>  href="<?php echo PT_LoadAdminLinkSettings('changelog'); ?>" data-ajax="?path=changelog">
                            <span class="nav-link-icon">
                                <i class="material-icons">update</i>
                            </span>
                            <span>Changelogs</span>
                        </a>
                    </li>

                    <li>
                        <a href="http://docs.playtubescript.com" target="_blank">
                            <span class="nav-link-icon">
                                <i class="material-icons">more_vert</i>
                            </span>
                            <span>FAQs & Docs</span>
                        </a>
                    </li>
                     <a class="pow_link" href="https://playtubescript.com/" target="_blank">
                        <p>Powered by</p>
                        <img src="https://demo.playtubescript.com/themes/default/img/logo-light.png?cache=<?php echo($pt->config->logo_cache) ?>">
                        <b class="badge">v<?php echo $config['script_version'];?></b>
                    </a>
                </ul>
            </div>
        </div>
        <!-- end::navigation -->

        <!-- Content body -->
        <div class="content-body">
            <!-- Content -->
            <div class="content ">
                <?php echo $page_loaded; ?>
            </div>
            <!-- ./ Content -->

        </div>
        <!-- ./ Content body -->
    </div>
    <!-- ./ Content wrapper -->
</div>
<!-- ./ Layout wrapper -->
<div class="select_pro_model"></div>
<script src="<?php echo PT_LoadAdminLink('vendors/sweetalert/sweetalert.min.js'); ?>"></script>
<script src="<?php echo(PT_LoadAdminLink('vendors/select2/js/select2.min.js')) ?>"></script>
    <script src="<?php echo(PT_LoadAdminLink('assets/js/examples/select2.js')) ?>"></script>
    <script src="<?php echo(PT_LoadAdminLink('assets/js/app.min.js')) ?>"></script>
    <script type="text/javascript">
        function showEncryptedAlert() {
            <?php foreach ($pt->encryptedKeys as $key => $value) {  
                if (!empty($pt->hiddenConfig[$value])) { ?> 
                if ($(".alert_<?php echo($value) ?>").length == 0) {
                    $("input[name='<?php echo($value) ?>']").before('<div class="alert alert-danger alert_<?php echo($value) ?>" role="alert">The secret key is not showing due security reasons, you can still overwrite the current one.</div>');
                    $("textarea[name='<?php echo($value) ?>']").before('<div class="alert alert-danger alert_<?php echo($value) ?>" role="alert">The secret key is not showing due security reasons, you can still overwrite the current one.</div>');
                }
            <?php } } ?>
        }
        $('body').on('click', function (e) {
            $('.dropdown-animating').removeClass('show');
            $('.dropdown-menu').removeClass('show');
        });
        function searchInFiles(keyword) {
            if (keyword.length > 2) {
                $.post('<?php echo $pt->config->site_url; ?>/aj/ap/search_in_pages', {keyword: keyword}, function(data, textStatus, xhr) {
                    if (data.html != '') {
                        $('#search_for_bar').html(data.html)
                    }
                    else{
                        $('#search_for_bar').html('')
                    }
                });
            }
            else{
                $('#search_for_bar').html('')
            }
        }
        jQuery(document).ready(function($) {
            showEncryptedAlert();
            jQuery.fn.highlight = function (str, className) {
                if (str != '') {
                    var aTags = document.getElementsByTagName("h2");
                    var bTags = document.getElementsByTagName("label");
                    var cTags = document.getElementsByTagName("h3");
                    var dTags = document.getElementsByTagName("h6");
                    var searchText = str.toLowerCase();

                    if (aTags.length > 0) {
                        for (var i = 0; i < aTags.length; i++) {
                            var tag_text = aTags[i].textContent.toLowerCase();
                            if (tag_text.indexOf(searchText) != -1) {
                                $(aTags[i]).addClass(className)
                            }
                        }
                    }

                    if (bTags.length > 0) {
                        for (var i = 0; i < bTags.length; i++) {
                            var tag_text = bTags[i].textContent.toLowerCase();
                            if (tag_text.indexOf(searchText) != -1) {
                                $(bTags[i]).addClass(className)
                            }
                        }
                    }

                    if (cTags.length > 0) {
                        for (var i = 0; i < cTags.length; i++) {
                            var tag_text = cTags[i].textContent.toLowerCase();
                            if (tag_text.indexOf(searchText) != -1) {
                                $(cTags[i]).addClass(className)
                            }
                        }
                    }

                    if (dTags.length > 0) {
                        for (var i = 0; i < dTags.length; i++) {
                            var tag_text = dTags[i].textContent.toLowerCase();
                            if (tag_text.indexOf(searchText) != -1) {
                                $(dTags[i]).addClass(className)
                            }
                        }
                    }
                }
            };
            jQuery.fn.highlight("<?php echo (!empty($_GET['highlight']) ? $_GET['highlight'] : '') ?>",'highlight_text');
        });
        $(document).on('click', '#search_for_bar a', function(event) {
            event.preventDefault();
            location.href = $(this).attr('href');
        });
        function ReadNotify() {
            $.get('<?php echo $pt->config->site_url; ?>/aj/ap/ReadNotify', function(data) {
                location.reload();
            });
        }
        function ChangeMode(mode) {
            if (mode == 'day') {
                $('body').removeClass('dark');
                $('.admin_mode').html('<span id="night-mode-text">Night mode </span><svg class="feather feather-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>');
                $('.admin_mode').attr('onclick', "ChangeMode('night')");
            }
            else{
                $('body').addClass('dark');
                $('.admin_mode').html('<span id="night-mode-text">Day mode </span><svg class="feather feather-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>');
                $('.admin_mode').attr('onclick', "ChangeMode('day')");
            }
            $.post('{{LINK }}', {mode: mode}, function(data, textStatus, xhr) { /* pass */ });
        }
        function delay(callback, ms) {
          var timer = 0;
          return function() {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
              callback.apply(context, args);
            }, ms || 0);
          };
        }
        function SelectProModel(type,self) {
            if ($(self).val() == 'pro') {
                $.get('<?php echo $pt->config->site_url; ?>/aj/ap/select_pro_model',{package_type: type}, function(data) {
                    $('.select_pro_model').html('');
                    $('.select_pro_model').html(data.html);
                    $('#SelectProModal').modal('show');
                });
            }
            if (type == 'can_use_itunes_affiliate') {
                if ($(self).val() == 'admin') {
                    $('.itunes_partner_div').slideDown();
                }
                else{
                    $('.itunes_partner_div').slideUp();
                }
            }
        }
        $(document).on("click", '.round_check input[type=radio]', function(event) { 
            var configName = $(this).attr('name');
            var objData = {};
            if ($(this).is(":checked") === true) {
                objData[configName] = $(this).val();
            }
            else{
                if ($('input[name='+configName+']')[0]) {
                    objData[configName] = $($('input[name='+configName+']')[0]).val();
                }
            }
            $.post('<?php echo $pt->config->site_url; ?>/aj/ap/save-settings', objData);
        });
        function Wo_SubmitSelectProForm(self) {
            let form_select_pro = $('.SelectProModalForm');
            form_select_pro.ajaxForm({
                url: '<?php echo $pt->config->site_url; ?>/aj/ap/select_pro_package',
                beforeSend: function() {
                    form_select_pro.find('.waves-effect').text('Please wait..');
                },
                success: function(data) {
                    form_select_pro.find('.waves-effect').text('Save');
                    $('#SelectProModal').animate({
                        scrollTop : 0                       // Scroll to top of body
                    }, 500);
                    if (data.status == 200) {
                        $('#SelectProModalAlert').html('<div class="alert alert-success"><i class="fa fa-check"></i> Settings updated successfully</div>');
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    }
                    else{
                        $('#SelectProModalAlert').html('<div class="alert alert-danger">'+data.message+'</div>');
                    }
                }
            });
            form_select_pro.submit();
        }

    </script>

</body>
</html>
