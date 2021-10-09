<?php

class PTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("p", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}


?>