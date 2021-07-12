<?php

class SpanTag extends Tag
{
		function SpanTag($class= null)
		{
			Tag::Tag("span", true, $class);
		}
		function style($value)
		{
			$this->insertAttribute("style", $value);
		}
}

?>