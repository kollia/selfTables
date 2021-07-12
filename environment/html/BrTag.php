<?php

class BrTag extends Tag
{
		function BrTag()
		{
			Tag::Tag("br", false, null);
		}
}

function br()
{
		return new BrTag();
}
?>
