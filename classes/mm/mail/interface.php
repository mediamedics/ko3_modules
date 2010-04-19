<?php

interface MM_Mail_Interface{
	
	public function __construct($tpl_data);
	
	public function get_recipient();
	
	public function get_sender();
	
	public function get_subject();
	
	public function get_text();
	
	public function get_html();
	
	public function get_attachment();
		
}

