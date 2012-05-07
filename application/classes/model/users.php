<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Users extends Model {
	
	/**
	 * Gets a user - django auth_user table + userprofile from g2 system
	 * @param int $userid
	 */
	public static function getUser($userid) {
		$sql = "SELECT
					auth_user.id,
					auth_user.username,
					auth_user.first_name,
					auth_user.last_name,
					auth_user.email,
					auth_user.password,
					auth_user.is_staff,
					auth_user.is_active,
					auth_user.is_superuser,
					UNIX_TIMESTAMP(auth_user.last_login) AS last_login,
					UNIX_TIMESTAMP(auth_user.date_joined) AS date_joined,
					playlist_userprofile.user_id,
					playlist_userprofile.id AS userprofile_id,
					playlist_userprofile.uploads,
					playlist_userprofile.api_key,
					playlist_userprofile.sa_id,
					playlist_userprofile.tokens,
					playlist_userprofile.s_playlistHistory
				FROM
					auth_user
					LEFT JOIN playlist_userprofile ON (playlist_userprofile.user_id = auth_user.id)
				WHERE
					playlist_userprofile.user_id = :userid
		
		";
		$user = DB::query(Database::SELECT, $sql)->param(':userid',$userid)->execute()->as_array();
		return Arr::flatten($user);
	}
}