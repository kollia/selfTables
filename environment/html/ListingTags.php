<?php

class UlTag extends Tag
{
		function UlTag($class= null)
		{
			Tag::Tag("ul", true, $class);
		}
}

class LiTag extends Tag
{
		function LiTag($class= null)
		{
			Tag::Tag("li", true, $class);
		}
}

?>