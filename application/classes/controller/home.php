<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends Controller_BaseTemplate {
	
	public function action_index() {
		$this->request->redirect('/playlist');

	}
	
}