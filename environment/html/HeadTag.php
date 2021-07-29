<?php

class HeadTag extends Tag
{
		
		function __construct()
		{
			Tag::Tag("head", true, null);
		}
}

?>