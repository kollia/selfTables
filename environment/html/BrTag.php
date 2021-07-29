<?php

class BrTag extends Tag
{
		function __construct()
		{
			Tag::Tag("br", false, null);
		}
}

function br()
{
		return new BrTag();
}
?>
