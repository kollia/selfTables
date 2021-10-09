<?php

class FieldsetTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("fieldset", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}

?>