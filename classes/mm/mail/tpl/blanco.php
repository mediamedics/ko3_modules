<?php

class MM_Mail_Tpl_Blanco extends Model implements MM_Mail_Interface{
	
	public $tpl_data;
	
	public function __construct($tpl_data = array()){
		
		$this->tpl_data = $tpl_data;
	}
	
	public function get_recipient(){

		return NULL;
	}
	
	public function get_sender(){

		return NULL;
	}

	public function get_subject(){

		return NULL;
	}
	
	public function get_text(){

		return NULL;
	}
	
	public function get_html(){

		return NULL;
	}
	
	public function get_attachment(){

		return NULL;
	}

}