<?php

class BodyTag extends Tag
{
		
		function __construct($class= null)
		{
			Tag::Tag("body", true, $class);
		}
		function bgcolor($value)
		{
			$this->insertAttribute("bgcolor", $value);
		}
}

?>