<?php

class TitleTag extends Tag
{
		function TitleTag($title= null)
		{
			STCheck::paramCheck($title, 1, "string", "null");

			Tag::Tag("title", true, null);
			if(is_String($title))
				$this->add($title);
		}
}

?>