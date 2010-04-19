<?php defined('SYSPATH') or die('No direct script access.');

class Controller_MM_Base extends Controller {
	
	protected $_ajax = false;
	protected $_internal = false;
	protected $config;
	protected $session;	
	
	public function before() {
		
		parent::before();		

		$this->config = Kohana::config('mm_controller');
		$this->session = Session::instance();		
 
		if (Request::$is_ajax ) {
			$this->_ajax = true;
		}		
		
		if ($this->request !== Request::instance()) {
			$this->_internal = true;
		}
		
	}
	
	public function after() {
		
		if ($this->_ajax === true) {
			// json_encode ?
			// echo json_encode($this->request->response);
			parent::after();
		} else {
			parent::after();
		}		
		
	}	
	
}