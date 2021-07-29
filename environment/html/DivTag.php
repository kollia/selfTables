<?php

class DivTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("div", true, $class);
		}
		function style($value)
		{
			$this->insertAttribute("style", $value);
		}
		function align($value)
		{
			$this->insertAttribute("align", $value);
		}
}

?>