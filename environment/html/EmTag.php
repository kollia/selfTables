<?php

class EmTag extends Tag
{
		function EmTag($class= null)
		{
			Tag::Tag("em", true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}


?>