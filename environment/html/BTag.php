<?php

class BTag extends Tag
{
		function __construct()
		{
			Tag::__construct("b", true, null);
		}
}

class BoldTag extends Tag
{
		function __construct()
		{
		    Tag::__construct("b", true, null);
		}
}

?>
