<?php
/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */
// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

	$LoginWithConfig = array(
			"callback" => $pt->config->site_url . '/social-login.php?provider=' . $provider,
			"providers" => array(
				// openid providers
				"OpenID" => array(
					"enabled" => true
				),
				"Yahoo" => array(
					"enabled" => true,
					"keys" => array("key" => "", "secret" => ""),
				),
				"AOL" => array(
					"enabled" => true
				),
				"Google" => array(
					"enabled" => true,
					"keys" => array("id" =>  $pt->config->google_app_ID, "secret" => $pt->config->google_app_key),
				),
				"Facebook" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->facebook_app_ID, "secret" => $pt->config->facebook_app_key),
					"scope" => "email",
					"trustForwarded" => false
				),
				"Twitter" => array(
					"enabled" => true,
					"keys" => array("key" => $pt->config->twitter_app_ID, "secret" => $pt->config->twitter_app_key),
					"includeEmail" => true
				),
				"LinkedIn" => array(
					"enabled" => true,
					"keys" => array("key" => $pt->config->linkedinAppId, "secret" => $pt->config->linkedinAppKey)
				),
				"Vkontakte" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->VkontakteAppId, "secret" => $pt->config->VkontakteAppKey)
				),
				"Instagram" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->instagramAppId, "secret" => $pt->config->instagramAppkey)
				),
				"QQ" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->qqAppId, "secret" => $pt->config->qqAppkey)
				),
				"WeChat" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->WeChatAppId, "secret" => $pt->config->WeChatAppkey)
				),
				"Discord" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->DiscordAppId, "secret" => $pt->config->DiscordAppkey)
				),
				"Mailru" => array(
					"enabled" => true,
					"keys" => array("id" => $pt->config->MailruAppId, "secret" => $pt->config->MailruAppkey)
				),
				// windows live
				"Live" => array(
					"enabled" => true,
					"keys" => array("id" => "", "secret" => "")
				),
				"Foursquare" => array(
					"enabled" => true,
					"keys" => array("id" => "", "secret" => "")
				),
			),
			// If you want to enable logging, set 'debug_mode' to true.
			// You can also set it to
			// - "error" To log only error messages. Useful in production
			// - "info" To log info and error messages (ignore debug messages)
			"debug_mode" => false,
			// Path to file writable by the web server. Required if 'debug_mode' is not false
			"debug_file" => "",
);
