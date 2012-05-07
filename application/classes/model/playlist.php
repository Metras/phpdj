<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Playlist extends Model {
	
	/**
	 * Get the messages to be displayed at the top of the playlist page that are pending for the user
	 */
	public static function getMsgs() {
		return array();
	}
	
	/**
	 * Gets all the data needed to show the playlist. Song, Album, Artist, Uploader, Adder, User info (fave etc)
	 * Pulls in stuff from all sorts of other tables in one monsterous query. It isn't pretty - but it is efficient!
	 */
	public static function getPlaylist($historylength,$user) {
		//Old Playlist first (Songs that have already been played)
		
		$oldplaylistsql = "SELECT
								playlist_oldplaylistentry.id,
								playlist_oldplaylistentry.adder_id,
								adder.username AS adder,
								UNIX_TIMESTAMP(playlist_oldplaylistentry.addtime) AS addtime,
								UNIX_TIMESTAMP(playlist_oldplaylistentry.playtime) AS playtime,
								playlist_oldplaylistentry.skipped,
								playlist_oldplaylistentry.song_id,
								playlist_song.title,
								playlist_song.length,
								playlist_song.artist_id,
								playlist_artist.name AS artist,
								playlist_song.album_id,
								playlist_album.name AS album,
								playlist_song.uploader_id,
								uploader.username AS uploader,
								'0' AS playing,
								'0' AS hijack,
								'0' AS pl,
								'0' AS can_remove,
								'0' AS can_skip,
								( SELECT ROUND(score, 0) FROM playlist_rating WHERE playlist_rating.user_id = :userid AND playlist_rating.song_id = playlist_oldplaylistentry.song_id ) AS user_vote,
								( SELECT AVG(playlist_rating.score) FROM playlist_rating WHERE playlist_rating.song_id = playlist_oldplaylistentry.song_id ) AS avg_score,
								( SELECT COUNT(*) FROM playlist_rating WHERE playlist_rating.song_id = playlist_oldplaylistentry.song_id ) AS vote_count,
								( SELECT COUNT(*) FROM playlist_userprofile_favourites WHERE playlist_userprofile_favourites.song_id = playlist_oldplaylistentry.song_id AND playlist_userprofile_favourites.userprofile_id = :userprofileid ) AS favourite
							FROM
								playlist_oldplaylistentry
								LEFT JOIN playlist_song ON (playlist_oldplaylistentry.song_id = playlist_song.id)
								LEFT JOIN playlist_artist ON (playlist_song.artist_id = playlist_artist.id)
								LEFT JOIN playlist_album ON (playlist_song.album_id = playlist_album.id)
								LEFT JOIN auth_user AS adder ON (playlist_oldplaylistentry.adder_id = adder.id)
								LEFT JOIN auth_user AS uploader ON (playlist_song.uploader_id = uploader.id)
							ORDER BY
								playlist_oldplaylistentry.id DESC
							LIMIT
								{$historylength}";
		$oldplaylist = DB::query(Database::SELECT, $oldplaylistsql)->param(':userid',$user['user_id'])->param(':userprofileid',$user['userprofile_id'])->execute()->as_array();
		$oldplaylist = array_reverse($oldplaylist,false);
		
		
		
		$playlistsql = "SELECT
							playlist_playlistentry.id,
							playlist_playlistentry.adder_id,
							adder.username AS adder,
							UNIX_TIMESTAMP(playlist_playlistentry.addtime) AS addtime,
							UNIX_TIMESTAMP(playlist_playlistentry.playtime) AS playtime,
							playlist_playlistentry.playing,
							playlist_playlistentry.hijack,
							playlist_playlistentry.song_id,
							playlist_song.title,
							playlist_song.length,
							playlist_song.artist_id,
							playlist_artist.name AS artist,
							playlist_song.album_id,
							playlist_album.name AS album,
							playlist_song.uploader_id,
							uploader.username AS uploader,
							'0' AS skipped,
							'1' AS pl,
							'0' AS can_skip,
							( SELECT ROUND(score, 0) FROM playlist_rating WHERE playlist_rating.user_id = :userid AND playlist_rating.song_id = playlist_playlistentry.song_id ) AS user_vote,
							( SELECT AVG(playlist_rating.score) FROM playlist_rating WHERE playlist_rating.song_id = playlist_playlistentry.song_id ) AS avg_score,
							( SELECT COUNT(*) FROM playlist_rating WHERE playlist_rating.song_id = playlist_playlistentry.song_id ) AS vote_count,
							( SELECT COUNT(*) FROM playlist_userprofile_favourites WHERE playlist_userprofile_favourites.song_id = playlist_playlistentry.song_id AND playlist_userprofile_favourites.userprofile_id = :userprofileid ) AS favourite
						FROM
							playlist_playlistentry
							LEFT JOIN playlist_song ON (playlist_playlistentry.song_id = playlist_song.id)
							LEFT JOIN playlist_artist ON (playlist_song.artist_id = playlist_artist.id)
							LEFT JOIN playlist_album ON (playlist_song.album_id = playlist_album.id)
							LEFT JOIN auth_user AS adder ON (playlist_playlistentry.adder_id = adder.id)
							LEFT JOIN auth_user AS uploader ON (playlist_song.uploader_id = uploader.id)";
		$playlist = DB::query(Database::SELECT, $playlistsql)->param(':userid',$user['user_id'])->param(':userprofileid',$user['userprofile_id'])->execute()->as_array();
		foreach ($playlist AS $k => $v) {
			//Also need to add in user permissions check (for mods to remove)
			if ($v['adder_id'] == $user['id']) {
				$playlist[$k]['can_remove'] = '1';
			} else {
				$playlist[$k]['can_remove'] = '0';
			}
			
		}
		return array_merge($oldplaylist,$playlist);
	}
	
	/**
	 * Get the length (in seconds) of the playlist
	 */
	public static function getPlaylistLength() {
		$sql = "SELECT
					SUM(playlist_song.length) AS totallength
				FROM
					playlist_playlistentry
					LEFT JOIN playlist_song ON (playlist_playlistentry.song_id = playlist_song.id)";
		return DB::query(Database::SELECT, $sql)->execute()->get('totallength',0);
	}
	
	/**
	 * Get the currently playing song with user vote
	 */
	public static function getCurrentSong($user) {
		$sql = "SELECT
					playlist_playlistentry.id,
					playlist_playlistentry.adder_id,
					adder.username AS adder,
					UNIX_TIMESTAMP(playlist_playlistentry.addtime) AS addtime,
					UNIX_TIMESTAMP(playlist_playlistentry.playtime) AS playtime,
					playlist_playlistentry.playing,
					playlist_playlistentry.hijack,
					playlist_playlistentry.song_id,
					playlist_song.title,
					playlist_song.length,
					playlist_song.artist_id,
					playlist_artist.name AS artist,
					playlist_song.album_id,
					playlist_album.name AS album,
					playlist_song.uploader_id,
					uploader.username AS uploader,
					( SELECT ROUND(score, 0) FROM playlist_rating WHERE playlist_rating.user_id = :userid AND playlist_rating.song_id = playlist_playlistentry.song_id ) AS user_vote,
					( SELECT AVG(playlist_rating.score) FROM playlist_rating WHERE playlist_rating.song_id = playlist_playlistentry.song_id ) AS avg_score,
					( SELECT COUNT(*) FROM playlist_rating WHERE playlist_rating.song_id = playlist_playlistentry.song_id ) AS vote_count
				FROM
					playlist_playlistentry
					LEFT JOIN playlist_song ON (playlist_playlistentry.song_id = playlist_song.id)
					LEFT JOIN playlist_artist ON (playlist_song.artist_id = playlist_artist.id)
					LEFT JOIN playlist_album ON (playlist_song.album_id = playlist_album.id)
					LEFT JOIN auth_user AS adder ON (playlist_playlistentry.adder_id = adder.id)
					LEFT JOIN auth_user AS uploader ON (playlist_song.uploader_id = uploader.id)";
		$entry = Arr::flatten(DB::query(Database::SELECT, $sql)->param(':userid',$user['user_id'])->execute()->as_array());
		$entry['song_position'] = 0;
		return $entry;
	}
}