<?php

class UlTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("ul", true, $class);
		}
}

class LiTag extends Tag
{
		function __construct($class= null)
		{
			Tag::Tag("li", true, $class);
		}
}

?>