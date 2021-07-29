<?php

class EmTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("em", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}


?>