<?php

/*
 * (c) Andrea Olivato <andrea@lnk.bio>
 *
 * Main Connector Class: manages login, redirect and retrieval of information
 *
 * This source file is subject to the GNU General Public License v3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

//namespace gimucco\TikTokLoginKit;

// /use Exception;

class Connector {
	// Base URLs used for the API calls
	public const BASE_REDIRECT_URL = 'https://open-api.tiktok.com/platform/oauth/connect/?client_key=%s&scope=%s&response_type=code&redirect_uri=%s&state=%s';
	public const BASE_AUTH_URL = 'https://open-api.tiktok.com/oauth/access_token/?client_key=%s&client_secret=%s&code=%s&grant_type=authorization_code';
	public const BASE_REFRESH_URL = 'https://open-api.tiktok.com/oauth/refresh_token/?client_key=%s&grant_type=refresh_token&refresh_token=%s';
	public const BASE_USER_URL = 'https://open-api.tiktok.com/oauth/userinfo/?open_id=%s&access_token=%s';
	public const BASE_VIDEOS_URL = 'https://open-api.tiktok.com/video/list/';

	// Name of the Session used to store the State. This is required to prevent CSRF attacks
	public const SESS_STATE = 'TIKTOK_STATE';

	// Name of the GET parameter we expect containing the authorization code to verify
	public const CODE_PARAM = 'code';

	// Permissions List
	public const PERMISSION_USER_BASIC = 'user.info.basic';
	public const PERMISSION_VIDEO_LIST = 'video.list';
	public const PERMISSION_SHARE_SOUND = 'share.sound.create';
	public const VALID_PERMISSIONS = [self::PERMISSION_USER_BASIC, self::PERMISSION_VIDEO_LIST, self::PERMISSION_SHARE_SOUND];

	// Fields for Video
	public const FIELD_EMBED_HTML = "embed_html";
	public const FIELD_EMBED_LINK = "embed_link";
	public const FIELD_LIKES = "like_count";
	public const FIELD_COMMENTS = "comment_count";
	public const FIELD_SHARES = "share_count";
	public const FIELD_VIEWS = "view_count";
	public const FIELD_TITLE = "title";
	public const FIELD_TIME = "create_time";
	public const FIELD_IMAGE = "cover_image_url";
	public const FIELD_URL = "share_url";
	public const FIELD_CAPTION = "video_description";
	public const FIELD_DURATION = "duration";
	public const FIELD_HEIGHT = "height";
	public const FIELD_WIDTH = "width";
	public const FIELD_ID = "id";
	public const FIELDS_ALL = [self::FIELD_ID, self::FIELD_WIDTH, self::FIELD_HEIGHT, self::FIELD_DURATION, self::FIELD_CAPTION, self::FIELD_URL, self::FIELD_IMAGE, self::FIELD_TIME, self::FIELD_EMBED_HTML, self::FIELD_EMBED_LINK, self::FIELD_LIKES, self::FIELD_COMMENTS, self::FIELD_SHARES, self::FIELD_VIEWS, self::FIELD_TITLE];

	// .ini file configuration
	public const INI_CLIENTID = 'client_id';
	public const INI_CLIENTSECRET = 'client_secret';
	public const INI_REDIRECTURI = 'redirect_uri';
	public const INI_REQUIRED = [self::INI_CLIENTID, self::INI_CLIENTSECRET, self::INI_REDIRECTURI];

	private $client_id;
	private $client_secret;
	private $redirect;
	private $token;
	private $openid;

	/**
	 * Main constructor
	 *
	 * Requires the basic configuration required by TikTok Apis.
	 *
	 * @param string $client_id The Client ID provided by TikTok Developer Portal
	 * @param string $client_secret The Client Secret provided by TikTok Developer Portal
	 * @param string $redirect_uri Redirect URI approved on the Developer Portal
	 */
	public function __construct(string $client_id, string $client_secret, string $redirect_uri) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->redirect = $redirect_uri;
	}

	/**
	 * Alternative constructor based on an .ini file
	 *
	 * Retrieves the .ini file, parses the required parameters, then returns the object via the standard constructor
	 *
	 * @param string $path
	 * @return Connector self
	 * @throws Exception If the path is not found or the .ini file doesn't contain all the required parameters
	 */
	public static function fromIni(string $path) {
		if (!file_exists($path)) {
			throw new \Exception('Ini file not found in requested path: '.$path);
		}
		$cfg = parse_ini_file($path);
		foreach (self::INI_REQUIRED as $required_info) {
			if (!isset($cfg[$required_info])) {
				throw new \Exception('Ini file is missing required info: '.$required_info);
			}
		}
		return new self($cfg[self::INI_CLIENTID], $cfg[self::INI_CLIENTSECRET], $cfg[self::INI_REDIRECTURI]);
	}

	/**
	 * Gets the redirect URI for frontend usage
	 *
	 * Generates the Redirect URI. This should be used in the frontend to redirect the user to TikTok to accept the API connection/permissions
	 *
	 * @param array $permissions an array containing all the permissions you want to use. Your app must be approved for these permissions
	 * @return string the URL to which you need to redirect the user
	 * @throws Exception If the requested permissions are wrongly formatter
	 */
	public function getRedirect(array $permissions = [self::PERMISSION_USER_BASIC]) {
		foreach ($permissions as $permission) {
			if (!in_array($permission, self::VALID_PERMISSIONS)) {
				throw new \Exception('Invalid Permission Requested. Valid permissions are: '.implode(", ", self::VALID_PERMISSIONS));
			}
		}
		$state = uniqid();
		$_SESSION[self::SESS_STATE] = $state;
		return sprintf(self::BASE_REDIRECT_URL, $this->client_id, implode(",", $permissions), urlencode($this->redirect), $state);
	}

	/**
	 * Checks the GET parameters to see if I am receiving a response from TikTok with the authorisation code
	 *
	 * @return bool true if I am receiving an authorisation code and I should validate it
	 */
	public static function receivingResponse() {
		if (isset($_GET[self::CODE_PARAM]) && $_GET[self::CODE_PARAM]) {
			return true;
		}
		return false;
	}

	/**
	 * Calls the TikTok APIs to verify an authorisation code and retrieve the access token
	 *
	 * First checks to validate the STATE variable. The GET of the state should be the same as the SESSION to prevent CSRF attacks
	 * Then calls the TikTok APIs to verify the received authorisation code and exchange it for an Access Token
	 * Set the Token and User ID within the class for further use
	 *
	 * @param string $code contains the code received via the GET parameter
	 * @return object the Access Token Info
	 * @throws Exception If the STATE is not valid or if the API return error
	 */
	public function verifyCode(string $code) {
		if (!isset($_SESSION[self::SESS_STATE]) || !isset($_GET['state'])) {
			throw new \Exception('Invalid State Variable: Session: '.$_SESSION[self::SESS_STATE].' VS GET : '.$_GET['state']);
			return false;
		}

		try {
			$url = sprintf(self::BASE_AUTH_URL, $this->client_id, $this->client_secret, $code);
			$res = self::get($url);
			$json = json_decode($res);
			if (isset($json->data->access_token) && $json->data->access_token) {
				$this->setToken($json->data->access_token);
				$this->setOpenID($json->data->open_id);
				return $json;
			} else {
				throw new \Exception('TikTok Api Error: '.$json->data->description);
			}
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * Sets the Access Token received after authorisation for further use
	 *
	 * @param string $token The access Token received by the APIs
	 * @return void
	 */
	public function setToken(string $token) {
		$this->token = $token;
	}

	/**
	 * Sets the User ID received after authorisation for further use
	 *
	 * @param string $openid The User ID received by the APIs
	 * @return void
	 */
	public function setOpenID(string $openid) {
		$this->openid = $openid;
	}

	/**
	 * Gets the Access Token, if set
	 *
	 * @return string
	 */
	public function getToken() {
		if (empty($this->token)) {
			return '';
		}
		return $this->token;
	}

	/**
	 * Retrieves the updated Access Token from the Refresh Token
	 *
	 * @param string $refresh_token
	 * @return mixed
	 * @throws Exception
	 */
	public function refreshToken(string $refresh_token) {
		try {
			$url = sprintf(self::BASE_REFRESH_URL, $this->client_id, $refresh_token);
			$res = self::get($url);
			$json = json_decode($res);
			if (empty($json->data->access_token)) {
				return false;
			}
			$this->setToken($json->data->access_token);
			return $json->data->access_token;
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * Calls the TikTok APIs to retrieve all available user information for the logged user
	 *
	 * @return object the JSON containing the user data
	 * @throws Exception If the API returns an error
	 */
	public function getUserInfo() {
		try {
			$url = sprintf(self::BASE_USER_URL, $this->openid, $this->token);
			$res = self::get($url);
			return json_decode($res);
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * Calls the TikTok APIs to retrieve available videos for the logged user
	 *
	 * @param int $cursor contains the cursor for pagination
	 * @param int $num_results max results returned by the API
	 * @return object the JSON containing the user data
	 * @throws Exception If the API returns an error
	 */
	public function getUserVideosInfo(int $cursor = 0, int $num_results = 20, array $fields = self::FIELDS_ALL) {
		if ($num_results < 1) {
			$num_results = 20;
		}
		try {
			$data = [
				'open_id' => $this->openid,
				'access_token' => $this->token,
				'cursor' => $cursor,
				'max_count' => $num_results,
				'fields' => $fields
			];
			$res = self::post(self::BASE_VIDEOS_URL, $data);
			return json_decode($res);
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * After Calling the TikTok API via the getUserInfo() method, builds and returns the User object of this class for easier handling
	 *
	 * @param int $cursor contains the cursor for pagination
	 * @param int $num_results max results returned by the API
	 * @return array collection of Videos objects
	 * @throws Exception If the API returns an error
	 */
	public function getUserVideos(int $cursor = 0, int $num_results = 20, array $fields = self::FIELDS_ALL) {
		try {
			$json = $this->getUserVideosInfo($cursor, $num_results, $fields);
			$videos = [];
			foreach ($json->data->videos as $v) {
				$_v = Video::fromJson($v);
				$videos[$_v->getID()] = $_v;
			}
			$ret = [
				'cursor' => $json->data->cursor,
				'has_more' => $json->data->has_more,
				'videos' => $videos
			];
			return $ret;
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * Helper method to paginate all results from TikTok video pages
	 *
	 * @param int $max_pages how many pages to fetch. If it's 0, means unlimited
	 * @return array collection of Videos objects
	 * @throws Exception If the API returns an error
	 */
	public function getUserVideoPages(int $max_pages = 0, array $fields = self::FIELDS_ALL) {
		try {
			$videos = [];
			$cursor = 0;
			$count_pages = 0;
			while ($vids = $this->getUserVideos($cursor, 20, $fields)) {
				$count_pages++;
				if ($count_pages > $max_pages && $max_pages > 0) {
					break;
				}
				foreach ($vids['videos'] as $v) {
					$videos[] = $v;
				}
				if ($vids['has_more']) {
					$cursor = $vids['cursor'];
				} else {
					break;
				}
			}
			return $videos;
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * After Calling the TikTok API via the getUserInfo() method, builds and returns the User object of this class for easier handling
	 *
	 * @return User the User object
	 * @throws Exception If the API returns an error
	 */
	public function getUser() {
		try {
			$json = $this->getUserInfo();
			return User::fromJson($json);
		} catch (\Exception $e) {
			throw new \Exception('TikTok Api Error: '.$e->getMessage());
		}
	}

	/**
	 * Basic HTTP wrapper to perform calls to the TikTok Api
	 *
	 * @param string $url The URL to call
	 * @return string the response of the call or false
	 */
	private static function get($url) {
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET"
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return false;
		} else {
			return $response;
		}
	}

	/**
	 * Basic HTTP wrapper to perform POST calls to the TikTok Api
	 *
	 * @param string $url The URL to call
	 * @return string the response of the call or false
	 */
	private static function post(string $url, array $data) {
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
			CURLOPT_POSTFIELDS => json_encode($data)
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return false;
		} else {
			return $response;
		}
	}
}
