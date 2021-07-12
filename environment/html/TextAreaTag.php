<?php

class TextAreaTag extends Tag
{
		function TextAreaTag($class= null)
		{
			Tag::Tag("textarea", true, $class);
		}
		function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		function cols($vlaue)
		{
			$this->insertAttribute("cols", $vlaue);
		}
		function rows($vlaue)
		{
			$this->insertAttribute("rows", $vlaue);
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
		function checked($checked= true)
		{
			if($checked)
				$this->insertAttribute("checked", "checked");
		}
}

?>