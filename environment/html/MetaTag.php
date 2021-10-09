<?php

class MetaTag extends Tag
{	
		function __construct()
		{
			Tag::__construct("meta", false);
		}
		function name($value)
		{
			$name= "name";
			if(	preg_match("/^content-type$/i", $value)
				or
				preg_match("/^Pragma$/i", $value)
				or
				preg_match("/^refresh$/i", $value)	)
			{//http-equiv="content-type" content= "text/html; charset=ISO-8859-1"
			 //http-equiv="Pragma" content= "no-cache"
			 //http-equiv="refresh" content= "[time]; URL=[address]"
				$name="http-equiv";
			}
			$this->insertAttribute($name, $value);
		}
		function content($content)
		{
			$this->insertAttribute("content", $content);
		}
}

?>