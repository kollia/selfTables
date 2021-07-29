<?php

class InputTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("input", false, $class);
		}
		function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		function type($vlaue)
		{
			$this->insertAttribute("type", $vlaue);
		}
		function value($vlaue)
		{
			$this->insertAttribute("value", $vlaue);
		}
		function size($vlaue)
		{
			$this->insertAttribute("size", $vlaue);
		}
		function maxlen($vlaue)
		{
			$this->insertAttribute("maxlen", $vlaue);
		}
		function onChange($vlaue)
		{
			$this->insertAttribute("onChange", $vlaue);
		}
		function onClick($vlaue)
		{
			$this->insertAttribute("onClick", $vlaue);
		}
		function accept($vlaue)
		{
			$this->insertAttribute("accept", $vlaue);
		}
		function tabindex($vlaue)
		{
			$this->insertAttribute("tabindex", $vlaue);
		}
		function checked($checked= true)
		{
			if($checked)
				$this->insertAttribute("checked", "checked");
		}
		function disabled()
		{
			$this->insertAttribute("disabled", "disabled");
		}
}

?>