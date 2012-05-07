<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Playlist extends Controller_BaseTemplate {
	
	public function before() {
		parent::before();
		$this->setmsgs(Model_Playlist::getMsgs());
	}
	
	public function action_index() {
		$playlist = Model_Playlist::getPlaylist($this->user['s_playlistHistory'],$this->user);
		$tokens = $this->user['tokens'];
		$playlistlength = Model_Playlist::getPlaylistLength();
		$lastremoval = 0;
		
		$this->setcontent(View::factory('playlist',array('playlist' => $playlist, 'playlistlength' => $playlistlength, 'tokens' => $tokens,'lastremoval' => $lastremoval, 'user' => $this->user)));
	}
	
}