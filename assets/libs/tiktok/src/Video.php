<?php

/*
 * (c) Andrea Olivato <andrea@lnk.bio>
 *
 * Helper class to structurise the Video object
 *
 * This source file is subject to the GNU General Public License v3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace gimucco\TikTokLoginKit;

class Video {
	private string $id;
	private string $share_url;
	private int $create_time;
	private string $cover_image_url;
	private string $video_description;
	private int $duration;
	private int $height;
	private int $width;
	private string $title;
	private string $embed_html;
	private string $embed_link;
	private int $like_count;
	private int $comment_count;
	private int $share_count;
	private int $view_count;

	/**
	 * Main constructor
	 *
	 * Builds the User Object based on all the parameters provided by the APIs
	 *
	 * @param string $id The  ID of the Video
	 * @param string $share_url The permalink URL of the Video
	 * @param int $create_time Unix Timestamp representation of the creation date/time
	 * @param string $cover_image_url The URL of the cover image (thumbnail of the video)
	 * @param string $video_description The caption of the post, can contain hashtags
	 * @param int $duration Duration of the video (in seconds)
	 * @param int $height Height of the Video (in pixels)
	 * @param int $width Width of the Video (in pixels)
	 * @param string $title Title of the Video
	 * @param string $embed_html HTML code to embed the Video
	 * @param string $embed_link URL of the Embed Video
	 * @param int $like_count number of Likes received by the Video
	 * @param int $comment_count number of Comments received by the Video
	 * @param int $share_count number of Shares received by the Video
	 * @param int $view_count number of Views received by the Video
	 * @return void
	 */
	public function __construct(string $id, string $share_url, int $create_time, string $cover_image_url, string $video_description, int $duration, int $height, int $width, string $title, string $embed_html, string $embed_link, int $like_count, int $comment_count, int $share_count, int $view_count) {
		$this->id = $id;
		$this->share_url = $share_url;
		$this->create_time = $create_time;
		$this->cover_image_url = $cover_image_url;
		$this->video_description = $video_description;
		$this->duration = $duration;
		$this->height = $height;
		$this->width = $width;
		$this->title = $title;
		$this->embed_html = $embed_html;
		$this->embed_link = $embed_link;
		$this->like_count = $like_count;
		$this->comment_count = $comment_count;
		$this->share_count = $share_count;
		$this->view_count = $view_count;
	}

	/**
	 * Alternative Constructor
	 *
	 * Builds the Video Object based on all the parameters provided by the APIs, based on the JSON object
	 *
	 * @param object $json The Video JSON returned by the APIs
	 * @return Video self
	 */
	public static function fromJson(object $json) {
		$id = '';
		if (!empty($json->id)) {
			$id = $json->id;
		}
		$share_url = '';
		if (!empty($json->share_url)) {
			$share_url = $json->share_url;
		}
		$create_time = 0;
		if (!empty($json->create_time)) {
			$create_time = (int) $json->create_time;
		}
		$cover_image_url = '';
		if (!empty($json->cover_image_url)) {
			$cover_image_url = $json->cover_image_url;
		}
		$video_description = '';
		if (!empty($json->video_description)) {
			$video_description = $json->video_description;
		}
		$duration = 0;
		if (!empty($json->duration)) {
			$duration = (int) $json->duration;
		}
		$height = 0;
		if (!empty($json->height)) {
			$height = (int) $json->height;
		}
		$width = 0;
		if (!empty($json->width)) {
			$width = (int) $json->width;
		}
		$title = '';
		if (!empty($json->title)) {
			$title = $json->title;
		}
		$embed_html = '';
		if (!empty($json->embed_html)) {
			$embed_html = $json->embed_html;
		}
		$embed_link = '';
		if (!empty($json->embed_link)) {
			$embed_link = $json->embed_link;
		}
		$like_count = 0;
		if (!empty($json->like_count)) {
			$like_count = (int) $json->like_count;
		}
		$comment_count = 0;
		if (!empty($json->comment_count)) {
			$comment_count = (int) $json->comment_count;
		}
		$share_count = 0;
		if (!empty($json->share_count)) {
			$share_count = (int) $json->share_count;
		}
		$view_count = 0;
		if (!empty($json->view_count)) {
			$view_count = (int) $json->view_count;
		}

		return new self($id, $share_url, $create_time, $cover_image_url, $video_description, $duration, $height, $width, $title, $embed_html, $embed_link, $like_count, $comment_count, $share_count, $view_count);
	}

	/**
	 * Get the Video ID
	 * @return string Video ID
	 */
	public function getID() {
		return $this->id;
	}
	/**
	 * Get the URL of the Video
	 * @return string URL of the Video
	 */
	public function getShareURL() {
		return $this->share_url;
	}
	/**
	 * Get the time of creation of the video, in Unix Timestamp
	 * @return int Time of Creation
	 */
	public function getCreateTime() {
		return $this->create_time;
	}
	/**
	 * Get the URL of Cover Image of the video (Thumbnail)
	 * @return string Cover Image URL
	 */
	public function getCoverImageURL() {
		return $this->cover_image_url;
	}
	/**
	 * Get the Caption of the Video
	 * @return string Caption of the Video
	 */
	public function getVideoDescription() {
		return $this->video_description;
	}
	/**
	 * Get the Duration of the video, in seconds
	 * @return int Duration
	 */
	public function getDuration() {
		return $this->duration;
	}
	/**
	 * Get the Height of the Video, in Pixels
	 * @return int Height of the Video
	 */
	public function getHeight() {
		return $this->height;
	}
	/**
	 * Get the Width of the Video, in Pixels
	 * @return int Width of the Video
	 */
	public function getWidth() {
		return $this->width;
	}
	/**
	 * Get the Title of the Video
	 * @return string Title of the Video
	 */
	public function getTitle() {
		return $this->title;
	}
	/**
	 * Get the HTML code of the Video Embed
	 * @return string HTML code of the Video Embed
	 */
	public function getEmbedHTML() {
		return $this->embed_html;
	}
	/**
	 * Get the Link to the embed of the Video
	 * @return string Link to the embed of the Video
	 */
	public function getEmbedLink() {
		return $this->embed_link;
	}
	/**
	 * Get the number of Likes of the Video
	 * @return int Likes of the Video
	 */
	public function getLikeCount() {
		return $this->like_count;
	}
	/**
	 * Get the number of Comments of the Video
	 * @return int Comments of the Video
	 */
	public function getCommentCount() {
		return $this->comment_count;
	}
	/**
	 * Get the number of Shares of the Video
	 * @return int Shares of the Video
	 */
	public function getShareCount() {
		return $this->share_count;
	}
	/**
	 * Get the number of Views of the Video
	 * @return int Views of the Video
	 */
	public function getViewCount() {
		return $this->view_count;
	}
}
