<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Noticearea extends Controller {
	
	public function action_index() {
		if ($this->request->is_initial()) { return; }
		
		$notice = Model_Noticearea::getNotice();
		$this->response->body(View::factory('noticearea',array('notice' => $notice)));
		
	}
	
}