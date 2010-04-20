<?php

class Text extends Kohana_Text{
	
	public static function ucwords($words, $charList = null){
		
	    if ($charList === NULL) {
	        return ucwords($words);
	    }

	    $capitalizeNext = true;

	    for ($i = 0, $max = strlen($words); $i < $max; $i++) {
	        if (strpos($charList, $words[$i]) !== false) {
	            $capitalizeNext = true;
	        } else if ($capitalizeNext) {
	            $capitalizeNext = false;
	            $words[$i] = strtoupper($words[$i]);
	        }
	    }
	
		return $words;
		
	}
	
	public static function pass($password){
		
		$config = Kohana::config('auth');
		
		$password = sha1($config->salt.$password.$config->salt2);
		
		return $password;
	}
	
}