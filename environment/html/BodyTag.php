<?php

class BodyTag extends Tag
{
		
		function __construct($class= null)
		{
			Tag::__construct("body", true, $class);
		}
		function bgcolor($value)
		{
			$this->insertAttribute("bgcolor", $value);
		}
		function style($value)
		{
			$this->insertAttribute("style", $value);
		}
}

?>