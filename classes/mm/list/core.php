<?php

abstract class MM_List_Core extends Model{
	
	public $page_nr;
	public $items_nr;
	public $orderby;
	public $sortdir;
	protected $tpl_obj;
	protected $tpl_loaded;
	
	public function __construct($tpl_name = NULL, $args = NULL){
		parent::__construct();
		
		$this->config = Kohana::config('list');
		
		$this->page_nr = isset($this->config['page_nr']) ? $this->config['page_nr'] : 1;
		$this->items_nr = isset($this->config['items_nr']) ? $this->config['items_nr'] : 10;
		$this->orderby = isset($this->config['orderby']) ? $this->config['orderby'] : 'id';
		$this->sortdir = isset($this->config['sortdir']) ? $this->config['sortdir'] : 'DESC';
		$this->base_url = isset($this->config['base_url']) ? $this->config['base_url'] : '';
		$this->sort_custom = false;
		
		if($tpl_name !== NULL && is_string($tpl_name)){
			$this->tpl_name = $tpl_name;
		}
		
		if($args !== NULL){
			$this->args = $args;
		}
		
	}
	
	public function base_url($url){
		
		if(is_string($url)){
			$this->base_url = $url;
		}
		
		return $this;
	}
	
	public function page($number){
		
		if($number >= 1 ){
			$this->page_nr = (int) $number;
		
			return $this;
		}else{
			$this->page_nr = 1;
			
			return $this;
		}
	}
	
	public function items($number){
	
		$this->items_nr = (int) $number;
		
		return $this;
	}
	
	protected function get_tpl($tpl_name, $args = NULL){
		
		$Class = 'MM_List_Tpl_'.Text::ucwords($tpl_name, '_');

		if(class_exists($Class)){

			$this->tpl = new $Class($args);
			return $this->tpl_loaded = true;
			
		}else{
			return $this->tpl_loaded = false;
		}
	}
	
	protected function tpl_loaded(){
		
		return $this->tpl_loaded;
	}
	
	public function orderby($field, $sortdir){
		
		$this->orderby = (string) $field;
		$sortdir = strtoupper((string) $sortdir);
		
		if($sortdir === 'ASC' OR $sortdir === 'DESC'){
			
			$this->sortdir = $sortdir;
		}else{
			$this->sortdir = 'ASC';
		}
		
		return $this;
	}
	
	public function get($tpl_name = NULL, $args = NULL){
		
		if($tpl_name === NULL AND isset($this->tpl_name) AND !empty($this->tpl_name)){
			$tpl_name = $this->tpl_name;
		}else{
			$this->tpl_name = $tpl_name;
		}
		
		if($args === NULL AND isset($this->args) AND !empty($this->args)){
			$args = $this->args;
		}
		
		if($this->get_tpl($tpl_name, $args)){
			
			$this->fetch_rows();
			
		}
		
	}
	
