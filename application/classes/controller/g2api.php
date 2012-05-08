<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Reimplementation of the G2 API / AJAX
 */
class Controller_G2Api extends Controller {
	
	/**
	 * All ajax calls (the website itself, not the api) come through here since the url is /ajax?foo
	 * We just parse the url and call the appropriate function here
	 */
	public function action_ajax() {
		
	}
	
	
	
	/**
	 * API
	 */
	
	public function action_vote() {
		
	}
}