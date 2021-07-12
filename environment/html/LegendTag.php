<?php

class LegendTag extends Tag
{
		function LegendTag($class= null)
		{
			Tag::Tag("legend", true, $class);
		}
}

?>