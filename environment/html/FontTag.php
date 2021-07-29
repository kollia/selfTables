<?php

class FontTag extends Tag
{
		
		function __construct($tag= null, $class= null)
		{
			Tag::Tag("font", true, $class);
			$this->add($tag);
		}
		function face($value)
		{
			$this->insertAttribute("face", $value);
		}
		function size($value)
		{
			$this->insertAttribute("size", $value);
		}
		function color($value)
		{
			$this->insertAttribute("color", $value);
		}
}

?>