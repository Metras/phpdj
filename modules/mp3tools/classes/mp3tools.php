<?php defined('SYSPATH') OR die('No direct access allowed.');

class Mp3tools {
	
	protected $_getid3;
	protected $_getid3_writer;
	
	protected $_tagformat;
	
	public function __construct() {
		$this->_tagformat = 'UTF-8';
		// Initalise getID3 object
		$this->_getid3 = new getID3;
		$this->_getid3->setOption(array('encoding' => $this->_tagformat));
	
		// Initialize getID3 tag-writing module
		$this->_getid3_writer = new getid3_writetags;
		$this->_getid3_writer->tagformats = array('id3v1', 'id3v2.3');
		$this->_getid3_writer->overwrite_tags = true;
		$this->_getid3_writer->tag_encoding = $this->_tagformat;
	}

	public function set_file($file) {
		$this->_getid3_writer->filename = $file;
	}
	/**
	 * 
	 * Set the tags
	 * @param array $tags
	 */
	public function set_tags($tags) {
		$this->_getid3_writer->tag_data = $tags;
	}
	
	public function write_tags() {
		return $this->_getid3_writer->WriteTags();
	}
	
	public function get_warnings() {
		return $this->_getid3_writer->warnings;
	}
	
	public function get_errors() {
		return $this->_getid3_writer->errors;
	}
}