<?php

class FieldsetTag extends Tag
{
		function FieldsetTag($class= null)
		{
			Tag::Tag("fieldset", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}

?>