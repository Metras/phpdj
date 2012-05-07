<?php defined('SYSPATH') or die('No direct script access.');

class Controller_BaseTemplate extends Controller_Template {

	public $template = 'basetemplate';
	public $skip_auth = false;
	
	protected $scripts = array();
	protected $stylesheets = array();

	public $selectedorg = 0;
	public $selectedcontest = 0;
	
	/**
	 * Initialize properties before running the controller methods (actions),
	 * so they are available to our action.
	 */
	public function before() {
		
		/*
		//Authentication first.
		if ( ! Auth::instance()->logged_in('login')) {
			$this->request->redirect('/login');
		}
		*/
		$this->user = Model_Users::getUser(2); //Temporary until authentication is done
		$this->now_playing = Model_Playlist::getCurrentSong($this->user);		
		
		//If we are handling an AJAX request or if this is an internal request we need to prevent auto rendering because
		//the output will always be customised in some way.
		if (($this->request->is_ajax()) || ( ! $this->request->is_initial())) {
			$this->auto_render = false;
		}
		
		//We call this ths after setting auto_render
		//so Controller_Template won't bring in the template if its not needed.
		parent::before();
		
		if ($this->auto_render) {
			$this->template->pagesetup = array();
			$this->template->pagesetup['title'] = 'G3';
			$this->template->pagesetup['styles'] = array();
			$this->template->pagesetup['scripts'] = array();
			$this->template->content = false;
			
			//Messages to show at the top of the page
			$this->template->msgs = array();
			
			//Song info at the bottom of the page
			$this->template->now_playing = $this->now_playing;
			$this->template->comments = Model_Comments::getComments($this->now_playing['song_id'],$this->user);
			
			$this->stylesheet('assets/css/style.css','screen');
				
			$this->script('assets/js/jquery-1.7.2.min.js');

		}

	}
	
	/**
	 * Add a script to the page.
	 * @param string $path
	 */
	public function script($path) {
		$this->scripts[] = $path;
	}
	
	/**
	 * Add a stylesheet to the page.
	 * @param string $path
	 * @param string $mediatype
	 */
	public function stylesheet($path,$mediatype) {
		$this->stylesheets[$path] = $mediatype;
	}
	
	/**
	 * Set the page title
	 * @param string $title
	 */
	public function setpagetitle($title) {
		$this->template->pagesetup['title'] = $title;
	}
	
	/**
	 * Set the page content
	 * @param mixed $view
	 */
	public function setcontent($view) {
		$this->template->content = $view;
	}

	/**
	 * Set any messages to be displayed to the user
	 * @param array $msgs
	 */
	public function setmsgs($msgs) {
		$this->template->msgs = $msgs;
	}
	
	public function after() {
	
		if ( $this->auto_render ) {
			
			// Add sheets and styles
			$this->template->pagesetup['styles']  = $this->stylesheets;
			$this->template->pagesetup['scripts'] = $this->scripts;
		}
		
		parent::after();

	}
}