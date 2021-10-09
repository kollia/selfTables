<?php

class UlTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("ul", true, $class);
		}
}

class LiTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("li", true, $class);
		}
}

?>