<?php

class BodyTag extends Tag
{
		
		function BodyTag($class= null)
		{
			Tag::Tag("body", true, $class);
		}
		function bgcolor($value)
		{
			$this->insertAttribute("bgcolor", $value);
		}
}

?>