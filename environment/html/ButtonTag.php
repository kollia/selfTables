<?php

class ButtonTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("button", true, $class);
			$this->insertAttribute("type", "button");
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
		function onClick($vlaue)
		{
			$this->insertAttribute("onClick", $vlaue);
		}
		function disabled()
		{
			$this->insertAttribute("disabled", "disabled");
		}
		function display()
		{
			if(!isset($this->aNames["type"]))
				$this->type("button");
			Tag::display();
		}
}

?>