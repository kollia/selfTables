<?php

class SpanTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("span", true, $class);
		}
		function style($value)
		{
			$this->insertAttribute("style", $value);
		}
}

?>