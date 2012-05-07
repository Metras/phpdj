<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Noticearea extends Model {
	
	public static function getNotice() {
		$msg = DB::select('value')->from('playlist_settings')->where('key','=','welcome_message')->execute()->get('value','');
		return $msg;
		
	}
}