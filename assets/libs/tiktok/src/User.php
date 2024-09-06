<?php

/*
 * (c) Andrea Olivato <andrea@lnk.bio>
 *
 * Helper class to structurise the User object
 *
 * This source file is subject to the GNU General Public License v3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace gimucco\TikTokLoginKit;

class User {

	private $open_id;
	private $union_id;
	private $avatar;
	private $avatar_larger;
	private $display_name;

	/**
	 * Main constructor
	 *
	 * Builds the User Object based on all the parameters provided by the APIs
	 *
	 * @param string $open_id The Open ID of the user (based on the specific application)
	 * @param string $union_id The Union ID of the user (based on the developer account)
	 * @param string $avatar The profile picture of the user
	 * @param string $avatar_larger The profile picture of the user, in better resolution
	 * @param string $display_name The Display Name (not the Username!!) of the user
	 * @return void
	 */
	public function __construct(string $open_id, string $union_id, string $avatar, string $avatar_larger, string $display_name) {
		$this->open_id = $open_id;
		$this->union_id = $union_id;
		$this->avatar = $avatar;
		$this->avatar_larger = $avatar_larger;
		$this->display_name = $display_name;
	}

	/**
	 * Alternative Constructor
	 *
	 * Builds the User Object based on all the parameters provided by the APIs, based on the JSON object
	 *
	 * @param object $json The user JSON returned by the APIs
	 * @return User self
	 */
	public static function fromJson(object $json) {
		return new self($json->data->open_id, $json->data->union_id, $json->data->avatar, $json->data->avatar_larger, $json->data->display_name);
	}

	/**
	 * Get the Open ID
	 * @return string Open ID
	 */
	public function getOpenID() {
		return $this->open_id;
	}
	/**
	 * Get the Union ID
	 * @return string Union ID
	 */
	public function getUnionID() {
		return $this->union_id;
	}
	/**
	 * Get the Avatar
	 * @return string Avatar
	 */
	public function getAvatar() {
		return $this->avatar;
	}
	/**
	 * Get the Larger Avatar
	 * @return string Larger Avatar
	 */
	public function getAvatarLarger() {
		return $this->avatar_larger;
	}
	/**
	 * Get the Display Name
	 * @return string Display Name
	 */
	public function getDisplayName() {
		return $this->display_name;
	}
}
