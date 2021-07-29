<?php

class PTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("p", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}


?>