	public function fetch_rows(){
		
		// check if tpl is loaded
		if($this->tpl_loaded()){
			
			// get db_obj from tpl
			$this->db_obj = $this->tpl->get_db_obj();
			
			// check if db_obj is the right object
			if(is_object($this->db_obj) AND $this->db_obj instanceof Database_Query_Builder){
				
				// add the SQL_CALC_FOUND_ROWS operator to the query builder, to be able to calculate the found_rows()
				$this->db_obj->select(DB::expr('SQL_CALC_FOUND_ROWS *'));
				
				// set the offset default to 0
				$this->db_obj->offset(0);
								
				// limit the query to items_nr
				if($this->items_nr > 0){
					$this->db_obj->limit($this->items_nr);
				}
				
				// calculate offset_nr if page_nr isset, else set to 0
				if($this->page_nr >= 1){	
					$this->offset_nr = $this->items_nr * ($this->page_nr - 1);					
					$this->db_obj->offset($this->offset_nr);
				}
				
					
				// check if orderby field is a database field
				if($this->tpl->is_db_field($this->orderby)){
				
					// set user-defined sortdir
					if(isset($this->sortdir)){
						
						$this->db_obj->order_by($this->orderby, $this->sortdir);
					}else{
						$this->db_obj->order_by($this->orderby);
					}
								
				// check if orderby field is a custom field
				}elseif($this->tpl->is_cust_field($this->orderby)){
							
					// pick a large limit for sorting purposes
					$this->db_obj->limit(10000);
					
					// reset the offset to zero
					$this->db_obj->offset(0);
					
					// set user-defined sortdir for id
					if(!empty($this->sortdir)){
					
						$this->db_obj->order_by('id', $this->sortdir);
						
					// default sortdir is DESC for id
					}else{
						
						$this->db_obj->order_by('id', 'DESC');
					}
					
					$this->sort_custom = true;
				}
							
				// execute SQL query
				$result = $this->db_obj->execute();
				
				// retrieve results as array
				$this->db_rows = $result->as_array();
				
				// calculate total row count as found by FOUND_ROWS(); needs to be called directly after a SQL_FOUND_ROWS * query
				$this->total_rows = DB::found_rows();
				
				// calculate the total pages 
				$this->total_pages = (int) ceil($this->total_rows / $this->items_nr);
				
				// if db results are empty we shouldn't proceed
				if(!empty($this->db_rows)){
					
					// use this->rows for manipulation
					$this->rows = $this->db_rows;
				
					// get all custom fields
					$cust_fields = $this->tpl->get_cust_fields();
				
					// check if cust_fields is array
					if(is_array($cust_fields)){
						
						// generate the custom value for each custom field
						foreach ($this->rows as &$row){
						
							// loop through each custom fields and calc values from tpl
							foreach ($cust_fields as $field){
							
								// insert new custom fields into this->rows
								$row[$field] = $this->tpl->get_cust_field($field, $row);			
							}
						}
						
						unset($row); // unset row pointer
					}
					
					// if sorting is custom, sort and slice the rows array
					if($this->sort_custom){
						$this->rows = $this->sort_cust_rows($this->rows);
						$this->rows = $this->slice_cust_rows($this->rows);
					}
										
					// filter row columns by defined fields	
					$this->sort_fields = $this->tpl->get_sort_fields();
					foreach ($this->rows as &$row){
						
						foreach ($row as $column => $value){
							
							if(!in_array($column, $this->sort_fields)){
								unset($row[$column]);
							}
						}	
					}	
					unset($row); // unset row pointer
										
					//remove trailing slash from base_url
					if(!empty($this->base_url)){
						$base_url = ($this->base_url[strlen($this->base_url) - 1] === '/') ? substr($this->base_url, 0, -1) : $this->base_url;
					}else{
						$base_url = '';
					}	
					
					$this->clean_url = $base_url;
					
					// set previous_url
					$this->previous_url = ($this->page_nr === 1) ? NULL : $base_url.'/'.($this->page_nr - 1);
					
					// set next_url
					$this->next_url = ($this->page_nr === $this->total_pages) ? NULL : $base_url.'/'.($this->page_nr + 1);
					
					// load pagination
					$this->get_pagination();
					
					// load css classes
					$this->get_css_classes();
					
					// finally: load view
					$this->get_view();
				}
			}
		}	
	}
	
	public function sort_cust_rows(array $rows){
		
		return Arr::multisort($rows, $this->orderby, $this->sortdir);		
	}
	
	public function slice_cust_rows(array $rows){
		
		if($this->page_nr >= 1){	
			$this->offset_nr = $this->items_nr * ($this->page_nr - 1);					
		}else{
			$this->offset_nr = 0;
		}
		
		return array_slice($rows, $this->offset_nr, $this->items_nr);
	}
	
	public function get_rows(){
		
		if(isset($this->rows)){
			return $this->rows;
		}
	}
	
	public function get_db_rows(){
		
		if(isset($this->db_rows)){
			return $this->db_rows;
		}
	}
	
	
	public function get_next_url(){
		
		if(isset($this->next_url)){
			return $this->next_url;
		}
	}
	
	public function get_previous_url(){
		
		if(isset($this->previous_url)){
			return $this->previous_url;
		}
	}
	
	public function get_total_rows(){
		
		if(isset($this->total_rows)){
			return $this->total_rows;
		}
	}
	
	
	public function get_total_pags(){
		
		if(isset($this->total_pages)){
			return $this->total_pages;
		}
	}
	
	public function get_sort_fields(){
		
		if($this->tpl_loaded()){
			$this->sort_fields = $tpl->get_sort_fields();
			return $this->sort_fields;
		}
	}
	
	public function set_cust_rows(){
		
	}
	
