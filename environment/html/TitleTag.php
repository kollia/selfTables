<?php

class TitleTag extends Tag
{
		function __construct($title= null)
		{
			STCheck::paramCheck($title, 1, "string", "null");

			Tag::__construct("title", true, null);
			if(is_String($title))
				$this->add($title);
		}
}

?>