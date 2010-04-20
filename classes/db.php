<?php

class DB extends Kohana_DB{
	
	public static function found_rows(){
		
		$rows = self::select(self::expr("FOUND_ROWS() AS `rowCount`"))->execute()->as_array();
		
		if(isset($rows[0]['rowCount'])){
			
			return (int) $rows[0]['rowCount'];

		}else{	
			
			return 0;
		}
	}
	
	
}