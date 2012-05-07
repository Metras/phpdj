<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Comments extends Model {
	
	/**
	 * Get all the comments for a given song id
	 * @param int $song_id
	 */
	public static function getComments($song_id) {
		$sql = "SELECT
					playlist_comment.id,
					playlist_comment.text,
					playlist_comment.user_id,
					playlist_comment.song_id,
					playlist_comment.time,
					UNIX_TIMESTAMP(playlist_comment.datetime) AS datetime,
					auth_user.username
				FROM
					playlist_comment
					LEFT JOIN auth_user ON (playlist_comment.user_id = auth_user.id)
				WHERE
					playlist_comment.song_id = :songid
				ORDER BY
					time ASC";
		return DB::query(Database::SELECT, $sql)->param(':songid',$song_id)->execute()->as_array();
	}
	
}