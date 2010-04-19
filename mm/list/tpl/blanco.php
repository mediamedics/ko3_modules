<?php

class MM_List_Tpl_Blanco extends MM_List_Tpl implements MM_List_Interface{
	
	protected $db_fields;
	protected $cust_fields;
	protected $db_obj;
	protected $args;

	protected function set_db_fields(){
						
		return array(
		);
	}
	
	protected function set_cust_fields(){
		
		return array(
		);
	}
	
	public function get_cust_field($field, $row = NULL){
		
		switch ($field){
			
			default:
			
				return NULL;
				
			break;
		}
	}
	
	protected function set_db_obj(){
		
		return DB::select();
		
	}
	

		
}
