<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_G2interface extends Model {
	
	/**
	 * Get a random song, doesn't matter what (banned or whatever)
	 */
	public static function get_random_song() {
		
		//Get the total rows in the table
		$rowcount = DB::select(array('COUNT("*")','count'))->from('playlist_song')->execute()->get('count',0);

		//Now select a row with a random offset
		$rand = mt_rand(0, $rowcount - 1);
		$song = Arr::flatten(DB::select( 'playlist_song.*',
										 'playlist_songdir.*',
										 array('playlist_album.name','album_name'),
										 array('playlist_artist.name','artist_name')
										)
							->from('playlist_song')
							->join('playlist_songdir')
							->on('playlist_song.location_id','=','playlist_songdir.id')
							->join('playlist_album')
							->on('playlist_song.album_id','=','playlist_album.id')
							->join('playlist_artist')
							->on('playlist_song.artist_id','=','playlist_artist.id')
							->limit(1)
							->offset($rand)
							->execute()
							->as_array()
							);
		$song = Model_G2interface::absolute_path($song);
		return $song;
		
	}
	
	/**
	 * Get a specific song
	 */
	public static function get_song($id) {
		
		//Get the song
		$song = Arr::flatten(DB::select( 'playlist_song.*',
										 'playlist_songdir.*',
										 array('playlist_album.name','album_name'),
										 array('playlist_artist.name','artist_name')
										)
							->from('playlist_song')
							->join('playlist_songdir')
							->on('playlist_song.location_id','=','playlist_songdir.id')
							->join('playlist_album')
							->on('playlist_song.album_id','=','playlist_album.id')
							->join('playlist_artist')
							->on('playlist_song.artist_id','=','playlist_artist.id')
							->where('playlist_song.id','=',$id)
							->execute()
							->as_array()
							);
		$song = Model_G2interface::absolute_path($song);
		return $song;
		
	}
	
	/**
	 * Work out the absolute path for the given song.
	 * @param array $song
	 */
	public static function absolute_path($song) {
		//We take the number of hash_letters from the sha_hash and add that as a subdirectory to the path, then add the format
		$subdir = ($song['hash_letters'] > 0) ? substr($song['sha_hash'],0,$song['hash_letters']).'/' : '';
		
		$song['abspath'] = $song['path'].'/'.$subdir.$song['sha_hash'].'.'.$song['format'];
		return $song;
	}
}
