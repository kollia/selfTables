<?php

class EmTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("em", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}


?>