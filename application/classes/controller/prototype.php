<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Prototype extends Controller {

	public function action_index()
	{
		$this->response->body('hello, world!');
	}

	public function action_getnext() {
		$config = Kohana::$config->load('phpdj');
  		$log = Log::instance();
		
		/**
		 * Delete old songs in tmp location. It is safe to delete the file even if its still being played
		 * because sc_trans stores it all in memory.
		 */
		$tmpdir = scandir($config['paths']['tmp']);
		
		foreach ($tmpdir AS $foo => $bar) {
			if (strpos($bar,'.mp3') !== false) {
				unlink($config['paths']['tmp'].'/'.$bar);
				$log->add(Log::NOTICE,'Removed tmp song: '.$config['paths']['tmp'].'/'.$bar);
			}
		}
		
		/**
		 * Pick a song
		 */
		
		$song = Model_G2interface::get_random_song();
		//$song = Model_G2interface::get_song(11);
		
		$log->add(Log::NOTICE,'Song Selected: '.$song['abspath']);
		
		/**
		 * Copy song to tmp location
		 */
		$tmpsongname = time().'.'.$song['format'];
		$tmpsong = $config['paths']['tmp'].'/'.$tmpsongname;
		copy($song['abspath'],$tmpsong);
		$log->add(Log::NOTICE,'Song Copied to: '.$tmpsong);
			
		/**
 		 * Change ID3 tags of tmp song
		 */
		$mp3tools = new Mp3tools(); 
		$mp3tools->set_file($tmpsong);
		
		$newtags = array(
			'title'   => array($song['title']),
			'artist'  => array($song['artist_name']),
			'album'   => array($song['album_name']),
			'genre'   => array($song['genre'])
		);
		$mp3tools->set_tags($newtags);
		
		// Write tags to file
		if ($mp3tools->write_tags()) {
			$log->add(Log::NOTICE,"Tags Rewritten OK");
			if (count($mp3tools->get_warnings()) > 0) {
				$log->add(Log::NOTICE,"There were some warnings rewriting tags:\n".implode("\n", $mp3tools->get_warnings()));
			}
		} else {
			$log->add(Log::NOTICE,"Failed to write tags:\n ".implode("\n", $mp3tools->get_errors()));
		}
		
		/**
		 * Return tmp song path and name to sc_trans
		 */
		$log->write();
		$this->response->body($tmpsong);
	}

	/**
	 * 
	 * Skip the current song
	 */
	public function action_skip() {

		//Perform external curl request 
		$config = Kohana::$config->load('phpdj');
		
		$url = 'http://localhost:'.$config['sc_trans']['adminport'].'/nextsong';
		$request = Request::factory($url);
		$request_client = $request->client()
							->options(CURLOPT_HTTPAUTH,CURLAUTH_BASIC)
							->options(CURLOPT_USERPWD,$config['sc_trans']['adminuser'].':'.$config['sc_trans']['adminpassword']);
		
		$response = $request->execute();
		$log = Log::instance()->add(Log::NOTICE,'Skipping...')->write();
		$this->response->body('Skipping...(may take a minute, give it time). sc_trans said: '.HTML::entities($response));
	}

	/**
	 * Get the current song from sc_serv
	 */
	public function action_what() {
		$config = Kohana::$config->load('phpdj');
		$streamid = $config['sc_serv']['streamid'];
		$url = 'http://localhost:'.$config['sc_serv']['port'].'/admin.cgi?sid='.$streamid.'&mode=viewxml&page=5';
		
		$request = Request::factory($url);
		$request_client = $request->client()
							->options(CURLOPT_HTTPAUTH,CURLAUTH_BASIC)
							->options(CURLOPT_USERPWD,'admin:'.$config['sc_serv'][$streamid]['adminpassword']);
		
		$response = $request->execute();
		$this->response->body(nl2br(HTML::entities($response)));

	}
}
