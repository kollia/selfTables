<?php

class SelectTag extends Tag
{
		function __construct($tag= null, $class= null)
		{
			Tag::Tag("select", true, $class);
			$this->add($tag);
		}
		function size($value)
		{
			$this->insertAttribute("size", $value);
		}
		function onChange($value)
		{
			$this->insertAttribute("onChange", $value);
		}
		function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		function disabled()
		{
			$this->insertAttribute("disabled", "disabled");
		}
		function tabindex($vlaue)
		{
			$this->insertAttribute("tabindex", $vlaue);
		}
}

class OptionTag extends Tag
{
		function __construct()
		{
			Tag::Tag("option", true);
		}
		function value($vlaue)
		{
			$this->insertAttribute("value", $vlaue);
		}
		function selected($set= true)
		{
			if($set)
				$this->insertAttribute("selected", "selected");
		}
}
?>