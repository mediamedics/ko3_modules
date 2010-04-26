<?php

class Flexdb_Core extends Model{
	
	public static $insert_id;
	public static $total_rows;
	public static $columns;
			
	public static function get_rows($table, $where = array(), $single = false, $single_field = NULL, $id_check = false){

		$vars = array();
					
		if(!is_array($where) AND is_int($where)){
			$vars = array(array('field'=>'id', 'operator'=>'=', 'value'=>$where));
		}elseif(is_array($where) AND count($where) > 0){
			
			$i = 0;
			foreach ($where as $key => $value){

				if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){

					$vars[$i]['operator'] = trim($matches[0]);
					$vars[$i]['field'] = str_replace($matches[0], '', $key);
					$vars[$i]['value'] = $value;

				}else{

					$vars[$i]['operator'] = '=';
					$vars[$i]['field'] = $key;
					$vars[$i]['value'] = $value;
				}

				$i ++;

			}
		}
						
		if(isset($vars)){
						
			$db = self::select();
			
			$db->from($table);
			
			if(count($vars) > 0){
				foreach ($vars as $value){									
					$db->and_where($value['field'], $value['operator'], $value['value']);					
				}
			} 
			
			if($single){				
				$db->limit(1);
			}
			
			if($id_check){
				$db->select('id');
			}
			
			try{
				
				$result = $db->execute();
								
				$array = $result->as_array();
	
				if(is_array($array)){
					
					if(count($array) > 0 AND isset($array[0])){
						
						if($id_check){
							//exit
							return true;
						}
						
						if($single_field != NULL AND isset($array[0][$single_field])){
					
							return $array[0][$single_field];
						}
						
						if($single){
							
							return $array[0];
							
						}
						
						return $array;
				
					}
				
				}else{
				
					return false;
				}
				
			}catch(Exception $e){
				
				return false;
				
			}
			
		}else{
			
			return false;
		}

	}
	
	public static function row_exists($table, $where = array()){
		
		return self::get_rows($table, $where, $single = true, $single_field = NULL, $id_check = true);
	}
			
	public static function id_exists($table, $where = array()){
		
		return self::get_rows($table, $where, $single = true, $single_field = NULL, $id_check = true);
	}
	
	public static function count_rows($table, $where = array()){
						
		$vars = array();

		if(!is_array($where) AND is_int($where)){
			// $where = array('id' => $where);
			$vars = array(array('field'=>'id', 'operator'=>'=', 'value'=>$where));			
		}elseif(is_array($where) AND count($where) > 0){
			
			$i = 0;
			foreach ($where as $key => $value){

				if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){

					$vars[$i]['operator'] = trim($matches[0]);
					$vars[$i]['field'] = str_replace($matches[0], '', $key);
					$vars[$i]['value'] = $value;

				}else{

					$vars[$i]['operator'] = '=';
					$vars[$i]['field'] = $key;
					$vars[$i]['value'] = $value;
				}

				$i ++;

			}
		}
		
		if(isset($vars)){
						
			$db = self::select(self::expr('SQL_CALC_FOUND_ROWS id'));
			
			$db->from($table);
			
			if(count($vars) > 0){
				foreach ($vars as $value){
				
					$db->and_where($value['field'], $value['operator'], $value['value']);
				
				}
			}
			
			$db->limit(1);
			
			try{
				
				$db->execute();
				
				try{
					$result = self::query(Database::SELECT, "SELECT FOUND_ROWS() as count")->execute()->as_array();
				
					if(isset($result[0]['count'])){
					
						return (int) $result[0]['count'];
					
					}else{
					
						return 0;
					}
			
				}catch(Exception $e){

					return false;
				}

				
			}catch(Exception $e){
								
				return false;
			}
			
		}else{
			
			return false;
		}

	}
	
	public static function sum_field($table, $field, $where){
						
		if(!is_array($where) AND is_int($where)){
			$where = array('id' => $where);
		}
				
		$vars = array();
		
		$i = 0;
		foreach ($where as $key => $value){
						
			if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){
					
				$vars[$i]['operator'] = trim($matches[0]);
				$vars[$i]['field'] = str_replace($matches[0], '', $key);
				$vars[$i]['value'] = $value;
				
			}else{
				
				$vars[$i]['operator'] = '=';
				$vars[$i]['field'] = $key;
				$vars[$i]['value'] = $value;
			}
			
			$i ++;
			
		}
		
		if(isset($vars)){
						
			$db = self::select(self::expr('SUM('.$field.') AS count'));
			
			$db->from($table);
			
			foreach ($vars as $value){
				
				$db->and_where($value['field'], $value['operator'], $value['value']);
				
			}
			
			$db->limit(1);
			
			try{
				
				$result = $db->execute();
								
				if(isset($result[0]['count'])){
					
					return (int) $result[0]['count'];
					
				}else{
					
					return 0;
				}
				
			}catch(Exception $e){
								
				return false;
				
			}
			
		}else{
			
			return false;
		}

	}
	
	public static function insert($table, array $columns){
				
		foreach ($columns as $field => $value){
			
			if(in_array($field, self::fields($table))){
				$fields[] = $field;
				$values[] = $value;
			}
		}
		
		if(!isset($fields)){
			$fields = array();
		}
		
		if(!isset($values)){
			$values = array();
		}
				
		try{
		
			$result = DB::insert($table, $fields)->values($values)->execute();
			
			list(self::$insert_id, self::$total_rows) = $result;
	
			return $result;
			
		}catch(Database_Exception $e){
					
			echo $e->getMessage();		
			return false;
		}
	}
	
	//$total_rows = DB::update('table_name')->set(array('column'=>'value'))->where('column','=','value')->execute();

	public static function update($table, array $columns, $where = NULL){
				
		if($where !== NULL AND is_int($where)){
			$where = array('id' => $where);
		}elseif($where === NULL){
			$where = array();
		}		
				
		foreach ($columns as $field => $value){
			
			if(in_array($field, self::fields($table))){
				$set_flds[$field] = $value;
			}
		}
		
		if(!isset($set_flds)){
			$set_flds = array();
		}
		
		
		$i = 0;
		foreach ($where as $key => $value){

			if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){

				$vars[$i]['operator'] = trim($matches[0]);
				$vars[$i]['field'] = str_replace($matches[0], '', $key);
				$vars[$i]['value'] = $value;

			}else{

				$vars[$i]['operator'] = '=';
				$vars[$i]['field'] = $key;
				$vars[$i]['value'] = $value;
			}

			$i ++;

		}
		
		
		try{
		
			$db = DB::update($table)->set($set_flds);
			
			if(isset($vars) AND is_array($vars) AND count($vars) > 0){
				foreach ($vars as $value){
				
					$db->and_where($value['field'], $value['operator'], $value['value']);
				
				}
			}
			
			self::$total_rows = $db->execute();
			
			return true;
			
		}catch(Database_Exception $e){
					
			echo $e->getMessage();		
			return false;
		}
	}
	
	public static function delete($table, $where){
	
		if($where !== NULL AND is_int($where)){
			$where = array('id' => $where);
		}elseif($where === NULL){
			$where = array();
		}		
				
		$i = 0;
		foreach ($where as $key => $value){

			if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){

				$vars[$i]['operator'] = trim($matches[0]);
				$vars[$i]['field'] = str_replace($matches[0], '', $key);
				$vars[$i]['value'] = $value;

			}else{

				$vars[$i]['operator'] = '=';
				$vars[$i]['field'] = $key;
				$vars[$i]['value'] = $value;
			}

			$i ++;

		}
		
		
		try{
		
			$db = DB::delete($table);
			
			if(isset($vars) AND is_array($vars) AND count($vars) > 0){
				foreach ($vars as $value){
				
					$db->and_where($value['field'], $value['operator'], $value['value']);
				
				}
			}
			
			self::$total_rows = $db->execute();
			
			return true;
			
		}catch(Database_Exception $e){
					
			echo $e->getMessage();		
			return false;
		}
	}
	
	public static function fields($table_name){
				
		if(isset(self::$columns[$table_name])){
			
			return self::$columns[$table_name];
			
		}else{
			
			try{
				$fields = self::query(Database::SELECT, 'SHOW COLUMNS FROM `'.$table_name.'`')->execute()->as_array();
			
				if(is_array($fields) AND count($fields) > 0){
				
					foreach ($fields as $key => $value){
						
						if($value['Field'] !== 'id'){
							$columns[] = $value['Field'];
						}
					
					}
				
					if(isset($columns)){
						
						self::$columns = $columns;
						
						return $columns;
						
					}else{
						
						return array();
					}
					
				}
				
			}catch(Database_Execption $e){
				
				return array();
				
			}
		}
	}
	
	public static function insert_id(){
		
		if(isset(self::$insert_id)){
			return self::$insert_id;
		}else{
			return NULL;
		}

	}

	public static function total_rows(){
				
		if(isset(self::$total_rows)){
			return self::$total_rows;
		}else{
			return NULL;
		}

	}
	
	
	public static function avg_field($table, $field, $where){
						
		if(!is_array($where) AND is_int($where)){
			$where = array('id' => $where);
		}
				
		$vars = array();
		
		$i = 0;
		foreach ($where as $key => $value){
						
			if(preg_match('/ ([=<>]){1,2}/', $key, $matches) && isset($matches[0])){
					
				$vars[$i]['operator'] = trim($matches[0]);
				$vars[$i]['field'] = str_replace($matches[0], '', $key);
				$vars[$i]['value'] = $value;
				
			}else{
				
				$vars[$i]['operator'] = '=';
				$vars[$i]['field'] = $key;
				$vars[$i]['value'] = $value;
			}
			
			$i ++;
			
		}
		
		if(isset($vars)){
						
			$db = self::select(self::expr('AVG('.$field.') AS count'));
			
			$db->from($table);
			
			foreach ($vars as $value){
				
				$db->and_where($value['field'], $value['operator'], $value['value']);
				
			}
			
			$db->limit(1);
			
			try{
				
				$result = $db->execute();
								
				if(isset($result[0]['count'])){
					
					return (int) $result[0]['count'];
					
				}else{
					
					return 0;
				}
				
			}catch(Exception $e){
								
				return false;
				
			}
			
		}else{
			
			return false;
		}

	}
	
	public static function get_row($table, $where = array()){
		
		return self::get_rows($table, $where, $single = true);
	}

	public static function get_value($table, $where, $fieldname){
		
		// echo Kohana::debug($fieldname);
		
		return self::get_rows($table, $where, $single = true, $single_field = $fieldname);
	}
	
	public static function __callStatic($name, $args){

		return call_user_func_array(array('DB', $name), $args);
	}
	
	
}