	public function get_css_classes(){
		
		if(isset($this->sort_fields) AND is_array($this->sort_fields)){
			
			foreach ($this->sort_fields as $sort_field){
				
				if($sort_field === $this->orderby){
					
					$class = 'List_sort List_sortdir_'.strtolower($this->sortdir);	
					
					$this->css_classes[$sort_field] = $class;
					unset($class);
				}else{
					$this->css_classes[$sort_field] = NULL;
				}
				
			}
			
		}
		
	}
	
	
	public function get_pagination(){
		
		// $this->page_nr = 27;
		// $this->total_pages = 32;
		
		if(isset($this->page_nr) AND $this->page_nr > 0 AND isset($this->total_pages) AND $this->page_nr <= $this->total_pages){
			
			$pages = array();
			
			if($this->total_pages <= 9 ){
												
				$i = 1;
				while ($i <= $this->total_pages){
					
					$pages[] = $i;
					
					$i ++;
				}
								
			}elseif($this->total_pages > 9){
								
				$pages = array(1, 2, 3);
				
				if($this->page_nr == 1){
										
					$page_block = array(1, 2, 3);
					
				}elseif($this->page_nr > 1){
										
					if(($this->page_nr + 1) <= $this->total_pages){
						
						$page_block[] = $this->page_nr - 1;
						$page_block[] = $this->page_nr;
						$page_block[] = $this->page_nr + 1;

					}elseif(($this->page_nr) <= $this->total_pages){
						
						$page_block[] = $this->page_nr - 1;
						$page_block[] = $this->page_nr + 1;
					}
					
				}
				
				if(min($page_block) <= (max($pages) + 1)){
					
					foreach($pages as $key => $value){
						
						if($value >= min($page_block)){
							
							unset($pages[$key]);
						}
					}
										
					$pages = array_merge($pages, $page_block);
					
					if(count($pages) < 6){
						
						while(count($pages) < 6){

							array_push($pages, max($pages) + 1);								
						}
					}
					
					$pages[] = NULL;
				
					$end_block[] = $this->total_pages - 2;
					$end_block[] = $this->total_pages - 1;
					$end_block[] = $this->total_pages;
					
					$pages = array_merge($pages, $end_block);
				
				}elseif(min($page_block) > max($pages)){

					$end_block[] = $this->total_pages - 2;
					$end_block[] = $this->total_pages - 1;
					$end_block[] = $this->total_pages;

					if(max($page_block) >= (min($end_block) - 1)){

						foreach ($end_block as $key => $value){

							if($value <= max($page_block)){

								unset($end_block[$key]);
							}
						}

						$pages[] = NULL;

						$end_block = array_merge($page_block, $end_block);

						if(count($end_block < 6)){

							while(count($end_block) < 6){

								array_unshift($end_block, min($end_block) - 1);								
							}
						}

						$pages = array_merge($pages, $end_block);
					}else{

						$pages[] = NULL;

						$pages = array_merge($pages, $page_block, array(NULL), $end_block);

					}
				}				
			}
			
			$pagination = '';
			
			if($this->page_nr > 1){	
				$this->prev_page = true;
				$pagination .= 'Prev ';
			}else{
				$this->prev_page = false;
			}
			
			foreach ($pages as $value){
				
				if($value !== NULL){
					
					if($value === $this->page_nr){
						$pagination .= '['.$value.'] ';  
					}else{
						$pagination .= $value.' ';
					}
				}else{
					$pagination .= '.. ';
				}
			}
			
			if($this->page_nr < $this->total_pages){
				$this->next_page = true;
				$pagination .= 'Next';
			}else{
				$this->next_page = false;
			}

			$this->pages = $pages;
			$this->pagination = $pagination;
			
		}
		
	}
	
	public function get_view(){
		
		$tpl_data['list'] = get_object_vars($this);
		$this->tpl_view = (string) View::factory('ui/list/'.$this->tpl_name, $tpl_data);
		
		return $this->tpl_view;
	}
	
	public function render($render = true){
		
		if(!isset($this->tpl_view) OR empty($this->tpl_view)){
			
			if($render){
				echo $this->get_view();
			}else{
				return $this->get_view();
			}
		}else{
			
			if($render){
				echo $this->tpl_view;
			}else{
				return $this->tpl_view;
			}
		}
	}
	
	
}