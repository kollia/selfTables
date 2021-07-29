<?php

class FieldsetTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("fieldset", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}

?>