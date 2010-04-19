<?php defined('SYSPATH') or die('No direct script access.');

class Controller_MM_Template extends Controller_Template {
	
	protected $_ajax = false;
	protected $_internal = false;	
	
	protected $config;
	protected $session;	
	
	protected $user;		
	
	public $template = '_template';
	public $apptype = 'public';
	public $request_admin = array(); // extra request options for admin controller to send to javascript
	public $tpl_data = array();	// special outline data array !
	public $js_runtime = array();
	
	public function before() {
		
		parent::before();		
		
		$this->config = Kohana::config('mm_controller');
		$this->session = Session::instance();		
		
		if ($this->auto_render) {
			// Initialize empty values
			$this->template->title   = '';
			$this->template->content = '';		
			$this->template->apptype = '';	
			$this->template->styles = array();
			$this->template->scripts = array();   
			$this->template->tpl_data = $this->tpl_data;   			
		}		
 
		if (Request::$is_ajax ) {
			$this->_ajax = true;
		}		
		
		if ($this->request !== Request::instance()) {
			$this->_internal = true;
		}		

		$this->js_runtime['request'] = array();
		$this->js_runtime['user'] = array();
		$this->template->request = array();
		$this->template->user = array();
		$this->template->debug = array();
		
		 #Set the language with URI
        $lng = Request::instance()->param('lang');
		$lang_short = Kohana::config('languages.short');
        i18n::$lang = $lang_short[$lng];
		$this->template->lang = $lng;
		$this->tpl_data['lang'] = $lng;
	}
	
	
	public function after() {
	
		$requestData = array(
			'controller' => $this->request->controller
			, 'action' => $this->request->action
			, 'lang' => $this->request->param('lang')
			// , 'section' => $this->request->param('section')
			, 'id' => $this->request->param('id')
			, 'uri' => $this->request->uri
			, 'is_ajax' => $this->_ajax
			, 'is_internal' => $this->_internal
			, 'apptype' => $this->apptype
			, 'admin' => $this->request_admin
		);
		
		$userData = array(
			
		);
		if ($this->user !== NULL) {
			$userData['id'] = $this->user->id;
			$userData['email'] = $this->user->email;
			$userData['name'] = $this->user->name;			
		}
	
		$this->tpl_data['request'] = $requestData;
		$this->tpl_data['user'] = $userData;
		$this->tpl_data['debug'] = $this->template->debug;
		
		$this->template->request = $requestData;
		$this->template->user = $userData;

	
		$this->template->tpl_data = $this->tpl_data; 		// after action before render
		$this->template->apptype = $this->apptype;			// public / app
		
		
		// tpl_data global for regular kohana views rendered by hmvc responses
		if (isset($this->tpl_data) && !empty($this->tpl_data)) {
			$this->template->set_global($this->tpl_data);
		}
		
//echo 'TEMPLATE MYVAR ='.$this->template->myvar;		
		
		if ($this->_ajax === true) {
			// Use the template content as the response
			$this->request->response = $this->template->content;
		} else {
			$this->template->styles[] = assets::tag('css','general');
			$this->template->styles[] = assets::tag('css','system');			
			$this->template->styles[] = assets::tag('css','plugins');			
			
			$this->template->scripts[] = assets::tag('js','mootools');
			$this->template->scripts[] = assets::tag('js','system');			
			$this->template->scripts[] = assets::tag('js','plugins');
			
			$this->template->scripts[] = assets::tag_external('js', 'google_jsapi');
			
			$this->js_runtime['request'] = $requestData;
			$this->js_runtime['user'] = $userData;
			// $this->js_runtime['user_logged_in'] = Mediamedics_User::logged_in();
			$this->js_runtime['developer'] = DEVELOPER;		
			
			parent::after();
			
			$this->template->scripts[] = '<script id="jsRuntime" type="text/javascript" charset="UTF-8"> mm.runtime='.json_encode($this->js_runtime).'; </script>';
		}		
		
	}	
	
}