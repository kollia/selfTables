<?php

class LegendTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("legend", true, $class);
		}
}

?>