<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+
$public = $db->getOne(T_USERS);
$public = PT_UserData($public->id);
$user_public_data = array();
foreach ($public as $key => $value) {
	if ($key != 'password' && $key != 'email_code') {
		$user_public_data[] = $key;
	}
	
}


// $user_public_data = array(
// 	'id',
// 	'username',
// 	'email',
// 	'first_name',
// 	'last_name',
// 	'gender',
// 	'language',
// 	'avatar',
// 	'cover',
// 	'about',
// 	'google',
// 	'facebook',
// 	'twitter',
// 	'verified',
// 	'is_pro',
// 	'url',
// 	'video_mon',
// 	'monetization',
// );

# Site public config 
$site_public_data = array(
	'theme',
	'censored_words',
	'title',
	'name',
	'keyword',
	'email',
	'description',
	'validation',
	'recaptcha',
	'language',
	'seo_link',
	'comment_system',
	'delete_account',
	'total_videos',
	'total_views',
	'total_users',
	'total_subs',
	'total_comments',
	'total_likes',
	'total_dislikes',
	'total_saved',
	'upload_system',
	'import_system',
	'autoplay_system',
	'user_registration',
	'verification_badge',
	'history_system',
	'comments_default_num',
	'fb_login',
	'tw_login',
	'plus_login',
	'go_pro',
	'user_ads',
	'max_upload',
	'theme_url',
	'site_url',
	'script_version',
	'push_id',
	'push_key',
	'payment_currency',
	'paypal_currency',
	'paypal_mode',
	'paypal_id',
	'paypal_secret',
	'checkout_payment',
	'checkout_mode',
	'checkout_currency',
	'checkout_seller_id',
	'checkout_publishable_key',
	'checkout_private_key',
	'credit_card',
	'stripe_currency',
	'stripe_secret',
	'stripe_id',
	'bank_payment',
	'bank_description',
	'bank_transfer_note',
	'pro_pkg_price'
);

$plist_video_data = array(
	'id',
	'video_id',
	'user_id',
	'title',
	'thumbnail',
	'time_date',
	'duration',
	'views',
	'time',
	'url'
);

