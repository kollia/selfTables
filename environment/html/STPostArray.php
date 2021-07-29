<?php

class STPostArray
{
	var $post;
	
	function __construct()
	{
		global $HTTP_POST_VARS;

		$this->post= $HTTP_POST_VARS;
	}
	function getArrayVars()
	{
		return $this->post;
	}
	function getValue($name)
	{
		if(!isset($this->post[$name]))
			return "";
		return $this->post[$name];
	}
	function exist($name)
	{
		if(isset($this->post[$name]))
			return true;
		return false;
	}
	function set($name, $value)
	{
		$this->post[$name]= $value;
	}
}
?>