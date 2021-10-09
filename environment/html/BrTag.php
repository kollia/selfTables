<?php

class BrTag extends Tag
{
		function __construct()
		{
			Tag::__construct("br", false, null);
		}
}

function br()
{
		return new BrTag();
}
?>
