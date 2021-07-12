<?php

class FormTag extends Tag
{
		function FormTag($class= null)
		{
			Tag::Tag("form", true, $class);
		}
		function method($value)
		{
			$this->insertAttribute("method", $value);
		}
		function name($value)
		{
			$this->insertAttribute("name", $value);
		}
		function action($value)
		{
			$this->insertAttribute("action", $value);
		}
		function enctype($value)
		{
			$this->insertAttribute("enctype", $value);
		}
}

?>