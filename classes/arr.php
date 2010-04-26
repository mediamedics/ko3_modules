<?php

class Arr extends Kohana_Arr{
	
	public static function xss(array $array){
		
		foreach ($array as &$value){
			if(is_array($value)){
				$value = self::xss($value);
			}else{
				$value = Security::xss_clean($value);
			}
		}
		
		return $array; 
	}
	
	public static function array_multisort_column($theArray, $column, $sortdir = SORT_ASC) {
		$sortby = array();
		foreach ($theArray as $key => $row) {
		    $sortby[$key] = $row[$column];
		}
		array_multisort($sortby, $sortdir, $theArray);
		return $theArray;
	}	
	
}