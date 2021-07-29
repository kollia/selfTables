<?php

class LegendTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("legend", true, $class);
		}
}

?>