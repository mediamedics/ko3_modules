<?php

abstract class MM_List_Tpl extends Model{
	
	public function __construct($args = NULL){
		
		$this->args = $args;
		
		$this->db = DB::select();
		$this->db_fields = $this->set_db_fields();
		$this->cust_fields = $this->set_cust_fields();
		$this->meta_fields = $this->set_meta_fields();
		$this->db_obj = $this->set_db_obj();		
	}	
	
	public final function get_db_obj(){
		
		return $this->db_obj;
	}
	
	protected final function set_meta_fields(){
		
		$meta_fields = array();
		
		if(!empty($this->db_fields) AND is_array($this->db_fields)){

			foreach ($this->db_fields as $value){
				
				$meta_fields[$value] = 'database';						
			}
		}
		
		if(!empty($this->cust_fields) AND is_array($this->cust_fields)){
			
			foreach ($this->cust_fields as $value){
				
				$meta_fields[$value] = 'custom';
			}
		}
		
		return $meta_fields;
		
	}
	
	public final function get_field_type($field){
		
		if(isset($this->meta_fields[$field])){
			return $this->meta_fields[$field];
		}else{
			return NULL;
		}
	}
	
	public final function is_cust_field($field){
		
		return ($this->get_field_type($field) === 'custom') ? true : false;
	}
	
	
	public final function is_db_field($field){
		
		return ($this->get_field_type($field) === 'database') ? true : false;
	}
	
	public final function get_db_fields(){
		
		return $this->db_fields;
	}
	
	public final function get_cust_fields(){
		
		return $this->cust_fields;
	}
	
	public final function get_sort_fields(){
		
		return array_merge($this->db_fields, $this->cust_fields);
	}
	
}