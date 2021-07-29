<?php

class ATag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("a", true, $class);
		}
		function href($value)
		{
			$this->insertAttribute("href", $value);
		}
		function target($value)
		{
			$this->insertAttribute("target", $value);
		}
		function onClick($value)
		{
			$this->insertAttribute("onclick", $value);
		}
		function onDbClick($value)
		{
			$this->insertAttribute("ondbclick", $value);
		}
		function onMouseDown($value)
		{
			$this->insertAttribute("onmousedown", $value);
		}
		function onMouseUp($value)
		{
			$this->insertAttribute("onmouseup", $value);
		}
		function onMouseOver($value)
		{
			$this->insertAttribute("onmouseover", $value);
		}
		function onMouseMove($value)
		{
			$this->insertAttribute("onmousemove", $value);
		}
		function onMouseOut($value)
		{
			$this->insertAttribute("onmouseout", $value);
		}
		function onKeyPress($value)
		{
			$this->insertAttribute("onkeypress", $value);
		}
		function onKeyDown($value)
		{
			$this->insertAttribute("onkeydown", $value);
		}
		function onKeyUp($value)
		{
			$this->insertAttribute("onkeyup", $value);
		}
}

?>